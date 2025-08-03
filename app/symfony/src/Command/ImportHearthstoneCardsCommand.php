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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-hearthstone-cards',
    description: 'Import Hearthstone cards from HearthstoneJSON API with local image storage and memory optimization'
)]

//la plus classique : php bin/console app:import-hearthstone-cards -l frFR --collectible-only
class ImportHearthstoneCardsCommand extends Command
{
    private int $batchSize = 25; // Réduit pour éviter surcharge mémoire
    private int $downloadedImages = 0;
    private float $totalImageSize = 0;

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
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Language locale for card names', 'frFR')
            ->addOption('collectible-only', 'c', InputOption::VALUE_NONE, 'Import only collectible cards')
            ->setHelp(<<<'EOF'
This command imports Hearthstone cards from HearthstoneJSON API.

Examples:
  <info>php bin/console app:import-hearthstone-cards</info>                 # All cards (EN)
  <info>php bin/console app:import-hearthstone-cards -l frFR</info>         # All cards (FR)
  <info>php bin/console app:import-hearthstone-cards --collectible-only</info> # Collectible only

The command will:
- Import card and set data from HearthstoneJSON
- Download and store all images locally in /public/uploads/hearthstone/
- Optimize memory usage with batch processing
- Handle missing images gracefully
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $collectibleOnly = $input->getOption('collectible-only');

        $cardType = $collectibleOnly ? 'collectible' : 'all';
        $io->title("🃏 Import Hearthstone Cards - " . ucfirst($cardType) . " Cards ({$locale})");

        try {
            // Optimisation mémoire globale
            ini_set('memory_limit', '512M');
            gc_enable();

            // 1. Récupérer le jeu Hearthstone
            $hearthstoneGame = $this->gameRepository->findOneBy(['slug' => 'hearthstone']);
            if (!$hearthstoneGame) {
                $io->error('Hearthstone game not found! Please run app:init-games first.');
                return Command::FAILURE;
            }

            // 2. Récupérer les données des cartes
            $io->section('📋 Step 1: Fetching cards data from HearthstoneJSON...');
            $cardsData = $this->fetchCardsData($locale, $collectibleOnly, $io);
            
            if (empty($cardsData)) {
                $io->error('No cards data found');
                return Command::FAILURE;
            }

            $io->writeln("Found " . count($cardsData) . " cards to process");

            // 3. Récupérer les métadonnées (types, raretés, etc.) - optionnel
            $io->section('🔍 Step 2: Fetching metadata...');
            $metadata = $this->fetchMetadata($io);

            // 4. Créer/Mettre à jour les sets
            $io->section('📦 Step 3: Creating/updating sets...');
            $this->createOrUpdateSets($cardsData, $hearthstoneGame, $io);

            // 5. Importer les cartes par batch
            $io->section('⬇️ Step 4: Importing cards with images...');
            $results = $this->importCardsInBatches($cardsData, $metadata, $io);

            // 6. Résumé final
            $this->displayFinalSummary($cardsData, $io);
            
            // 7. Déterminer le statut de retour
            if ($results['errors'] > ($results['imported'] / 2)) {
                return Command::FAILURE;
            }

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
            
            if ($response->getStatusCode() !== 200) {
                $io->warning("Metadata not available (HTTP {$response->getStatusCode()}), continuing without metadata...");
                return [];
            }
            
            $io->writeln("✅ Metadata fetched successfully");
            return $response->toArray();
            
        } catch (\Exception $e) {
            $io->warning("Metadata fetch failed: " . $e->getMessage() . ", continuing without metadata...");
            return [];
        }
    }

    private function createOrUpdateSets(array $cardsData, Game $hearthstoneGame, SymfonyStyle $io): void
    {
        $setsFound = [];
        
        // Collecter tous les sets uniques
        foreach ($cardsData as $cardData) {
            if (isset($cardData['set']) && !in_array($cardData['set'], $setsFound)) {
                $setsFound[] = $cardData['set'];
            }
        }

        // Créer ou mettre à jour chaque set
        foreach ($setsFound as $setExternalId) {
            $hearthstoneSet = $this->hearthstoneSetRepository->findByExternalId($setExternalId);
            
            if (!$hearthstoneSet) {
                $hearthstoneSet = new HearthstoneSet();
                $hearthstoneSet->setExternalId($setExternalId);
                $hearthstoneSet->setGame($hearthstoneGame);
                $hearthstoneSet->setName($setExternalId); // Nom basique, sera amélioré avec metadata
                $hearthstoneSet->setTotalCards(0);
                $hearthstoneSet->setIsStandardLegal(false);
                $io->writeln("📦 Creating new set: {$setExternalId}");
            } else {
                $io->writeln("🔄 Updating existing set: {$setExternalId}");
            }
            
            $this->entityManager->persist($hearthstoneSet);
        }
        
        $this->entityManager->flush();
        $this->entityManager->clear(); // Nettoyer immédiatement
        gc_collect_cycles();
    }

