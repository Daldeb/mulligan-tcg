<?php

namespace App\Service;

/**
 * Service de validation et formatage des numéros SIRET français
 */
class SiretValidationService
{
    /**
     * Valide un numéro SIRET avec l'algorithme de Luhn
     */
    public function validateSiret(string $siret): array
    {
        // Nettoyer le SIRET (supprimer espaces, tirets, points)
        $cleanSiret = $this->cleanSiret($siret);
        
        $result = [
            'valid' => false,
            'formatted' => null,
            'errors' => []
        ];

        // Vérification longueur
        if (strlen($cleanSiret) !== 14) {
            $result['errors'][] = 'Le SIRET doit contenir exactement 14 chiffres';
            return $result;
        }

        // Vérification que ce sont bien des chiffres
        if (!ctype_digit($cleanSiret)) {
            $result['errors'][] = 'Le SIRET ne doit contenir que des chiffres';
            return $result;
        }

        // Validation avec algorithme de Luhn
        if (!$this->luhnCheck($cleanSiret)) {
            $result['errors'][] = 'Le numéro SIRET est invalide (clé de contrôle incorrecte)';
            return $result;
        }

        $result['valid'] = true;
        $result['formatted'] = $this->formatSiret($cleanSiret);

        return $result;
    }

    /**
     * Nettoie un numéro SIRET en supprimant les caractères non numériques
     */
    public function cleanSiret(string $siret): string
    {
        return preg_replace('/[^0-9]/', '', $siret);
    }

    /**
     * Formate un SIRET nettoyé avec des espaces : 123 456 789 01234
     */
    public function formatSiret(string $cleanSiret): string
    {
        if (strlen($cleanSiret) !== 14) {
            return $cleanSiret;
        }

        return substr($cleanSiret, 0, 3) . ' ' . 
               substr($cleanSiret, 3, 3) . ' ' . 
               substr($cleanSiret, 6, 3) . ' ' . 
               substr($cleanSiret, 9, 5);
    }

    /**
     * Algorithme de Luhn officiel pour les SIRET français
     * Source: INSEE + Documentation officielle
     */
    private function luhnCheck(string $siret): bool
    {
        $sum = 0;
        $length = strlen($siret);

        // Parcourir de droite à gauche
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $siret[$i];
            $position = $length - $i; // Position depuis la droite (1, 2, 3, ...)
            
            // Multiplier par 2 les chiffres en position PAIRE (2, 4, 6, ...) depuis la droite
            if ($position % 2 === 0) {
                $digit *= 2;
                
                // Si résultat > 9, soustraire 9 (équivalent à additionner les chiffres)
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }

        // Valide si la somme est un multiple de 10
        return $sum % 10 === 0;
    }

    /**
     * Extrait le SIREN (9 premiers chiffres) d'un SIRET
     */
    public function extractSiren(string $siret): ?string
    {
        $cleanSiret = $this->cleanSiret($siret);
        
        if (strlen($cleanSiret) !== 14) {
            return null;
        }

        return substr($cleanSiret, 0, 9);
    }

    /**
     * Extrait le NIC (5 derniers chiffres) d'un SIRET
     */
    public function extractNic(string $siret): ?string
    {
        $cleanSiret = $this->cleanSiret($siret);
        
        if (strlen($cleanSiret) !== 14) {
            return null;
        }

        return substr($cleanSiret, 9, 5);
    }

    /**
     * Génère un masque de saisie pour l'interface utilisateur
     */
    public function getSiretMask(): string
    {
        return '999 999 999 99999';
    }

    /**
     * Valide et formate un SIRET en une seule opération
     */
    public function validateAndFormat(string $siret): array
    {
        $validation = $this->validateSiret($siret);
        
        if ($validation['valid']) {
            return [
                'valid' => true,
                'siret' => $validation['formatted'],
                'siren' => $this->extractSiren($siret),
                'nic' => $this->extractNic($siret),
                'message' => 'SIRET valide'
            ];
        }

        return [
            'valid' => false,
            'siret' => null,
            'siren' => null,
            'nic' => null,
            'errors' => $validation['errors']
        ];
    }
}