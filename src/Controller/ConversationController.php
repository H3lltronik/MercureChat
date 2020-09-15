<?php

namespace App\Controller;

use Exception;
use Lcobucci\JWT\Builder;
use App\Entity\Participant;
use App\Entity\Conversation;
use Lcobucci\JWT\Signer\Key;
use App\Repository\UserRepository;
use Symfony\Component\WebLink\Link;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\Mercure\Update;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @Route("/conversation", name="conversation.")
 */
class ConversationController extends AbstractController {

    public function __construct(UserRepository $userRepository, 
        EntityManagerInterface $entityManagerInterface,
        ConversationRepository $conversationRepository) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManagerInterface;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * @Route("/{id}", name="getConversation")
     */
    public function index(Request $request, int $id) {
        $otherUser = $request->get('otherUser', 0);
        $otherUser = $this->userRepository->find($id);

        if (is_null($otherUser)) {
            throw new \Exception("The user was not found");
        }
        
        if ($otherUser->getId() === $this->getUser()->getId()) {
            throw new \Exception("XD");
        }

        $conversation = $this->conversationRepository->findConversationByParticipants(
            $otherUser->getId(),
            $this->getUser()->getId(),
        );

        if (count($conversation)) {
            throw new \Exception("The convesation already exists");
        }

        $conversation = new Conversation();

        $participant = new Participant();
        $participant->setUser($this->getUser());
        $participant->setConversation($conversation);

        $otherParticipant = new Participant();
        $otherParticipant->setUser($otherUser);
        $otherParticipant->setConversation($conversation);

        $this->entityManager->getConnection ()->beginTransaction();
        try {
            $this->entityManager->persist($conversation);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($otherParticipant);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }

        
        dump($conversation);
        return $this->json([
            'id' => $conversation->getId()
        ], Response::HTTP_CREATED, [], []);
    }

    /**
     * @Route("/", name="getConversation", methods={"GET"})
     */
    public function getConvs(PublisherInterface $publisher) {
        $conversations = $this->conversationRepository->findConversationByUser($this->getUser()->getId());

        $update = new Update(
            'test',
            json_encode(['status' => 'EVELYN!']),
            true,
        );

        // The Publisher service is an invokable object
        $publisher($update);

        return $this->json($conversations);
    }

    /**
     * @Route("/index", name="getConversationFront", methods={"GET"})
     */
    public function front(Request $request) {
        $conversations = $this->conversationRepository->findConversationByUser($this->getUser()->getId());

        // This parameter is automatically created by the MercureBundle
        $hubUrl = $this->getParameter('mercure.default_hub');

        // Link: <http://localhost:3000/.well-known/mercure>; rel="mercure"
        $this->addLink($request, new Link('mercure', $hubUrl));

        $token = (new Builder())
        ->withClaim('mercure', ['subscribe' => "test"])
        ->getToken(
            new Sha256(),
            new Key($this->getParameter('mercure_secret_key'))
        );

        $response = $this->render('conversation/index.html.twig');

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->setCookie(Cookie::create('mercureAuthorization', $token, 0, ''));
        
        // $response->headers->setCookie(
        //     new Cookie(
        //         'mercureAuthorization',
        //         $token,
        //         (new \DateTime())
        //         ->add(new \DateInterval('PT2H')),
        //         '/.well-known/mercure',
        //         null,
        //         false,
        //         true,
        //         false,
        //         "strict"
        //     )
        // );

        return $response;
    }

    /**
     * @Route("/discover", name="discover")
     */
    public function discover(PublisherInterface $publisher, Request $request) {
        // This parameter is automatically created by the MercureBundle
        $hubUrl = $this->getParameter('mercure.default_hub');

        // Link: <http://localhost:3000/.well-known/mercure>; rel="mercure"
        $this->addLink($request, new Link('mercure', $hubUrl));

        return $this->json([
            '@id' => '/books/1',
            'availability' => 'https://schema.org/InStock',
        ]);
    }
}
