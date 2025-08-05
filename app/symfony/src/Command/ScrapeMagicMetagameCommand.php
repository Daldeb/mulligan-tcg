<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:scrape-magic-metagame',
    description: 'Scrape Standard & Commander metagame decks from AetherHub and save images + metadata'
)]
class ScrapeMagicMetagameCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $formats = [
            'standard' => [
                'url' => 'https://aetherhub.com/Metagame/Standard-BO1/30',
                'outputDir' => 'public/uploads/magic/metagame',
                'label' => 'Standard BO1'
            ],
            'commander' => [
                'url' => 'https://aetherhub.com/Metagame/Historic-Brawl/30',
                'outputDir' => 'public/uploads/magic/commander_metagame',
                'label' => 'Commander (Historic Brawl)'
            ]
        ];

        $scriptPath = __DIR__ . '/Scripts/scraper-aetherhub.js';

        if (!file_exists($scriptPath)) {
            $io->error("❌ Le script JS est introuvable : {$scriptPath}");
            return Command::FAILURE;
        }

        foreach ($formats as $key => $config) {
            $url = $config['url'];
            $outputDir = rtrim($config['outputDir'], '/');
            $metadataOutputPath = $outputDir . '/metagame_decks.json';
            $label = $config['label'];

            $io->title("✨ Scraping Magic ({$label}) → {$url}");
            $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath, $label]);
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

            $io->success("🎉 Scraping {$label} terminé avec succès !");
        }

        return Command::SUCCESS;
    }
}
