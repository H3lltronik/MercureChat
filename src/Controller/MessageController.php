<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/messages", name="message.")
 */
class MessageController extends AbstractController {

    const ATTRIBUTES_TO_SERIALIZE = ["id", "content", "createdAt", "mine"];

    public function __construct(EntityManagerInterface $entityManagerInterface, MessageRepository $messageRepository,
    UserRepository $userRepository) {
        $this->entityManagerInterface = $entityManagerInterface;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
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

        dd($messages);

        return $this->json($messages, Response::HTTP_OK, [], [
            "attributes" => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    /**
     * @Route("/{id}", name="newMessage", methods={"POST"})
     */
    public function newMessage(Request $request, Conversation $conversation) {
        $user = $this->getUser();
        $content = $request->get('content', null);
          
        $message = new Message();
        $message->setContent($content);
        // $message->setUser($user);
        $message->setUser($this->userRepository->findOneBy(["id" => 2]));
        $message->setMine(true);
        
        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $this->entityManagerInterface->getConnection()->beginTransaction();
        try {
            $this->entityManagerInterface->persist($message);
            $this->entityManagerInterface->persist($conversation);
            $this->entityManagerInterface->flush();
            $this->entityManagerInterface->commit();
        } catch (\Throwable $th) {
            $this->entityManagerInterface->rollback();
            throw $th;
        }

        return $this->json($message, Response::HTTP_CREATED, [], [
            "attributes" => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
