<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DebugController extends AbstractController
{
    #[Route('/debug/db', methods: ['GET'])]
    public function dbCheck(EntityManagerInterface $em): JsonResponse
    {
        try {
            $count = $em->createQuery('SELECT COUNT(c.id) FROM App\Entity\HearthstoneCard c')->getSingleScalarResult();
            return new JsonResponse(['count' => (int) $count]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/debug/db-write', methods: ['POST'])]
    public function dbWriteTest(EntityManagerInterface $em): JsonResponse
    {
        $card = new \App\Entity\HearthstoneCard();
        $card->setId('DEBUG_CARD_' . uniqid());
        $card->setDbfId(999999);
        $card->setName('Debug Card');
        $card->setCardSet('CORE');
        $card->setRarity('COMMON');
        $card->setType('SPELL');
        $card->setCardClass('NEUTRAL');
        $card->setImagePath(null);
        $card->setData([]);
        $card->setImportedAt(new \DateTime());

        $em->persist($card);
        $em->flush();

        return new JsonResponse(['status' => 'inserted', 'id' => $card->getId()]);
    }

}