    private function importCardsInBatches(array $cardsData, array $metadata, SymfonyStyle $io): array
    {
        $progressBar = $io->createProgressBar(count($cardsData));
        $progressBar->setFormat(
            "%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% | 📥 Downloaded: %downloaded% images | Total: %total_size%"
        );
        
        // Variables personnalisées pour la progress bar
        $progressBar->setMessage('0', 'downloaded');
        $progressBar->setMessage('0.0 MB', 'total_size');
        $progressBar->start();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $batchCount = 0;

        $batches = array_chunk($cardsData, $this->batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $cardData) {
                try {
                    $result = $this->importSingleCard($cardData, $metadata);
                    
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
                    $cardId = $cardData['id'] ?? 'unknown';
                    error_log("Error importing card {$cardId}: " . $e->getMessage());
                }

                $progressBar->advance();
                
                // Mettre à jour les messages de la progress bar
                $progressBar->setMessage((string)$this->downloadedImages, 'downloaded');
                $progressBar->setMessage(number_format($this->totalImageSize / 1048576, 1) . ' MB', 'total_size');
            }

            // Flush et nettoyage mémoire après chaque batch
            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles();
            
            $batchCount++;
            
            // Nettoyage mémoire agressif tous les 10 batches
            if ($batchCount % 10 === 0) {
                $this->forceMemoryCleanup();
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        // Statistiques finales
        $avgImageSize = $this->downloadedImages > 0 ? ($this->totalImageSize / $this->downloadedImages / 1024) : 0;
        
        $io->createTable()
            ->setHeaders(['Status', 'Count'])
            ->setRows([
                ['✅ Imported', $imported],
                ['🔄 Updated', $updated],
                ['⏭️ Skipped', $skipped],
                ['❌ Errors', $errors],
                ['📊 Total processed', count($cardsData)],
                ['🖼️ Images downloaded', $this->downloadedImages],
                ['💾 Total size', number_format($this->totalImageSize / 1048576, 1) . ' MB'],
                ['📈 Average image size', number_format($avgImageSize, 1) . ' KB']
            ])
            ->render();

        if ($errors > 0) {
            $io->warning("Import completed with {$errors} errors. Check error logs.");
        }

        $io->success("🎉 Import completed successfully! {$imported} new cards, {$updated} updated cards.");
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'skipped' => $skipped
        ];
    }

    private function importSingleCard(array $cardData, array $metadata): string
    {
        // Vérifications de base
        if (empty($cardData['id']) || empty($cardData['name'])) {
            return 'skipped';
        }

        // Récupérer le set (rechargé depuis la DB pour éviter entités détachées)
        $hearthstoneSet = $this->hearthstoneSetRepository->findByExternalId($cardData['set']);
        if (!$hearthstoneSet) {
            throw new \Exception("Set not found: {$cardData['set']}");
        }

        // Vérifier si la carte existe déjà
        $existingCard = $this->hearthstoneCardRepository->findByExternalId($cardData['id']);
        
        if (!$existingCard) {
            $card = new HearthstoneCard();
            $card->setExternalId($cardData['id']);
            $card->setHearthstoneSet($hearthstoneSet);
            $status = 'imported';
        } else {
            $card = $existingCard;
            $status = 'updated';
        }

        // Télécharger l'image
        $imageInfo = $this->imageDownloadService->downloadHearthstoneCard($cardData['id']);
        
        if ($imageInfo['downloaded']) {
            $this->downloadedImages++;
            $this->totalImageSize += $imageInfo['size'];
        }

        // Mapper les données de l'API vers l'entité
        $card->setDbfId($cardData['dbfId']);
        $card->setName($cardData['name']);
        $card->setImageUrl($imageInfo['path']); // Peut être null
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
        $card->setIsStandardLegal(false); // Sera mis à jour par la commande de légalité
        $card->setIsWildLegal(true);
        $card->setIsCollectible($cardData['collectible'] ?? true);
        $card->setUpdatedAt(new \DateTimeImmutable());
        $card->setLastSyncedAt(new \DateTimeImmutable());

        $this->entityManager->persist($card);

        return $status;
    }

    private function forceMemoryCleanup(): void
    {
        // Détacher toutes les entités
        $this->entityManager->clear();
        
        // Forcer le garbage collection
        gc_collect_cycles();
        
        // Optionnel : Log utilisation mémoire actuelle
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        error_log("Memory usage: " . number_format($memoryUsage / 1048576, 1) . "MB / {$memoryLimit}");
    }

    private function displayFinalSummary(array $cardsData, SymfonyStyle $io): void
    {
        $io->section('📊 Final Summary');
        
        // Mémoire finale
        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $io->writeln("💾 Memory usage: " . number_format($finalMemory / 1048576, 1) . " MB");
        $io->writeln("📈 Peak memory: " . number_format($peakMemory / 1048576, 1) . " MB");
        
        if ($this->downloadedImages > 0) {
            $avgSize = $this->totalImageSize / $this->downloadedImages / 1024;
            $io->writeln("🖼️ Average image size: " . number_format($avgSize, 1) . " KB");
        }
    }
}