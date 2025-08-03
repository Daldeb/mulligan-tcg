<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private string $uploadsDirectory,
        private string $publicPath,
        private SluggerInterface $slugger
    ) {}

    public function uploadAvatar(UploadedFile $file, int $userId): string
    {
        // Validation du fichier
        $this->validateImageFile($file);

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = 'avatar_' . $userId . '_' . uniqid() . '.' . $file->guessExtension();

        // Créer le dossier s'il n'existe pas
        $avatarDirectory = $this->uploadsDirectory . '/avatars';
        if (!is_dir($avatarDirectory)) {
            mkdir($avatarDirectory, 0755, true);
        }

        try {
            $file->move($avatarDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        return 'avatars/' . $newFilename;
    }

    public function deleteFile(string $filename): bool
    {
        $filePath = $this->uploadsDirectory . '/' . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    public function getAvatarUrl(string $filename): string
    {
        return $this->publicPath . '/uploads/' . $filename;
    }

    private function validateImageFile(UploadedFile $file): void
    {
        // Vérifier la taille (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Le fichier est trop volumineux (maximum 5MB)');
        }

        // Vérifier le type MIME
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP');
        }

        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->guessExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Extension de fichier non autorisée');
        }
    }

    public function resizeImage(string $filePath, int $maxWidth = 200, int $maxHeight = 200): void
    {
        // Optionnel: Redimensionner l'image pour optimiser les performances
        if (!extension_loaded('gd')) {
            return; // GD non disponible, on laisse l'image telle quelle
        }

        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return;
        }

        [$width, $height, $type] = $imageInfo;

        // Si l'image est déjà de la bonne taille, ne rien faire
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }

        // Calculer les nouvelles dimensions en conservant le ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Créer l'image source
        $sourceImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_GIF => imagecreatefromgif($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => null
        };

        if (!$sourceImage) {
            return;
        }

        // Créer l'image de destination
        $destImage = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionner
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        // Sauvegarder
        match($type) {
            IMAGETYPE_JPEG => imagejpeg($destImage, $filePath, 90),
            IMAGETYPE_PNG => imagepng($destImage, $filePath),
            IMAGETYPE_GIF => imagegif($destImage, $filePath),
            IMAGETYPE_WEBP => imagewebp($destImage, $filePath, 90),
        };

        // Nettoyer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($destImage);
    }


    public function uploadPostImage(UploadedFile $file, int $postId): string
    {
        // Validation du fichier
        $this->validateImageFile($file);

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = 'post_' . $postId . '_' . uniqid() . '.' . $file->guessExtension();

        // Créer le dossier s'il n'existe pas
        $postDirectory = $this->uploadsDirectory . '/posts';
        if (!is_dir($postDirectory)) {
            mkdir($postDirectory, 0755, true);
        }

        try {
            $file->move($postDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        return 'posts/' . $newFilename;
    }

    public function uploadPostAttachment(UploadedFile $file, int $postId): array
    {
        // Validation étendue pour différents types de fichiers
        $this->validateAttachmentFile($file);

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = 'attachment_' . $postId . '_' . uniqid() . '.' . $file->guessExtension();

        // Créer le dossier s'il n'existe pas
        $attachmentDirectory = $this->uploadsDirectory . '/attachments';
        if (!is_dir($attachmentDirectory)) {
            mkdir($attachmentDirectory, 0755, true);
        }

        try {
            $file->move($attachmentDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        return [
            'filename' => 'attachments/' . $newFilename,
            'originalName' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mimeType' => $file->getMimeType()
        ];
    }

    private function validateAttachmentFile(UploadedFile $file): void
    {
        // Vérifier la taille (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \Exception('Le fichier est trop volumineux (maximum 10MB)');
        }

        // Types autorisés : images + documents
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Type de fichier non autorisé');
        }
    }

    public function uploadShopLogo(UploadedFile $file, int $shopId): string
    {
        // Validation du fichier
        $this->validateImageFile($file);

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = 'shop_logo_' . $shopId . '_' . uniqid() . '.' . $file->guessExtension();

        // Créer le dossier s'il n'existe pas
        $logoDirectory = $this->uploadsDirectory . '/shop-logos';
        if (!is_dir($logoDirectory)) {
            mkdir($logoDirectory, 0755, true);
        }

        try {
            $file->move($logoDirectory, $newFilename);
            
            // Redimensionner le logo (optionnel)
            $this->resizeImage($logoDirectory . '/' . $newFilename, 300, 300);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du logo: ' . $e->getMessage());
        }

        return 'shop-logos/' . $newFilename;
    }

    public function getShopLogoUrl(string $filename): string
    {
        return $this->publicPath . '/uploads/' . $filename;
    }

    public function getPostImageUrl(string $filename): string
    {
        return $this->publicPath . '/uploads/' . $filename;
    }
}