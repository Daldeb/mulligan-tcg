<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:scrape-hearthstone-metagame',
    description: 'Scrape standard & wild metagame decks from HS Guru and save images + metadata'
)]
class ScrapeHearthstoneMetagameCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $formats = [
            'standard' => [
                'url' => 'https://www.hsguru.com/decks?format=2&period=past_day&rank=all',
                'outputDir' => 'public/uploads/hearthstone/metagame'
            ],
            'wild' => [
                'url' => 'https://www.hsguru.com/decks?format=1&period=past_day&rank=all',
                'outputDir' => 'public/uploads/hearthstone/wild_metagame'
            ]
        ];

        $scriptPath = __DIR__ . '/Scripts/scraper-hsguru.js';

        if (!file_exists($scriptPath)) {
            $io->error("❌ Le script JS est introuvable : {$scriptPath}");
            return Command::FAILURE;
        }

        $successCount = 0;
        $totalFormats = count($formats);

        foreach ($formats as $label => $config) {
            $url = $config['url'];
            $outputDir = rtrim($config['outputDir'], '/');
            $metadataOutputPath = $outputDir . '/metagame_decks.json';

            $io->title("📊 Scraping HS Guru ({$label}) → {$url}");
            
            // ========================================
            // NETTOYAGE RADICAL AVANT CHAQUE FORMAT
            // ========================================
            if ($label !== 'standard') {
                $io->writeln("🧹 Nettoyage radical avant {$label}...");
                
                // 1. Tuer TOUS les processus Chrome/Chromium
                $killCommands = [
                    ['pkill', '-9', '-f', 'chromium'],
                    ['pkill', '-9', '-f', 'chrome'],
                    ['pkill', '-9', '-f', 'node.*scraper']
                ];
                
                foreach ($killCommands as $cmd) {
                    $killProcess = new Process($cmd);
                    $killProcess->run();
                }
                
                // 2. Forcer le garbage collection PHP
                gc_collect_cycles();
                
                // 3. Pause LONGUE pour que tout se libère
                $io->writeln("⏰ Pause de 20 secondes pour libération complète...");
                sleep(20);
            } else {
                // Nettoyage léger pour le premier format
                $io->writeln("🧹 Nettoyage préventif...");
                $killProcess = new Process(['pkill', '-f', 'chromium']);
                $killProcess->run();
                sleep(3);
            }

            // ========================================
            // LANCEMENT AVEC CONFIGURATION ISOLÉE
            // ========================================
            $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath]);
            $process->setTimeout(500); // Augmenté encore plus
            
            // Variables d'environnement ultra-restrictives
            $process->setEnv([
                'NODE_OPTIONS' => '--max-old-space-size=200 --expose-gc',
                'PUPPETEER_ARGS' => '--memory-pressure-off --max_old_space_size=200',
                'CHROME_DEVEL_SANDBOX' => '0'
            ]);

            $io->section("🕵️ Lancement du scraper Puppeteer ({$label})...");

            try {
                $process->mustRun(function ($type, $buffer) use ($io) {
                    if (Process::ERR === $type) {
                        $io->error($buffer);
                    } else {
                        $io->write($buffer);
                    }
                });

                // Vérifier que le fichier JSON a été créé
                if (!file_exists($metadataOutputPath)) {
                    $io->error("❌ Le fichier JSON n'a pas été généré : {$metadataOutputPath}");
                    
                    // En cas d'échec, continuer quand même
                    $io->warning("⚠️ Le scraping {$label} a échoué, mais on continue...");
                    continue;
                }

                $io->success("🎉 Scraping {$label} terminé avec succès ! Images dans {$outputDir}");
                $successCount++;

                // Petit nettoyage immédiat après succès
                $quickKill = new Process(['pkill', '-f', 'chromium']);
                $quickKill->run();

            } catch (\Exception $e) {
                $io->error("💥 Erreur pendant l'exécution du script Node pour {$label} : " . $e->getMessage());
                
                // Nettoyage forcé en cas d'erreur
                $forceKill = new Process(['pkill', '-9', '-f', 'chromium']);
                $forceKill->run();
                
                $io->warning("⚠️ Le scraping {$label} a échoué, mais on continue avec les autres formats...");
                continue;
            }
        }

        // ========================================
        // NETTOYAGE FINAL COMPLET
        // ========================================
        $io->writeln("🧹 Nettoyage final...");
        $finalKill = new Process(['pkill', '-9', '-f', 'chromium']);
        $finalKill->run();

        // ========================================
        // RÉSUMÉ FINAL
        // ========================================
        $io->newLine();
        if ($successCount === $totalFormats) {
            $io->success("🎉 Tous les scrapings Hearthstone terminés avec succès ! ({$successCount}/{$totalFormats})");
            return Command::SUCCESS;
        } elseif ($successCount > 0) {
            $io->warning("⚠️ Scraping partiellement réussi : {$successCount}/{$totalFormats} formats traités");
            return Command::SUCCESS; // Succès partiel acceptable
        } else {
            $io->error("❌ Tous les scrapings Hearthstone ont échoué");
            return Command::FAILURE;
        }
    }
}