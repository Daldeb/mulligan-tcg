<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\Pokemon\PokemonSet;
use App\Entity\Pokemon\PokemonCard;
use App\Repository\GameRepository;
use App\Repository\Pokemon\PokemonSetRepository;
use App\Repository\Pokemon\PokemonCardRepository;
use App\Service\ImageDownloadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-pokemon-cards',
    description: 'Import Pokemon cards from a specific set via TCGdex API with local image storage'
)]
class ImportPokemonCardsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private GameRepository $gameRepository,
        private PokemonSetRepository $pokemonSetRepository,
        private PokemonCardRepository $pokemonCardRepository,
        private ImageDownloadService $imageDownloadService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('setId', InputArgument::REQUIRED, 'Set ID to import (e.g., sm9, sv01, det1)')
            ->setHelp(<<<'EOF'
This command imports Pokemon cards from TCGdex API for a specific set.

Examples:
  <info>php bin/console app:import-pokemon-cards det1</info>   # Detective Pikachu (18 cards)
  <info>php bin/console app:import-pokemon-cards sm9</info>    # Team Up (196 cards)
  <info>php bin/console app:import-pokemon-cards sv01</info>   # Scarlet & Violet (258 cards)

The command will:
- Download set information (logo, symbol)
- Import only "normal" variant cards (no holo, reverse, etc.)
- Download and store all images locally in /public/uploads/pokemon/
- Respect API rate limits with 2-second delays between requests
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $setId = $input->getArgument('setId');

        $io->title("🃏 Import Pokemon Cards - Set: {$setId}");

        try {
            // 1. Récupérer le jeu Pokemon
            $pokemonGame = $this->gameRepository->findOneBy(['slug' => 'pokemon']);
            if (!$pokemonGame) {
                $io->error('Pokemon game not found! Please run app:init-games first.');
                return Command::FAILURE;
            }

            // 2. Récupérer les infos du set
            $io->section('📋 Step 1: Fetching set information...');
            $setData = $this->fetchSetData($setId);
            
            if (!$setData) {
                $io->error("Set '{$setId}' not found in TCGdex API");
                return Command::FAILURE;
            }

            // 3. Créer/Mettre à jour le PokemonSet avec téléchargement des assets
            $pokemonSet = $this->createOrUpdateSet($setData, $pokemonGame, $io);
            $io->success("✅ Set '{$pokemonSet->getName()}' ready ({$pokemonSet->getTotalCards()} cards total)");

            // 4. Récupérer la liste des cartes
            $io->section('🔍 Step 2: Fetching cards list...');
            $cardsList = $this->fetchCardsList($setId);
            
            if (empty($cardsList)) {
                $io->warning("No cards found for set '{$setId}'");
                return Command::SUCCESS;
            }

            $io->writeln("Found " . count($cardsList) . " cards to process");

            // 5. Importer chaque carte avec détails et images
            $io->section('⬇️ Step 3: Importing cards with details and images...');
            $imported = 0;
            $skipped = 0;
            $errors = 0;

            // Créer un mapping ID → URL d'image depuis la liste
            $imageUrls = [];
            foreach ($cardsList as $cardBrief) {
                if (isset($cardBrief['image'])) {
                    $imageUrls[$cardBrief['id']] = $cardBrief['image'];
                }
            }

            $progressBar = $io->createProgressBar(count($cardsList));
            $progressBar->setFormat('very_verbose');
            $progressBar->start();

            foreach ($cardsList as $cardBrief) {
                try {
                    // Récupérer les détails de la carte
                    $cardDetails = $this->fetchCardDetails($cardBrief['id']);
                    
                    if (!$cardDetails) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Ajouter l'URL d'image depuis la liste (fix API incohérente)
                    $cardDetails['image'] = $imageUrls[$cardBrief['id']] ?? null;

                    // Vérifier qu'on a une image
                    if (!$cardDetails['image']) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Filtrer les variants (garder seulement "normal")
                    if (!$this->isNormalVariant($cardDetails)) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Importer la carte avec téléchargement d'image
                    $this->importCard($cardDetails, $pokemonSet);
                    $imported++;

                } catch (\Exception $e) {
                    $errors++;
                    $io->writeln("\n❌ Error importing card {$cardBrief['id']}: " . $e->getMessage());
                }

                $progressBar->advance();

                // Délai courtois de 2 secondes
                // sleep(2);
            }

            $progressBar->finish();
            $io->newLine(2);

            // Résumé final
            $io->createTable()
                ->setHeaders(['Status', 'Count'])
                ->setRows([
                    ['✅ Imported', $imported],
                    ['⏭️ Skipped (variants)', $skipped],
                    ['❌ Errors', $errors],
                    ['📊 Total processed', count($cardsList)]
                ])
                ->render();

            if ($errors > 0) {
                $io->warning("Import completed with {$errors} errors. Check logs above.");
                return $errors > ($imported / 2) ? Command::FAILURE : Command::SUCCESS;
            }

            $io->success("🎉 Import completed successfully! {$imported} cards imported.");

        } catch (\Exception $e) {
            $io->error("💥 Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function fetchSetData(string $setId): ?array
    {
        $response = $this->httpClient->request('GET', "https://api.tcgdex.net/v2/fr/sets/{$setId}");
        
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->toArray();
    }

    private function fetchCardsList(string $setId): array
    {
        $response = $this->httpClient->request('GET', "https://api.tcgdex.net/v2/fr/cards", [
            'query' => [
                'set.id' => $setId,
                'pagination:itemsPerPage' => 1000
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        return $response->toArray();
    }

    private function fetchCardDetails(string $cardId): ?array
    {
        $response = $this->httpClient->request('GET', "https://api.tcgdex.net/v2/fr/cards/{$cardId}");
        
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->toArray();
    }

    private function createOrUpdateSet(array $setData, Game $pokemonGame, SymfonyStyle $io): PokemonSet
    {
        $pokemonSet = $this->pokemonSetRepository->findByExternalId($setData['id']);
        
        if (!$pokemonSet) {
            $pokemonSet = new PokemonSet();
            $pokemonSet->setExternalId($setData['id']);
            $pokemonSet->setGame($pokemonGame);
            $io->writeln("📦 Creating new set: {$setData['name']}");
        } else {
            $io->writeln("🔄 Updating existing set: {$setData['name']}");
        }

        // Données de base
        $pokemonSet->setName($setData['name']);
        $pokemonSet->setTotalCards($setData['cardCount']['total']);
        $pokemonSet->setOfficialCards($setData['cardCount']['official']);
        $pokemonSet->setUpdatedAt(new \DateTimeImmutable());

        // Téléchargement du logo (seulement si présent)
        if (isset($setData['logo']) && !empty($setData['logo'])) {
            $io->writeln("🖼️ Downloading set logo...");
            $logoPath = $this->imageDownloadService->downloadPokemonSetAsset(
                $setData['logo'], 
                $setData['id'], 
                'logos'
            );
            $pokemonSet->setLogoUrl($logoPath);
        } else {
            $io->writeln("ℹ️ No logo available for this set");
        }

        // Téléchargement du symbol (seulement si présent)
        if (isset($setData['symbol']) && !empty($setData['symbol'])) {
            $io->writeln("🔣 Downloading set symbol...");
            $symbolPath = $this->imageDownloadService->downloadPokemonSetAsset(
                $setData['symbol'], 
                $setData['id'], 
                'symbols'
            );
            $pokemonSet->setSymbolUrl($symbolPath);
        } else {
            $io->writeln("ℹ️ No symbol available for this set");
        }

        $this->entityManager->persist($pokemonSet);
        $this->entityManager->flush();

        return $pokemonSet;
    }

    private function isNormalVariant(array $cardDetails): bool
    {
        // Garder seulement les cartes avec variant "normal"
        return isset($cardDetails['variants']['normal']) && $cardDetails['variants']['normal'] === true;
    }

    private function importCard(array $cardDetails, PokemonSet $pokemonSet): void
    {
        $existingCard = $this->pokemonCardRepository->findByExternalId($cardDetails['id']);
        
        if (!$existingCard) {
            $card = new PokemonCard();
            $card->setExternalId($cardDetails['id']);
            $card->setPokemonSet($pokemonSet);
        } else {
            $card = $existingCard;
        }

        // Télécharger l'image de la carte
        $localImagePath = $this->imageDownloadService->downloadPokemonCard(
            $cardDetails['image'], 
            $cardDetails['id']
        );

        // Mapper les données de l'API vers l'entité
        $card->setLocalId($cardDetails['localId']);
        $card->setName($cardDetails['name']);
        $card->setImageUrl($localImagePath);
        $card->setIllustrator($cardDetails['illustrator'] ?? null);
        $card->setRarity($cardDetails['rarity'] ?? null);
        $card->setTypes($cardDetails['types'] ?? null);
        $card->setHp($cardDetails['hp'] ?? null);
        $card->setIsStandardLegal($cardDetails['legal']['standard'] ?? false);
        $card->setIsExpandedLegal($cardDetails['legal']['expanded'] ?? true);
        $card->setUpdatedAt(new \DateTimeImmutable());
        $card->setLastSyncedAt(new \DateTimeImmutable());

        $this->entityManager->persist($card);
        $this->entityManager->flush();
    }
}