<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
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
        private CommentRepository $commentRepo,
        private CommentVoteRepository $commentVoteRepo
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

    #[Route('/comments/{id}/vote', name: 'api_comment_vote', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function vote(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $comment = $this->commentRepo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $voteType = $data['type'] ?? null;

        if (!in_array($voteType, [CommentVote::TYPE_UP, CommentVote::TYPE_DOWN])) {
            return $this->json(['error' => 'Invalid vote type'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur a déjà voté
        $existingVote = $this->commentVoteRepo->findOneBy([
            'user' => $user,
            'comment' => $comment
        ]);

        $userVote = null;

        if ($existingVote) {
            if ($existingVote->getType() === $voteType) {
                // Même vote = ne rien faire (pas de toggle) - COMPORTEMENT REDDIT
                $userVote = $voteType; // Garde le vote existant
            } else {
                // Vote opposé = neutraliser (supprimer le vote) - COMPORTEMENT REDDIT
                $this->em->remove($existingVote);
                $userVote = null;
            }
        } else {
            // Nouveau vote
            $vote = new CommentVote();
            $vote->setUser($user);
            $vote->setComment($comment);
            $vote->setType($voteType);
            $this->em->persist($vote);
            $userVote = $voteType;
        }

        $this->em->flush();

        // Recalculer le score
        $this->updateCommentScore($comment);

        return $this->json([
            'success' => true,
            'newScore' => $comment->getScore(),
            'userVote' => $userVote
        ]);
    }

    #[Route('/comments/{id}/vote', name: 'api_comment_remove_vote', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeVote(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $comment = $this->commentRepo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        $existingVote = $this->commentVoteRepo->findOneBy([
            'user' => $user,
            'comment' => $comment
        ]);

        if ($existingVote) {
            $this->em->remove($existingVote);
            $this->em->flush();
            $this->updateCommentScore($comment);
        }

        return $this->json([
            'success' => true,
            'newScore' => $comment->getScore(),
            'userVote' => null
        ]);
    }

    #[Route('/comments/{id}', name: 'api_comment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $comment = $this->commentRepo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        if ($comment->isDeleted()) {
            return $this->json(['error' => 'Comment already deleted'], Response::HTTP_GONE);
        }

        if (!$comment->canBeDeletedBy($user)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Marquer le commentaire comme supprimé (garde les réponses intactes)
        $comment->markAsDeleted($user);
        $comment->updateTimestamp();
        
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    private function updateCommentScore(Comment $comment): void
    {
        $upvotes = $this->commentVoteRepo->countVotesForComment($comment->getId(), CommentVote::TYPE_UP);
        $downvotes = $this->commentVoteRepo->countVotesForComment($comment->getId(), CommentVote::TYPE_DOWN);
        
        $score = $upvotes - $downvotes;
        $comment->setScore($score);
        
        $this->em->flush();
    }
}