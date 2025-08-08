<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/decks')]
class MetagameController extends AbstractController
{
    #[Route('/metagame', name: 'api_decks_metagame', methods: ['GET'])]
    public function getMetagameDecks(Request $request): JsonResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads';
        
        $metagameData = [
            'hearthstone' => $this->loadGameMetagame($uploadsDir, 'hearthstone'),
            'magic' => $this->loadGameMetagame($uploadsDir, 'magic'),
            'pokemon' => $this->loadGameMetagame($uploadsDir, 'pokemon')
        ];

        // Filtrer les jeux qui ont des decks
        $metagameData = array_filter($metagameData, function($decks) {
            return !empty($decks);
        });

        return $this->json([
            'success' => true,
            'data' => $metagameData,
            'lastUpdated' => date('Y-m-d H:i:s')
        ]);
    }

    private function loadGameMetagame(string $uploadsDir, string $game): array
    {
        $allDecks = [];
        
        // Configuration des formats par jeu
        $formatPaths = $this->getGameFormatPaths($game);
        
        foreach ($formatPaths as $format => $path) {
            $fullPath = $uploadsDir . '/' . $path;
            $jsonFile = $fullPath . '/metagame_decks.json';
            
            if (file_exists($jsonFile)) {
                $jsonContent = file_get_contents($jsonFile);
                $decks = json_decode($jsonContent, true);
                
                if (is_array($decks)) {
                    // Ajouter les métadonnées de format et chemin d'image
                    foreach ($decks as &$deck) {
                        $deck['game'] = $game;
                        $deck['format'] = $format;
                        $deck['imagePath'] = 'http://localhost:8000/uploads/' . $path . '/' . $deck['image'];
                        
                        // Ajouter un ID unique basé sur le jeu, format et titre
                        $deck['id'] = md5($game . '_' . $format . '_' . $deck['title']);
                        
                        // Standardiser les champs selon le jeu
                        $deck = $this->standardizeDeckData($deck, $game);
                    }
                    
                    $allDecks = array_merge($allDecks, $decks);
                }
            }
        }
        
        return $allDecks;
    }

    private function getGameFormatPaths(string $game): array
    {
        switch ($game) {
            case 'hearthstone':
                return [
                    'standard' => 'hearthstone/metagame',
                    'wild' => 'hearthstone/wild_metagame'
                ];
            case 'magic':
                return [
                    'standard' => 'magic/metagame',
                    'commander' => 'magic/commander_metagame'
                ];
            case 'pokemon':
                return [
                    'standard' => 'pokemon/metagame'
                ];
            default:
                return [];
        }
    }

    private function standardizeDeckData(array $deck, string $game): array
    {
        // Champs communs
        $standardized = [
            'id' => $deck['id'],
            'title' => $deck['title'],
            'game' => $deck['game'],
            'format' => $deck['format'],
            'image' => $deck['image'],
            'imagePath' => $deck['imagePath'],
            'url' => $deck['url'] ?? null,
            'lastUpdated' => date('Y-m-d H:i:s')
        ];

        // Métadonnées spécifiques par jeu
        switch ($game) {
            case 'hearthstone':
                $standardized['metadata'] = [
                    'deckcode' => $deck['deckcode'] ?? null,
                    'winrate' => $deck['winrate'] ?? null,
                    'games' => $deck['games'] ?? null,
                    'class' => $deck['class'] ?? null,
                    'archetype' => $this->extractArchetypeFromTitle($deck['title'])
                ];
                break;
                
            case 'magic':
                $standardized['metadata'] = [
                    'format' => $deck['format'] ?? 'Unknown',
                    'colors' => $this->extractColorsFromTitle($deck['title']),
                    'archetype' => $this->extractArchetypeFromTitle($deck['title'])
                ];
                break;
                
            case 'pokemon':
                $standardized['metadata'] = [
                    'player' => $deck['player'] ?? null,
                    'rank' => $deck['rank'] ?? null,
                    'tournament' => $deck['tournament'] ?? null,
                    'archetype' => $this->extractArchetypeFromTitle($deck['title'])
                ];
                break;
        }

        return $standardized;
    }

    private function extractArchetypeFromTitle(string $title): ?string
    {
        // Liste d'archetypes communs
        $archetypes = [
            'Aggro', 'Control', 'Midrange', 'Combo', 'Tempo', 'Big', 'Zoo', 'Burn',
            'Ramp', 'Mill', 'Token', 'Tribal', 'Artifacts', 'Enchantments'
        ];
        
        foreach ($archetypes as $archetype) {
            if (stripos($title, $archetype) !== false) {
                return $archetype;
            }
        }
        
        return null;
    }

    private function extractColorsFromTitle(string $title): array
    {
        $colors = [];
        $colorPatterns = [
            'W' => ['White', 'Plains', 'Mono-White'],
            'U' => ['Blue', 'Island', 'Mono-Blue'],
            'B' => ['Black', 'Swamp', 'Mono-Black'],
            'R' => ['Red', 'Mountain', 'Mono-Red'],
            'G' => ['Green', 'Forest', 'Mono-Green']
        ];
        
        foreach ($colorPatterns as $color => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($title, $pattern) !== false) {
                    $colors[] = $color;
                    break;
                }
            }
        }
        
        return array_unique($colors);
    }
}