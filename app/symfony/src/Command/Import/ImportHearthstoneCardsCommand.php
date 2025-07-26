<?php

namespace App\Command\Import;

use App\Entity\Card\HearthstoneCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'import:hearthstone:cards',
    description: 'Import Hearthstone cards into the database'
)]
class ImportHearthstoneCardsCommand extends Command
{
    // 🎯 Extensions Standard 2025 (validées avec l'analyse JSON)
    private const STANDARD_SETS = [
        'CORE',                 // 288 cartes - Jeu de base permanent
        'THE_LOST_CITY',        // 145 cartes - La cité perdue d'Ungoro
        'EMERALD_DREAM',        // 183 cartes - Au cœur du rêve d'émeraude
        'WHIZBANGS_WORKSHOP',   // 183 cartes - L'atelier de Mystiflex
        'ISLAND_VACATION',      // 183 cartes - Paradis en péril
        'SPACE',                // 194 cartes - Extension spatiale
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Language locale to import (ex: enUS, frFR)', 'frFR')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format to import (standard, wild, all)', 'standard')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of cards to process before flush', '20');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locale = $input->getOption('locale');
        $format = $input->getOption('format');
        $batchSize = (int) $input->getOption('batch-size');

        $jsonUrl = "https://api.hearthstonejson.com/v1/latest/$locale/cards.collectible.json";
        $output->writeln("📥 Téléchargement du JSON [$locale] - Format: $format...");
        $cards = json_decode(file_get_contents($jsonUrl), true);

        if (!$cards) {
            $output->writeln("❌ Erreur : Impossible de télécharger ou parser le JSON");
            return Command::FAILURE;
        }

        $output->writeln("🔍 Analyse de " . count($cards) . " cartes disponibles...");

        // Filtrage selon le format
        if ($format === 'standard') {
            $cards = array_filter($cards, fn($card) => in_array($card['set'] ?? '', self::STANDARD_SETS));
            $output->writeln("🎯 Filtre Standard appliqué : " . count($cards) . " cartes sélectionnées");
            
            // Affichage du détail par extension
            $setCount = [];
            foreach ($cards as $card) {
                $set = $card['set'] ?? 'UNKNOWN';
                $setCount[$set] = ($setCount[$set] ?? 0) + 1;
            }
            foreach ($setCount as $set => $count) {
                $output->writeln("   📦 $set: $count cartes");
            }
        }

        // Création du dossier d'images
        $fs = new Filesystem();
        $imgBaseDir = $this->projectDir . "/public/images/hearthstone/$locale/";
        if (!$fs->exists($imgBaseDir)) {
            $fs->mkdir($imgBaseDir, 0755);
            $output->writeln("📁 Dossier créé : $imgBaseDir");
        }

        $count = 0;
        $batchCount = 0;
        $totalCards = count($cards);

        $output->writeln("🚀 Début de l'import ($totalCards cartes à traiter)...");

        foreach ($cards as $cardData) {
            if (empty($cardData['id']) || empty($cardData['dbfId'])) {
                $output->writeln("⚠️  Carte ignorée : ID ou dbfId manquant");
                continue;
            }

            $dbfId = $cardData['dbfId'];
            $cardId = $cardData['id'];

            // Recherche de la carte existante par dbfId ET locale
            $card = $this->em->getRepository(HearthstoneCard::class)->findOneBy([
                'dbfId' => $dbfId,
                'locale' => $locale,
            ]);

            $isNew = false;
            if (!$card) {
                $card = new HearthstoneCard();
                $card->setId(Uuid::v4()->toRfc4122());
                $card->setDbfId($dbfId);
                $card->setImportedAt(new \DateTimeImmutable());
                $card->setLocale($locale); // 🎯 Définition de la langue
                $isNew = true;
            }

            // Mise à jour des données de la carte
            $card->setName($cardData['name'] ?? 'Unnamed');
            $card->setCardSet($cardData['set'] ?? null);
            $card->setRarity($cardData['rarity'] ?? null);
            $card->setType($cardData['type'] ?? null);
            $card->setCardClass($cardData['cardClass'] ?? null);
            $card->setData($cardData);

            // Gestion de l'image
            $imgUrl = "https://art.hearthstonejson.com/v1/render/latest/$locale/256x/$cardId.png";
            $imgPath = $imgBaseDir . $cardId . '.png';
            $imgRelativePath = "/images/hearthstone/$locale/$cardId.png";

            // Téléchargement de l'image si nécessaire
            if ($isNew || !$fs->exists($imgPath)) {
                try {
                    $imageContent = @file_get_contents($imgUrl);
                    if ($imageContent !== false) {
                        file_put_contents($imgPath, $imageContent);
                        $output->writeln("📥 Image téléchargée: $cardId.png");
                    } else {
                        $output->writeln("⚠️  Échec téléchargement image: $cardId.png");
                    }
                } catch (\Exception $e) {
                    $output->writeln("❌ Erreur image $cardId: " . $e->getMessage());
                }
            }

            $card->setImagePath($imgRelativePath);

            $this->em->persist($card);
            $output->writeln("✅ [{$cardData['set']}] {$card->getName()} ($cardId) [$locale]");
            
            $count++;
            $batchCount++;

            // 🚀 Flush par batch de 20 pour suivre la progression
            if ($batchCount >= $batchSize) {
                $output->writeln("");
                $output->writeln("💾 === FLUSH BATCH $batchCount/$count cartes ===");
                
                try {
                    $this->em->flush();
                    $output->writeln("✅ Batch sauvegardé avec succès !");
                    
                    // Libération de la mémoire
                    $this->em->clear();
                    
                    // Affichage de progression
                    $percentage = round(($count / $totalCards) * 100, 1);
                    $output->writeln("📊 Progression: $count/$totalCards cartes ($percentage%)");
                    $output->writeln("");
                    
                } catch (\Exception $e) {
                    $output->writeln("❌ Erreur lors du flush: " . $e->getMessage());
                    return Command::FAILURE;
                }
                
                $batchCount = 0;
            }
        }

        // Flush final pour les cartes restantes
        if ($batchCount > 0) {
            $output->writeln("");
            $output->writeln("💾 === FLUSH FINAL ===");
            try {
                $this->em->flush();
                $output->writeln("✅ Dernier batch sauvegardé !");
            } catch (\Exception $e) {
                $output->writeln("❌ Erreur lors du flush final: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $output->writeln("");
        $output->writeln("🎉 ===== IMPORT TERMINÉ =====");
        $output->writeln("📊 Total : $count cartes importées");
        $output->writeln("🌍 Langue : $locale");
        $output->writeln("🎮 Format : $format");
        $output->writeln("📁 Images : $imgBaseDir");
        
        return Command::SUCCESS;
    }
}