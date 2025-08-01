<?php

namespace App\Controller\Api;

use App\Entity\Post;
use App\Entity\Forum;
use App\Repository\ForumRepository;
use App\Repository\PostRepository;
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
        private ForumRepository $forumRepo
    ) {}

    #[Route('', name: 'api_post_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'], $data['content'], $data['forumSlug'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $forum = $this->forumRepo->findOneBySlug($data['forumSlug']);
        if (!$forum) {
            return $this->json(['error' => 'Forum not found'], Response::HTTP_NOT_FOUND);
        }

        $post = new Post();
        $post->setTitle($data['title']);
        $post->setSlug(preg_replace('/[^a-z0-9\-]+/', '-', strtolower($data['title'])));
        $post->setContent($data['content']);
        $post->setAuthor($this->getUser());
        $post->setForum($forum);

        $errors = $validator->validate($post);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($post);
        $this->em->flush();

        return $this->json([
            'message' => 'Post created successfully',
            'postId' => $post->getId(),
            'slug' => $post->getSlug()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_post_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // On récupère tous les commentaires plats pour l’instant
        $comments = $this->em->getRepository(\App\Entity\Comment::class)
            ->findBy(['post' => $post], ['createdAt' => 'ASC']);

        $commentData = array_map(function ($comment) {
            return [
                'id' => $comment->getId(),
                'content' => $comment->isDeleted() ? '[Commentaire supprimé]' : $comment->getContent(),
                'author' => $comment->getAuthor()->getPseudo(),
                'score' => $comment->getScore(),
                'parentId' => $comment->getParent()?->getId(),
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $comments);

        return $this->json([
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->isDeleted() ? '[Post supprimé]' : $post->getContent(),
            'author' => $post->getAuthor()->getPseudo(),
            'score' => $post->getScore(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'comments' => $commentData
        ]);
    }
}
