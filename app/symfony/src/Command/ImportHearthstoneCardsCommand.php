<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\Hearthstone\HearthstoneSet;
use App\Entity\Hearthstone\HearthstoneCard;
use App\Repository\GameRepository;
use App\Repository\Hearthstone\HearthstoneSetRepository;
use App\Repository\Hearthstone\HearthstoneCardRepository;
use App\Service\ImageDownloadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-hearthstone-cards',
    description: 'Import Hearthstone cards from HearthstoneJSON API with local image storage'
)]
class ImportHearthstoneCardsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private GameRepository $gameRepository,
        private HearthstoneSetRepository $hearthstoneSetRepository,
        private HearthstoneCardRepository $hearthstoneCardRepository,
        private ImageDownloadService $imageDownloadService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale (frFR, enUS, etc.)', 'frFR')
            ->addOption('collectible-only', 'c', InputOption::VALUE_NONE, 'Import only collectible cards')
            ->setHelp(<<<'EOF'
This command imports ALL Hearthstone cards from HearthstoneJSON API.
Set legality (Standard/Wild) is managed separately via app:update-hearthstone-sets-legality.

Examples:
  <info>php bin/console app:import-hearthstone-cards</info>               # All cards (recommended)
  <info>php bin/console app:import-hearthstone-cards -l enUS</info>       # English locale
  <info>php bin/console app:import-hearthstone-cards --collectible-only</info> # Only collectible

The command will:
- Download card data from HearthstoneJSON API
- Create sets automatically based on card data
- Download and store all card images locally in /public/uploads/hearthstone/
- Support French and English locales
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $collectibleOnly = $input->getOption('collectible-only');

        $io->title("🃏 Import Hearthstone Cards - All Cards ({$locale})");

        try {
            // 1. Récupérer le jeu Hearthstone
            $hearthstoneGame = $this->gameRepository->findOneBy(['slug' => 'hearthstone']);
            if (!$hearthstoneGame) {
                $io->error('Hearthstone game not found! Please run app:init-games first.');
                return Command::FAILURE;
            }

            // 2. Récupérer les données des cartes depuis HearthstoneJSON
            $io->section('📋 Step 1: Fetching cards data from HearthstoneJSON...');
            $cardsData = $this->fetchCardsData($locale, $collectibleOnly, $io);
            
            if (empty($cardsData)) {
                $io->error("No cards found for locale '{$locale}'");
                return Command::FAILURE;
            }

            $io->writeln("Found " . count($cardsData) . " cards to process");

            // 3. Récupérer les métadonnées (sets, classes, etc.)
            $io->section('🔍 Step 2: Fetching metadata...');
            $metadata = $this->fetchMetadata($io);

            // 4. Créer les sets nécessaires
            $io->section('📦 Step 3: Creating/updating sets...');
            $sets = $this->createOrUpdateSets($cardsData, $hearthstoneGame, $metadata, $io);

            // 5. Importer les cartes
            $io->section('⬇️ Step 4: Importing cards with images...');
            $result = $this->importCards($cardsData, $sets, $locale, $io);

            // Résumé final détaillé
            $io->createTable()
                ->setHeaders(['Status', 'Count'])
                ->setRows([
                    ['✅ Imported', $result['imported']],
                    ['🔄 Updated', $result['updated']],
                    ['⏭️ Skipped', $result['skipped']],
                    ['❌ Errors', $result['errors']],
                    ['📊 Total processed', count($cardsData)],
                    ['🖼️ Images downloaded', $result['images_downloaded']],
                    ['💾 Total size', $result['total_size_mb'] . ' MB']
                ])
                ->render();

            if ($result['errors'] > 0) {
                $io->warning("Import completed with {$result['errors']} errors.");
                return $result['errors'] > ($result['imported'] / 2) ? Command::FAILURE : Command::SUCCESS;
            }

            $io->success("🎉 Import completed successfully! {$result['imported']} cards imported, {$result['updated']} updated.");

        } catch (\Exception $e) {
            $io->error("💥 Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function fetchCardsData(string $locale, bool $collectibleOnly, SymfonyStyle $io): array
    {
        $endpoint = $collectibleOnly ? 'cards.collectible.json' : 'cards.json';
        $url = "https://api.hearthstonejson.com/v1/latest/{$locale}/{$endpoint}";
        
        $io->writeln("📡 Fetching from: {$url}");

        $response = $this->httpClient->request('GET', $url);
        
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to fetch cards data: HTTP {$response->getStatusCode()}");
        }

        return $response->toArray();
    }

    private function fetchMetadata(SymfonyStyle $io): array
    {
        $url = "https://api.hearthstonejson.com/v1/latest/enUS/enums.json";
        
        $io->writeln("📡 Fetching metadata from: {$url}");

        try {
            $response = $this->httpClient->request('GET', $url);
            
            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
        } catch (\Exception $e) {
            $io->writeln("⚠️ Could not fetch metadata: " . $e->getMessage());
        }

        return [];
    }

    private function createOrUpdateSets(array $cardsData, Game $hearthstoneGame, array $metadata, SymfonyStyle $io): array
    {
        $sets = [];
        $setsToCreate = [];

        // Grouper les cartes par set
        foreach ($cardsData as $card) {
            $setId = $card['set'] ?? 'UNKNOWN';
            if (!isset($setsToCreate[$setId])) {
                $setsToCreate[$setId] = [];
            }
            $setsToCreate[$setId][] = $card;
        }

        foreach ($setsToCreate as $setId => $setCards) {
            $hearthstoneSet = $this->hearthstoneSetRepository->findByExternalId($setId);
            
            if (!$hearthstoneSet) {
                $hearthstoneSet = new HearthstoneSet();
                $hearthstoneSet->setExternalId($setId);
                $hearthstoneSet->setGame($hearthstoneGame);
                $io->writeln("📦 Creating new set: {$setId}");
            } else {
                $io->writeln("🔄 Updating existing set: {$setId}");
            }

            // Nom du set (à améliorer avec metadata si disponible)
            $setName = $this->getSetName($setId, $metadata);
            $hearthstoneSet->setName($setName);
            $hearthstoneSet->setTotalCards(count($setCards));
            $hearthstoneSet->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($hearthstoneSet);
            $sets[$setId] = $hearthstoneSet;
        }

        $this->entityManager->flush();
        return $sets;
    }

    private function getSetName(string $setId, array $metadata): string
    {
        // Mapping basique des sets connus
        $setNames = [
            'CORE' => 'Core Set',
            'EXPERT1' => 'Classic',
            'NAXX' => 'Naxxramas',
            'GVG' => 'Goblins vs Gnomes',
            'BRM' => 'Blackrock Mountain',
            'TGT' => 'The Grand Tournament',
            'LOE' => 'League of Explorers',
            'WHIZBANGS_WORKSHOP' => 'Whizbang\'s Workshop',
            'ISLAND_VACATION' => 'Perils in Paradise',
            'SPACE' => 'The Great Dark Beyond',
            'EMERALD_DREAM' => 'Into the Emerald Dream',
            'THE_LOST_CITY' => 'The Lost City of Un\'Goro',
        ];

        return $setNames[$setId] ?? $setId;
    }

    private function importCards(array $cardsData, array $sets, string $locale, SymfonyStyle $io): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $totalDownloaded = 0;
        $totalSize = 0;
        $batchSize = 50; // Traiter par batch pour éviter les problèmes mémoire

        $progressBar = $io->createProgressBar(count($cardsData));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% | 📥 %message%');
        $progressBar->setMessage('Starting import...');
        $progressBar->start();

        $batch = 0;
        foreach ($cardsData as $index => $cardData) {
            try {
                $result = $this->importSingleCard($cardData, $sets, $locale);
                
                switch ($result['status']) {
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

                // Suivi des téléchargements
                if (isset($result['image'])) {
                    if ($result['image']['downloaded']) {
                        $totalDownloaded++;
                    }
                    $totalSize += $result['image']['size'];
                    
                    $sizeMB = round($totalSize / 1024 / 1024, 1);
                    $progressBar->setMessage("Downloaded: {$totalDownloaded} images | Total: {$sizeMB} MB");
                }

                // Flush par batch pour libérer la mémoire
                if (++$batch >= $batchSize) {
                    $this->entityManager->flush(); // Sauvegarder le batch
                    $this->entityManager->clear(); // Libère les entités de la mémoire
                    
                    // Recharger les sets pour éviter les entités détachées
                    foreach ($sets as $setId => $set) {
                        $sets[$setId] = $this->hearthstoneSetRepository->findByExternalId($setId);
                    }
                    
                    gc_collect_cycles(); // Force garbage collection
                    $batch = 0;
                }

            } catch (\Exception $e) {
                $errors++;
                $cardId = $cardData['id'] ?? 'unknown';
                $progressBar->setMessage("❌ Error: {$cardId}");
                // Log l'erreur mais continue (pas d'affichage pour éviter le spam)
                error_log("Error importing card {$cardId}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Flush final pour sauvegarder les dernières cartes
        $this->entityManager->flush();

        // Statistiques finales détaillées
        $finalSizeMB = round($totalSize / 1024 / 1024, 1);
        $io->writeln("📊 Images téléchargées : {$totalDownloaded} nouvelles images");
        $io->writeln("💾 Taille totale : {$finalSizeMB} MB");
        
        if ($totalDownloaded > 0) {
            $avgSizeKB = round(($totalSize / $totalDownloaded) / 1024, 1);
            $io->writeln("📏 Taille moyenne par image : {$avgSizeKB} KB");
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'images_downloaded' => $totalDownloaded,
            'total_size_mb' => $finalSizeMB
        ];
    }

    private function importSingleCard(array $cardData, array $sets, string $locale): array
    {
        // Vérifications de base
        if (!isset($cardData['id']) || !isset($cardData['dbfId'])) {
            return ['status' => 'skipped'];
        }

        $setId = $cardData['set'] ?? 'UNKNOWN';
        if (!isset($sets[$setId])) {
            return ['status' => 'skipped'];
        }

        $existingCard = $this->hearthstoneCardRepository->findByExternalId($cardData['id']);
        $isUpdate = $existingCard !== null;
        
        $card = $existingCard ?: new HearthstoneCard();
        
        if (!$isUpdate) {
            $card->setExternalId($cardData['id']);
            $card->setHearthstoneSet($sets[$setId]);
        }

        // Télécharger l'image de la carte avec suivi
        $imageResult = $this->imageDownloadService->downloadHearthstoneCard(
            $cardData['id'], 
            $locale
        );

        // Mapper les données HearthstoneJSON vers l'entité
        $card->setDbfId($cardData['dbfId']);
        $card->setName($cardData['name'] ?? 'Unknown');
        $card->setImageUrl($imageResult['path']); // Peut être null si image manquante
        $card->setArtist($cardData['artist'] ?? null);
        $card->setCost($cardData['cost'] ?? null);
        $card->setAttack($cardData['attack'] ?? null);
        $card->setHealth($cardData['health'] ?? null);
        $card->setCardClass($cardData['cardClass'] ?? 'NEUTRAL');
        $card->setCardType($cardData['type'] ?? 'UNKNOWN');
        $card->setRarity($cardData['rarity'] ?? null);
        $card->setText($cardData['text'] ?? null);
        $card->setFlavor($cardData['flavor'] ?? null);
        $card->setMechanics($cardData['mechanics'] ?? null);
        $card->setFaction($cardData['faction'] ?? null);
        $card->setIsCollectible($cardData['collectible'] ?? true);
        
        // Légalité (à affiner selon les rotations)
        $card->setIsStandardLegal($this->isStandardLegal($cardData));
        $card->setIsWildLegal(true); // Toutes les cartes sont légales en Wild
        
        $card->setUpdatedAt(new \DateTimeImmutable());
        $card->setLastSyncedAt(new \DateTimeImmutable());

        $this->entityManager->persist($card);
        
        // NE PAS FLUSH à chaque carte - sera fait par batch dans importCards()

        return [
            'status' => $isUpdate ? 'updated' : 'imported',
            'image' => $imageResult
        ];
    }

    private function isStandardLegal(array $cardData): bool
    {
        // Logique simplifiée - à affiner selon les rotations Hearthstone
        $standardSets = ['CORE', 'EXPERT1']; // À adapter
        return in_array($cardData['set'] ?? '', $standardSets);
    }
}