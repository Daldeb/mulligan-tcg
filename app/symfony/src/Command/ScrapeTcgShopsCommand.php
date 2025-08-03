<?php

namespace App\Command;

use App\Entity\Shop;
use App\Entity\Address;
use App\Repository\ShopRepository;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:scrape-tcg-shops',
    description: 'Scrape des boutiques TCG depuis OpenStreetMap et insertion en BDD'
)]
class ScrapeTcgShopsCommand extends Command
{
    // Mots-clés pour recherche par nom
    private const TCG_KEYWORDS = [
        'magasin de jeux de cartes',
        'tcg'
    ];

    // Tags OSM précis pour boutiques de jeux/cartes
    private const OSM_SHOP_TAGS = [
        'games',
        'toys',
        'hobby',
        'collector'
    ];

    private const CITIES_FRANCE = [
        ['name' => 'Paris', 'lat' => 48.8566, 'lon' => 2.3522],
        ['name' => 'Marseille', 'lat' => 43.2965, 'lon' => 5.3698],
        ['name' => 'Lyon', 'lat' => 45.7640, 'lon' => 4.8357],
        ['name' => 'Toulouse', 'lat' => 43.6047, 'lon' => 1.4442],
        ['name' => 'Nice', 'lat' => 43.7102, 'lon' => 7.2620],
        ['name' => 'Nantes', 'lat' => 47.2184, 'lon' => -1.5536],
        ['name' => 'Montpellier', 'lat' => 43.6110, 'lon' => 3.8767],
        ['name' => 'Strasbourg', 'lat' => 48.5734, 'lon' => 7.7521],
        ['name' => 'Bordeaux', 'lat' => 44.8378, 'lon' => -0.5792],
        ['name' => 'Lille', 'lat' => 50.6292, 'lon' => 3.0573],
        ['name' => 'Rennes', 'lat' => 48.1173, 'lon' => -1.6778],
        ['name' => 'Reims', 'lat' => 49.2583, 'lon' => 4.0317],
        ['name' => 'Saint-Étienne', 'lat' => 45.4397, 'lon' => 4.3872],
        ['name' => 'Le Havre', 'lat' => 49.4944, 'lon' => 0.1079],
        ['name' => 'Toulon', 'lat' => 43.1242, 'lon' => 5.9280],
        ['name' => 'Grenoble', 'lat' => 45.1885, 'lon' => 5.7245],
        ['name' => 'Dijon', 'lat' => 47.3220, 'lon' => 5.0415],
        ['name' => 'Angers', 'lat' => 47.4784, 'lon' => -0.5632],
        ['name' => 'Nîmes', 'lat' => 43.8367, 'lon' => 4.3601],
        ['name' => 'Clermont-Ferrand', 'lat' => 45.7797, 'lon' => 3.0863]
    ];

