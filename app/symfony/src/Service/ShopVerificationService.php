<?php

namespace App\Service;

use App\Entity\RoleRequest;
use Psr\Log\LoggerInterface;

/**
 * Service d'orchestration pour la vérification automatique des boutiques
 */
class ShopVerificationService
{
    public function __construct(
        private SiretValidationService $siretValidator,
        private SirenApiService $sirenApi,
        private LoggerInterface $logger
    ) {}

    /**
     * Enrichit automatiquement une demande de rôle boutique
     */
    public function enrichRoleRequest(RoleRequest $roleRequest): void
    {
        if ($roleRequest->getRequestedRole() !== RoleRequest::ROLE_SHOP) {
            return; // Pas une demande boutique
        }

        $siret = $roleRequest->getSiretNumber();
        if (!$siret) {
            $this->logger->warning('Pas de SIRET pour enrichissement', [
                'role_request_id' => $roleRequest->getId()
            ]);
            return;
        }

        try {
            $this->logger->info('Début enrichissement boutique', [
                'role_request_id' => $roleRequest->getId(),
                'siret' => $siret
            ]);

            // 1. Validation SIRET
            $siretValidation = $this->siretValidator->validateAndFormat($siret);
            if (!$siretValidation['valid']) {
                $this->logger->error('SIRET invalide pour enrichissement', [
                    'siret' => $siret,
                    'errors' => $siretValidation['errors'] ?? []
                ]);
                return;
            }

            // 2. Récupération données INSEE (résilient)
            $inseeData = $this->sirenApi->getCompanyData($siret);

            // 3. Recherche Google Places (basée sur données user)
            $googleData = $this->searchGooglePlace(
                $roleRequest->getShopName(),
                $roleRequest->getShopAddressString()
            );

            // 4. Calcul score de confiance
            $verificationScore = $this->calculateVerificationScore([
                'siret_valid' => $siretValidation['valid'],
                'insee_available' => $inseeData['insee_available'] ?? false,
                'insee_data' => $inseeData['insee_data'] ?? null,
                'google_found' => $googleData['found'] ?? false,
                'user_data' => [
                    'shop_name' => $roleRequest->getShopName(),
                    'shop_address' => $roleRequest->getShopAddressString()
                ]
            ]);

            // 5. Sauvegarde données d'enrichissement
            $enrichmentData = [
                'siret_validation' => $siretValidation,
                'insee_data' => $inseeData,
                'google_data' => $googleData,
                'verification_details' => [
                    'score_breakdown' => $verificationScore['breakdown'],
                    'recommendations' => $verificationScore['recommendations']
                ],
                'processed_at' => date('c')
            ];

            $roleRequest->setSirenData($enrichmentData);
            $roleRequest->setVerificationScore($verificationScore['score']);
            $roleRequest->setVerificationDate(new \DateTimeImmutable());
            $roleRequest->setGooglePlaceId($googleData['place_id'] ?? null);

            $this->logger->info('Enrichissement terminé', [
                'role_request_id' => $roleRequest->getId(),
                'score' => $verificationScore['score'],
                'insee_available' => $inseeData['insee_available'] ?? false
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur enrichissement boutique', [
                'role_request_id' => $roleRequest->getId(),
                'error' => $e->getMessage()
            ]);

            // Sauvegarde info d'erreur sans bloquer
            $roleRequest->setSirenData([
                'error' => $e->getMessage(),
                'processed_at' => date('c')
            ]);
            $roleRequest->setVerificationScore(0);
            $roleRequest->setVerificationDate(new \DateTimeImmutable());
        }
    }

