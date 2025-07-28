<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController
{
    #[Route('/api/test', name: 'api_test')]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'status' => '🟢 Symfony OK',
            'time' => date('c'),
            'env' => $_ENV['APP_ENV'] ?? 'not set'
        ]);
    }
}
