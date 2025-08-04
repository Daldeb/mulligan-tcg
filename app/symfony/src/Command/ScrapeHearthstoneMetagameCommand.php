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

        foreach ($formats as $label => $config) {
            $url = $config['url'];
            $outputDir = rtrim($config['outputDir'], '/');
            $metadataOutputPath = $outputDir . '/metagame_decks.json';

            $io->title("📊 Scraping HS Guru ({$label}) → {$url}");
            $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath]);
            $process->setTimeout(300);

            $io->section('🕵️ Lancement du scraper Puppeteer...');

            try {
                $process->mustRun(function ($type, $buffer) use ($io) {
                    if (Process::ERR === $type) {
                        $io->error($buffer);
                    } else {
                        $io->write($buffer);
                    }
                });
            } catch (\Exception $e) {
                $io->error("💥 Erreur pendant l'exécution du script Node : " . $e->getMessage());
                return Command::FAILURE;
            }

            if (!file_exists($metadataOutputPath)) {
                $io->error("❌ Le fichier JSON n'a pas été généré : {$metadataOutputPath}");
                return Command::FAILURE;
            }

            $io->success("🎉 Scraping {$label} terminé avec succès ! Images dans {$outputDir}");
        }

        return Command::SUCCESS;
    }
}
