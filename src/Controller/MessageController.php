<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use App\Repository\ParticipantRepository;
use Symfony\Component\Mercure\Update;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/messages", name="message.")
 */
class MessageController extends AbstractController {

    const ATTRIBUTES_TO_SERIALIZE = ["id", "content", "createdAt", "mine"];

    public function __construct(EntityManagerInterface $entityManagerInterface, MessageRepository $messageRepository,
    UserRepository $userRepository, ParticipantRepository $participantRepository) {
        $this->entityManager = $entityManagerInterface;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->participantRepository = $participantRepository;
    }

    /**
     * @Route("/{id}", name="getMessages", methods={"GET"})
     */
    public function index(Request $request, Conversation $conversation) {
        $this->denyAccessUnlessGranted('view', $conversation);

        $messages = $this->messageRepository->findMessageByConversationId(
            $conversation->getId()
        );

        array_map (function ($message) {
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId()
                ? true:false
            );
        }, $messages);

        dump($messages);

        return $this->json($messages, Response::HTTP_OK, [], [
            "attributes" => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    /**
     * @Route("/{id}", name="newMessage", methods={"POST"})
     */
    public function newMessage(Request $request, Conversation $conversation, SerializerInterface $serializer, PublisherInterface $publisher)
    {
        $user = $this->getUser();

        $recipient = $this->participantRepository->findParticipantByConversationIdAndUserId(
            $conversation->getId(),
            $user->getId()
        );

        $content = $request->get('content', null);
        $message = new Message();
        $message->setContent($content);
        $message->setUser($user);

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
        $message->setMine(false);
        $messageSerialized = $serializer->serialize($message, 'json', [
            'attributes' => ['id', 'content', 'createdAt', 'mine', 'conversation' => ['id']]
        ]);
        $update = new Update(
            [
                sprintf("/conversations/%s/%s", $recipient->getUser()->getUsername(), $conversation->getId()),
                sprintf("/conversations/%s", $recipient->getUser()->getUsername())
            ],
            $messageSerialized,
            true,
        );
        $publisher($update);

        $message->setMine(true);
        return $this->json($message, Response::HTTP_CREATED, [], [
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
