<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\Magic\MagicSet;
use App\Entity\Magic\MagicCard;
use App\Repository\GameRepository;
use App\Repository\Magic\MagicSetRepository;
use App\Repository\Magic\MagicCardRepository;
use App\Service\ImageDownloadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-magic-cards',
    description: 'Import Magic: The Gathering cards from Scryfall API with French/English support'
)]
class ImportMagicCardsCommand extends Command
{
    private int $batchSize = 25;
    private int $downloadedImages = 0;
    private float $totalImageSize = 0;
    private array $processedOracleIds = []; // Cache oracle_ids pour éviter duplicatas
    private array $processedSetIds = []; // Cache set_ids déjà traités

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private GameRepository $gameRepository,
        private MagicSetRepository $magicSetRepository,
        private MagicCardRepository $magicCardRepository,
        private ImageDownloadService $imageDownloadService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Language locale (fr, en)', 'fr')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Magic format (standard, commander)', 'standard')
            ->addOption('complete-missing', 'c', InputOption::VALUE_NONE, 'Complete missing cards with this locale')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would be imported without importing')
            ->setHelp(<<<'EOF'
Import Magic: The Gathering cards from Scryfall API.

Examples:
  <info>php bin/console app:import-magic-cards --locale=fr --format=standard</info>
  <info>php bin/console app:import-magic-cards --locale=fr --format=commander</info>
  <info>php bin/console app:import-magic-cards --locale=en --complete-missing</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $format = $input->getOption('format');
        $completeMissing = $input->getOption('complete-missing');
        $dryRun = $input->getOption('dry-run');

        $io->title("🃏 Magic: The Gathering Cards Import - {$locale} - {$format}");

        try {
            // Optimisation mémoire
            ini_set('memory_limit', '1024M');
            gc_enable();
            
            // Clear EntityManager au démarrage
            $this->entityManager->clear();
            $this->processedOracleIds = [];
            $this->processedSetIds = [];
            
            // Pré-charger tous les oracle_ids existants pour éviter duplicatas
            if ($completeMissing) {
                $existingCards = $this->magicCardRepository->findAll();
                foreach ($existingCards as $card) {
                    $this->processedOracleIds[] = $card->getOracleId();
                }
                $io->writeln("📋 Loaded " . count($this->processedOracleIds) . " existing oracle_ids");
            }

            // Vérifier que Magic existe
            $magicGame = $this->gameRepository->findOneBy(['slug' => 'magic']);
            if (!$magicGame) {
                $io->error('Magic game not found! Please run app:init-games first.');
                return Command::FAILURE;
            }

            // Récupérer les données avec filtrage strict
            $io->section('📋 Fetching cards from Scryfall...');
            $cardsData = $this->fetchCardsData($format, $locale, $completeMissing, $io);
            
            if (empty($cardsData)) {
                $io->error('No cards found');
                return Command::FAILURE;
            }

            $io->writeln("Found " . count($cardsData) . " cards to process");

            if ($dryRun) {
                $this->displayDryRunStats($cardsData, $io);
                return Command::SUCCESS;
            }

            // Import par batches
            $io->section('⬇️ Importing cards with memory optimization...');
            $results = $this->importCardsInBatches($cardsData, $magicGame, $io);

            // Résumé final
            $this->displayFinalSummary($results, $io);

        } catch (\Exception $e) {
            $io->error("💥 Import failed: " . $e->getMessage());
            $io->writeln("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Récupère les cartes avec filtrage strict côté application
     */
    private function fetchCardsData(string $format, string $locale, bool $completeMissing, SymfonyStyle $io): array
    {
        $query = $this->buildScryfallQuery($format, $locale);
        $io->writeln("📡 Scryfall query: {$query}");

        $allCards = [];
        $page = 1;
        $hasMore = true;
        $filteredOut = 0;

        while ($hasMore) {
            $io->writeln("Fetching page {$page}...");
            
            $response = $this->makeScryfallRequest('https://api.scryfall.com/cards/search', [
                'q' => $query,
                'page' => $page
            ]);
            
            if (empty($response['data'])) {
                break;
            }

            foreach ($response['data'] as $cardData) {
                // FILTRAGE STRICT CÔTÉ APPLICATION
                
                // 1. Skip si completion mode et carte existe déjà
                if ($completeMissing && in_array($cardData['oracle_id'], $this->processedOracleIds)) {
                    continue;
                }
                
                // 2. Skip si oracle_id déjà traité dans cette session
                if (in_array($cardData['oracle_id'], $this->processedOracleIds)) {
                    continue;
                }
                
                // 3. Filtrage strict des variantes (CÔTÉ APPLICATION)
                if ($this->shouldSkipCard($cardData)) {
                    $filteredOut++;
                    continue;
                }
                
                // Ajouter oracle_id au cache pour éviter duplicatas
                $this->processedOracleIds[] = $cardData['oracle_id'];
                $allCards[] = $cardData;
            }

            $hasMore = $response['has_more'] ?? false;
            $page++;

            // Rate limit Scryfall
            usleep(100000);
        }

        if ($filteredOut > 0) {
            $io->writeln("🗂️ Filtered out {$filteredOut} variant/duplicate cards");
        }

        return $allCards;
    }

    /**
     * Détermine si une carte doit être ignorée (filtrage strict)
     */
    private function shouldSkipCard(array $cardData): bool
    {
        // Layouts à ignorer (tokens, emblems)
        $ignoredLayouts = ['token', 'emblem', 'scheme', 'vanguard', 'art_series', 'double_faced_token'];
        if (in_array($cardData['layout'] ?? '', $ignoredLayouts)) {
            return true;
        }
        
        // Flags disqualifiants
        if ($cardData['promo'] ?? false) return true;
        if ($cardData['variation'] ?? false) return true;
        if ($cardData['reprint'] ?? false) return true; // FILTRAGE STRICT DES REPRINTS
        if ($cardData['digital'] ?? false) return true;
        
        // Doit avoir une version nonfoil
        $finishes = $cardData['finishes'] ?? [];
        if (!in_array('nonfoil', $finishes)) return true;
        
        // Doit être disponible en papier
        $games = $cardData['games'] ?? [];
        if (!in_array('paper', $games)) return true;
        
        return false;
    }

    /**
     * Import des cartes par batches avec gestion robuste des sets
     */
    private function importCardsInBatches(array $cardsData, Game $magicGame, SymfonyStyle $io): array
    {
        $progressBar = $io->createProgressBar(count($cardsData));
        $progressBar->setFormat(
            "%current%/%max% [%bar%] %percent:3s%% | Images: %downloaded% | Size: %total_size% | Memory: %memory%"
        );
        
        $progressBar->setMessage('0', 'downloaded');
        $progressBar->setMessage('0.0MB', 'total_size');
        $progressBar->setMessage('0MB', 'memory');
        $progressBar->start();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        // Garder l'ID du jeu Magic pour le récupérer après clear()
        $magicGameId = $magicGame->getId();

        $batches = array_chunk($cardsData, $this->batchSize);

        foreach ($batches as $batchIndex => $batch) {
            // Récupérer le jeu Magic depuis la base après clear()
            $currentMagicGame = $this->gameRepository->find($magicGameId);
            
            // Pré-créer tous les sets de ce batch pour éviter les conflits
            $this->preCreateBatchSets($batch, $currentMagicGame, $io);
            
            foreach ($batch as $cardData) {
                try {
                    // Récupérer le jeu Magic frais pour chaque carte
                    $freshMagicGame = $this->gameRepository->find($magicGameId);
                    $result = $this->importSingleCard($cardData, $freshMagicGame);
                    
                    switch ($result) {
                        case 'imported':
                            $imported++;
                            break;
                        case 'updated':
                            $updated++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $io->writeln("\n❌ Error with card: " . ($cardData['name'] ?? 'Unknown'));
                    $io->writeln("   Error: " . $e->getMessage());
                    
                    // Continuer l'import malgré l'erreur
                    continue;
                }

                $progressBar->advance();
                
                // Mise à jour progress bar
                $progressBar->setMessage((string)$this->downloadedImages, 'downloaded');
                $progressBar->setMessage(number_format($this->totalImageSize / 1048576, 1) . 'MB', 'total_size');
                $progressBar->setMessage(number_format(memory_get_usage(true) / 1048576, 0) . 'MB', 'memory');
            }

            // Flush et cleanup après chaque batch
            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles();

            // Rate limit entre batches
            usleep(50000);
        }

        $progressBar->finish();
        $io->newLine(2);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Pré-créé tous les sets d'un batch pour éviter les conflits
     */
    private function preCreateBatchSets(array $batch, Game $magicGame, SymfonyStyle $io): void
    {
        $uniqueSetIds = [];
        
        // Extraire tous les set_ids uniques du batch
        foreach ($batch as $cardData) {
            $setId = $cardData['set_id'];
            if (!in_array($setId, $uniqueSetIds) && !in_array($setId, $this->processedSetIds)) {
                $uniqueSetIds[] = $setId;
            }
        }
        
        // Créer tous les sets manquants en une fois
        foreach ($uniqueSetIds as $setId) {
            // Trouver la première carte de ce set dans le batch pour les données
            $setCardData = null;
            foreach ($batch as $cardData) {
                if ($cardData['set_id'] === $setId) {
                    $setCardData = $cardData;
                    break;
                }
            }
            
            if ($setCardData) {
                $this->createSetIfNotExists($setCardData, $magicGame);
                $this->processedSetIds[] = $setId;
            }
        }
        
        // Flush immédiatement tous les sets
        $this->entityManager->flush();
    }

    /**
     * Crée un set seulement s'il n'existe pas
     */
    private function createSetIfNotExists(array $cardData, Game $magicGame): ?MagicSet
    {
        $setId = $cardData['set_id'];
        
        // Vérifier d'abord en base
        $existingSet = $this->magicSetRepository->findByScryfallId($setId);
        if ($existingSet) {
            return $existingSet;
        }
        
        // Créer le nouveau set
        $magicSet = new MagicSet();
        $magicSet->setScryfallId($setId);
        $magicSet->setSetCode($cardData['set']);
        $magicSet->setName($cardData['set_name']);
        $magicSet->setSetType($cardData['set_type']);
        $magicSet->setSetUri($cardData['set_uri'] ?? null);
        $magicSet->setScryfallUri($cardData['scryfall_set_uri'] ?? null);
        $magicSet->setSearchUri($cardData['set_search_uri'] ?? null);
        $magicSet->setGame($magicGame);
        $magicSet->markAsSynced();

        $this->entityManager->persist($magicSet);
        
        return $magicSet;
    }

    /**
     * Import d'une carte Magic
     */
    private function importSingleCard(array $cardData, Game $magicGame): string
    {
        // Vérifications de base
        if (empty($cardData['oracle_id']) || empty($cardData['name'])) {
            return 'skipped';
        }

        // Récupérer le set (déjà créé dans preCreateBatchSets)
        $magicSet = $this->magicSetRepository->findByScryfallId($cardData['set_id']);
        if (!$magicSet) {
            throw new \Exception("Set {$cardData['set_id']} not found - should have been pre-created");
        }

        // Vérifier si carte existe (deduplication par oracle_id)
        $existingCard = $this->magicCardRepository->findByOracleId($cardData['oracle_id']);
        
        if (!$existingCard) {
            $card = new MagicCard();
            $card->setOracleId($cardData['oracle_id']);
            $card->setMagicSet($magicSet);
            $status = 'imported';
        } else {
            $card = $existingCard;
            $status = 'updated';
        }

        // Télécharger images
        $imageInfo = $this->downloadCardImage($cardData);
        
        if ($imageInfo['downloaded']) {
            $this->downloadedImages++;
            $this->totalImageSize += $imageInfo['size'];
        }

        // Mapper toutes les données
        $this->mapCardData($card, $cardData, $imageInfo);

        $this->entityManager->persist($card);

        return $status;
    }

    /**
     * Mapping complet des données API vers entité
     */
    private function mapCardData(MagicCard $card, array $cardData, array $imageInfo): void
    {
        // IDs et métadonnées
        $card->setScryfallId($cardData['id']);
        $card->setName($cardData['name']);
        $card->setPrintedName($cardData['printed_name'] ?? null);
        $card->setLang($cardData['lang'] ?? 'en');
        
        // Gameplay
        $card->setManaCost($cardData['mana_cost'] ?? null);
        $card->setCmc($cardData['cmc'] ?? null);
        $card->setTypeLine($cardData['type_line'] ?? null);
        $card->setPrintedTypeLine($cardData['printed_type_line'] ?? null);
        $card->setOracleText($cardData['oracle_text'] ?? null);
        $card->setPrintedText($cardData['printed_text'] ?? null);
        $card->setFlavorText($cardData['flavor_text'] ?? null);
        $card->setPower($cardData['power'] ?? null);
        $card->setToughness($cardData['toughness'] ?? null);
        
        // Couleurs et mots-clés
        $card->setColors($cardData['colors'] ?? null);
        $card->setColorIdentity($cardData['color_identity'] ?? null);
        $card->setKeywords($cardData['keywords'] ?? null);
        $card->setProducedMana($cardData['produced_mana'] ?? null);
        $card->setRarity($cardData['rarity'] ?? 'common');
        
        // Légalité des formats
        $legalities = $cardData['legalities'] ?? [];
        $card->setIsStandardLegal(($legalities['standard'] ?? 'not_legal') === 'legal');
        $card->setIsCommanderLegal(($legalities['commander'] ?? 'not_legal') === 'legal');
        
        // Images
        $card->setImageUrl($imageInfo['normal_path']);
        $card->setImageUrlLarge($imageInfo['large_path']);
        
        // Art et design
        $card->setArtist($cardData['artist'] ?? null);
        $card->setArtistIds($cardData['artist_ids'] ?? null);
        $card->setIllustrationId($cardData['illustration_id'] ?? null);
        $card->setLayout($cardData['layout'] ?? 'normal');
        $card->setFrame($cardData['frame'] ?? '2015');
        $card->setFrameEffects($cardData['frame_effects'] ?? null);
        $card->setBorderColor($cardData['border_color'] ?? 'black');
        $card->setSecurityStamp($cardData['security_stamp'] ?? null);
        $card->setWatermark($cardData['watermark'] ?? null);
        
        // Flags
        $card->setIsPromo($cardData['promo'] ?? false);
        $card->setIsReprint($cardData['reprint'] ?? false);
        $card->setIsReserved($cardData['reserved'] ?? false);
        $card->setIsFullArt($cardData['full_art'] ?? false);
        $card->setIsTextless($cardData['textless'] ?? false);
        $card->setIsBooster($cardData['booster'] ?? true);
        $card->setIsDigital($cardData['digital'] ?? false);
        
        // Métadonnées plateformes
        $card->setGames($cardData['games'] ?? null);
        $card->setFinishes($cardData['finishes'] ?? null);
        $card->setMultiverseIds($cardData['multiverse_ids'] ?? null);
        $card->setMtgoId($cardData['mtgo_id'] ?? null);
        $card->setArenaId($cardData['arena_id'] ?? null);
        $card->setTcgplayerId($cardData['tcgplayer_id'] ?? null);
        $card->setCardmarketId($cardData['cardmarket_id'] ?? null);
        $card->setEdhrecRank($cardData['edhrec_rank'] ?? null);
        $card->setPennyRank($cardData['penny_rank'] ?? null);
        
        // Date de sortie
        if (isset($cardData['released_at'])) {
            try {
                $card->setReleasedAt(new \DateTimeImmutable($cardData['released_at']));
            } catch (\Exception $e) {
                // Ignorer les erreurs de date
            }
        }
        
        $card->markAsSynced();
    }

    /**
     * Télécharge les images avec stratégie de fallback
     */
    private function downloadCardImage(array $cardData): array
    {
        if (!isset($cardData['image_uris'])) {
            return [
                'normal_path' => null,
                'large_path' => null,
                'downloaded' => false,
                'size' => 0
            ];
        }

        $scryfallId = $cardData['id'];
        $imageUris = $cardData['image_uris'];
        
        // Stratégie : normal prioritaire, large en fallback
        $normalInfo = $this->imageDownloadService->downloadMagicCard($imageUris, $scryfallId, 'normal');
        $largeInfo = $this->imageDownloadService->downloadMagicCard($imageUris, $scryfallId, 'large');
        
        return [
            'normal_path' => $normalInfo['path'],
            'large_path' => $largeInfo['path'],
            'downloaded' => $normalInfo['downloaded'] || $largeInfo['downloaded'],
            'size' => $normalInfo['size'] + $largeInfo['size']
        ];
    }

    /**
     * Requête Scryfall simplifiée (filtrage côté application)
     */
    private function buildScryfallQuery(string $format, string $locale): string
    {
        $formatQuery = match ($format) {
            'standard' => 'f:standard',
            'commander' => 'f:commander', 
            default => throw new \InvalidArgumentException("Format inconnu: {$format}")
        };

        // Requête simplifiée - filtrage principal côté application
        return "{$formatQuery} lang:{$locale}";
    }

    /**
     * Requête Scryfall avec gestion erreurs
     */
    private function makeScryfallRequest(string $url, array $params = []): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'query' => $params,
            'headers' => [
                'User-Agent' => 'MulliganTCG/1.0',
                'Accept' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Scryfall API error: HTTP {$response->getStatusCode()}");
        }

        $data = $response->toArray();
        
        if (isset($data['object']) && $data['object'] === 'error') {
            throw new \Exception("Scryfall API error: " . ($data['details'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Statistiques en mode dry-run
     */
    private function displayDryRunStats(array $cardsData, SymfonyStyle $io): void
    {
        $io->section('📊 Dry Run Analysis');
        
        $stats = [
            'total' => count($cardsData),
            'standard' => 0,
            'commander' => 0,
            'both_formats' => 0,
            'sets' => [],
            'rarities' => []
        ];

        foreach ($cardsData as $cardData) {
            $legalities = $cardData['legalities'] ?? [];
            $isStandard = ($legalities['standard'] ?? 'not_legal') === 'legal';
            $isCommander = ($legalities['commander'] ?? 'not_legal') === 'legal';
            
            if ($isStandard) $stats['standard']++;
            if ($isCommander) $stats['commander']++;
            if ($isStandard && $isCommander) $stats['both_formats']++;
            
            $setCode = $cardData['set'] ?? 'unknown';
            $stats['sets'][$setCode] = ($stats['sets'][$setCode] ?? 0) + 1;
            
            $rarity = $cardData['rarity'] ?? 'common';
            $stats['rarities'][$rarity] = ($stats['rarities'][$rarity] ?? 0) + 1;
        }

        $io->createTable()
            ->setHeaders(['Metric', 'Count'])
            ->setRows([
                ['Total cards', $stats['total']],
                ['Standard legal', $stats['standard']],
                ['Commander legal', $stats['commander']],
                ['Both formats', $stats['both_formats']],
                ['Unique sets', count($stats['sets'])],
                ['Common cards', $stats['rarities']['common'] ?? 0],
                ['Uncommon cards', $stats['rarities']['uncommon'] ?? 0],
                ['Rare cards', $stats['rarities']['rare'] ?? 0],
                ['Mythic cards', $stats['rarities']['mythic'] ?? 0]
            ])
            ->render();
    }

    /**
     * Résumé final de l'import
     */
    private function displayFinalSummary(array $results, SymfonyStyle $io): void
    {
        $io->section('📊 Import Summary');
        
        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $io->createTable()
            ->setHeaders(['Status', 'Count'])
            ->setRows([
                ['✅ Imported', $results['imported']],
                ['🔄 Updated', $results['updated']],
                ['⏭️ Skipped', $results['skipped']],
                ['❌ Errors', $results['errors']],
                ['🖼️ Images downloaded', $this->downloadedImages],
                ['💾 Total image size', number_format($this->totalImageSize / 1048576, 1) . ' MB'],
                ['💾 Final memory', number_format($finalMemory / 1048576, 1) . ' MB'],
                ['📈 Peak memory', number_format($peakMemory / 1048576, 1) . ' MB']
            ])
            ->render();

        if ($results['errors'] > 0) {
            $io->warning("Import terminé avec {$results['errors']} erreurs. Vérifiez les logs.");
        } else {
            $io->success("🎉 Import réussi ! {$results['imported']} nouvelles cartes, {$results['updated']} mises à jour.");
        }
    }
}