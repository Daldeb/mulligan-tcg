<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        #[CurrentUser] ?User $user,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        if (null === $user) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
            ]
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // ✅ Supporte pseudo OU username
        $pseudo = $data['pseudo'] ?? $data['username'] ?? null;

        if (!$data || !isset($data['email'], $data['password']) || !$pseudo) {
            return $this->json(['error' => 'Champs requis: email, password, pseudo'], 400);
        }

        // Vérifier si l'email est déjà utilisé
        $existingEmail = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingEmail) {
            return $this->json(['error' => 'Un compte existe déjà avec cet email'], 409);
        }

        // Vérifier si le pseudo est déjà utilisé
        $existingPseudo = $em->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
        if ($existingPseudo) {
            return $this->json(['error' => 'Ce pseudo est déjà utilisé'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPseudo($pseudo);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // Générer le token JWT
        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
            ]
        ]);
    }
}