<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\EmailService;
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
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        // ✅ Vérifier si le compte est vérifié
        if (!$user->isVerified()) {
            return $this->json([
                'error' => 'Compte non vérifié',
                'message' => 'Veuillez vérifier votre email avant de vous connecter',
                'needsVerification' => true
            ], 403);
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
                'isVerified' => $user->isVerified()
            ]
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        EmailService $emailService
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
        
        // ✅ Générer le token de vérification
        $user->generateVerificationToken();

        $em->persist($user);
        $em->flush();

        // ✅ Envoyer l'email de vérification
        try {
            $emailService->sendVerificationEmail(
                $user->getEmail(),
                $user->getPseudo(),
                $user->getVerificationToken()
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer l'inscription
            error_log('Erreur envoi email: ' . $e->getMessage());
        }

        return $this->json([
            'success' => true,
            'message' => 'Inscription réussie ! Vérifiez votre email pour activer votre compte.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudo' => $user->getPseudo(),
                'isVerified' => false
            ]
        ]);
    }

    #[Route('/api/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 404);
        }

        if (!$user->isVerificationTokenValid()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        // ✅ Activer le compte
        $user->setIsVerified(true);
        $user->clearVerificationToken();

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.'
        ]);
    }

    #[Route('/api/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        EntityManagerInterface $em,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        if ($user->isVerified()) {
            return $this->json(['error' => 'Compte déjà vérifié'], 400);
        }

        // ✅ Générer un nouveau token
        $user->generateVerificationToken();
        $em->flush();

        // ✅ Renvoyer l'email
        try {
            $emailService->sendVerificationEmail(
                $user->getEmail(),
                $user->getPseudo(),
                $user->getVerificationToken()
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Email de vérification renvoyé !'
        ]);
    }

        #[Route('/api/password-reset', name: 'api_password_reset', methods: ['POST'])]
    public function requestPasswordReset(
        Request $request,
        EntityManagerInterface $em,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // ✅ Toujours retourner success pour éviter l'énumération d'emails
        if (!$user) {
            return $this->json([
                'success' => true,
                'message' => 'Si cet email existe dans notre base, vous recevrez un lien de réinitialisation.'
            ]);
        }

        // ✅ Vérifier que le compte est vérifié
        if (!$user->isVerified()) {
            return $this->json([
                'error' => 'Compte non vérifié',
                'message' => 'Veuillez d\'abord vérifier votre email avant de réinitialiser votre mot de passe.'
            ], 403);
        }

        // ✅ Générer le token de reset
        $user->generateResetToken();
        $em->flush();

        // ✅ Envoyer l'email de reset
        try {
            $emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getPseudo(),
                $user->getResetToken()
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email reset: ' . $e->getMessage());
            return $this->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Si cet email existe dans notre base, vous recevrez un lien de réinitialisation.'
        ]);
    }

    #[Route('/api/password-reset/confirm', name: 'api_password_reset_confirm', methods: ['POST'])]
    public function confirmPasswordReset(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token'], $data['password'])) {
            return $this->json(['error' => 'Token et nouveau mot de passe requis'], 400);
        }

        // ✅ Validation du mot de passe
        if (strlen($data['password']) < 6) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $data['token']]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 404);
        }

        if (!$user->isResetTokenValid()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        // ✅ Mettre à jour le mot de passe
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->clearResetToken();
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès ! Vous pouvez maintenant vous connecter.'
        ]);
    }
}