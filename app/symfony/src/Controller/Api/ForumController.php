<?php

namespace App\Controller\Api;

use App\Entity\Forum;
use App\Entity\Post;
use App\Repository\ForumRepository;
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
        private ForumRepository $forumRepo
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
        $forums = $this->forumRepo->findBy([], ['name' => 'ASC']);

        $data = array_map(fn(Forum $f) => [
            'id' => $f->getId(),
            'name' => $f->getName(),
            'slug' => $f->getSlug(),
            'description' => $f->getDescription(),
            'icon' => $f->getIcon(),
            'isOfficial' => $f->isOfficial(),
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

        $data = array_map(function (Post $post) {
            return [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $post->getSlug(),
                'author' => $post->getAuthor()->getPseudo(),
                'score' => $post->getScore(),
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

}
