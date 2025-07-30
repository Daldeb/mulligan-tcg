<?php

namespace App\Controller;

use App\Service\SirenApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
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

    #[Route('/test-shop-verification', name: 'test_shop_verification')]
    public function testShopVerification(SirenApiService $sirenService): JsonResponse
    {
        // Données de test pour une boutique fictive
        $testData = [
            'shop_name' => 'Apple Store Opéra',
            'siret' => '13002526500013',
            'shop_address' => '12 Rue Halévy, 75009 Paris'
        ];

        $result = $sirenService->getCompanyData($testData['siret']);
        
        return $this->json([
            'test_data' => $testData,
            'siret_verification' => $result,
            'next_step' => 'Integration in ProfileController for real workflow',
            'timestamp' => date('c')
        ]);
    }
}