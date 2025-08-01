<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageDownloadService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private Filesystem $filesystem,
        private string $uploadsDir
    ) {}

    /**
     * Télécharger une image de carte Pokemon avec format TCGdx
     * URL format: https://assets.tcgdx.net/fr/sm/det1/1 → https://assets.tcgdx.net/fr/sm/det1/1/high.webp
     */
    public function downloadPokemonCard(string $imageUrl, string $cardId): string
    {
        // Construire l'URL complète avec qualité et extension TCGdx
        $fullImageUrl = $imageUrl . '/high.webp';
        
        $extension = 'webp';
        $localPath = "pokemon/cards/{$cardId}.{$extension}";
        $fullPath = $this->uploadsDir . '/' . $localPath;
        
        // Créer le dossier si nécessaire
        $this->filesystem->mkdir(dirname($fullPath));
        
        // Télécharger seulement si pas déjà présent
        if (!$this->filesystem->exists($fullPath)) {
            $response = $this->httpClient->request('GET', $fullImageUrl);
            
            if ($response->getStatusCode() === 200) {
                $this->filesystem->dumpFile($fullPath, $response->getContent());
            } else {
                throw new \Exception("Failed to download card image: HTTP {$response->getStatusCode()} for {$fullImageUrl}");
            }
        }
        
        return $localPath;
    }

    /**
     * Télécharger logo/symbol de set Pokemon avec format TCGdx
     * URL format: https://assets.tcgdx.net/univ/sm/det1/symbol → https://assets.tcgdx.net/univ/sm/det1/symbol.webp
     */
    public function downloadPokemonSetAsset(string $imageUrl, string $setId, string $type): ?string
    {
        if (!$imageUrl) {
            return null;
        }
        
        // Construire l'URL complète avec extension TCGdx
        $fullImageUrl = $imageUrl . '.webp';
        
        $extension = 'webp';
        $localPath = "pokemon/sets/{$type}/{$setId}.{$extension}";
        $fullPath = $this->uploadsDir . '/' . $localPath;
        
        // Créer le dossier si nécessaire
        $this->filesystem->mkdir(dirname($fullPath));
        
        // Télécharger seulement si pas déjà présent
        if (!$this->filesystem->exists($fullPath)) {
            try {
                $response = $this->httpClient->request('GET', $fullImageUrl);
                
                if ($response->getStatusCode() === 200) {
                    $this->filesystem->dumpFile($fullPath, $response->getContent());
                } else {
                    // Si l'asset n'existe pas (404), on retourne null sans planter
                    return null;
                }
            } catch (\Exception $e) {
                // Log l'erreur mais continue sans planter la commande
                error_log("Warning: Could not download set asset {$fullImageUrl}: " . $e->getMessage());
                return null;
            }
        }
        
        return $localPath;
    }

/**
     * Télécharger une image de carte Hearthstone depuis HearthstoneJSON
     * URL format: https://art.hearthstonejson.com/v1/render/latest/frFR/256x/{cardId}.png
     */
    public function downloadHearthstoneCard(string $cardId, string $locale = 'frFR'): array
    {
        $fullImageUrl = "https://art.hearthstonejson.com/v1/render/latest/{$locale}/256x/{$cardId}.png";
        
        $extension = 'png';
        $localPath = "hearthstone/cards/{$cardId}.{$extension}";
        $fullPath = $this->uploadsDir . '/' . $localPath;
        
        // Créer le dossier si nécessaire
        $this->filesystem->mkdir(dirname($fullPath));
        
        $downloaded = false;
        $fileSize = 0;
        
        // Vérifier si le fichier existe déjà
        if ($this->filesystem->exists($fullPath)) {
            // Fichier existe déjà, récupérer sa taille
            $fileSize = filesize($fullPath);
            return [
                'path' => $localPath,
                'downloaded' => false, // Pas téléchargé cette fois
                'size' => $fileSize
            ];
        }
        
        // Télécharger le fichier
        try {
            $response = $this->httpClient->request('GET', $fullImageUrl);
            
            if ($response->getStatusCode() === 200) {
                $content = $response->getContent();
                $this->filesystem->dumpFile($fullPath, $content);
                $fileSize = strlen($content);
                $downloaded = true;
                
                return [
                    'path' => $localPath,
                    'downloaded' => true,
                    'size' => $fileSize
                ];
                
            } else if ($response->getStatusCode() === 404) {
                // Image manquante - continuer sans erreur
                error_log("Warning: Hearthstone card image not found: {$cardId}");
                return [
                    'path' => null,
                    'downloaded' => false,
                    'size' => 0
                ];
            } else {
                throw new \Exception("Failed to download Hearthstone card image: HTTP {$response->getStatusCode()} for {$fullImageUrl}");
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                // Image 404 - continuer sans erreur
                error_log("Warning: Hearthstone card image not found: {$cardId}");
                return [
                    'path' => null,
                    'downloaded' => false,
                    'size' => 0
                ];
            }
            error_log("Warning: Could not download Hearthstone card {$cardId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Télécharger logo/symbol de set Hearthstone
     * Note: HearthstoneJSON ne fournit pas directement ces assets, 
     * cette méthode est préparée pour une éventuelle extension
     */
    public function downloadHearthstoneSetAsset(string $imageUrl, string $setId, string $type): ?string
    {
        if (!$imageUrl) {
            return null;
        }
        
        $extension = $this->getImageExtension($imageUrl);
        $localPath = "hearthstone/sets/{$type}/{$setId}.{$extension}";
        $fullPath = $this->uploadsDir . '/' . $localPath;
        
        // Créer le dossier si nécessaire
        $this->filesystem->mkdir(dirname($fullPath));
        
        // Télécharger seulement si pas déjà présent
        if (!$this->filesystem->exists($fullPath)) {
            try {
                $response = $this->httpClient->request('GET', $imageUrl);
                
                if ($response->getStatusCode() === 200) {
                    $this->filesystem->dumpFile($fullPath, $response->getContent());
                } else {
                    return null;
                }
            } catch (\Exception $e) {
                error_log("Warning: Could not download Hearthstone set asset {$imageUrl}: " . $e->getMessage());
                return null;
            }
        }
        
        return $localPath;
    }

    /**
     * Obtenir l'extension d'une URL d'image (fallback)
     */
    private function getImageExtension(string $url): string
    {
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
        return $pathInfo['extension'] ?? 'png';
    }

    /**
     * Vérifier si un fichier image existe localement
     */
    public function imageExists(string $localPath): bool
    {
        return $this->filesystem->exists($this->uploadsDir . '/' . $localPath);
    }

    /**
     * Obtenir le chemin complet d'une image locale
     */
    public function getFullImagePath(string $localPath): string
    {
        return $this->uploadsDir . '/' . $localPath;
    }
}