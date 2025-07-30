<?php

namespace App\Controller\Api;

use App\Repository\RoleRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin', name: 'api_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/role-requests', name: 'role_requests', methods: ['GET'])]
    public function getRoleRequests(RoleRequestRepository $roleRequestRepository): JsonResponse
    {
        // Récupérer toutes les demandes en attente
        $pendingRequests = $roleRequestRepository->findPendingRequests();
        
        // Formater les données pour le frontend
        $formattedRequests = [];
        foreach ($pendingRequests as $request) {
            $formattedRequests[] = [
                'id' => $request->getId(),
                'user' => [
                    'id' => $request->getUser()->getId(),
                    'pseudo' => $request->getUser()->getPseudo(),
                    'email' => $request->getUser()->getEmail(),
                ],
                'requestedRole' => $request->getRequestedRole(),
                'status' => $request->getStatus(),
                'message' => $request->getMessage(),
                'createdAt' => $request->getCreatedAt()->format('Y-m-d H:i:s'),
                // Infos spécifiques boutique si applicable
                'shopName' => $request->getShopName(),
                'shopAddress' => $request->getShopAddress(),
                'shopPhone' => $request->getShopPhone(),
                'shopWebsite' => $request->getShopWebsite(),
                'siretNumber' => $request->getSiretNumber(),
            ];
        }

        return $this->json([
            'requests' => $formattedRequests,
            'total' => count($formattedRequests)
        ]);
    }

    #[Route('/role-requests/stats', name: 'role_requests_stats', methods: ['GET'])]
    public function getRoleRequestsStats(RoleRequestRepository $roleRequestRepository): JsonResponse
    {
        $stats = $roleRequestRepository->getRequestStats();
        
        return $this->json($stats);
    }
}