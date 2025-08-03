<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\GameFormat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-games',
    description: 'Initialise les jeux et formats de base dans la base de données'
)]
class InitGamesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des jeux et formats TCG');

        // Vérifier si des jeux existent déjà
        $existingGames = $this->entityManager->getRepository(Game::class)->findAll();
        if (!empty($existingGames)) {
            $io->warning('Des jeux existent déjà en base de données.');
            if (!$io->confirm('Voulez-vous continuer et ajouter/mettre à jour les jeux ?')) {
                return Command::SUCCESS;
            }
        }

        $this->createGamesAndFormats($io);

        $io->success('Jeux et formats initialisés avec succès !');

        return Command::SUCCESS;
    }

    private function createGamesAndFormats(SymfonyStyle $io): void
    {
        $gamesData = [
            [
                'name' => 'Magic the Gathering',
                'slug' => 'magic',
                'description' => 'Le premier jeu de cartes à collectionner au monde, créé par Richard Garfield en 1993.',
                'primary_color' => '#663399',
                'display_order' => 1,
                'formats' => [
                    ['name' => 'Standard', 'slug' => 'standard', 'description' => 'Format utilisant les cartes des 2 dernières années', 'order' => 1],
                    ['name' => 'Commander', 'slug' => 'commander', 'description' => 'Format multijoueur avec deck de 100 cartes uniques', 'order' => 2],
                ]
            ],
            [
                'name' => 'Pokemon',
                'slug' => 'pokemon',
                'description' => 'Jeu de cartes basé sur l\'univers Pokémon, alliant stratégie et collection.',
                'primary_color' => '#e74c3c',
                'display_order' => 2,
                'formats' => [
                    ['name' => 'Standard', 'slug' => 'standard', 'description' => 'Format officiel avec les cartes récentes', 'order' => 1],
                    ['name' => 'Expanded', 'slug' => 'expanded', 'description' => 'Format étendu avec plus de sets autorisés', 'order' => 2],
                ]
            ],
            [
                'name' => 'Hearthstone',
                'slug' => 'hearthstone',
                'description' => 'Jeu de cartes numérique dans l\'univers de Warcraft par Blizzard Entertainment.',
                'primary_color' => '#ff8c00',
                'display_order' => 3,
                'formats' => [
                    ['name' => 'Standard', 'slug' => 'standard', 'description' => 'Format avec les cartes des 2 dernières années', 'order' => 1],
                    ['name' => 'Wild', 'slug' => 'wild', 'description' => 'Format avec toutes les cartes de l\'histoire du jeu', 'order' => 2],
                ]
            ],
        ];

        foreach ($gamesData as $gameData) {
            $game = $this->createOrUpdateGame($gameData, $io);
            $this->createFormatsForGame($game, $gameData['formats'], $io);
        }

        $this->entityManager->flush();
    }

    private function createOrUpdateGame(array $gameData, SymfonyStyle $io): Game
    {
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['slug' => $gameData['slug']]);
        
        if ($game) {
            $io->text("Mise à jour du jeu : {$gameData['name']}");
        } else {
            $game = new Game();
            $io->text("Création du jeu : {$gameData['name']}");
        }

        $game->setName($gameData['name'])
             ->setSlug($gameData['slug'])
             ->setDescription($gameData['description'])
             ->setPrimaryColor($gameData['primary_color'])
             ->setDisplayOrder($gameData['display_order'])
             ->setIsActive(true);

        if (!$game->getId()) {
            $this->entityManager->persist($game);
        }

        return $game;
    }

    private function createFormatsForGame(Game $game, array $formatsData, SymfonyStyle $io): void
    {
        foreach ($formatsData as $formatData) {
            $format = $this->entityManager->getRepository(GameFormat::class)
                ->findOneBy(['slug' => $formatData['slug'], 'game' => $game]);

            if ($format) {
                $io->text("  └─ Mise à jour du format : {$formatData['name']}");
            } else {
                $format = new GameFormat();
                $io->text("  └─ Création du format : {$formatData['name']}");
            }

            $format->setName($formatData['name'])
                   ->setSlug($formatData['slug'])
                   ->setDescription($formatData['description'])
                   ->setDisplayOrder($formatData['order'])
                   ->setGame($game)
                   ->setIsActive(true);

            if (!$format->getId()) {
                $this->entityManager->persist($format);
            }
        }
    }
}