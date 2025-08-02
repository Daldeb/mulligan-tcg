<?php

namespace App\Command;

use App\Repository\Hearthstone\HearthstoneSetRepository;
use App\Repository\Hearthstone\HearthstoneCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:update-hearthstone-legality',
    description: 'Update Standard/Wild legality for all Hearthstone sets and cards based on configuration'
)]
class UpdateHearthstoneSetsLegalityCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HearthstoneSetRepository $hearthstoneSetRepository,
        private HearthstoneCardRepository $hearthstoneCardRepository,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔄 Mise à jour complète de la légalité Hearthstone');

        try {
            // 1. Charger la configuration
            $config = $this->loadConfiguration($io);
            
            // ÉTAPE 1: Mettre à jour les SETS
            $io->section('📦 Étape 1: Mise à jour des sets');
            $setsUpdated = $this->updateSetsLegality($config, $io);
            
            // ÉTAPE 2: Mettre à jour les CARTES
            $io->section('🃏 Étape 2: Mise à jour des cartes');
            $cardsUpdated = $this->updateCardsLegality($io);
            
            // RÉSUMÉ FINAL
            $io->success("🎉 Terminé ! {$setsUpdated} sets et {$cardsUpdated} cartes mis à jour.");
            
        } catch (\Exception $e) {
            $io->error("💥 Erreur : " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function loadConfiguration(SymfonyStyle $io): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $configPath = $projectDir . '/config/hearthstone_sets.yaml';
        
        if (!file_exists($configPath)) {
            throw new \Exception("Configuration manquante : {$configPath}");
        }

        $io->writeln("📋 Chargement de la configuration : {$configPath}");
        return Yaml::parseFile($configPath);
    }

    private function updateSetsLegality(array $config, SymfonyStyle $io): int
    {
        // Récupérer tous les sets existants
        $allSets = $this->hearthstoneSetRepository->findAll();
        $io->writeln("📊 Sets trouvés en BDD : " . count($allSets));

        $updated = 0;
        $standardCount = 0;
        $wildCount = 0;
        $excludedCount = 0;

        foreach ($allSets as $set) {
            $externalId = $set->getExternalId();
            $oldLegality = $set->isStandardLegal();
            
            // Déterminer la nouvelle légalité
            $newLegality = $this->determineSetLegality($externalId, $config);
            
            // Mettre à jour si changement
            if ($oldLegality !== $newLegality) {
                $set->setIsStandardLegal($newLegality);
                $set->setUpdatedAt(new \DateTimeImmutable());
                $updated++;
                
                $status = $newLegality ? '✅ Standard' : '❌ Wild';
                $io->writeln("🔄 {$externalId}: {$status}");
            }
            
            // Statistiques
            if ($this->isExcludedSet($externalId, $config)) {
                $excludedCount++;
            } elseif ($newLegality) {
                $standardCount++;
            } else {
                $wildCount++;
            }
        }

        // Sauvegarder les changements des sets
        $this->entityManager->flush();

        // Afficher le résumé des sets
        $io->createTable()
            ->setHeaders(['Statut Sets', 'Nombre'])
            ->setRows([
                ['✅ Standard', $standardCount],
                ['❌ Wild Only', $wildCount],
                ['🚫 Exclus', $excludedCount],
                ['🔄 Mis à jour', $updated],
                ['📊 Total', count($allSets)]
            ])
            ->render();

        if ($updated > 0) {
            $io->writeln("✅ {$updated} sets ont été mis à jour.");
        } else {
            $io->writeln("ℹ️ Aucun set à mettre à jour.");
        }

        return $updated;
    }

    private function updateCardsLegality(SymfonyStyle $io): int
    {
        // Récupérer toutes les cartes avec leurs sets
        $cards = $this->hearthstoneCardRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.hearthstoneSet', 's')
            ->addSelect('s')
            ->getQuery()
            ->getResult();

        $io->writeln("🃏 Cartes trouvées en BDD : " . count($cards));

        $updated = 0;
        $standardCards = 0;
        $wildOnlyCards = 0;
        $excludedCards = 0;

        foreach ($cards as $card) {
            $set = $card->getHearthstoneSet();
            if (!$set) {
                continue; // Skip les cartes sans set
            }

            $oldStandard = $card->isStandardLegal();
            $newStandard = $set->isStandardLegal();

            // Mettre à jour la légalité de la carte selon son set
            if ($oldStandard !== $newStandard) {
                $card->setIsStandardLegal($newStandard);
                $card->setIsWildLegal(true); // Toutes les cartes sont jouables en Wild
                $card->setUpdatedAt(new \DateTimeImmutable());
                $updated++;
            }

            // Statistiques finales
            if (!$set->isStandardLegal() && $this->isSetExcluded($set->getExternalId())) {
                $excludedCards++;
            } elseif ($newStandard) {
                $standardCards++;
            } else {
                $wildOnlyCards++;
            }
        }

        // Sauvegarder les changements des cartes
        $this->entityManager->flush();

        // Afficher le résumé des cartes
        $io->createTable()
            ->setHeaders(['Statut Cartes', 'Nombre'])
            ->setRows([
                ['✅ Standard + Wild', $standardCards],
                ['❌ Wild Only', $wildOnlyCards],
                ['🚫 Exclues', $excludedCards],
                ['🔄 Mises à jour', $updated],
                ['📊 Total', count($cards)]
            ])
            ->render();

        if ($updated > 0) {
            $io->writeln("✅ {$updated} cartes ont été mises à jour.");
        } else {
            $io->writeln("ℹ️ Aucune carte à mettre à jour.");
        }

        return $updated;
    }

    private function determineSetLegality(string $externalId, array $config): bool
    {
        // 1. Sets exclus = pas légaux (techniques/cosmétiques)
        if ($this->isExcludedSet($externalId, $config)) {
            return false;
        }

        // 2. Sets Standard explicites
        if (in_array($externalId, $config['hearthstone']['standard_sets'] ?? [])) {
            return true;
        }

        // 3. Tous les autres = Wild uniquement
        return false;
    }

    private function isExcludedSet(string $externalId, array $config): bool
    {
        return in_array($externalId, $config['hearthstone']['excluded_sets'] ?? []);
    }

    private function isSetExcluded(string $externalId): bool
    {
        // Pour les statistiques, on peut recharger la config ou la passer en paramètre
        // Version simplifiée qui considère les sets techniques comme exclus
        $technicalSets = ['HERO_SKINS', 'PLACEHOLDER_202204', 'VANILLA', 'LEGACY', 'EVENT'];
        return in_array($externalId, $technicalSets);
    }
}