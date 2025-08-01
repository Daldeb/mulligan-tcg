<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private CommentRepository $commentRepo
    ) {}

    #[Route('/posts/{id}/comments', name: 'api_comment_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function commentPost(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $post = $this->postRepo->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['content'])) {
            return $this->json(['error' => 'Missing content'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthor($this->getUser());
        $comment->setContent($data['content']);

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(['message' => 'Comment added', 'commentId' => $comment->getId()], Response::HTTP_CREATED);
    }

    #[Route('/comments/{id}/comments', name: 'api_comment_reply', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function replyToComment(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $parent = $this->commentRepo->find($id);
        if (!$parent) {
            return $this->json(['error' => 'Parent comment not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || empty($data['content'])) {
            return $this->json(['error' => 'Missing content'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setPost($parent->getPost());
        $comment->setParent($parent);
        $comment->setAuthor($this->getUser());
        $comment->setContent($data['content']);

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json(['message' => 'Reply added', 'commentId' => $comment->getId()], Response::HTTP_CREATED);
    }
}
