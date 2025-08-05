<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:scrape-pokemon-metagame',
    description: 'Scrape Pokémon Standard metagame decks from LimitlessTCG'
)]
class ScrapePokemonMetagameCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url = 'https://limitlesstcg.com/decks/lists?sort=last';
        $outputDir = 'public/uploads/pokemon/metagame';
        $metadataOutputPath = $outputDir . '/metagame_decks.json';
        $scriptPath = __DIR__ . '/Scripts/scraper-limitless.js';

        if (!file_exists($scriptPath)) {
            $io->error("❌ Script JS introuvable : {$scriptPath}");
            return Command::FAILURE;
        }

        $io->title("🔍 Scraping Pokémon Standard decks → {$url}");

        $process = new Process(['node', $scriptPath, $url, $outputDir, $metadataOutputPath]);
        $process->setTimeout(600);

        try {
            $process->mustRun(function ($type, $buffer) use ($io) {
                if ($type === Process::ERR) {
                    $io->error($buffer);
                } else {
                    $io->write($buffer);
                }
            });
        } catch (\Exception $e) {
            $io->error("💥 Erreur NodeJS : " . $e->getMessage());
            return Command::FAILURE;
        }

        if (!file_exists($metadataOutputPath)) {
            $io->error("❌ Fichier JSON non généré !");
            return Command::FAILURE;
        }

        $io->success("🎉 Scraping Pokémon terminé avec succès !");
        return Command::SUCCESS;
    }
}
