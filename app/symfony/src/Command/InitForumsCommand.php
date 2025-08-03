<?php

namespace App\Command;

use App\Entity\Forum;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-forums',
    description: 'Initialise les forums pour chaque jeu dans la base de données'
)]
class InitForumsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des forums TCG');

        // Vérifier que les jeux existent
        $games = $this->entityManager->getRepository(Game::class)->findAll();
        if (empty($games)) {
            $io->error('Aucun jeu trouvé en base de données. Lancez d\'abord la commande app:init-games');
            return Command::FAILURE;
        }

        // Vérifier si des forums existent déjà
        $existingForums = $this->entityManager->getRepository(Forum::class)->findAll();
        if (!empty($existingForums)) {
            $io->warning('Des forums existent déjà en base de données.');
            if (!$io->confirm('Voulez-vous continuer et ajouter/mettre à jour les forums ?')) {
                return Command::SUCCESS;
            }
        }

        $this->createForums($io);

        $io->success('Forums initialisés avec succès !');

        return Command::SUCCESS;
    }

    private function createForums(SymfonyStyle $io): void
    {
        $forumsData = [
            [
                'game_slug' => 'magic',
                'name' => 'Magic the Gathering',
                'slug' => 'magic',
                'description' => 'Forum officiel pour les fans de Magic: The Gathering',
                'icon' => null,
                'is_official' => true
            ],
            [
                'game_slug' => 'pokemon',
                'name' => 'Pokémon',
                'slug' => 'pokemon',
                'description' => 'Forum dédié aux joueurs et collectionneurs Pokémon',
                'icon' => null,
                'is_official' => true
            ],
            [
                'game_slug' => 'hearthstone',
                'name' => 'Hearthstone',
                'slug' => 'hearthstone',
                'description' => 'Forum dédié aux joueurs de Hearthstone',
                'icon' => null,
                'is_official' => true
            ]
        ];

        foreach ($forumsData as $forumData) {
            $this->createOrUpdateForum($forumData, $io);
        }

        $this->entityManager->flush();
    }

    private function createOrUpdateForum(array $forumData, SymfonyStyle $io): void
    {
        // Récupérer le jeu associé
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['slug' => $forumData['game_slug']]);
        if (!$game) {
            $io->error("Jeu '{$forumData['game_slug']}' non trouvé");
            return;
        }

        // Vérifier si le forum existe déjà
        $forum = $this->entityManager->getRepository(Forum::class)->findOneBy(['slug' => $forumData['slug']]);
        
        if ($forum) {
            $io->text("Mise à jour du forum : {$forumData['name']}");
        } else {
            $forum = new Forum();
            $io->text("Création du forum : {$forumData['name']}");
        }

        $forum->setName($forumData['name'])
              ->setSlug($forumData['slug'])
              ->setDescription($forumData['description'])
              ->setIcon($forumData['icon'])
              ->setIsOfficial($forumData['is_official'])
              ->setGame($game);

        // Définir created_at seulement si c'est un nouveau forum
        if (!$forum->getId()) {
            $forum->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($forum);
        }
    }
}