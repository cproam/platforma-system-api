<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Infrastructure\DI\Container;
use App\Infrastructure\Security\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\LogEntry;
use App\Entity\User;
use App\Entity\Role;

final class Kernel
{
    public function __construct(private readonly RouteCollection $routes, private readonly ?Container $container = null) {}

    public function handle(Request $request): Response
    {
        // Handle CORS preflight early
            if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $resp = new Response('', 204);
            $resp = $this->applyCors($resp, $request, true);
            // log success for preflight
            try {
                /** @var EntityManagerInterface $em */
                $em = $this->container?->get(EntityManagerInterface::class);
                if ($em) {
                    $ip = $request->getClientIp() ?: ($request->server->get('REMOTE_ADDR') ?? null);
                    $log = new LogEntry('OPTIONS', $request->getPathInfo(), 204, null, null, $ip, 'preflight', null);
                    $em->persist($log);
                    $em->flush();
                }
            } catch (\Throwable) {
            }
            return $resp;
        }

        $context = (new RequestContext())->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $parameters = $matcher->match($request->getPathInfo());
            $controller = $parameters['_controller'] ?? null;

            // Authentication: allow /auth/login without token, require JWT otherwise
            $path = $request->getPathInfo();
            $status = 200;
            $message = null;
            $userId = null;
            $ip = $request->getClientIp() ?: ($request->server->get('REMOTE_ADDR') ?? null);
            if ($path !== '/auth/login') {
                $authHeader = $request->headers->get('Authorization', '');
                if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $m)) {
                    return $this->logAndRespond($request, 401, 'Unauthorized');
                } else {
                    $token = $m[1];
                    try {
                        /** @var JwtService $jwt */
                        $jwt = $this->container?->get(JwtService::class);
                        $claims = $jwt->verify($token);
                        $userId = isset($claims['sub']) ? (int)$claims['sub'] : null;
                        $request->attributes->set('auth', $claims);
                        // Check ban for authenticated users as well
                        /** @var EntityManagerInterface $em */
                        $em = $this->container?->get(EntityManagerInterface::class);
                        if ($em && $userId) {
                            $user = $em->find(User::class, $userId);
                            if ($user && $user->isBanned()) {
                                return $this->logAndRespond($request, 403, 'banned');
                            }
                        }
                    } catch (\Throwable) {
                        return $this->logAndRespond($request, 401, 'Invalid token');
                    }
                }
            }

            if (is_callable($controller)) {
                return $controller($request, $parameters);
            }

            if (is_string($controller) && str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
                if (class_exists($class) && method_exists($class, $method)) {
                    $instance = $this->container?->get($class) ?? new $class();
            $response = $instance->$method($request, $parameters);
                    // Apply CORS headers
                    $response = $this->applyCors($response, $request);
                    // Log success
                    try {
                        /** @var EntityManagerInterface $em */
                        $em = $this->container?->get(EntityManagerInterface::class);
                        if ($em) {
                            $userIdForLog = $userId;
                $actionObj = $this->deriveActionObject($request->getMethod(), $request->getPathInfo());
                $log = new LogEntry($request->getMethod(), $request->getPathInfo(), $response->getStatusCode(), null, $userIdForLog, $ip, $actionObj['action'], $actionObj['object']);
                            $em->persist($log);
                            $em->flush();
                        }
                    } catch (\Throwable) {
                    }
                    return $response;
                }
            }

            return $this->logAndRespond($request, 500, 'Controller not found');
        } catch (ResourceNotFoundException) {
            return $this->logAndRespond($request, 404, 'Not Found');
        } catch (\Throwable) {
            return $this->logAndRespond($request, 500, 'Server Error');
        }
    }

    private function logAndRespond(Request $request, int $status, ?string $message): Response
    {
        try {
            /** @var EntityManagerInterface $em */
            $em = $this->container?->get(EntityManagerInterface::class);
            if ($em) {
                $claims = (array) $request->attributes->get('auth', []);
                $uid = isset($claims['sub']) ? (int)$claims['sub'] : null;
                $ip = $request->getClientIp() ?: ($request->server->get('REMOTE_ADDR') ?? null);
                $actionObj = $this->deriveActionObject($request->getMethod(), $request->getPathInfo());
                $log = new LogEntry($request->getMethod(), $request->getPathInfo(), $status, $message, $uid, $ip, $actionObj['action'], $actionObj['object']);
                $em->persist($log);
                $em->flush();
            }
        } catch (\Throwable) {
            // ignore logging failures
        }
        $payload = [
            'error' => [
                'code' => $status,
                'message' => $message ?? '',
            ],
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'requestId' => bin2hex(random_bytes(8)),
        ];
        $resp = new JsonResponse($payload, $status);
        return $this->applyCors($resp, $request);
    }

    private function deriveActionObject(string $method, string $path): array
    {
        $action = strtolower($method);
        $object = null;
        // Basic heuristic: use first path segment as object
        // e.g., /franchises/123 -> object 'franchises'; /auth/login -> 'auth'
        $trimmed = trim($path, '/');
        if ($trimmed !== '') {
            $parts = explode('/', $trimmed);
            $object = $parts[0] ?? null;
        }
        return ['action' => $action, 'object' => $object];
    }

    private function getOrCreateAnonUserId(EntityManagerInterface $em, ?string $ip): int
    {
        $roleRepo = $em->getRepository(Role::class);
        $anonRole = $roleRepo->findOneBy(['name' => 'anon']);
        if (!$anonRole) {
            $anonRole = new Role('anon');
            $em->persist($anonRole);
            $em->flush();
        }
        $email = ($ip ?: 'unknown') . '@anon.local';
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            $anonKey = bin2hex(random_bytes(16));
            $user = new User($email, '', $anonRole, $ip, false, $anonKey);
            $em->persist($user);
            $em->flush();
        }
        return $user->getId() ?? 0;
    }

    private function applyCors(Response $response, Request $request, bool $isPreflight = false): Response
    {
        $origin = (string) $request->headers->get('Origin', '');
        $allowedOrigins = \App\Infrastructure\Config\Config::corsAllowedOrigins();
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
        if ($isPreflight) {
            $response->headers->set('Access-Control-Max-Age', '600');
        }
        return $response;
    }
}
