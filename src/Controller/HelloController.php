<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class HelloController
{
    public function hello(Request $request, array $params): JsonResponse
    {
        $name = (string)($params['name'] ?? 'world');
        return new JsonResponse([
            'message' => "Hello, $name!",
        ]);
    }
}