    /**
     * Recherche Google Places (version basique)
     */
    private function searchGooglePlace(string $shopName, ?string $shopAddress): array
    {
        try {
            if (!$shopName || !$shopAddress) {
                return ['found' => false, 'reason' => 'Données insuffisantes'];
            }

            // TODO: Intégrer vraie API Google Places plus tard
            // Pour l'instant, simulation basée sur la logique métier
            
            $query = trim($shopName . ' ' . $shopAddress);
            
            // Simulation de recherche intelligente
            $mockResult = [
                'found' => strlen($query) > 10, // Critère basique
                'place_id' => strlen($query) > 10 ? 'mock_place_' . md5($query) : null,
                'name' => $shopName,
                'address' => $shopAddress,
                'confidence' => min(100, max(0, (strlen($query) - 10) * 10)),
                'source' => 'MOCK_GOOGLE_PLACES'
            ];

            $this->logger->info('Recherche Google Places (mock)', [
                'query' => $query,
                'found' => $mockResult['found']
            ]);

            return $mockResult;

        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche Google Places', [
                'error' => $e->getMessage()
            ]);

            return ['found' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calcule un score de confiance global (0-100)
     */
    private function calculateVerificationScore(array $data): array
    {
        $score = 0;
        $breakdown = [];
        $recommendations = [];

        // 1. SIRET valide (30 points)
        if ($data['siret_valid']) {
            $score += 30;
            $breakdown['siret'] = 30;
        } else {
            $breakdown['siret'] = 0;
            $recommendations[] = 'SIRET invalide - Vérifier la saisie';
        }

        // 2. Données INSEE disponibles (25 points)
        if ($data['insee_available'] && $data['insee_data']) {
            $inseeData = $data['insee_data'];
            
            if ($inseeData['active'] ?? false) {
                $score += 25;
                $breakdown['insee'] = 25;
            } else {
                $score += 10; // Entreprise inactive
                $breakdown['insee'] = 10;
                $recommendations[] = 'Entreprise marquée comme inactive dans SIRENE';
            }

            // Bonus correspondance nom (10 points max)
            $nameMatch = $this->calculateNameSimilarity(
                $data['user_data']['shop_name'] ?? '',
                $inseeData['company_name'] ?? ''
            );
            $nameBonus = (int)($nameMatch * 0.1); // 0-10 points
            $score += $nameBonus;
            $breakdown['name_match'] = $nameBonus;

        } else {
            $breakdown['insee'] = 0;
            $recommendations[] = 'Données INSEE non disponibles - Score basé uniquement sur les données saisies';
        }

        // 3. Google Places trouvé (20 points)
        if ($data['google_found']) {
            $score += 20;
            $breakdown['google_places'] = 20;
        } else {
            $breakdown['google_places'] = 0;
            $recommendations[] = 'Boutique non trouvée sur Google Places';
        }

        // 4. Données complètes utilisateur (15 points)
        $userData = $data['user_data'];
        $completeness = 0;
        if (!empty($userData['shop_name'])) $completeness += 7;
        if (!empty($userData['shop_address'])) $completeness += 8;
        
        $score += $completeness;
        $breakdown['data_completeness'] = $completeness;

        // Limitation à 100
        $score = min(100, $score);

        return [
            'score' => $score,
            'breakdown' => $breakdown,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Calcule la similarité entre deux noms (0-100)
     */
    private function calculateNameSimilarity(string $name1, string $name2): int
    {
        if (!$name1 || !$name2) {
            return 0;
        }

        // Normalisation
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Similarité basique avec similar_text
        $similarity = 0;
        similar_text($name1, $name2, $similarity);

        return (int)$similarity;
    }

    /**
     * Récupère les données d'enrichissement pour l'affichage admin
     */
    public function getVerificationData(RoleRequest $roleRequest): array
    {
        $sirenData = $roleRequest->getSirenData() ?? [];
        
        return [
            'score' => $roleRequest->getVerificationScore(),
            'confidence_level' => $roleRequest->getConfidenceLevel(),
            'verification_date' => $roleRequest->getVerificationDate()?->format('c'),
            'needs_verification' => $roleRequest->needsVerification(),
            'details' => $sirenData,
            'google_place_id' => $roleRequest->getGooglePlaceId()
        ];
    }
}