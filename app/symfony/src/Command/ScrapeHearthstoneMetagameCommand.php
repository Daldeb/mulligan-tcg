<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:scrape-hearthstone-standard',
    description: 'Scrape Hearthstone Standard metagame decks from HS Guru'
)]
class ScrapeHearthstoneStandardCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->scrapeFormat($input, $output, 'standard');
    }

    private function scrapeFormat(InputInterface $input, OutputInterface $output, string $format): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = [
            'url' => 'https://www.hsguru.com/decks?format=2&period=past_day&rank=all',
            'outputDir' => 'public/uploads/hearthstone/metagame'
        ];

        $scriptPath = __DIR__ . '/Scripts/scraper-hsguru.js';

        if (!file_exists($scriptPath)) {
            $io->error("❌ Le script JS est introuvable : {$scriptPath}");
            return Command::FAILURE;
        }

        $url = $config['url'];
        $outputDir = rtrim($config['outputDir'], '/');
        $metadataOutputPath = $outputDir . '/metagame_decks.json';

        $io->title("📊 Scraping HS Guru Standard → {$url}");

        // Nettoyage préventif
        $io->writeln("🧹 Nettoyage des processus Chrome...");
        $killProcess = new Process(['pkill', '-f', 'chromium']);
        $killProcess->run();
        sleep(2);

        $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath]);
        $process->setTimeout(300);
        
        $process->setEnv([
            'NODE_OPTIONS' => '--max-old-space-size=256'
        ]);

        $io->section('🕵️ Lancement du scraper Puppeteer...');

        try {
            $process->mustRun(function ($type, $buffer) use ($io) {
                if (Process::ERR === $type) {
                    $io->error($buffer);
                } else {
                    $io->write($buffer);
                }
            });

            if (!file_exists($metadataOutputPath)) {
                $io->error("❌ Le fichier JSON n'a pas été généré : {$metadataOutputPath}");
                return Command::FAILURE;
            }

            $io->success("🎉 Scraping Hearthstone Standard terminé avec succès !");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("💥 Erreur pendant l'exécution du script Node : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

// =================================================================================

#[AsCommand(
    name: 'app:scrape-hearthstone-wild',
    description: 'Scrape Hearthstone Wild metagame decks from HS Guru'
)]
class ScrapeHearthstoneWildCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->scrapeFormat($input, $output, 'wild');
    }

    private function scrapeFormat(InputInterface $input, OutputInterface $output, string $format): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = [
            'url' => 'https://www.hsguru.com/decks?format=1&period=past_day&rank=all',
            'outputDir' => 'public/uploads/hearthstone/wild_metagame'
        ];

        $scriptPath = __DIR__ . '/Scripts/scraper-hsguru.js';

        if (!file_exists($scriptPath)) {
            $io->error("❌ Le script JS est introuvable : {$scriptPath}");
            return Command::FAILURE;
        }

        $url = $config['url'];
        $outputDir = rtrim($config['outputDir'], '/');
        $metadataOutputPath = $outputDir . '/metagame_decks.json';

        $io->title("📊 Scraping HS Guru Wild → {$url}");

        // Nettoyage préventif
        $io->writeln("🧹 Nettoyage des processus Chrome...");
        $killProcess = new Process(['pkill', '-f', 'chromium']);
        $killProcess->run();
        sleep(2);

        $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath]);
        $process->setTimeout(300);
        
        $process->setEnv([
            'NODE_OPTIONS' => '--max-old-space-size=256'
        ]);

        $io->section('🕵️ Lancement du scraper Puppeteer...');

        try {
            $process->mustRun(function ($type, $buffer) use ($io) {
                if (Process::ERR === $type) {
                    $io->error($buffer);
                } else {
                    $io->write($buffer);
                }
            });

            if (!file_exists($metadataOutputPath)) {
                $io->error("❌ Le fichier JSON n'a pas été généré : {$metadataOutputPath}");
                return Command::FAILURE;
            }

            $io->success("🎉 Scraping Hearthstone Wild terminé avec succès !");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("💥 Erreur pendant l'exécution du script Node : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

// =================================================================================

#[AsCommand(
    name: 'app:scrape-hearthstone-metagame',
    description: 'Scrape both Hearthstone Standard & Wild metagame (calls both commands)'
)]
class ScrapeHearthstoneMetagameCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title("🔥 Scraping Hearthstone Metagame Complet");
        
        $success = 0;
        
        // Standard
        $io->section("1️⃣ Scraping Standard...");
        $standardProcess = new Process(['php', 'bin/console', 'app:scrape-hearthstone-standard']);
        $standardProcess->setTimeout(400);
        
        try {
            $standardProcess->mustRun();
            $io->success("✅ Standard terminé");
            $success++;
        } catch (\Exception $e) {
            $io->error("❌ Standard échoué : " . $e->getMessage());
        }
        
        // Pause entre formats
        $io->writeln("⏰ Pause de 15 secondes entre formats...");
        sleep(15);
        
        // Wild  
        $io->section("2️⃣ Scraping Wild...");
        $wildProcess = new Process(['php', 'bin/console', 'app:scrape-hearthstone-wild']);
        $wildProcess->setTimeout(400);
        
        try {
            $wildProcess->mustRun();
            $io->success("✅ Wild terminé");
            $success++;
        } catch (\Exception $e) {
            $io->error("❌ Wild échoué : " . $e->getMessage());
        }
        
        // Résumé
        if ($success === 2) {
            $io->success("🎉 Scraping Hearthstone complet réussi ! (2/2)");
            return Command::SUCCESS;
        } elseif ($success === 1) {
            $io->warning("⚠️ Scraping Hearthstone partiel (1/2)");
            return Command::SUCCESS;
        } else {
            $io->error("❌ Scraping Hearthstone complètement échoué");
            return Command::FAILURE;
        }
    }
}