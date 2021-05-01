<?php

namespace App\Controller;

use App\Repository\ConversationRepository;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(ConversationRepository  $conversationRepository) {
        $ID = $this->getUser()->getId();
        $username = $this->getUser()->getUsername();
        $conversations = $conversationRepository->findConversationByUser($ID);
        $conversationsIds = array_map(function ($conversation) {
            return sprintf("/conversations/%s/%d", $this->getUser()->getUsername(), $conversation["conversationId"]);
        }, $conversations);
        dump($conversationsIds);
        
        $token = (new Builder())
            ->withClaim('mercure', ['subscribe' => [sprintf("/conversations/%s", $username), ...$conversationsIds ]])
            ->getToken(new Sha256(), new Key($this->getParameter('mercure_secret_key')));

        $cookie = Cookie::create('mercureAuthorization')
            ->withValue($token)
            ->withPath('/.well-known/mercure')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('strict')
        ;

        $response = $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
        ]);
        $response->headers->setCookie($cookie);
        return $response;
    }
}
