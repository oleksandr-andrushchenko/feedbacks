<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Message\Event\ActivityEvent;
use App\Repository\Messenger\MessengerUserRepository;
use App\Service\IdGenerator;
use App\Service\ORM\EntityManager;
use App\Transfer\Messenger\MessengerUserTransfer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerUserUpserter
{
    public function __construct(
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly EntityManager $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
        private readonly ?LoggerInterface $logger = null,
    )
    {
    }

    public function upsertMessengerUser(MessengerUserTransfer $transfer): MessengerUser
    {
        $messengerUser = $this->messengerUserRepository->findOneByMessengerAndIdentifier(
            $transfer->getMessenger(),
            $transfer->getId(),
        );

        $this->logger?->debug(__METHOD__, [
            'messengerUser' => $messengerUser,
        ]);

        $created = false;

        if ($messengerUser === null) {
            $created = true;
            $messengerUser = new MessengerUser(
                $this->idGenerator->generateId(),
                $transfer->getMessenger(),
                $transfer->getId()
            );
            $this->logger?->debug(__METHOD__, [
                'messengerUser' => $messengerUser,
            ]);
            $this->entityManager->persist($messengerUser);
        }

        if (!empty($transfer->getUsername())) {
            $messengerUser->setUsername($transfer->getUsername());
        }
        if (empty($messengerUser->getName()) && !empty($transfer->getName())) {
            $messengerUser->setName($transfer->getName());
        }
        if (!empty($transfer->getTelegramBotId())) {
            $messengerUser->addTelegramBotId($transfer->getTelegramBotId());
        }

        $this->logger?->debug(__METHOD__, [
            'messengerUser' => $messengerUser,
        ]);

        if ($created) {
            $this->eventBus->dispatch(new ActivityEvent(entity: $messengerUser, action: 'created'));
        }

        return $messengerUser;
    }
}