    private const SERVICES_MAPPING = [
        'tournament' => ['tournoi'],
        'deck_building' => ['deck'],
        'singles' => ['carte unique', 'singles']
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly ShopRepository $shopRepository,
        private readonly AddressRepository $addressRepository,
        private readonly SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Source de scrapping (overpass|nominatim|all)', 'all')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limite du nombre de résultats par ville', 50)
            ->addOption('city', 'c', InputOption::VALUE_OPTIONAL, 'Ville spécifique à scrapper')
            ->addOption('radius', 'r', InputOption::VALUE_OPTIONAL, 'Rayon de recherche en km', 15)
            ->addOption('update-existing', null, InputOption::VALUE_NONE, 'Met à jour les boutiques existantes')
            ->setHelp('Cette commande scrape les boutiques TCG depuis OpenStreetMap et les insère en base de données.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $source = $input->getOption('source');
        $limit = (int) $input->getOption('limit');
        $cityFilter = $input->getOption('city');
        $radius = (float) $input->getOption('radius');
        $updateExisting = $input->getOption('update-existing');

        $io->title('🎯 Import des boutiques TCG en base de données');

        $cities = $cityFilter ? 
            array_filter(self::CITIES_FRANCE, fn($c) => stripos($c['name'], $cityFilter) !== false) : 
            self::CITIES_FRANCE;

        $totalShopsFound = 0;
        $totalShopsCreated = 0;
        $totalShopsUpdated = 0;
        $errors = [];

        $progressBar = new ProgressBar($output, count($cities));
        $progressBar->start();

        foreach ($cities as $city) {
            try {
                $cityResults = $this->scrapeCityShops($city, $source, $limit, $radius, $io);
                
                foreach ($cityResults as $shopData) {
                    $totalShopsFound++;
                    
                    $result = $this->processShopData($shopData, $updateExisting, $io);
                    if ($result['created']) $totalShopsCreated++;
                    if ($result['updated']) $totalShopsUpdated++;
                }
                
            } catch (\Exception $e) {
                $errors[] = "Erreur pour {$city['name']}: " . $e->getMessage();
                $io->error("Erreur lors du scrapping de {$city['name']}: " . $e->getMessage());
            }
            
            $progressBar->advance();
            usleep(500000); // 500ms entre les villes
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success('✅ Import terminé !');
        $io->table(['Métrique', 'Valeur'], [
            ['Villes scannées', count($cities)],
            ['Boutiques trouvées', $totalShopsFound],
            ['Boutiques créées', $totalShopsCreated],
            ['Boutiques mises à jour', $totalShopsUpdated],
            ['Erreurs', count($errors)]
        ]);

        return Command::SUCCESS;
    }

    private function scrapeCityShops(array $city, string $source, int $limit, float $radius, SymfonyStyle $io): array
    {
        $allResults = [];

        $io->text("  🏙️ Scrapping de {$city['name']}...");

        if ($source === 'overpass' || $source === 'all') {
            $overpassResults = $this->scrapeOverpassAPI($city, $radius, $io);
            $allResults = array_merge($allResults, $overpassResults);
        }

        if ($source === 'nominatim' || $source === 'all') {
            $nominatimResults = $this->scrapeNominatimAPI($city, $limit, $io);
            $allResults = array_merge($allResults, $nominatimResults);
        }

        $unique = $this->deduplicateResults($allResults);
        return array_slice($unique, 0, $limit);
    }

    private function scrapeOverpassAPI(array $city, float $radius, SymfonyStyle $io): array
    {
        $results = [];
        $io->text("    🔍 Overpass API...");

        try {
            $radiusDeg = $radius / 111;
            $south = $city['lat'] - $radiusDeg;
            $west = $city['lon'] - $radiusDeg;
            $north = $city['lat'] + $radiusDeg;
            $east = $city['lon'] + $radiusDeg;

            $tcgKeywords = implode('|', self::TCG_KEYWORDS);
            $shopTags = implode('|', self::OSM_SHOP_TAGS);
            
            $query = "[out:json][timeout:25];
                (
                  node[\"name\"~\"{$tcgKeywords}\",i]({$south},{$west},{$north},{$east});
                  way[\"name\"~\"{$tcgKeywords}\",i]({$south},{$west},{$north},{$east});
                  
                  node[\"shop\"~\"^({$shopTags})$\"]({$south},{$west},{$north},{$east});
                  way[\"shop\"~\"^({$shopTags})$\"]({$south},{$west},{$north},{$east});
                );
                out center meta;";

            $response = $this->httpClient->request('POST', 'https://overpass-api.de/api/interpreter', [
                'body' => $query,
                'headers' => ['Content-Type' => 'text/plain']
            ]);

            $data = $response->toArray();
            
            if (isset($data['elements'])) {
                foreach ($data['elements'] as $element) {
                    $shopData = $this->parseOverpassElement($element, $city['name']);
                    if ($shopData && $this->isRelevantTcgShop($shopData)) {
                        $results[] = $shopData;
                    }
                }
            }

        } catch (\Exception $e) {
            $io->warning("    ⚠️ Erreur Overpass API: " . $e->getMessage());
        }

        $io->text("    ✅ Overpass: " . count($results) . " boutiques");
        return $results;
    }

    private function scrapeNominatimAPI(array $city, int $limit, SymfonyStyle $io): array
    {
        $results = [];
        $io->text("    🔍 Nominatim API...");

        $searches = ['magasin de jeux de cartes', 'tcg'];

        foreach ($searches as $keyword) {
            try {
                $query = "{$keyword} {$city['name']} France";
                
                $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                    'query' => [
                        'q' => $query,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'extratags' => 1,
                        'limit' => 20,
                        'countrycodes' => 'fr'
                    ],
                    'headers' => [
                        'User-Agent' => 'MULLIGAN-TCG-Scraper/1.0 (contact@mulligantcg.fr)'
                    ]
                ]);

                $data = $response->toArray();
                
                foreach ($data as $place) {
                    $shopData = $this->parseNominatimPlace($place, $city['name']);
                    if ($shopData && $this->isRelevantTcgShop($shopData)) {
                        $results[] = $shopData;
                    }
                }

                sleep(1);
                
            } catch (\Exception $e) {
                $io->warning("    ⚠️ Erreur Nominatim pour '{$keyword}': " . $e->getMessage());
            }
        }

        $io->text("    ✅ Nominatim: " . count($results) . " boutiques");
        return $results;
    }

    private function parseOverpassElement(array $element, string $cityName): ?array
    {
        if (!isset($element['tags']['name'])) {
            return null;
        }

        $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
        $lon = $element['lon'] ?? $element['center']['lon'] ?? null;

        // Extraire l'adresse des tags OSM
        $addressData = $this->buildAddressFromTags($element['tags']);

        // Si l'adresse est incomplète et qu'on a les coordonnées, utiliser le géocodage inverse
        if (!$addressData['has_complete_address'] && $lat && $lon) {
            $reversedAddress = $this->reverseGeocode($lat, $lon);
            if ($reversedAddress) {
                // Fusionner les données : priorité aux tags OSM, compléter avec géocodage inverse
                $addressData['full_address'] = $addressData['full_address'] ?: $reversedAddress['full_address'];
                $addressData['postal_code'] = $addressData['postal_code'] ?: $reversedAddress['postal_code'];
                $addressData['city_from_tags'] = $addressData['city_from_tags'] ?: $reversedAddress['city'];
                $addressData['geocoding_source'] = $reversedAddress['source'];
            }
            
            // Petit délai pour respecter les limites de Nominatim
            usleep(100000); // 100ms
        }

        // Valeurs par défaut si toujours vide
        $finalAddress = $addressData['full_address'] ?: 'Adresse non spécifiée';
        $finalPostalCode = $addressData['postal_code'] ?: '00000';

        return [
            'name' => $element['tags']['name'],
            'latitude' => $lat,
            'longitude' => $lon,
            'shop_type' => $element['tags']['shop'] ?? null,
            'website' => $element['tags']['website'] ?? $element['tags']['contact:website'] ?? null,
            'phone' => $element['tags']['phone'] ?? $element['tags']['contact:phone'] ?? null,
            'opening_hours' => $element['tags']['opening_hours'] ?? null,
            'address' => $finalAddress,
            'postal_code' => $finalPostalCode,
            'city_from_tags' => $addressData['city_from_tags'],
            'city' => $cityName,
            'source' => 'overpass_api',
            'geocoding_used' => isset($addressData['geocoding_source']),
            'osm_id' => $element['id'] ?? null,
            'osm_type' => $element['type'] ?? null,
            'tags' => $element['tags'] ?? []
        ];
    }

    private function parseNominatimPlace(array $place, string $cityName): ?array
    {
        if (!isset($place['display_name'])) {
            return null;
        }

        $nameParts = explode(',', $place['display_name']);
        $name = trim($nameParts[0]);

        return [
            'name' => $name,
            'latitude' => (float) $place['lat'],
            'longitude' => (float) $place['lon'],
            'address' => $place['display_name'],
            'city' => $cityName,
            'source' => 'nominatim_api',
            'osm_id' => $place['osm_id'] ?? null,
            'osm_type' => $place['osm_type'] ?? null,
            'place_id' => $place['place_id'] ?? null,
            'tags' => $place['extratags'] ?? []
        ];
    }

    private function buildAddressFromTags(array $tags): array
    {
        // Essayer d'abord les tags contact: puis addr:
        $houseNumber = $tags['contact:housenumber'] ?? $tags['addr:housenumber'] ?? null;
        $street = $tags['contact:street'] ?? $tags['addr:street'] ?? null;
        $postalCode = $tags['contact:postcode'] ?? $tags['addr:postcode'] ?? null;
        $city = $tags['contact:city'] ?? $tags['addr:city'] ?? null;
        
        // Construire l'adresse complète
        $addressParts = [];
        if ($houseNumber) $addressParts[] = $houseNumber;
        if ($street) $addressParts[] = $street;
        
        $fullAddress = implode(', ', array_filter($addressParts)) ?: null;
        
        return [
            'full_address' => $fullAddress,
            'postal_code' => $postalCode,
            'city_from_tags' => $city,
            'has_complete_address' => ($fullAddress && $postalCode)
        ];
    }

    private function reverseGeocode(float $lat, float $lon): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/reverse', [
                'query' => [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lon,
                    'zoom' => 18,
                    'addressdetails' => 1
                ],
                'headers' => [
                    'User-Agent' => 'MULLIGAN-TCG-Scraper/1.0 (contact@mulligantcg.fr)'
                ]
            ]);

            $data = $response->toArray();
            
            if (!isset($data['address'])) {
                return null;
            }

            $address = $data['address'];
            
            // Construire l'adresse depuis le géocodage inverse
            $addressParts = [];
            if (isset($address['house_number'])) $addressParts[] = $address['house_number'];
            if (isset($address['road'])) $addressParts[] = $address['road'];
            
            $fullAddress = implode(', ', array_filter($addressParts));
            
            return [
                'full_address' => $fullAddress ?: null,
                'postal_code' => $address['postcode'] ?? null,
                'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'source' => 'reverse_geocoding'
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isRelevantTcgShop(array $shopData): bool
    {
        $name = strtolower($shopData['name']);
        $address = strtolower($shopData['address'] ?? '');
        $shopType = strtolower($shopData['shop_type'] ?? '');
        
        $tcgKeywords = [
            'tcg', 'magic', 'pokemon', 'cartes', 'card', 'jeux de cartes',
            'trading card', 'magasin de jeux', 'games', 'jeu', 'carte à jouer',
            'collection', 'collector', 'hobby'
        ];

        $negativeKeywords = [
            'micromania', 'fnac', 'cultura', 'auchan', 'carrefour', 'leclerc', 'intermarché',
            'casino', 'monoprix', 'franprix', 'lidl', 'aldi', 'super u', 'système u',
            'supermarché', 'hypermarché', 'pharmacie', 'banque', 'restaurant', 'café', 
            'bar', 'hotel', 'station', 'service', 'coiffeur', 'boulangerie', 'librairie',
            'tabac', 'presse', 'kiosque', 'bureau', 'école', 'collège', 'lycée',
            'playstation', 'xbox', 'nintendo', 'gaming center', 'cyber',
            'jeux video', 'console', 'retrogaming'
        ];

        foreach ($negativeKeywords as $negative) {
            if (str_contains($name, $negative) || str_contains($address, $negative)) {
                return false;
            }
        }

        foreach ($tcgKeywords as $keyword) {
            if (str_contains($name, $keyword) || str_contains($address, $keyword)) {
                return true;
            }
        }

        if (in_array($shopType, self::OSM_SHOP_TAGS)) {
            if (in_array($shopType, ['games', 'toys', 'hobby', 'collector'])) {
                $allText = $name . ' ' . $address;
                if (isset($shopData['tags'])) {
                    $allText .= ' ' . strtolower(implode(' ', array_values($shopData['tags'])));
                }
                
                $tcgIndices = ['jeu', 'carte', 'game', 'magic', 'pokemon', 'tcg', 'collection'];
                foreach ($tcgIndices as $indice) {
                    if (str_contains($allText, $indice)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    private function deduplicateResults(array $results): array
    {
        $unique = [];
        $seen = [];

        foreach ($results as $result) {
            $latRounded = round($result['latitude'] ?? 0, 4);
            $lonRounded = round($result['longitude'] ?? 0, 4);
            $key = strtolower($result['name']) . '|' . $latRounded . '|' . $lonRounded;
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $result;
            }
        }

        return $unique;
    }

    private function processShopData(array $shopData, bool $updateExisting, SymfonyStyle $io): array
    {
        $created = false;
        $updated = false;

        $existingShop = $this->findExistingShop($shopData);
        
        if ($existingShop) {
            if ($updateExisting) {
                $this->updateExistingShop($existingShop, $shopData);
                $updated = true;
                $io->text("      🔄 Mise à jour: {$shopData['name']}");
            } else {
                $io->text("      ⏭️  Ignoré (existe): {$shopData['name']}");
            }
        } else {
            $this->createNewShop($shopData);
            $created = true;
            $io->text("      ✨ Créé: {$shopData['name']}");
        }

        return ['created' => $created, 'updated' => $updated];
    }

    private function findExistingShop(array $shopData): ?Shop
    {
        // Recherche par nom exact
        $byName = $this->shopRepository->findOneBy(['name' => $shopData['name']]);
        if ($byName) return $byName;

        // Recherche par proximité géographique en utilisant la méthode du repository
        if (isset($shopData['latitude'], $shopData['longitude'])) {
            $nearbyShops = $this->shopRepository->findNearby(
                $shopData['latitude'], 
                $shopData['longitude'], 
                0.1 // 100m de rayon
            );
            
            foreach ($nearbyShops as $nearbyData) {
                $shop = $nearbyData['shop'];
                $similarity = 0;
                similar_text(strtolower($shop->getName()), strtolower($shopData['name']), $similarity);
                if ($similarity > 70) {
                    return $shop;
                }
            }
        }

        return null;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    private function createNewShop(array $shopData): void
    {
        // Créer l'adresse avec les vraies données des tags OSM
        $address = new Address();
        $address->setStreetAddress($shopData['address']);
        
        // Utiliser la ville des tags OSM si disponible, sinon celle de la recherche
        $cityToUse = $shopData['city_from_tags'] ?? $shopData['city'];
        $address->setCity($cityToUse);
        
        // Utiliser le vrai code postal extrait des tags OSM
        $address->setPostalCode($shopData['postal_code']);
        $address->setCountry('France');
        
        if (isset($shopData['latitude'], $shopData['longitude'])) {
            $address->setLatitude((string) $shopData['latitude']);
            $address->setLongitude((string) $shopData['longitude']);
        }

        $this->entityManager->persist($address);

        // Créer la boutique avec les bonnes constantes
        $shop = new Shop();
        $shop->setName($shopData['name']);
        
        // Générer un slug unique
        $baseSlug = $this->slugger->slug($shopData['name'])->lower();
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->shopRepository->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $shop->setSlug($slug);
        
        $shop->setType(Shop::TYPE_SCRAPED);
        $shop->setStatus(Shop::STATUS_PENDING);
        $shop->setAddress($address);
        
        if ($shopData['phone'] ?? null) {
            $shop->setPhone($shopData['phone']);
        }
        
        if ($shopData['website'] ?? null) {
            $shop->setWebsite($shopData['website']);
        }

        if (isset($shopData['opening_hours'])) {
            $shop->setOpeningHours(['raw' => $shopData['opening_hours']]);
        }

        $scrapingData = [
            'source' => $shopData['source'],
            'scraped_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'osm_id' => $shopData['osm_id'] ?? null,
            'osm_type' => $shopData['osm_type'] ?? null,
            'shop_type' => $shopData['shop_type'] ?? null,
            'geocoding_used' => $shopData['geocoding_used'] ?? false,
            'tags' => $shopData['tags'] ?? []
        ];
        $shop->setScrapingData($scrapingData);

        $confidenceScore = $this->calculateConfidenceScore($shopData);
        $shop->setConfidenceScore($confidenceScore);

        $services = $this->detectServices($shopData);
        $shop->setServices($services);

        $this->entityManager->persist($shop);
        $this->entityManager->flush();
    }

    private function updateExistingShop(Shop $shop, array $shopData): void
    {
        if (!$shop->getPhone() && ($shopData['phone'] ?? null)) {
            $shop->setPhone($shopData['phone']);
        }
        
        if (!$shop->getWebsite() && ($shopData['website'] ?? null)) {
            $shop->setWebsite($shopData['website']);
        }

        $address = $shop->getAddress();
        if ($address && !$address->getLatitude() && isset($shopData['latitude'])) {
            $address->setLatitude((string) $shopData['latitude']);
            $address->setLongitude((string) $shopData['longitude']);
        }

        $existingServices = $shop->getServices() ?? [];
        $newServices = $this->detectServices($shopData);
        $mergedServices = array_unique(array_merge($existingServices, $newServices));
        $shop->setServices($mergedServices);

        // Utilisation de la méthode existante de l'entité
        $shop->updateTimestamp();

        $this->entityManager->flush();
    }

    private function calculateConfidenceScore(array $shopData): int
    {
        $score = 50;

        if (isset($shopData['latitude'], $shopData['longitude'])) {
            $score += 25;
        }

        if ($shopData['website'] ?? null) {
            $score += 15;
        }

        if ($shopData['phone'] ?? null) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function detectServices(array $shopData): array
    {
        $services = [];
        $text = strtolower($shopData['name'] . ' ' . ($shopData['address'] ?? ''));
        
        if (isset($shopData['tags'])) {
            $tagsText = strtolower(implode(' ', array_values($shopData['tags'])));
            $text .= ' ' . $tagsText;
        }

        foreach (self::SERVICES_MAPPING as $service => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $services[] = $service;
                    break;
                }
            }
        }

        return array_unique($services);
    }
}