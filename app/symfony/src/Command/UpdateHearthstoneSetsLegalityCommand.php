<?php

namespace App\Command;

use App\Repository\Hearthstone\HearthstoneSetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:update-hearthstone-sets-legality',
    description: 'Update Standard/Wild legality for all Hearthstone sets based on configuration'
)]
class UpdateHearthstoneSetsLegalityCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HearthstoneSetRepository $hearthstoneSetRepository,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔄 Mise à jour de la légalité des sets Hearthstone');

        try {
            // 1. Charger la configuration
            $config = $this->loadConfiguration($io);
            
            // 2. Récupérer tous les sets existants
            $allSets = $this->hearthstoneSetRepository->findAll();
            $io->writeln("📊 Sets trouvés en BDD : " . count($allSets));

            // 3. Mettre à jour chaque set
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

            // 4. Sauvegarder les changements
            $this->entityManager->flush();

            // 5. Résumé
            $io->createTable()
                ->setHeaders(['Statut', 'Nombre de sets'])
                ->setRows([
                    ['✅ Standard', $standardCount],
                    ['❌ Wild Only', $wildCount],
                    ['🚫 Exclus', $excludedCount],
                    ['🔄 Mis à jour', $updated],
                    ['📊 Total', count($allSets)]
                ])
                ->render();

            if ($updated > 0) {
                $io->success("🎉 Mise à jour terminée ! {$updated} sets ont été modifiés.");
            } else {
                $io->note("ℹ️ Aucune mise à jour nécessaire. Tous les sets sont déjà à jour.");
            }

        } catch (\Exception $e) {
            $io->error("💥 Erreur lors de la mise à jour : " . $e->getMessage());
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
}