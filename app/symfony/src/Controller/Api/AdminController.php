<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\RoleRequestRepository;
use App\Service\ShopVerificationService;
use App\Service\NotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/admin', name: 'api_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private NotificationManager $notificationManager
    ) {}

    #[Route('/role-requests', name: 'role_requests', methods: ['GET'])]
    public function getRoleRequests(
        RoleRequestRepository $roleRequestRepository,
        ShopVerificationService $shopVerification
    ): JsonResponse {
        // Récupérer toutes les demandes en attente
        $pendingRequests = $roleRequestRepository->findPendingRequests();

        // Formater les données pour le frontend avec enrichissement
        $formattedRequests = [];
        foreach ($pendingRequests as $request) {
            $baseData = [
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
            ];

            // 🆕 Données enrichies pour les demandes boutique
            if ($request->getRequestedRole() === 'ROLE_SHOP') {
                $baseData['shop_data'] = [
                    'name' => $request->getShopName(),
                    'address' => $this->formatShopAddress($request),
                    'phone' => $request->getShopPhone(),
                    'website' => $request->getShopWebsite(),
                    'siret' => $request->getSiretNumber(),
                ];

                // 🆕 Données de vérification automatique
                $verificationData = $shopVerification->getVerificationData($request);
                $baseData['verification'] = [
                    'score' => $verificationData['score'],
                    'confidence_level' => $verificationData['confidence_level'],
                    'verification_date' => $verificationData['verification_date'],
                    'needs_verification' => $verificationData['needs_verification'],
                    'google_place_id' => $verificationData['google_place_id'],
                    'details' => $this->formatVerificationDetails($verificationData['details'] ?? [])
                ];
            }

            $formattedRequests[] = $baseData;
        }

        return $this->json([
            'requests' => $formattedRequests,
            'total' => count($formattedRequests),
            'summary' => [
                'shop_requests' => count(array_filter($formattedRequests, fn($r) => $r['requestedRole'] === 'ROLE_SHOP')),
                'organizer_requests' => count(array_filter($formattedRequests, fn($r) => $r['requestedRole'] === 'ROLE_ORGANIZER')),
            ]
        ]);
    }

    #[Route('/role-requests/stats', name: 'role_requests_stats', methods: ['GET'])]
    public function getRoleRequestsStats(RoleRequestRepository $roleRequestRepository): JsonResponse
    {
        $stats = $roleRequestRepository->getRequestStats();

        return $this->json($stats);
    }

    #[Route('/role-requests/{id}/verification-details', name: 'role_request_verification', methods: ['GET'])]
    public function getVerificationDetails(
        int $id,
        RoleRequestRepository $roleRequestRepository,
        ShopVerificationService $shopVerification
    ): JsonResponse {
        $request = $roleRequestRepository->find($id);
        
        if (!$request) {
            return $this->json(['error' => 'Demande non trouvée'], 404);
        }

        if ($request->getRequestedRole() !== 'ROLE_SHOP') {
            return $this->json(['error' => 'Pas une demande boutique'], 400);
        }

        $verificationData = $shopVerification->getVerificationData($request);
        
        return $this->json([
            'request_id' => $id,
            'verification' => $verificationData,
            'detailed_breakdown' => $this->getDetailedBreakdown($verificationData['details'] ?? []),
            'google_maps' => $this->getGoogleMapsData($request, $verificationData['google_place_id'])
        ]);
    }

    #[Route('/role-requests/{id}/approve', name: 'role_request_approve', methods: ['POST'])]
    public function approveRoleRequest(
        int $id,
        RoleRequestRepository $roleRequestRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $request = $roleRequestRepository->find($id);
        
        if (!$request) {
            return $this->json(['error' => 'Demande non trouvée'], 404);
        }
        
        if (!$request->isPending()) {
            return $this->json(['error' => 'Cette demande a déjà été traitée'], 400);
        }
        
        try {
            /** @var User $admin */
            $admin = $this->getUser();
            
            // Utiliser la méthode approve de l'entité
            $request->approve($admin);
            
            // Ajouter le rôle à l'utilisateur
            $user = $request->getUser();
            $roles = $user->getRoles();
            
            if (!in_array($request->getRequestedRole(), $roles)) {
                $roles[] = $request->getRequestedRole();
                $user->setRoles(array_unique($roles));
            }
            
            // 🆕 CRÉER LA NOTIFICATION D'APPROBATION
            $notification = $this->notificationManager->createRoleApprovedNotification($request);
            
            // Sauvegarder tout en une transaction
            $entityManager->flush();
            
            return $this->json([
                'message' => 'Demande approuvée avec succès',
                'request' => [
                    'id' => $request->getId(),
                    'status' => $request->getStatus(),
                    'reviewedAt' => $request->getReviewedAt()->format('c'),
                    'reviewedBy' => [
                        'id' => $admin->getId(),
                        'pseudo' => $admin->getPseudo()
                    ]
                ],
                'notification' => [
                    'id' => $notification->getId(),
                    'created' => true,
                    'message' => 'Notification envoyée à l\'utilisateur'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'approbation'], 500);
        }
    }

    #[Route('/role-requests/{id}/reject', name: 'role_request_reject', methods: ['POST'])]
    public function rejectRoleRequest(
        int $id,
        Request $httpRequest,
        RoleRequestRepository $roleRequestRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $roleRequest = $roleRequestRepository->find($id);
        
        if (!$roleRequest) {
            return $this->json(['error' => 'Demande non trouvée'], 404);
        }
        
        if (!$roleRequest->isPending()) {
            return $this->json(['error' => 'Cette demande a déjà été traitée'], 400);
        }
        
        try {
            /** @var User $admin */
            $admin = $this->getUser();
            $data = json_decode($httpRequest->getContent(), true);
            
            // Récupérer la réponse admin si fournie
            $adminResponse = $data['adminResponse'] ?? null;
            
            // Utiliser la méthode reject de l'entité
            $roleRequest->reject($admin, $adminResponse);
            
            // 🆕 CRÉER LA NOTIFICATION DE REJET
            $notification = $this->notificationManager->createRoleRejectedNotification($roleRequest);
            
            // Sauvegarder tout en une transaction
            $entityManager->flush();
            
            return $this->json([
                'message' => 'Demande rejetée avec succès',
                'request' => [
                    'id' => $roleRequest->getId(),
                    'status' => $roleRequest->getStatus(),
                    'reviewedAt' => $roleRequest->getReviewedAt()->format('c'),
                    'adminResponse' => $roleRequest->getAdminResponse(),
                    'reviewedBy' => [
                        'id' => $admin->getId(),
                        'pseudo' => $admin->getPseudo()
                    ]
                ],
                'notification' => [
                    'id' => $notification->getId(),
                    'created' => true,
                    'message' => 'Notification envoyée à l\'utilisateur'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du rejet'], 500);
        }
    }

    /**
     * Formate l'adresse de la boutique pour l'affichage admin
     */
    private function formatShopAddress($request): ?array
    {
        $address = $request->getShopAddress();
        if (!$address) {
            return null;
        }

        return [
            'id' => $address->getId(),
            'street' => $address->getStreetAddress(),
            'city' => $address->getCity(),
            'postal_code' => $address->getPostalCode(),
            'country' => $address->getCountry(),
            'full_address' => $address->getFullAddress(),
            'coordinates' => [
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude(),
                'has_coordinates' => $address->hasCoordinates()
            ]
        ];
    }

    /**
     * Formate les détails de vérification pour l'affichage admin
     */
    private function formatVerificationDetails(array $details): array
    {
        if (empty($details)) {
            return ['message' => 'Aucune donnée de vérification disponible'];
        }

        $formatted = [
            'processed_at' => $details['processed_at'] ?? null,
            'insee_status' => 'unknown',
            'google_status' => 'unknown',
            'recommendations' => []
        ];

        // Statut INSEE
        if (isset($details['insee_data'])) {
            $inseeData = $details['insee_data'];
            $formatted['insee_status'] = $inseeData['insee_available'] ? 'available' : 'unavailable';
            
            if ($inseeData['insee_available'] && isset($inseeData['insee_data'])) {
                $formatted['insee_company'] = [
                    'name' => $inseeData['insee_data']['company_name'] ?? null,
                    'active' => $inseeData['insee_data']['active'] ?? false,
                    'address' => $inseeData['insee_data']['address'] ?? null,
                    'activity_code' => $inseeData['insee_data']['activity_code'] ?? null,
                    'creation_date' => $inseeData['insee_data']['creation_date'] ?? null
                ];
            }
        }

        // Statut Google Places
        if (isset($details['google_data'])) {
            $googleData = $details['google_data'];
            $formatted['google_status'] = ($googleData['found'] ?? false) ? 'found' : 'not_found';
            
            if ($googleData['found'] ?? false) {
                $formatted['google_place'] = [
                    'name' => $googleData['name'] ?? null,
                    'address' => $googleData['address'] ?? null,
                    'confidence' => $googleData['confidence'] ?? 0
                ];
            }
        }

        // Recommandations
        if (isset($details['verification_details']['recommendations'])) {
            $formatted['recommendations'] = $details['verification_details']['recommendations'];
        }

        return $formatted;
    }

    /**
     * Analyse détaillée du score pour l'admin
     */
    private function getDetailedBreakdown(array $details): array
    {
        if (empty($details['verification_details']['score_breakdown'])) {
            return ['message' => 'Pas de détail de score disponible'];
        }

        $breakdown = $details['verification_details']['score_breakdown'];
        
        return [
            'siret_validation' => [
                'points' => $breakdown['siret'] ?? 0,
                'max_points' => 30,
                'status' => ($breakdown['siret'] ?? 0) > 0 ? 'valid' : 'invalid'
            ],
            'insee_data' => [
                'points' => $breakdown['insee'] ?? 0,
                'max_points' => 25,
                'status' => ($breakdown['insee'] ?? 0) > 0 ? 'available' : 'unavailable'
            ],
            'name_match' => [
                'points' => $breakdown['name_match'] ?? 0,
                'max_points' => 10,
                'status' => ($breakdown['name_match'] ?? 0) > 5 ? 'good' : 'poor'
            ],
            'google_places' => [
                'points' => $breakdown['google_places'] ?? 0,
                'max_points' => 20,
                'status' => ($breakdown['google_places'] ?? 0) > 0 ? 'found' : 'not_found'
            ],
            'data_completeness' => [
                'points' => $breakdown['data_completeness'] ?? 0,
                'max_points' => 15,
                'status' => ($breakdown['data_completeness'] ?? 0) > 10 ? 'complete' : 'incomplete'
            ]
        ];
    }

    /**
     * Données pour l'affichage Google Maps
     */
    private function getGoogleMapsData($request, ?string $googlePlaceId): array
    {
        $shopName = $request->getShopName();
        $address = $request->getShopAddress();
        
        if (!$shopName || !$address) {
            return ['available' => false];
        }

        // URL d'embed Google Maps
        $query = urlencode($shopName . ' ' . $address->getFullAddress());
        $embedUrl = "https://www.google.com/maps/embed/v1/search?key=YOUR_API_KEY&q={$query}";
        
        return [
            'available' => true,
            'embed_url' => $embedUrl,
            'search_query' => $shopName . ' ' . $address->getFullAddress(),
            'place_id' => $googlePlaceId,
            'coordinates' => [
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude()
            ]
        ];
    }
}