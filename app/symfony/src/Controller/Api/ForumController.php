<?php

namespace App\Controller\Api;

use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Service\FileUploadService;
use App\Repository\ForumRepository;
use App\Repository\PostSaveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/forums')]
class ForumController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ForumRepository $forumRepo,
        private FileUploadService $fileUploadService,
        private PostSaveRepository $postSaveRepo
    ) {}

    #[Route('', name: 'api_forum_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['slug'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $forum = new Forum();
        $forum->setName($data['name']);
        $forum->setSlug($data['slug']);
        $forum->setDescription($data['description'] ?? null);
        $forum->setIcon($data['icon'] ?? null);
        $forum->setIsOfficial($data['isOfficial'] ?? false);

        $errors = $validator->validate($forum);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($forum);
        $this->em->flush();

        return $this->json([
            'message' => 'Forum created successfully',
            'forum' => [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'slug' => $forum->getSlug(),
                'description' => $forum->getDescription(),
                'icon' => $forum->getIcon(),
                'isOfficial' => $forum->isOfficial(),
                'createdAt' => $forum->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_forum_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if ($user && $user->hasSelectedGames()) {
            // Filtrer les forums selon les jeux sélectionnés par l'utilisateur
            $selectedGameIds = $user->getSelectedGames();
            $forums = $this->forumRepo->findByGameIds($selectedGameIds);
        } else {
            // Si pas connecté ou pas de jeux sélectionnés, afficher tous les forums
            $forums = $this->forumRepo->findBy([], ['name' => 'ASC']);
        }

        $data = array_map(fn(Forum $f) => [
            'id' => $f->getId(),
            'name' => $f->getName(),
            'slug' => $f->getSlug(),
            'description' => $f->getDescription(),
            'icon' => $f->getIcon(),
            'isOfficial' => $f->isOfficial(),
            'gameId' => $f->getGameId(),
            'createdAt' => $f->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $forums);

        return $this->json($data);
    }
    
    #[Route('/{slug}/posts', name: 'api_forum_posts', methods: ['GET'])]
    public function getPosts(string $slug): JsonResponse
    {
        $forum = $this->forumRepo->findOneBySlug($slug);

        if (!$forum) {
            return $this->json(['error' => 'Forum not found'], Response::HTTP_NOT_FOUND);
        }

        $posts = $this->em->getRepository(Post::class)->findBy(
            ['forum' => $forum],
            ['createdAt' => 'DESC'],
            20
        );

        // Récupérer le compteur de commentaires pour chaque post
        $commentRepo = $this->em->getRepository(Comment::class);
        
        $data = array_map(function (Post $post) use ($commentRepo) {
            // Compter les commentaires non supprimés pour ce post
            $commentsCount = $commentRepo->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.post = :post')
                ->andWhere('c.isDeleted = false')
                ->setParameter('post', $post)
                ->getQuery()
                ->getSingleScalarResult();

            // Vérifier si le post est sauvegardé par l'utilisateur connecté
            $isSaved = false;
            if ($this->getUser()) {
                $isSaved = $this->postSaveRepo->isPostSavedByUser($this->getUser(), $post);
            }

            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $post->getSlug(),
                'author' => $post->getAuthor()->getPseudo(),
                'authorAvatar' => $post->getAuthor()->getAvatar(),
                'score' => $post->getScore(),
                'commentsCount' => (int) $commentsCount,
                'isSaved' => $isSaved,
                'content' => $post->getContent(), // Ajouter le contenu pour les previews
                'postType' => $post->getPostType(),
                'linkUrl' => $post->getLinkUrl(),
                'tags' => $post->getTags(),
                'attachments' => $post->getAttachments(),
                'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'isPinned' => $post->isPinned(),
                'isLocked' => $post->isLocked(),
            ];
        }, $posts);

        return $this->json([
            'forum' => [
                'id' => $forum->getId(),
                'name' => $forum->getName(),
                'slug' => $forum->getSlug(),
                'description' => $forum->getDescription(),
            ],
            'posts' => $data
        ]);
    }

    #[Route('/{slug}/posts', name: 'api_forum_create_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createPost(string $slug, Request $request, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $forum = $this->forumRepo->findOneBySlug($slug);
        if (!$forum) {
            return $this->json(['error' => 'Forum not found'], Response::HTTP_NOT_FOUND);
        }

        // Gérer les données JSON ET les fichiers
        $data = [];
        if ($request->isMethod('POST') && $request->getContent() && !$request->files->count()) {
            // Requête JSON pure
            $data = json_decode($request->getContent(), true);
        } else {
            // FormData avec fichiers
            $data = $request->request->all();
        }

        if (!$data || !isset($data['title'], $data['content'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $post = new Post();
        $post->setTitle($data['title']);
        $post->setSlug($this->generateSlug($data['title']));
        $post->setContent($data['content']);
        $post->setAuthor($user);
        $post->setForum($forum);
        
        // Gestion du type de post
        $postType = $data['postType'] ?? 'text';
        $post->setPostType($postType);

        // Gestion du lien externe
        if (!empty($data['linkUrl'])) {
            $post->setLinkUrl($data['linkUrl']);
            if ($postType === 'text') {
                $post->setPostType('link');
            }
        }

        // Gestion des tags
        if (!empty($data['tags'])) {
            $tags = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
            $cleanTags = array_map('trim', array_filter($tags));
            $post->setTags($cleanTags);
        }

        $errors = $validator->validate($post);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder d'abord pour avoir l'ID
        $this->em->persist($post);
        $this->em->flush();

        // Gestion des fichiers uploadés
        $attachments = [];
        $uploadedFiles = $request->files->all();
        
        foreach ($uploadedFiles as $key => $file) {
            if ($file && $file->isValid()) {
                try {
                    if (str_starts_with($key, 'image_')) {
                        // Image de post
                        $filename = $this->fileUploadService->uploadPostImage($file, $post->getId());
                        $attachments[] = [
                            'type' => 'image',
                            'filename' => $filename,
                            'originalName' => $file->getClientOriginalName(),
                            'url' => $this->fileUploadService->getPostImageUrl($filename)
                        ];
                        if ($post->getPostType() === 'text') {
                            $post->setPostType('image');
                        }
                    } else {
                        // Fichier joint
                        $attachmentData = $this->fileUploadService->uploadPostAttachment($file, $post->getId());
                        $attachments[] = array_merge($attachmentData, [
                            'type' => 'file',
                            'url' => $this->fileUploadService->getPostImageUrl($attachmentData['filename'])
                        ]);
                    }
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        if (!empty($attachments)) {
            $post->setAttachments($attachments);
        }

        // Sauvegarder les modifications
        $this->em->flush();

        return $this->json([
            'message' => 'Post created successfully',
            'postId' => $post->getId(),
            'slug' => $post->getSlug(),
            'attachments' => $attachments
        ], Response::HTTP_CREATED);
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $baseSlug = $slug;
        $counter = 1;

        // Assurer l'unicité du slug
        $postRepo = $this->em->getRepository(Post::class);
        while ($postRepo->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}