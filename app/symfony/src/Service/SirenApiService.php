<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'interrogation de l'API SIRENE INSEE officielle 3.11
 */
class SirenApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $inseeApiBaseUrl,
        private string $inseeBearerToken
    ) {}

    /**
     * Récupère les données d'un SIRET depuis l'API INSEE officielle 3.11
     * Système résilient : fonctionne même si l'API échoue
     */
    public function getCompanyData(string $siret): array
    {
        $cleanSiret = preg_replace('/[^0-9]/', '', $siret);
        
        if (strlen($cleanSiret) !== 14) {
            return [
                'success' => false,
                'error' => 'SIRET doit contenir 14 chiffres',
                'siret' => $siret,
                'api_call_date' => date('c')
            ];
        }

        // Structure de base (toujours présente)
        $baseResult = [
            'success' => false, // Sera mis à true si API fonctionne
            'siret' => $cleanSiret,
            'siren' => substr($cleanSiret, 0, 9),
            'api_call_date' => date('c'),
            'insee_data' => null,
            'insee_available' => false
        ];

        try {
            // URL API INSEE officielle
            $url = $this->inseeApiBaseUrl . '/siret/' . $cleanSiret;
            
            $this->logger->info('Tentative appel API INSEE', ['siret' => $cleanSiret]);

            // Appel HTTP avec authentification Bearer
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->inseeBearerToken,
                    'User-Agent' => 'MULLIGAN-TCG/1.0'
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                if (isset($data['etablissement'])) {
                    // ✅ API INSEE fonctionne
                    $etablissement = $data['etablissement'];
                    $uniteLegale = $etablissement['uniteLegale'] ?? [];

                    $baseResult['success'] = true;
                    $baseResult['insee_available'] = true;
                    $baseResult['insee_data'] = [
                        'company_name' => $this->extractCompanyName($uniteLegale),
                        'active' => $etablissement['etatAdministratifEtablissement'] === 'A',
                        'address' => $this->extractAddress($etablissement['adresseEtablissement'] ?? []),
                        'activity_code' => $etablissement['activitePrincipaleEtablissement'] ?? null,
                        'creation_date' => $etablissement['dateCreationEtablissement'] ?? null,
                        'source' => 'INSEE_OFFICIAL'
                    ];

                    $this->logger->info('API INSEE réussie', ['siret' => $cleanSiret]);
                } else {
                    throw new \Exception('Structure de réponse INSEE invalide');
                }
            } else {
                throw new \Exception('API INSEE erreur HTTP ' . $response->getStatusCode());
            }

        } catch (\Exception $e) {
            // ⚠️ API INSEE échoue - mais on continue quand même
            $this->logger->warning('API INSEE indisponible - Continuons sans', [
                'siret' => $cleanSiret,
                'error' => $e->getMessage()
            ]);

            $baseResult['success'] = true; // Le process global réussit
            $baseResult['insee_available'] = false;
            $baseResult['insee_error'] = $e->getMessage();
        }

        return $baseResult;
    }

    /**
     * Extrait le nom de l'entreprise depuis les données INSEE
     */
    private function extractCompanyName(array $uniteLegale): ?string
    {
        // Priorité : dénomination officielle
        if (!empty($uniteLegale['denominationUniteLegale'])) {
            return $uniteLegale['denominationUniteLegale'];
        }
        
        // Dénomination usuelle
        if (!empty($uniteLegale['denominationUsuelle1UniteLegale'])) {
            return $uniteLegale['denominationUsuelle1UniteLegale'];
        }

        // Pour les personnes physiques : Nom + Prénom
        $nom = $uniteLegale['nomUniteLegale'] ?? '';
        $prenom = $uniteLegale['prenomUsuelUniteLegale'] ?? '';
        if ($nom && $prenom) {
            return trim($prenom . ' ' . $nom);
        }

        return $nom ?: null;
    }

    /**
     * Extrait l'adresse de l'établissement INSEE
     */
    private function extractAddress(array $adresse): array
    {
        if (empty($adresse)) {
            return ['full_address' => null];
        }

        $components = [];
        
        // Numéro et voie
        if (!empty($adresse['numeroVoieEtablissement'])) {
            $components[] = $adresse['numeroVoieEtablissement'];
        }
        if (!empty($adresse['typeVoieEtablissement'])) {
            $components[] = $adresse['typeVoieEtablissement'];
        }
        if (!empty($adresse['libelleVoieEtablissement'])) {
            $components[] = $adresse['libelleVoieEtablissement'];
        }

        $street = implode(' ', $components);
        $city = $adresse['libelleCommuneEtablissement'] ?? '';
        $postalCode = $adresse['codePostalEtablissement'] ?? '';

        return [
            'street' => $street ?: null,
            'city' => $city ?: null,
            'postal_code' => $postalCode ?: null,
            'full_address' => trim($street . ', ' . $postalCode . ' ' . $city, ', ') ?: null
        ];
    }
}