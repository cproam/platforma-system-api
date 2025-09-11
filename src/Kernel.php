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
use Psr\Container\ContainerInterface;
use App\Infrastructure\Security\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\LogEntry;
use App\Entity\User;

final class Kernel
{
    public function __construct(private readonly RouteCollection $routes, private readonly ?ContainerInterface $container = null) {}

    public function handle(Request $request): Response
    {
        if ($this->isPreflight($request)) {
            return $this->handlePreflight($request);
        }

        try {
            $parameters = $this->matchRoute($request);
            $controllerRef = $parameters['_controller'] ?? null;

            if ($this->isAuthRequired($request->getPathInfo())) {
                $authResult = $this->authenticate($request);
                if ($authResult !== true) {
                    return $authResult; // already a Response
                }
            }

            $response = $this->executeController($controllerRef, $request, $parameters);
            $response = $this->applyCors($response, $request);
            $this->logRequest($request, $response->getStatusCode(), null);
            return $response;
        } catch (ResourceNotFoundException) {
            return $this->error($request, 404, 'Not Found');
        } catch (\Throwable) {
            return $this->error($request, 500, 'Server Error');
        }
    }

    /* ---------------- Core Steps ---------------- */

    private function matchRoute(Request $request): array
    {
        $context = (new RequestContext())->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);
        return $matcher->match($request->getPathInfo());
    }

    private function executeController(mixed $controllerRef, Request $request, array $parameters): Response
    {
        if (is_callable($controllerRef)) {
            return $controllerRef($request, $parameters);
        }
        if (is_string($controllerRef) && str_contains($controllerRef, '::')) {
            [$class, $method] = explode('::', $controllerRef, 2);
            if (class_exists($class) && method_exists($class, $method)) {
                $instance = $this->container?->get($class) ?? new $class();
                return $instance->$method($request, $parameters);
            }
        }
        throw new \RuntimeException('Controller not found');
    }

    /* ---------------- Authentication ---------------- */

    private function isAuthRequired(string $path): bool
    {
        return $path !== '/auth/login';
    }

    private function authenticate(Request $request): bool|Response
    {
        $authHeader = $request->headers->get('Authorization', '');
        if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $m)) {
            return $this->error($request, 401, 'Unauthorized');
        }
        $token = $m[1];
        try {
            /** @var JwtService $jwt */
            $jwt = $this->container?->get(JwtService::class);
            $claims = $jwt->verify($token);
            $userId = isset($claims['sub']) ? (int)$claims['sub'] : null;
            $request->attributes->set('auth', $claims);
            /** @var EntityManagerInterface $em */
            $em = $this->container?->get(EntityManagerInterface::class);
            if ($em && $userId) {
                $user = $em->find(User::class, $userId);
                if ($user && $user->isBanned()) {
                    return $this->error($request, 403, 'banned');
                }
            }
            return true;
        } catch (\Throwable) {
            return $this->error($request, 401, 'Invalid token');
        }
    }

    /* ---------------- Preflight ---------------- */

    private function isPreflight(Request $request): bool
    {
        return strtoupper($request->getMethod()) === 'OPTIONS';
    }

    private function handlePreflight(Request $request): Response
    {
        $resp = new Response('', 204);
        $resp = $this->applyCors($resp, $request, true);
        $this->logRequest($request, 204, null, 'preflight');
        return $resp;
    }

    /* ---------------- Logging & Errors ---------------- */

    private function logRequest(Request $request, int $status, ?string $message = null, ?string $forcedAction = null): void
    {
        try {
            /** @var EntityManagerInterface $em */
            $em = $this->container?->get(EntityManagerInterface::class);
            if (!$em) {
                return;
            }
            $claims = (array)$request->attributes->get('auth', []);
            $uid = isset($claims['sub']) ? (int)$claims['sub'] : null;
            $ip = $request->getClientIp() ?: ($request->server->get('REMOTE_ADDR') ?? null);
            $actionObj = $this->deriveActionObject($request->getMethod(), $request->getPathInfo());
            if ($forcedAction) {
                $actionObj['action'] = $forcedAction;
            }
            $log = new LogEntry(
                $request->getMethod(),
                $request->getPathInfo(),
                $status,
                $message,
                $uid,
                $ip,
                $actionObj['action'],
                $actionObj['object']
            );
            $em->persist($log);
            $em->flush();
        } catch (\Throwable) {
            // ignore logging failures
        }
    }

    private function error(Request $request, int $status, string $message): Response
    {
        $this->logRequest($request, $status, $message);
        $payload = [
            'error' => ['code' => $status, 'message' => $message],
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'requestId' => bin2hex(random_bytes(8)),
        ];
        $resp = new JsonResponse($payload, $status);
        return $this->applyCors($resp, $request);
    }

    /* ---------------- Utility ---------------- */

    private function applyCors(Response $response, Request $request, bool $isPreflight = false): Response
    {
        $origin = (string)$request->headers->get('Origin', '');
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

    private function deriveActionObject(string $method, string $path): array
    {
        $action = strtolower($method);
        $object = null;
        $trimmed = trim($path, '/');
        if ($trimmed !== '') {
            $parts = explode('/', $trimmed);
            $object = $parts[0] ?? null;
        }
        return ['action' => $action, 'object' => $object];
    }
}
