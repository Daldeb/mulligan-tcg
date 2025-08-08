<?php

namespace App\Controller\Api;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\EventRegistration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events', name: 'api_events_')]
#[IsGranted('ROLE_USER')]
class FollowEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('/{id}/follow', name: 'follow', methods: ['POST'])]
    public function followEvent(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->followEvent($id);
        $this->em->flush();

        return $this->json([
            'message' => 'Événement ajouté au suivi',
            'is_following' => true
        ]);
    }

    #[Route('/{id}/unfollow', name: 'unfollow', methods: ['POST'])]
    public function unfollowEvent(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->unfollowEvent($id);
        $this->em->flush();

        return $this->json([
            'message' => 'Événement retiré du suivi',
            'is_following' => false
        ]);
    }

    #[Route('/user/followed', name: 'followed', methods: ['GET'])]
    public function getFollowedEvents(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $followedIds = $user->getFollowedEvents();
        
        if (empty($followedIds)) {
            return $this->json(['events' => []]);
        }

        // Nettoyer les IDs inexistants
        $events = $this->eventRepository->findBy(['id' => $followedIds]);
        $existingIds = array_map(fn($e) => $e->getId(), $events);
        
        if (count($existingIds) !== count($followedIds)) {
            $user->setFollowedEvents($existingIds);
            $this->em->flush();
        }

        return $this->json([
            'events' => array_map([$this, 'serializeEvent'], $events)
        ]);
    }

    private function serializeEvent($event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'start_date' => $event->getStartDate()?->format('c'),
            'status' => $event->getStatus(),
            'current_participants' => $event->getCurrentParticipants(),
            'max_participants' => $event->getMaxParticipants()
        ];
    }

    #[Route('/user/participating', name: 'participating', methods: ['GET'])]
    public function getParticipatingEvents(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les inscriptions de l'utilisateur
        $registrations = $this->em->getRepository(EventRegistration::class)
            ->findBy(['user' => $user], ['registeredAt' => 'DESC']);
        
        $events = array_map(function($registration) {
            return $this->serializeEvent($registration->getEvent());
        }, $registrations);

        return $this->json([
            'events' => $events
        ]);
    }
}