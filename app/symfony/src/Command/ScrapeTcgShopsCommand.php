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

#[AsCommand(
    name: 'app:scrape-tcg-shops',
    description: 'Scrape des boutiques TCG depuis OpenStreetMap (100% gratuit)'
)]
class ScrapeTcgShopsCommand extends Command
{
    // Mots-clés pour recherche par nom
    private const TCG_KEYWORDS = [
        'magasin de jeux de cartes',
        'tcg'
    ];

    // Tags OSM précis pour boutiques de jeux/cartes (sans les supermarchés)
    private const OSM_SHOP_TAGS = [
        'games',        // Magasins de jeux
        'toys',         // Magasins de jouets (filtrage intelligent après)
        'hobby',        // Magasins de loisirs créatifs
        'collector'     // Boutiques de collection
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

    // Services ultra-simples
    private const SERVICES_MAPPING = [
        'tournament' => ['tournoi'],
        'deck_building' => ['deck'],
        'singles' => ['carte unique', 'singles']
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly ShopRepository $shopRepository,
        private readonly AddressRepository $addressRepository
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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans sauvegarde')
            ->addOption('export-file', null, InputOption::VALUE_OPTIONAL, 'Fichier d\'export détaillé')
            ->addOption('update-existing', null, InputOption::VALUE_NONE, 'Met à jour les boutiques existantes')
            ->setHelp('Cette commande scrape les boutiques TCG depuis OpenStreetMap et génère un rapport détaillé.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $source = $input->getOption('source');
        $limit = (int) $input->getOption('limit');
        $cityFilter = $input->getOption('city');
        $radius = (float) $input->getOption('radius');
        $dryRun = $input->getOption('dry-run');
        $exportFile = $input->getOption('export-file');
        $updateExisting = $input->getOption('update-existing');

        // Générer nom de fichier si pas spécifié
        if (!$exportFile) {
            $date = (new \DateTime())->format('Ymd_His');
            $exportFile = "var/scraping_report_{$date}.txt";
        }

        $io->title('🎯 Scrapping des boutiques TCG - Ultra-Précis');
        
        if ($dryRun || $exportFile) {
            $io->info("Génération du rapport détaillé : {$exportFile}");
        }

        $cities = $cityFilter ? 
            array_filter(self::CITIES_FRANCE, fn($c) => stripos($c['name'], $cityFilter) !== false) : 
            self::CITIES_FRANCE;

        $totalShopsFound = 0;
        $totalShopsCreated = 0;
        $totalShopsUpdated = 0;
        $errors = [];
        $allShopsData = [];

        $progressBar = new ProgressBar($output, count($cities));
        $progressBar->start();

        foreach ($cities as $city) {
            try {
                $cityResults = $this->scrapeCityShops($city, $source, $limit, $radius, $io);
                
                foreach ($cityResults as $shopData) {
                    $totalShopsFound++;
                    $allShopsData[] = $shopData;
                    
                    if (!$dryRun && !$exportFile) {
                        $result = $this->processShopData($shopData, $updateExisting, $io);
                        if ($result['created']) $totalShopsCreated++;
                        if ($result['updated']) $totalShopsUpdated++;
                    }
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

        // Générer le rapport détaillé
        if ($exportFile || $dryRun) {
            $this->generateDetailedReport($allShopsData, $exportFile, $io);
        }

        // Résumé final
        $io->success('✅ Scrapping terminé !');
        $io->table(['Métrique', 'Valeur'], [
            ['Villes scannées', count($cities)],
            ['Boutiques trouvées', $totalShopsFound],
            ['Boutiques créées', $dryRun ? 'N/A (dry-run)' : $totalShopsCreated],
            ['Boutiques mises à jour', $dryRun ? 'N/A (dry-run)' : $totalShopsUpdated],
            ['Erreurs', count($errors)]
        ]);

        if ($exportFile || $dryRun) {
            $io->success("📄 Rapport détaillé généré : {$exportFile}");
        }

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

            // Requête HYBRIDE : par nom ET par tags OSM
            $tcgKeywords = implode('|', self::TCG_KEYWORDS);
            $shopTags = implode('|', self::OSM_SHOP_TAGS);
            
            $query = "[out:json][timeout:25];
                (
                  // Recherche par nom (comme avant)
                  node[\"name\"~\"{$tcgKeywords}\",i]({$south},{$west},{$north},{$east});
                  way[\"name\"~\"{$tcgKeywords}\",i]({$south},{$west},{$north},{$east});
                  
                  // Recherche par tags shop=games/toys/hobby/collector
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

        // UNIQUEMENT ces 2 recherches
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

                sleep(1); // Rate limiting Nominatim
                
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

        return [
            'name' => $element['tags']['name'],
            'latitude' => $lat,
            'longitude' => $lon,
            'shop_type' => $element['tags']['shop'] ?? null,
            'website' => $element['tags']['website'] ?? null,
            'phone' => $element['tags']['phone'] ?? null,
            'opening_hours' => $element['tags']['opening_hours'] ?? null,
            'address' => $this->buildAddressFromTags($element['tags']),
            'city' => $cityName,
            'source' => 'overpass_api',
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

    private function buildAddressFromTags(array $tags): string
    {
        $parts = [];
        
        if (isset($tags['addr:housenumber'])) $parts[] = $tags['addr:housenumber'];
        if (isset($tags['addr:street'])) $parts[] = $tags['addr:street'];
        if (isset($tags['addr:postcode'])) $parts[] = $tags['addr:postcode'];
        if (isset($tags['addr:city'])) $parts[] = $tags['addr:city'];
        
        return implode(', ', array_filter($parts)) ?: 'Adresse non spécifiée';
    }

    private function isRelevantTcgShop(array $shopData): bool
    {
        $name = strtolower($shopData['name']);
        $address = strtolower($shopData['address'] ?? '');
        $shopType = strtolower($shopData['shop_type'] ?? '');
        
        // Mots-clés positifs TCG spécifiques
        $tcgKeywords = [
            'tcg', 'magic', 'pokemon', 'cartes', 'card', 'jeux de cartes',
            'trading card', 'magasin de jeux', 'games', 'jeu', 'carte à jouer',
            'collection', 'collector', 'hobby'
        ];

        // Blacklist étendue - EXCLUSIONS STRICTES
        $negativeKeywords = [
            // Grandes enseignes
            'micromania', 'fnac', 'cultura', 'auchan', 'carrefour', 'leclerc', 'intermarché',
            'casino', 'monoprix', 'franprix', 'lidl', 'aldi', 'super u', 'système u',
            
            // Types de commerces non-TCG
            'supermarché', 'hypermarché', 'pharmacie', 'banque', 'restaurant', 'café', 
            'bar', 'hotel', 'station', 'service', 'coiffeur', 'boulangerie', 'librairie',
            'tabac', 'presse', 'kiosque', 'bureau', 'école', 'collège', 'lycée',
            
            // Jeux vidéo pure (sans cartes)
            'playstation', 'xbox', 'nintendo', 'gaming center', 'cyber',
            'jeux video', 'console', 'retrogaming'
        ];

        // EXCLUSION STRICTE - Si contient un mot négatif = éliminé
        foreach ($negativeKeywords as $negative) {
            if (str_contains($name, $negative) || str_contains($address, $negative)) {
                return false;
            }
        }

        // INCLUSION PAR NOM - Si contient mots-clés TCG
        foreach ($tcgKeywords as $keyword) {
            if (str_contains($name, $keyword) || str_contains($address, $keyword)) {
                return true;
            }
        }

        // INCLUSION PAR TAG OSM - Si tag shop pertinent
        if (in_array($shopType, self::OSM_SHOP_TAGS)) {
            // Pour les tags génériques, vérifier qu'il y a un indice TCG
            if (in_array($shopType, ['games', 'toys', 'hobby', 'collector'])) {
                // Recherche d'indices TCG dans le nom ou tags additionnels
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
        $byName = $this->shopRepository->findOneBy(['name' => $shopData['name']]);
        if ($byName) return $byName;

        if (isset($shopData['latitude'], $shopData['longitude'])) {
            $nearby = $this->shopRepository->findNearby(
                $shopData['latitude'], 
                $shopData['longitude'], 
                0.1
            );
            
            foreach ($nearby as $shop) {
                $similarity = 0;
                similar_text(strtolower($shop->getName()), strtolower($shopData['name']), $similarity);
                if ($similarity > 70) {
                    return $shop;
                }
            }
        }

        return null;
    }

    private function createNewShop(array $shopData): void
    {
        $address = new Address();
        $address->setFullAddress($shopData['address']);
        $address->setCity($shopData['city']);
        $address->setCountry('France');
        
        if (isset($shopData['latitude'], $shopData['longitude'])) {
            $address->setLatitude($shopData['latitude']);
            $address->setLongitude($shopData['longitude']);
        }

        $this->entityManager->persist($address);

        $shop = new Shop();
        $shop->setName($shopData['name']);
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
            $address->setLatitude($shopData['latitude']);
            $address->setLongitude($shopData['longitude']);
        }

        $existingServices = $shop->getServices() ?? [];
        $newServices = $this->detectServices($shopData);
        $mergedServices = array_unique(array_merge($existingServices, $newServices));
        $shop->setServices($mergedServices);

        $shop->updateTimestamp();
        $this->entityManager->flush();
    }

    private function calculateConfidenceScore(array $shopData): int
    {
        $score = 50; // Score de base plus élevé car recherche ultra-précise

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

    private function generateDetailedReport(array $allShopsData, string $filename, SymfonyStyle $io): void
    {
        $io->text("📝 Génération du rapport détaillé...");
        
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $content = $this->buildReportContent($allShopsData);
        file_put_contents($filename, $content);
        
        $io->success("✅ Rapport généré : {$filename}");
    }

    private function buildReportContent(array $allShopsData): string
    {
        $report = [];
        
        $report[] = "==================================================================================";
        $report[] = "                     RAPPORT BOUTIQUES TCG - VERSION ULTRA-PRÉCISE";
        $report[] = "                         Recherche: 'magasin de jeux de cartes' + 'tcg'";
        $report[] = "                         " . date('Y-m-d H:i:s');
        $report[] = "==================================================================================";
        $report[] = "";
        $report[] = "📊 STATISTIQUES GLOBALES :";
        $report[] = "   • Total boutiques trouvées : " . count($allShopsData);
        $report[] = "   • Recherche HYBRIDE : noms TCG + tags OSM (games/toys/hobby/collector)";
        $report[] = "   • Filtrage intelligent : exclusion Micromania, grandes enseignes, etc.";
        $report[] = "";
        
        // Grouper par ville
        $byCity = [];
        foreach ($allShopsData as $shop) {
            $city = $shop['city'];
            if (!isset($byCity[$city])) {
                $byCity[$city] = [];
            }
            $byCity[$city][] = $shop;
        }
        
        $report[] = "🏙️ RÉPARTITION PAR VILLE :";
        foreach ($byCity as $city => $shops) {
            $report[] = "   • {$city} : " . count($shops) . " boutique(s)";
        }
        $report[] = "";
        $report[] = "==================================================================================";
        $report[] = "";
        
        // Détail par ville
        foreach ($byCity as $city => $shops) {
            $report[] = "🏙️ VILLE : " . strtoupper($city);
            $report[] = str_repeat("-", 80);
            $report[] = "";
            
            foreach ($shops as $index => $shop) {
                $shopNumber = $index + 1;
                $report[] = "📍 BOUTIQUE #{$shopNumber} - " . strtoupper($shop['name']);
                $report[] = str_repeat("·", 50);
                
                $report[] = "🏪 Nom : " . $shop['name'];
                $report[] = "📍 Adresse : " . $shop['address'];
                $report[] = "🏙️ Ville : " . $shop['city'];
                
                if (isset($shop['latitude'], $shop['longitude'])) {
                    $report[] = "🌍 GPS : {$shop['latitude']}, {$shop['longitude']}";
                    $report[] = "🗺️ Maps : https://www.openstreetmap.org/?mlat={$shop['latitude']}&mlon={$shop['longitude']}&zoom=18";
                } else {
                    $report[] = "🌍 GPS : Non disponible";
                }
                
                if ($shop['phone'] ?? null) {
                    $report[] = "📞 Téléphone : " . $shop['phone'];
                } else {
                    $report[] = "📞 Téléphone : Non renseigné";
                }
                
                if ($shop['website'] ?? null) {
                    $report[] = "🌐 Site web : " . $shop['website'];
                } else {
                    $report[] = "🌐 Site web : Non renseigné";
                }
                
                if ($shop['opening_hours'] ?? null) {
                    $report[] = "🕒 Horaires : " . $shop['opening_hours'];
                } else {
                    $report[] = "🕒 Horaires : Non renseignés";
                }
                
                $report[] = "🔍 Source : " . ucfirst(str_replace('_api', '', $shop['source']));
                
                $confidenceScore = $this->calculateConfidenceScore($shop);
                $confidenceEmoji = $confidenceScore >= 80 ? '🟢' : ($confidenceScore >= 60 ? '🟡' : '🟠');
                $report[] = "⭐ Score confiance : {$confidenceEmoji} {$confidenceScore}/100";
                
                // Analyse de pertinence enrichie
                $name = strtolower($shop['name']);
                $shopType = $shop['shop_type'] ?? '';
                
                if (str_contains($name, 'tcg')) {
                    $report[] = "✅ Pertinence : Contient 'tcg' dans le nom";
                } elseif (str_contains($name, 'cartes') || str_contains($name, 'card')) {
                    $report[] = "✅ Pertinence : Mention de cartes dans le nom";
                } elseif (str_contains($name, 'jeux') || str_contains($name, 'games')) {
                    $report[] = "✅ Pertinence : Magasin de jeux détecté";
                } elseif ($shopType) {
                    $report[] = "✅ Pertinence : Tag OSM shop={$shopType}";
                } else {
                    $report[] = "✅ Pertinence : Détection automatique";
                }
                
                if ($shop['osm_id'] ?? null) {
                    $osmType = $shop['osm_type'] ?? 'node';
                    $report[] = "🔗 OSM Link : https://www.openstreetmap.org/{$osmType}/{$shop['osm_id']}";
                }
                
                $report[] = "";
            }
            
            $report[] = "";
        }
        
        $report[] = "==================================================================================";
        $report[] = "Rapport généré le " . date('Y-m-d H:i:s') . " - RECHERCHE HYBRIDE INTELLIGENTE";
        $report[] = "Stratégie : Noms TCG + Tags OSM + Filtrage anti-grandes enseignes";
        $report[] = "==================================================================================";
        
        return implode("\n", $report);
    }
}