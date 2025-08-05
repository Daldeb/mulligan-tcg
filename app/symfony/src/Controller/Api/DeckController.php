<?php

namespace App\Controller\Api;

use App\Entity\Deck;
use App\Entity\Game;
use App\Entity\GameFormat;
use App\Entity\DeckCard;
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
        $deck->setHearthstoneClass($data['hearthstoneClass'] ?? null);

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
        if (isset($data['hearthstoneClass'])) { 
            $deck->setHearthstoneClass($data['hearthstoneClass']);
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
 * Mettre à jour un deck complet (métadonnées + cartes)
 */
#[Route('/{id}', name: 'update', methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
public function update(int $id, Request $request): JsonResponse
{
    $deck = $this->deckRepository->find($id);
    
    if (!$deck || $deck->getUser() !== $this->getUser()) {
        return $this->json(['success' => false, 'message' => 'Deck introuvable'], 404);
    }

    $data = json_decode($request->getContent(), true);
    
    // Validation des données requises
    if (empty($data['title'])) {
        return $this->json([
            'success' => false,
            'message' => 'Titre requis'
        ], 400);
    }

    try {
        // Mettre à jour les métadonnées du deck
        $deck->setTitle(trim($data['title']));
        $deck->setDescription($data['description'] ?? null);
        $deck->setHearthstoneClass($data['hearthstoneClass'] ?? null);
        if (isset($data['isPublic'])) {
            $deck->setIsPublic((bool)$data['isPublic']);
        }

        // Gérer les cartes du deck
        if (isset($data['cards']) && is_array($data['cards'])) {
            // Supprimer toutes les cartes existantes du deck
            foreach ($deck->getDeckCards() as $deckCard) {
                $this->entityManager->remove($deckCard);
            }
            $deck->getDeckCards()->clear();
            
            // Ajouter les nouvelles cartes
            foreach ($data['cards'] as $cardData) {
                if (isset($cardData['cardId']) && isset($cardData['quantity'])) {
                    $cardId = $cardData['cardId'];
                    $quantity = $cardData['quantity'];
                    
                    // Récupérer la carte selon le jeu
                    // Récupérer la carte selon le jeu
                    $gameSlug = $deck->getGame()->getSlug();
                    $card = null;

                    if ($gameSlug === 'hearthstone') {
                        $card = $this->entityManager->getRepository('App\Entity\Hearthstone\HearthstoneCard')->find($cardId);
                    } elseif ($gameSlug === 'pokemon') {
                        $card = $this->entityManager->getRepository('App\Entity\Pokemon\PokemonCard')->find($cardId);
                    } elseif ($gameSlug === 'magic') {
                        $card = $this->entityManager->getRepository('App\Entity\Magic\MagicCard')->find($cardId);
                    }

                    if ($card) {
                        $deckCard = new \App\Entity\DeckCard();
                        $deckCard->setDeck($deck);
                        $deckCard->setQuantity($quantity);
                        
                        // Assigner la carte selon le type
                        if ($gameSlug === 'hearthstone') {
                            $deckCard->setHearthstoneCard($card);
                        } elseif ($gameSlug === 'pokemon') {
                            $deckCard->setPokemonCard($card);
                        } elseif ($gameSlug === 'magic') {
                            $deckCard->setMagicCard($card);
                        }
                        
                        $deck->addDeckCard($deckCard);
                        $this->entityManager->persist($deckCard);
                    }
                }
            }
        }

        // Recalculer les statistiques du deck
        $deck->recalculateStats();
        
        // Sauvegarder en base
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Deck mis à jour avec succès',
            'data' => [
                'id' => $deck->getId(),
                'title' => $deck->getTitle(),
                'totalCards' => $deck->getTotalCards(),
                'validDeck' => $deck->isValidDeck()
            ]
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du deck'
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

    private function extractMainCardType(string $typeLine): string
    {
        $typeLine = strtolower($typeLine);
        
        // Gestion des termes français ET anglais
        if (strpos($typeLine, 'creature') !== false || strpos($typeLine, 'créature') !== false) return 'creature';
        if (strpos($typeLine, 'planeswalker') !== false) return 'planeswalker';
        if (strpos($typeLine, 'land') !== false || strpos($typeLine, 'terrain') !== false) return 'land';
        if (strpos($typeLine, 'artifact') !== false || strpos($typeLine, 'artefact') !== false) return 'artifact';
        if (strpos($typeLine, 'enchantment') !== false || strpos($typeLine, 'enchantement') !== false) return 'enchantment';
        if (strpos($typeLine, 'instant') !== false || strpos($typeLine, 'éphémère') !== false) return 'instant';
        if (strpos($typeLine, 'sorcery') !== false || strpos($typeLine, 'rituel') !== false) return 'sorcery';
        if (strpos($typeLine, 'battle') !== false || strpos($typeLine, 'bataille') !== false) return 'battle';
        
        return 'other';
    }

    /**
     * Sérialise un deck pour l'API
     */
    private function serializeDeck(Deck $deck): array
    {
        $serializedCards = [];
        
        // Sérialiser les cartes du deck avec leurs détails
        foreach ($deck->getDeckCards() as $deckCard) {
            $card = $deckCard->getCard(); // Méthode qui retourne HearthstoneCard, PokemonCard ou MagicCard
            
            if ($card) {
                $cardData = [
                    'quantity' => $deckCard->getQuantity(),
                    'card' => [
                        'id' => $card->getId(),
                        'name' => method_exists($card, 'getDisplayName') ? $card->getDisplayName() : $card->getName(),
                        'imageUrl' => $card->getImageUrl(),
                    ]
                ];

                // ✅ CORRECTION: Sérialisation spécifique selon le type de jeu
                $gameSlug = $deck->getGame()->getSlug();
                
                if ($gameSlug === 'hearthstone') {
                    // Propriétés Hearthstone
                    $cardData['card']['cost'] = method_exists($card, 'getCost') ? $card->getCost() : null;
                    $cardData['card']['rarity'] = method_exists($card, 'getRarity') ? $card->getRarity() : null;
                    $cardData['card']['cardType'] = method_exists($card, 'getCardType') ? $card->getCardType() : null;
                    $cardData['card']['cardClass'] = method_exists($card, 'getCardClass') ? $card->getCardClass() : null;
                    $cardData['card']['isStandardLegal'] = method_exists($card, 'isStandardLegal') ? $card->isStandardLegal() : false;
                    $cardData['card']['isWildLegal'] = method_exists($card, 'isWildLegal') ? $card->isWildLegal() : false;
                    
                } elseif ($gameSlug === 'magic') {
                    // ✅ PROPRIÉTÉS MAGIC COMPLÈTES
                    $cardData['card']['manaCost'] = method_exists($card, 'getManaCost') ? $card->getManaCost() : null;
                    $cardData['card']['cmc'] = method_exists($card, 'getCmc') ? $card->getCmc() : null;
                    $cardData['card']['power'] = method_exists($card, 'getPower') ? $card->getPower() : null;
                    $cardData['card']['toughness'] = method_exists($card, 'getToughness') ? $card->getToughness() : null;
                    $cardData['card']['rarity'] = method_exists($card, 'getRarity') ? $card->getRarity() : null;
                    $cardData['card']['typeLine'] = method_exists($card, 'getDisplayTypeLine') ? $card->getDisplayTypeLine() : null;
                    $cardData['card']['colors'] = method_exists($card, 'getColors') ? $card->getColors() : [];
                    $cardData['card']['colorIdentity'] = method_exists($card, 'getColorIdentity') ? $card->getColorIdentity() : [];
                    $cardData['card']['text'] = method_exists($card, 'getDisplayText') ? $card->getDisplayText() : null;
                    $cardData['card']['isStandardLegal'] = method_exists($card, 'isStandardLegal') ? $card->isStandardLegal() : false;
                    $cardData['card']['isCommanderLegal'] = method_exists($card, 'isCommanderLegal') ? $card->isCommanderLegal() : false;
                    $cardData['card']['isCreature'] = method_exists($card, 'isCreature') ? $card->isCreature() : false;
                    $cardData['card']['isLand'] = method_exists($card, 'isLand') ? $card->isLand() : false;
                    $cardData['card']['isLegendary'] = method_exists($card, 'isLegendary') ? $card->isLegendary() : false;
                    $cardData['card']['canBeCommander'] = method_exists($card, 'canBeCommander') ? $card->canBeCommander() : false;
                    
                    // ✅ CALCULER LE CARDTYPE AVEC LA MÊME LOGIQUE QUE L'API
                    $cardData['card']['cardType'] = $this->extractMainCardType($cardData['card']['typeLine'] ?? '');
                    
                } elseif ($gameSlug === 'pokemon') {
                    // Propriétés Pokemon (à implémenter si nécessaire)
                    $cardData['card']['rarity'] = method_exists($card, 'getRarity') ? $card->getRarity() : null;
                    // TODO: Ajouter autres propriétés Pokemon
                }

                $serializedCards[] = $cardData;
            }
        }

        // ✅ CORRECTION: Ajouter colorIdentity pour Magic
        $additionalData = [];
        if ($deck->getGame()->getSlug() === 'magic') {
            // Calculer l'identité de couleur à partir des cartes
            $allColors = [];
            foreach ($serializedCards as $cardEntry) {
                if (!empty($cardEntry['card']['colorIdentity'])) {
                    $allColors = array_merge($allColors, $cardEntry['card']['colorIdentity']);
                }
            }
            $colorOrder = ['W', 'U', 'B', 'R', 'G'];
            $additionalData['colorIdentity'] = array_values(array_intersect($colorOrder, array_unique($allColors)));
        }

        return array_merge([
            'id' => $deck->getId(),
            'slug' => $deck->getSlug(),
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
            'hearthstoneClass' => $deck->getHearthstoneClass(),
            'cards' => $serializedCards,
        ], $additionalData);
    }
}