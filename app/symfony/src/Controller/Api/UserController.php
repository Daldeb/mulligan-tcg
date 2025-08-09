<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {}

    /**
     * Recherche d'utilisateurs par pseudo/email
     */
    #[Route('/search', name: 'api_users_search', methods: ['GET'])]
    public function searchUsers(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min(10, max(1, (int) $request->query->get('limit', 5)));

        if (strlen($query) < 2) {
            return $this->json([
                'users' => [],
                'message' => 'Au moins 2 caractères requis'
            ]);
        }

        try {
            // Recherche dans pseudo et email
            $users = $this->userRepository->createQueryBuilder('u')
                ->where('u.pseudo LIKE :query OR u.email LIKE :query')
                ->andWhere('u.isVerified = true')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('u.pseudo', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            $usersData = array_map(function (User $user) {
                return [
                    'id' => $user->getId(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar(),
                    'roles' => $user->getRoles(),
                    'displayName' => $user->getDisplayName(),
                    'entityType' => $user->getEntityType(),
                    'isShopOwner' => $user->isShopOwner(),
                    'createdAt' => $user->getCreatedAt()->format('Y-m-d')
                ];
            }, $users);

            return $this->json([
                'users' => $usersData,
                'count' => count($usersData),
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la recherche',
                'users' => []
            ], 500);
        }
    }

    /**
     * Suggestions d'utilisateurs actifs (pour auto-complétion)
     */
    #[Route('/suggestions', name: 'api_users_suggestions', methods: ['GET'])]
    public function getUserSuggestions(): JsonResponse
    {
        try {
            // Récupérer 10 utilisateurs les plus actifs récemment
            $users = $this->userRepository->createQueryBuilder('u')
                ->where('u.isVerified = true')
                ->andWhere('u.lastLoginAt IS NOT NULL')
                ->orderBy('u.lastLoginAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            $usersData = array_map(function (User $user) {
                return [
                    'id' => $user->getId(),
                    'pseudo' => $user->getPseudo(),
                    'avatar' => $user->getAvatar(),
                    'roles' => $user->getRoles(),
                    'displayName' => $user->getDisplayName(),
                    'entityType' => $user->getEntityType(),
                    'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d')
                ];
            }, $users);

            return $this->json([
                'users' => $usersData,
                'count' => count($usersData)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des suggestions',
                'users' => []
            ], 500);
        }
    }

    /**
     * Profil public d'un utilisateur
     */
    #[Route('/{id}/public', name: 'api_user_public_profile', methods: ['GET'])]
    public function getPublicProfile(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user || !$user->isVerified()) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Statistiques publiques basiques
        $postRepository = $this->entityManager->getRepository(\App\Entity\Post::class);
        $commentRepository = $this->entityManager->getRepository(\App\Entity\Comment::class);
        
        $postsCount = $postRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.author = :user')
            ->andWhere('p.isDeleted = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $commentsCount = $commentRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.author = :user')
            ->andWhere('c.isDeleted = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo(),
                'avatar' => $user->getAvatar(),
                'bio' => $user->getBio(),
                'favoriteClass' => $user->getFavoriteClass(),
                'roles' => $user->getRoles(),
                'displayName' => $user->getDisplayName(),
                'entityType' => $user->getEntityType(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'stats' => [
                    'postsCount' => (int) $postsCount,
                    'commentsCount' => (int) $commentsCount
                ]
            ]
        ]);
    }
}