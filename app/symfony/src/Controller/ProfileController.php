<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\RoleRequest;
use App\Repository\RoleRequestRepository;
use App\Repository\AddressRepository;
use App\Service\AddressService;
use App\Entity\Shop;
use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Address;
use App\Service\FileUploadService;
use App\Service\ShopVerificationService; 
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
        private RoleRequestRepository $roleRequestRepository,
        private AddressRepository $addressRepository,
        private AddressService $addressService
    ) {}

    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

            // 🆕 Calculer les statistiques des topics
        $postRepository = $this->entityManager->getRepository(Post::class);
        $commentRepository = $this->entityManager->getRepository(Comment::class);

        $topicsCreated = $postRepository->countTopicsCreatedByUser($user);
        $topicsParticipated = $commentRepository->countTopicsParticipatedByUser($user);
        
        // Récupérer les demandes de rôle de l'utilisateur
        $roleRequests = $this->roleRequestRepository->findNonRejectedByUser($user);

        // Sérialiser l'adresse utilisateur si elle existe
        $userAddress = null;
        if ($user->getAddress()) {
            $address = $user->getAddress();
            $userAddress = [
                'id' => $address->getId(),
                'streetAddress' => $address->getStreetAddress(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalCode(),
                'country' => $address->getCountry(),
                'fullAddress' => $address->getFullAddress(),
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude(),
                'hasCoordinates' => $address->hasCoordinates()
            ];
        }

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
            'selectedGames' => $user->getSelectedGames(),
            'stats' => [
                'topicsCreated' => $topicsCreated,
                'topicsParticipated' => $topicsParticipated
            ],
            'address' => $userAddress,
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('c'),
            'roleRequests' => array_map(function($request) {
                $shopAddress = null;
                if ($request->getShopAddress()) {
                    $address = $request->getShopAddress();
                    $shopAddress = [
                        'id' => $address->getId(),
                        'streetAddress' => $address->getStreetAddress(),
                        'city' => $address->getCity(),
                        'postalCode' => $address->getPostalCode(),
                        'country' => $address->getCountry(),
                        'fullAddress' => $address->getFullAddress()
                    ];
                }

                return [
                    'id' => $request->getId(),
                    'requestedRole' => $request->getRequestedRole(),
                    'status' => $request->getStatus(),
                    'message' => $request->getMessage(),
                    'shopName' => $request->getShopName(),
                    'shopAddress' => $shopAddress,
                    'shopPhone' => $request->getShopPhone(),
                    'shopWebsite' => $request->getShopWebsite(),
                    'siretNumber' => $request->getSiretNumber(),
                    'createdAt' => $request->getCreatedAt()->format('c'),
                    'reviewedAt' => $request->getReviewedAt()?->format('c'),
                    'adminResponse' => $request->getAdminResponse()
                ];
            }, $roleRequests)
        ]);
    }

    #[Route('/api/profile/update', name: 'api_profile_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        // Mise à jour des champs de profil classiques
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

        // Gestion de l'adresse utilisateur (optionnelle)
        if (isset($data['address'])) {
            $addressData = $data['address'];
            
            // Si adresse fournie et complète
            if (!empty($addressData['streetAddress']) && !empty($addressData['city']) && !empty($addressData['postalCode'])) {
                
                // Validation de l'adresse via AddressService
                $addressErrors = $this->addressService->validateFrenchAddress(
                    $addressData['streetAddress'],
                    $addressData['city'],
                    $addressData['postalCode']
                );

                if (!empty($addressErrors)) {
                    return $this->json([
                        'error' => 'Adresse invalide',
                        'addressErrors' => $addressErrors
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Rechercher ou créer l'adresse
                $address = $this->addressRepository->findOrCreateSimilar(
                    $addressData['streetAddress'],
                    $addressData['city'],
                    $addressData['postalCode'],
                    $addressData['country'] ?? 'France'
                );

                // Enrichir avec coordonnées si nécessaire
                if (!$address->hasCoordinates()) {
                    $this->addressService->enrichAddressWithCoordinates($address);
                }

                // Associer l'adresse à l'utilisateur
                $user->setAddress($address);
                
            } elseif (isset($addressData['remove']) && $addressData['remove'] === true) {
                // Supprimer l'adresse utilisateur
                $user->setAddress(null);
            }
        }

        // Validation du user mis à jour
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
            
            // Sérialiser l'adresse mise à jour
            $userAddress = null;
            if ($user->getAddress()) {
                $address = $user->getAddress();
                $userAddress = [
                    'id' => $address->getId(),
                    'streetAddress' => $address->getStreetAddress(),
                    'city' => $address->getCity(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'fullAddress' => $address->getFullAddress(),
                    'latitude' => $address->getLatitude(),
                    'longitude' => $address->getLongitude(),
                    'hasCoordinates' => $address->hasCoordinates()
                ];
            }
            
            return $this->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'pseudo' => $user->getPseudo(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'bio' => $user->getBio(),
                    'favoriteClass' => $user->getFavoriteClass(),
                    'address' => $userAddress
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

    // 🆕 MÉTHODE MISE À JOUR AVEC ENRICHISSEMENT AUTOMATIQUE
    #[Route('/api/profile/request-role', name: 'api_profile_request_role', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function requestRole(
        Request $request,
        ShopVerificationService $shopVerification // 🆕 Service injecté
    ): JsonResponse {
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
            $roleRequest->setShopPhone($data['shopPhone'] ?? null);
            $roleRequest->setShopWebsite($data['shopWebsite'] ?? null);
            $roleRequest->setSiretNumber($data['siretNumber'] ?? null);

            // Gestion de l'adresse boutique (OBLIGATOIRE pour les boutiques)
            if (!isset($data['shopAddress']) || empty($data['shopAddress']['streetAddress']) || 
                empty($data['shopAddress']['city']) || empty($data['shopAddress']['postalCode'])) {
                return $this->json([
                    'error' => 'Adresse boutique obligatoire',
                    'details' => ['Une adresse complète est requise pour les demandes de rôle boutique']
                ], Response::HTTP_BAD_REQUEST);
            }

            $shopAddressData = $data['shopAddress'];
            
            // Validation de l'adresse boutique
            $addressErrors = $this->addressService->validateFrenchAddress(
                $shopAddressData['streetAddress'],
                $shopAddressData['city'],
                $shopAddressData['postalCode']
            );

            if (!empty($addressErrors)) {
                return $this->json([
                    'error' => 'Adresse boutique invalide',
                    'addressErrors' => $addressErrors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Rechercher ou créer l'adresse boutique
            $shopAddress = $this->addressRepository->findOrCreateSimilar(
                $shopAddressData['streetAddress'],
                $shopAddressData['city'],
                $shopAddressData['postalCode'],
                $shopAddressData['country'] ?? 'France'
            );

            // Enrichir avec coordonnées si nécessaire
            if (!$shopAddress->hasCoordinates()) {
                $this->addressService->enrichAddressWithCoordinates($shopAddress);
            }

            $roleRequest->setShopAddress($shopAddress);
        }

        // Validation de la demande complète
        $errors = $this->validator->validate($roleRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['error' => 'Données invalides', 'details' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Persister la demande de base
            $this->entityManager->persist($roleRequest);
            $this->entityManager->flush();

            // 🆕 ENRICHISSEMENT AUTOMATIQUE pour les boutiques
            if ($data['role'] === RoleRequest::ROLE_SHOP) {
                $shopVerification->enrichRoleRequest($roleRequest);
                $this->entityManager->flush(); // Sauvegarder les données d'enrichissement
            }

            // Sérialiser l'adresse boutique si présente
            $shopAddress = null;
            if ($roleRequest->getShopAddress()) {
                $address = $roleRequest->getShopAddress();
                $shopAddress = [
                    'id' => $address->getId(),
                    'streetAddress' => $address->getStreetAddress(),
                    'city' => $address->getCity(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'fullAddress' => $address->getFullAddress()
                ];
            }

            // 🆕 Inclure les données d'enrichissement dans la réponse
            $enrichmentData = null;
            if ($data['role'] === RoleRequest::ROLE_SHOP) {
                $enrichmentData = [
                    'verification_score' => $roleRequest->getVerificationScore(),
                    'confidence_level' => $roleRequest->getConfidenceLevel(),
                    'verification_date' => $roleRequest->getVerificationDate()?->format('c'),
                    'siren_data_available' => !empty($roleRequest->getSirenData())
                ];
            }

            return $this->json([
                'message' => 'Demande de rôle envoyée avec succès',
                'request' => [
                    'id' => $roleRequest->getId(),
                    'requestedRole' => $roleRequest->getRequestedRole(),
                    'status' => $roleRequest->getStatus(),
                    'shopName' => $roleRequest->getShopName(),
                    'shopAddress' => $shopAddress,
                    'siretNumber' => $roleRequest->getSiretNumber(),
                    'enrichment' => $enrichmentData, // 🆕 Données d'enrichissement
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
        $requests = $this->roleRequestRepository->findNonRejectedByUser($user);

        return $this->json(array_map(function($request) {
            $shopAddress = null;
            if ($request->getShopAddress()) {
                $address = $request->getShopAddress();
                $shopAddress = [
                    'id' => $address->getId(),
                    'streetAddress' => $address->getStreetAddress(),
                    'city' => $address->getCity(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'fullAddress' => $address->getFullAddress(),
                    'latitude' => $address->getLatitude(),
                    'longitude' => $address->getLongitude(),
                    'hasCoordinates' => $address->hasCoordinates()
                ];
            }

            return [
                'id' => $request->getId(),
                'requestedRole' => $request->getRequestedRole(),
                'status' => $request->getStatus(),
                'message' => $request->getMessage(),
                'adminResponse' => $request->getAdminResponse(),
                'createdAt' => $request->getCreatedAt()->format('c'),
                'reviewedAt' => $request->getReviewedAt()?->format('c'),
                'shopName' => $request->getShopName(),
                'shopAddress' => $shopAddress,
                'shopPhone' => $request->getShopPhone(),
                'shopWebsite' => $request->getShopWebsite(),
                'siretNumber' => $request->getSiretNumber()
            ];
        }, $requests));
    }

    #[Route('/api/profile/selected-games', name: 'api_profile_selected_games_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateSelectedGames(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['selectedGames']) || !is_array($data['selectedGames'])) {
            return $this->json(['error' => 'Liste de jeux invalide'], Response::HTTP_BAD_REQUEST);
        }

        $user->replaceSelectedGames($data['selectedGames']);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Jeux sélectionnés mis à jour',
            'selectedGames' => $user->getSelectedGames()
        ]);
    }

    /**
 * Modifier sa boutique
 */
#[Route('/api/profile/shop', name: 'profile_update_shop', methods: ['PUT'])]
public function updateShop(Request $request, EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user || !in_array('ROLE_SHOP', $user->getRoles())) {
        return $this->json(['error' => 'Non autorisé'], 403);
    }

    // Récupérer la boutique de l'user
    $shop = $em->getRepository(Shop::class)->findOneBy(['owner' => $user]);
    if (!$shop) {
        return $this->json(['error' => 'Aucune boutique trouvée'], 404);
    }

    $data = json_decode($request->getContent(), true);

    // Validation des données
    if (empty($data['name']) || strlen($data['name']) < 2) {
        return $this->json(['error' => 'Le nom de la boutique est requis (minimum 2 caractères)'], 400);
    }

    // Mise à jour des champs autorisés
    $shop->setName($data['name']);
    $shop->setDescription($data['description'] ?? null);
    $shop->setPhone($data['phone'] ?? null);
    $shop->setEmail($data['email'] ?? null);
    $shop->setWebsite($data['website'] ?? null);
    $shop->setPrimaryColor($data['primaryColor'] ?? null);
    $shop->setServices($data['services'] ?? []);
    $shop->setSpecializedGames($data['specializedGames'] ?? []);
    $shop->setOpeningHours($data['openingHours'] ?? null);
    $shop->setIsActive($data['isActive'] ?? true);

    // Gestion de l'adresse
    if (!empty($data['address'])) {
        $address = $shop->getAddress();
        if (!$address) {
            $address = new Address();
            $em->persist($address);
        }
        
        $address->setStreetAddress($data['address']['streetAddress'] ?? '');
        $address->setCity($data['address']['city'] ?? '');
        $address->setPostalCode($data['address']['postalCode'] ?? '');
        $address->setCountry($data['address']['country'] ?? 'France');
        $address->setLatitude($data['address']['latitude'] ?? null);
        $address->setLongitude($data['address']['longitude'] ?? null);
        
        $shop->setAddress($address);
    }

    $shop->updateTimestamp();
    $em->flush();

    return $this->json([
        'success' => true,
        'message' => 'Boutique mise à jour avec succès',
        'shop' => [
            'id' => $shop->getId(),
            'name' => $shop->getName(),
            'description' => $shop->getDescription(),
            'phone' => $shop->getPhone(),
            'email' => $shop->getEmail(),
            'website' => $shop->getWebsite(),
            'primaryColor' => $shop->getPrimaryColor(),
            'services' => $shop->getServices(),
            'specializedGames' => $shop->getSpecializedGames(),
            'openingHours' => $shop->getOpeningHours(),
            'isActive' => $shop->isActive(),
            'address' => $shop->getAddress() ? [
                'streetAddress' => $shop->getAddress()->getStreetAddress(),
                'city' => $shop->getAddress()->getCity(),
                'postalCode' => $shop->getAddress()->getPostalCode(),
                'country' => $shop->getAddress()->getCountry(),
                'latitude' => $shop->getAddress()->getLatitude(),
                'longitude' => $shop->getAddress()->getLongitude(),
                'fullAddress' => $shop->getAddress()->getFullAddress()
            ] : null
        ]
    ]);
}

    /**
     * Upload logo boutique
     */
    #[Route('/api/profile/shop/logo', name: 'profile_upload_shop_logo', methods: ['POST'])]
    public function uploadShopLogo(Request $request, FileUploadService $fileUploadService, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_SHOP', $user->getRoles())) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        // Récupérer la boutique de l'user
        $shop = $em->getRepository(Shop::class)->findOneBy(['owner' => $user]);
        if (!$shop) {
            return $this->json(['error' => 'Aucune boutique trouvée'], 404);
        }

        $uploadedFile = $request->files->get('logo');
        if (!$uploadedFile) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        try {
            // Supprimer l'ancien logo s'il existe
            if ($shop->getLogo()) {
                $fileUploadService->deleteFile($shop->getLogo());
            }

            // Upload du nouveau logo
            $filename = $fileUploadService->uploadShopLogo($uploadedFile, $shop->getId());
            
            // Mise à jour de la boutique
            $shop->setLogo($filename);
            $shop->updateTimestamp();
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Logo mis à jour avec succès',
                'logo' => $filename
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/profile/posts', name: 'api_profile_posts', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getUserPosts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Paramètres de pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;
        
        // Récupérer les posts de l'utilisateur avec pagination
        $postRepository = $this->entityManager->getRepository(Post::class);
        $posts = $postRepository->findByUserWithPagination($user, $limit, $offset);
        $totalPosts = $postRepository->countByUser($user);
        
        // Sérialiser les posts
        $postsData = array_map(function (Post $post) {
            // Compter les commentaires
            $commentRepository = $this->entityManager->getRepository(Comment::class);
            $commentsCount = $commentRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.post = :post')
                ->andWhere('c.isDeleted = false')
                ->setParameter('post', $post)
                ->getQuery()
                ->getSingleScalarResult();

            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $post->getSlug(),
                'content' => substr($post->getContent(), 0, 200) . (strlen($post->getContent()) > 200 ? '...' : ''),
                'score' => $post->getScore(),
                'commentsCount' => (int) $commentsCount,
                'postType' => $post->getPostType(),
                'tags' => $post->getTags(),
                'createdAt' => $post->getCreatedAt()->format('c'),
                'updatedAt' => $post->getUpdatedAt()?->format('c'),
                'isPinned' => $post->isPinned(),
                'isLocked' => $post->isLocked(),
                'forum' => [
                    'id' => $post->getForum()->getId(),
                    'name' => $post->getForum()->getName(),
                    'slug' => $post->getForum()->getSlug()
                ]
            ];
        }, $posts);
        
        return $this->json([
            'posts' => $postsData,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalPosts / $limit),
                'totalPosts' => $totalPosts,
                'postsPerPage' => $limit,
                'hasNextPage' => ($page * $limit) < $totalPosts,
                'hasPrevPage' => $page > 1
            ]
        ]);
    }

}