<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private EmailService $emailService,
        private UserRepository $userRepository
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, RateLimiterFactory $registerLimiter): JsonResponse
    {
        // Rate limiting - 5 tentatives par IP par heure
        $limiter = $registerLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'message' => 'Trop de tentatives d\'inscription. Réessayez dans une heure.'
            ], 429);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'], $data['pseudo'])) {
            return $this->json([
                'message' => 'Données manquantes'
            ], 400);
        }

        // Vérifier si l'email existe déjà
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'message' => 'Cet email est déjà utilisé'
            ], 422);
        }

        // Vérifier si le pseudo existe déjà
        if ($this->userRepository->findOneBy(['pseudo' => $data['pseudo']])) {
            return $this->json([
                'message' => 'Ce pseudo est déjà pris'
            ], 422);
        }

        // Validation du mot de passe
        if (strlen($data['password']) < 8) {
            return $this->json([
                'message' => 'Le mot de passe doit faire au moins 8 caractères'
            ], 422);
        }

        try {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPseudo($data['pseudo']);
            
            // Optionnel : prénom/nom si fournis
            if (isset($data['firstName'])) {
                $user->setFirstName($data['firstName']);
            }
            if (isset($data['lastName'])) {
                $user->setLastName($data['lastName']);
            }

            // Hash du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Générer le token de vérification
            $user->generateVerificationToken();

            // Validation
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'message' => 'Données invalides',
                    'errors' => $errorMessages
                ], 422);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Envoyer l'email de vérification
            $this->emailService->sendVerificationEmail($user);

            return $this->json([
                'message' => 'Inscription réussie ! Un email de vérification a été envoyé.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'pseudo' => $user->getPseudo(),
                    'isVerified' => $user->isVerified()
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/verify-email/{token}', name: 'api_verify_email', methods: ['GET', 'POST'])]
    public function verifyEmail(string $token): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return $this->json([
                'message' => 'Token de vérification invalide'
            ], 404);
        }

        if (!$user->isVerificationTokenValid()) {
            return $this->json([
                'message' => 'Token de vérification expiré'
            ], 410);
        }

        $user->setIsVerified(true);
        $user->clearVerificationToken();
        
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.',
            'verified' => true
        ]);
    }

    #[Route('/api/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json(['message' => 'Email requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Ne pas révéler si l'email existe ou non
            return $this->json([
                'message' => 'Si cet email existe, un nouveau lien de vérification a été envoyé.'
            ]);
        }

        if ($user->isVerified()) {
            return $this->json([
                'message' => 'Ce compte est déjà vérifié'
            ], 422);
        }

        // Générer un nouveau token
        $user->generateVerificationToken();
        $this->entityManager->flush();

        // Renvoyer l'email
        $this->emailService->sendVerificationEmail($user);

        return $this->json([
            'message' => 'Un nouveau lien de vérification a été envoyé.'
        ]);
    }

    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json(['message' => 'Email requis'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Ne pas révéler si l'email existe ou non
            return $this->json([
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'
            ]);
        }

        // Générer le token de reset
        $user->generateResetPasswordToken();
        $this->entityManager->flush();

        // Envoyer l'email de reset
        $this->emailService->sendPasswordResetEmail($user);

        return $this->json([
            'message' => 'Un lien de réinitialisation a été envoyé à votre email.'
        ]);
    }

    #[Route('/api/reset-password/{token}', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['password'])) {
            return $this->json(['message' => 'Mot de passe requis'], 400);
        }

        if (strlen($data['password']) < 8) {
            return $this->json([
                'message' => 'Le mot de passe doit faire au moins 8 caractères'
            ], 422);
        }

        $user = $this->userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user) {
            return $this->json([
                'message' => 'Token de réinitialisation invalide'
            ], 404);
        }

        if (!$user->isResetPasswordTokenValid()) {
            return $this->json([
                'message' => 'Token de réinitialisation expiré'
            ], 410);
        }

        // Mettre à jour le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->clearResetPasswordToken();

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatar(),
            'favoriteClass' => $user->getFavoriteClass(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/api/profile', name: 'api_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs autorisés
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['bio'])) {
            $user->setBio($data['bio']);
        }
        if (isset($data['favoriteClass'])) {
            $user->setFavoriteClass($data['favoriteClass']);
        }
        
        // Vérification si le pseudo est différent et disponible
        if (isset($data['pseudo']) && $data['pseudo'] !== $user->getPseudo()) {
            $existingUser = $this->userRepository->findOneBy(['pseudo' => $data['pseudo']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json([
                    'message' => 'Ce pseudo est déjà pris'
                ], 422);
            }
            $user->setPseudo($data['pseudo']);
        }

        // Validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'message' => 'Données invalides',
                'errors' => $errorMessages
            ], 422);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'bio' => $user->getBio(),
                'favoriteClass' => $user->getFavoriteClass()
            ]
        ]);
    }
}