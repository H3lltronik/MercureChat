<?php

namespace App\Security\Voter;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ConversationVoter extends Voter {
    const VIEW = 'view';

    public function __construct(ConversationRepository $conversationRepository) {
        $this->conversationRepository = $conversationRepository;
    }


    protected function supports(string $attribute, $subject) {
        return $attribute == self::VIEW && $subject instanceof Conversation;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token) {
        $result = $this->conversationRepository->checkIfUserisParticipant(
            $subject->getId(),
            $token->getUser()->getId()
        );
        // dd($result);

        // dd($attribute, $subject, $token);

        return !!$result;
    }

}