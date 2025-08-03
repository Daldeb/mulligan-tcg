<?php

namespace App\Controller\Api;

use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\User;
use App\Entity\PostVote;
use App\Entity\PostSave;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
use App\Repository\PostVoteRepository;
use App\Repository\PostSaveRepository;
use App\Repository\CommentVoteRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/posts')]
class PostController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private ForumRepository $forumRepo,
        private FileUploadService $fileUploadService,
        private PostVoteRepository $postVoteRepo,
        private PostSaveRepository $postSaveRepo,
        private CommentVoteRepository $commentVoteRepo
    ) {}

    #[Route('', name: 'api_post_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Gérer les données JSON ET les fichiers
        $data = [];
        if ($request->isMethod('POST') && $request->getContent() && !$request->files->count()) {
            // Requête JSON pure
            $data = json_decode($request->getContent(), true);
        } else {
            // FormData avec fichiers
            $data = $request->request->all();
        }

        if (!$data || !isset($data['title'], $data['content'], $data['forumSlug'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $forum = $this->forumRepo->findOneBySlug($data['forumSlug']);
        if (!$forum) {
            return $this->json(['error' => 'Forum not found'], Response::HTTP_NOT_FOUND);
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
            $tags = is_string($data['tags']) ? explode(',', $data['tags']) : $data['tags'];
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
            $this->em->flush(); // Sauvegarder à nouveau
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

    #[Route('/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer le vote de l'utilisateur actuel (si connecté)
        $userVote = null;
        $isSaved = false;
        if ($this->getUser()) {
            $vote = $this->postVoteRepo->findOneBy([
                'user' => $this->getUser(),
                'post' => $post
            ]);
            $userVote = $vote ? $vote->getType() : null;
            
            // Vérifier si le post est sauvegardé par l'utilisateur
            $isSaved = $this->postSaveRepo->isPostSavedByUser($this->getUser(), $post);
        }

        // On récupère tous les commentaires plats pour l'instant
        $comments = $this->em->getRepository(\App\Entity\Comment::class)
            ->findBy(['post' => $post], ['createdAt' => 'ASC']);

        $commentData = array_map(function ($comment) {
            // Calculer les votes pour chaque commentaire
            $upvotes = $this->commentVoteRepo->countVotesForComment($comment->getId(), 'UP');
            $downvotes = $this->commentVoteRepo->countVotesForComment($comment->getId(), 'DOWN');
            
            // Récupérer le vote de l'utilisateur pour ce commentaire (si connecté)
            $userCommentVote = null;
            if ($this->getUser()) {
                $vote = $this->commentVoteRepo->findOneBy([
                    'user' => $this->getUser(),
                    'comment' => $comment
                ]);
                $userCommentVote = $vote ? $vote->getType() : null;
            }
            
            return [
                'id' => $comment->getId(),
                'content' => $comment->isDeleted() ? '[Commentaire supprimé]' : $comment->getContent(),
                'author' => $comment->getAuthor()->getPseudo(),
                'authorAvatar' => $comment->getAuthor()->getAvatar(),
                'score' => $comment->getScore(),
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'userVote' => $userCommentVote,
                'parentId' => $comment->getParent()?->getId(),
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $comments);

        return $this->json([
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->isDeleted() ? '[Post supprimé]' : $post->getContent(),
            'author' => $post->getAuthor()->getPseudo(),
            'authorAvatar' => $post->getAuthor()->getAvatar(),
            'score' => $post->getScore(),
            'userVote' => $userVote,
            'isSaved' => $isSaved,
            'postType' => $post->getPostType(),
            'linkUrl' => $post->getLinkUrl(),
            'linkPreview' => $post->getLinkPreview(),
            'tags' => $post->getTags(),
            'attachments' => $post->getAttachments(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'forum' => [
                'id' => $post->getForum()->getId(),
                'name' => $post->getForum()->getName(),
                'slug' => $post->getForum()->getSlug(),
                'description' => $post->getForum()->getDescription()
            ],
            'comments' => $commentData
        ]);
    }

    #[Route('/{id}/vote', name: 'api_post_vote', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function vote(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $voteType = $data['type'] ?? null;

        if (!in_array($voteType, [PostVote::TYPE_UP, PostVote::TYPE_DOWN])) {
            return $this->json(['error' => 'Invalid vote type'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur a déjà voté
        $existingVote = $this->postVoteRepo->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        if ($existingVote) {
            if ($existingVote->getType() === $voteType) {
                // Même vote = ne rien faire (pas de toggle)
                $userVote = $voteType; // Garde le vote existant
            } else {
                // Vote opposé = neutraliser (supprimer le vote)
                $this->em->remove($existingVote);
                $userVote = null;
            }
        } else {
            // Nouveau vote
            $vote = new PostVote();
            $vote->setUser($user);
            $vote->setPost($post);
            $vote->setType($voteType);
            $this->em->persist($vote);
            $userVote = $voteType;
        }

        $this->em->flush();

        // Recalculer le score
        $this->updatePostScore($post);

        return $this->json([
            'success' => true,
            'newScore' => $post->getScore(),
            'userVote' => $userVote
        ]);
    }

    #[Route('/{id}/vote', name: 'api_post_remove_vote', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeVote(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $existingVote = $this->postVoteRepo->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        if ($existingVote) {
            $this->em->remove($existingVote);
            $this->em->flush();
            $this->updatePostScore($post);
        }

        return $this->json([
            'success' => true,
            'newScore' => $post->getScore(),
            'userVote' => null
        ]);
    }

    private function updatePostScore(Post $post): void
    {
        $upvotes = $this->postVoteRepo->countVotesForPost($post->getId(), PostVote::TYPE_UP);
        $downvotes = $this->postVoteRepo->countVotesForPost($post->getId(), PostVote::TYPE_DOWN);
        
        $score = $upvotes - $downvotes;
        $post->setScore($score);
        
        $this->em->flush();
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $baseSlug = $slug;
        $counter = 1;

        // Assurer l'unicité du slug
        while ($this->postRepo->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    #[Route('/{id}/save', name: 'api_post_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function savePost(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si le post est déjà sauvegardé
        $existingSave = $this->postSaveRepo->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        if ($existingSave) {
            // Déjà sauvegardé = désauvegarder (toggle)
            $this->em->remove($existingSave);
            $isSaved = false;
            $message = 'Post retiré des sauvegardes';
        } else {
            // Pas encore sauvegardé = sauvegarder
            $postSave = new PostSave();
            $postSave->setUser($user);
            $postSave->setPost($post);
            $this->em->persist($postSave);
            $isSaved = true;
            $message = 'Post ajouté aux sauvegardes';
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'isSaved' => $isSaved,
            'message' => $message
        ]);
    }

    #[Route('/{id}/save', name: 'api_post_unsave', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function unsavePost(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $existingSave = $this->postSaveRepo->findOneBy([
            'user' => $user,
            'post' => $post
        ]);

        if ($existingSave) {
            $this->em->remove($existingSave);
            $this->em->flush();
        }

        return $this->json([
            'success' => true,
            'isSaved' => false,
            'message' => 'Post retiré des sauvegardes'
        ]);
    }
}