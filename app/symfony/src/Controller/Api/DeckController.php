<?php

namespace App\Controller\Api;

use App\Entity\Deck;
use App\Entity\Game;
use App\Entity\GameFormat;
use App\Repository\DeckRepository;
use App\Repository\GameRepository;
use App\Repository\GameFormatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/decks', name: 'api_decks_')]
class DeckController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeckRepository $deckRepository,
        private GameRepository $gameRepository,
        private GameFormatRepository $formatRepository,
        private ValidatorInterface $validator
    ) {}

    /**
     * Créer un nouveau deck (depuis la modale)
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['title'], $data['gameId'], $data['formatId'])) {
            return $this->json([
                'success' => false,
                'message' => 'Titre, jeu et format requis'
            ], 400);
        }

        // Récupérer les entités Game et GameFormat
        $game = $this->gameRepository->find($data['gameId']);
        $gameFormat = $this->formatRepository->find($data['formatId']);

        if (!$game || !$gameFormat) {
            return $this->json([
                'success' => false,
                'message' => 'Jeu ou format invalide'
            ], 400);
        }

        // Vérifier que le format appartient au jeu
        if ($gameFormat->getGame()->getId() !== $game->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Format incompatible avec le jeu sélectionné'
            ], 400);
        }

        // Créer le deck
        $deck = new Deck();
        $deck->setTitle(trim($data['title']));
        $deck->setDescription($data['description'] ?? null);
        $deck->setArchetype($data['archetype'] ?? null);
        $deck->setGame($game);
        $deck->setGameFormat($gameFormat);
        $deck->setUser($this->getUser());
        $deck->setIsPublic(false); 
        $deck->setValidDeck(false); 

        // Générer un slug unique
        $baseSlug = $this->generateSlug($data['title']);
        $slug = $this->ensureUniqueSlug($baseSlug, $this->getUser());
        
        // Générer un slug unique
        $baseSlug = $this->generateSlug($data['title']);
        $slug = $this->ensureUniqueSlug($baseSlug, $this->getUser());
        $deck->setSlug($slug);  

        // Validation Symfony
        $errors = $this->validator->validate($deck);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errorMessages
            ], 400);
        }

        try {
            $this->entityManager->persist($deck);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Deck créé avec succès',
                'data' => [
                    'id' => $deck->getId(),
                    'slug' => $slug, // Temporaire jusqu'à ajout colonne
                    'title' => $deck->getTitle(),
                    'gameSlug' => $game->getSlug(),
                    'formatSlug' => $gameFormat->getSlug(),
                    'editUrl' => "/mes-decks/{$game->getSlug()}/{$gameFormat->getSlug()}/{$slug}"
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du deck'
            ], 500);
        }
    }

    /**
     * Récupérer un deck par son slug pour l'édition
     */
    #[Route('/by-slug/{gameSlug}/{formatSlug}/{deckSlug}', name: 'get_by_slug', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getBySlug(string $gameSlug, string $formatSlug, string $deckSlug): JsonResponse
    {
        $game = $this->gameRepository->findOneBy(['slug' => $gameSlug]);
        $format = $this->formatRepository->findOneBy([
            'slug' => $formatSlug,
            'game' => $game  // 🎯 Chercher le format pour CE jeu spécifique
        ]);
                
        if (!$game || !$format) {
            return $this->json(['success' => false, 'message' => 'Jeu ou format introuvable'], 404);
        }

        // Recherche par slug exact
        $deck = $this->deckRepository->findOneBy([
            'slug' => $deckSlug,
            'user' => $this->getUser(),
            'game' => $game,
            'gameFormat' => $format
        ]);

        if (!$deck) {
            return $this->json(['success' => false, 'message' => 'Deck introuvable'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->serializeDeck($deck)
        ]);
    }

    /**
     * Mettre à jour les métadonnées d'un deck
     */
    #[Route('/{id}/metadata', name: 'update_metadata', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateMetadata(int $id, Request $request): JsonResponse
    {
        $deck = $this->deckRepository->find($id);
        
        if (!$deck || $deck->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Deck introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour des métadonnées
        if (isset($data['title'])) {
            $deck->setTitle(trim($data['title']));
        }
        if (isset($data['description'])) {
            $deck->setDescription($data['description']);
        }
        if (isset($data['archetype'])) {
            $deck->setArchetype($data['archetype']);
        }
        if (isset($data['isPublic'])) {
            $deck->setIsPublic((bool)$data['isPublic']);
        }

        $deck->updateTimestamp();

        try {
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Métadonnées mises à jour'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Récupérer les decks d'un utilisateur
     */
    #[Route('/my-decks', name: 'my_decks', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMyDecks(): JsonResponse
    {
        $decks = $this->deckRepository->findBy(
            ['user' => $this->getUser()],
            ['updatedAt' => 'DESC']
        );

        $serializedDecks = array_map([$this, 'serializeDeck'], $decks);

        return $this->json([
            'success' => true,
            'data' => $serializedDecks
        ]);
    }

    /**
     * Supprimer un deck
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $deck = $this->deckRepository->find($id);
        
        if (!$deck || $deck->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Deck introuvable'], 404);
        }

        try {
            $this->entityManager->remove($deck);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Deck supprimé'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Génère un slug à partir d'un titre
     */
    private function generateSlug(string $title): string
    {
        // Convertir en minuscules et remplacer espaces/caractères spéciaux
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Limiter la longueur
        return substr($slug, 0, 50);
    }

    /**
     * Assure l'unicité du slug pour un utilisateur
     */
    private function ensureUniqueSlug(string $baseSlug, $user): string
    {
        $slug = $baseSlug . '-' . substr(uniqid(), -6); // aggro-beast-a1b2c3
        
        // Vérifier unicité globale par slug
        while ($this->deckRepository->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . substr(uniqid(), -6);
        }
        
        return $slug;
    }

    /**
     * Sérialise un deck pour l'API
     */
    private function serializeDeck(Deck $deck): array
    {
        return [
            'id' => $deck->getId(),
            'title' => $deck->getTitle(),
            'description' => $deck->getDescription(),
            'archetype' => $deck->getArchetype(),
            'isPublic' => $deck->isPublic(),
            'validDeck' => $deck->isValidDeck(),
            'totalCards' => $deck->getTotalCards(),
            'averageCost' => $deck->getAverageCost(),
            'game' => [
                'id' => $deck->getGame()->getId(),
                'name' => $deck->getGame()->getName(),
                'slug' => $deck->getGame()->getSlug()
            ],
            'format' => [
                'id' => $deck->getGameFormat()->getId(),
                'name' => $deck->getGameFormat()->getName(),
                'slug' => $deck->getGameFormat()->getSlug()
            ],
            'author' => $deck->getAuthorName(),
            'createdAt' => $deck->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $deck->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'cards' => [], // À implémenter selon les besoins
        ];
    }
}