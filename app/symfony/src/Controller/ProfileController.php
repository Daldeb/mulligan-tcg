<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RoleRequest;
use App\Repository\RoleRequestRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher,
        private FileUploadService $fileUploadService,
        private RoleRequestRepository $roleRequestRepository
    ) {}

    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les demandes de rôle de l'utilisateur
        $roleRequests = $this->roleRequestRepository->findByUser($user);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'pseudo' => $user->getPseudo(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatar(),
            'favoriteClass' => $user->getFavoriteClass(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'roleRequests' => array_map(fn($request) => [
                'id' => $request->getId(),
                'requestedRole' => $request->getRequestedRole(),
                'status' => $request->getStatus(),
                'createdAt' => $request->getCreatedAt()->format('c'),
                'reviewedAt' => $request->getReviewedAt()?->format('c'),
                'adminResponse' => $request->getAdminResponse()
            ], $roleRequests)
        ]);
    }

    #[Route('/api/profile/update', name: 'api_profile_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs autorisés
        if (isset($data['pseudo'])) {
            $user->setPseudo($data['pseudo']);
        }

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

        // Validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->flush();
            
            return $this->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'pseudo' => $user->getPseudo(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'bio' => $user->getBio(),
                    'favoriteClass' => $user->getFavoriteClass()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise à jour'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/profile/avatar', name: 'api_profile_avatar', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $uploadedFile = $request->files->get('avatar');

        if (!$uploadedFile) {
            return $this->json(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer l'ancien avatar s'il existe
            if ($user->getAvatar()) {
                $this->fileUploadService->deleteFile($user->getAvatar());
            }

            // Upload du nouveau fichier
            $filename = $this->fileUploadService->uploadAvatar($uploadedFile, $user->getId());
            $user->setAvatar($filename);
            
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Avatar mis à jour avec succès',
                'avatar' => $filename,
                'avatarUrl' => $this->fileUploadService->getAvatarUrl($filename)
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/profile/password', name: 'api_profile_password', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json(['error' => 'Mot de passe actuel et nouveau mot de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json(['error' => 'Mot de passe actuel incorrect'], Response::HTTP_BAD_REQUEST);
        }

        // Validation du nouveau mot de passe
        if (strlen($data['newPassword']) < 6) {
            return $this->json(['error' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);
            
            $this->entityManager->flush();

            return $this->json(['message' => 'Mot de passe mis à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise à jour'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/profile/request-role', name: 'api_profile_request_role', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function requestRole(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['role']) || !in_array($data['role'], [RoleRequest::ROLE_ORGANIZER, RoleRequest::ROLE_SHOP])) {
            return $this->json(['error' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier qu'il n'y a pas déjà une demande en cours
        if ($this->roleRequestRepository->hasPendingRequestForRole($user, $data['role'])) {
            return $this->json(['error' => 'Une demande pour ce rôle est déjà en cours'], Response::HTTP_BAD_REQUEST);
        }

        // Créer la demande
        $roleRequest = new RoleRequest();
        $roleRequest->setUser($user);
        $roleRequest->setRequestedRole($data['role']);
        $roleRequest->setMessage($data['message'] ?? null);

        // Informations spécifiques aux boutiques
        if ($data['role'] === RoleRequest::ROLE_SHOP) {
            $roleRequest->setShopName($data['shopName'] ?? null);
            $roleRequest->setShopAddress($data['shopAddress'] ?? null);
            $roleRequest->setShopPhone($data['shopPhone'] ?? null);
            $roleRequest->setShopWebsite($data['shopWebsite'] ?? null);
            $roleRequest->setSiretNumber($data['siretNumber'] ?? null);
        }

        // Validation
        $errors = $this->validator->validate($roleRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($roleRequest);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Demande de rôle envoyée avec succès',
                'request' => [
                    'id' => $roleRequest->getId(),
                    'requestedRole' => $roleRequest->getRequestedRole(),
                    'status' => $roleRequest->getStatus(),
                    'createdAt' => $roleRequest->getCreatedAt()->format('c')
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la création de la demande'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/profile/role-requests', name: 'api_profile_role_requests', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getRoleRequests(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $requests = $this->roleRequestRepository->findByUser($user);

        return $this->json(array_map(fn($request) => [
            'id' => $request->getId(),
            'requestedRole' => $request->getRequestedRole(),
            'status' => $request->getStatus(),
            'message' => $request->getMessage(),
            'adminResponse' => $request->getAdminResponse(),
            'createdAt' => $request->getCreatedAt()->format('c'),
            'reviewedAt' => $request->getReviewedAt()?->format('c'),
            'shopName' => $request->getShopName(),
            'shopAddress' => $request->getShopAddress()
        ], $requests));
    }
}