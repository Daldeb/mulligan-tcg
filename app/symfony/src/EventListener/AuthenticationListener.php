<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticationListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        
        // Vérification de type explicite pour Intelephense
        if (!$user instanceof User) {
            return;
        }
        
        if (!$user->isVerified()) {
            throw new AccessDeniedHttpException('Compte non vérifié. Vérifiez votre email avant de vous connecter.');
        }

        // Ajouter des données supplémentaires au token
        $payload = $event->getData();
        $payload['isVerified'] = $user->isVerified();
        $payload['pseudo'] = $user->getPseudo();
        
        $event->setData($payload);
    }
}