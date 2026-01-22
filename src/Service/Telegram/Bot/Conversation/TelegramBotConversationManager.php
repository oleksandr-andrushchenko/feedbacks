<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation;
use App\Model\Telegram\TelegramBotConversationState;
use App\Repository\Telegram\Bot\TelegramBotConversationRepository;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Group\TelegramBotGroupRegistry;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotChatProvider;
use App\Service\Util\Array\ArrayNullFilter;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @todo: use cache to store conversations (one conversation per chat)
 */
class TelegramBotConversationManager
{
    public function __construct(
        private readonly TelegramBotAwareHelper $telegramBotAwareHelper,
        private readonly TelegramBotConversationRepository $telegramBotConversationRepository,
        private readonly EntityManager $entityManager,
        private readonly NormalizerInterface $conversationStateNormalizer,
        private readonly DenormalizerInterface $conversationStateDenormalizer,
        private readonly ArrayNullFilter $arrayNullFilter,
        private readonly TelegramBotGroupRegistry $telegramBotGroupRegistry,
        private readonly TelegramBotChatProvider $telegramBotChatProvider,
        private readonly LoggerInterface $logger,
        private readonly bool $saveRequests = false,
    )
    {
    }

    public function getCurrentTelegramConversation(TelegramBot $bot): ?TelegramBotConversation
    {
        $messengerUser = $bot->getMessengerUser();
        $chatId = $this->telegramBotChatProvider->getTelegramChatByUpdate($bot->getUpdate())?->getId();

        $this->logger?->debug(__METHOD__, [
            'messengerUser' => $messengerUser,
            'chatId' => $chatId,
        ]);

        if ($messengerUser === null || $chatId === null) {
            return null;
        }

        $hash = $this->createTelegramConversationHash($messengerUser->getId(), $chatId, $bot->getEntity()->getId());
        $conversation = $this->telegramBotConversationRepository->findOneNonDeletedByHash($hash);

        $this->logger?->debug(__METHOD__, [
            'hash' => $hash,
            'conversation' => $conversation,
        ]);

        return $conversation;
    }

    public function startTelegramConversation(TelegramBot $bot, string $class): void
    {
        $entity = $this->createTelegramConversation($bot, $class);

        $this->executeConversation($bot, $entity, 'invoke');
    }

    public function createTelegramConversationHash(string $messengerUserId, int|string $chatId, string $botId): string
    {
        return md5($messengerUserId . '-' . $chatId . '-' . $botId);
    }

    public function createTelegramConversation(TelegramBot $bot, string $class, TelegramBotConversationState $state = null): TelegramBotConversation
    {
        $messengerUserId = $bot->getMessengerUser()->getId();
        $chatId = $this->telegramBotChatProvider->getTelegramChatByUpdate($bot->getUpdate())?->getId();
        $botId = $bot->getEntity()->getId();
        $hash = $this->createTelegramConversationHash($messengerUserId, $chatId, $botId);

        $entity = new TelegramBotConversation(
            $hash,
            $messengerUserId,
            (string) $chatId,
            $botId,
            $class,
            $state === null ? null : $this->normalizeState($state),
        );
        $this->entityManager->persist($entity);

        return $entity;
    }

    public function executeTelegramConversation(TelegramBot $bot, string $class, TelegramBotConversationState $state, string $method): void
    {
        $entity = $this->createTelegramConversation($bot, $class, $state);

        $this->executeConversation($bot, $entity, $method);
    }

    public function continueTelegramConversation(TelegramBot $bot, TelegramBotConversation $conversation): void
    {
        $this->executeConversation($bot, $conversation, 'invoke');
    }

    public function denormalizeState(?array $state, string $class): TelegramBotConversationState
    {
        if ($state === null) {
            return new $class();
        }

        return $this->conversationStateDenormalizer->denormalize($state, $class);
    }

    public function normalizeState(TelegramBotConversationState $state): array
    {
        $normalized = $this->conversationStateNormalizer->normalize($state);

        return $this->arrayNullFilter->filterNulls($normalized);
    }

    public function stopCurrentTelegramConversation(TelegramBot $bot): void
    {
        $conversation = $this->getCurrentTelegramConversation($bot);

        if ($conversation === null) {
            return;
        }

        $this->stopTelegramConversation($conversation);
    }

    public function stopTelegramConversation(TelegramBotConversation $entity): void
    {
        if ($this->saveRequests) {
            $entity->setDeletedAt(new DateTimeImmutable());
            $entity->setExpireAt((new DateTimeImmutable())->setTimestamp(time() + 7 * 24 * 60 * 60));
        } else {
            $this->entityManager->remove($entity);
        }
    }

    public function executeConversation(TelegramBot $bot, TelegramBotConversation $entity, string $method): TelegramBotConversationInterface
    {
        $group = $this->telegramBotGroupRegistry->getTelegramGroup($bot->getEntity()->getGroup());
        // todo: throw not found exception
        $conversation = $group->getTelegramConversationFactory()->createTelegramConversation($entity->getClass());

        $state = $this->denormalizeState($entity->getState(), get_class($conversation->getState()));
        $conversation->setState($state);

        $tg = $this->telegramBotAwareHelper->withTelegramBot($bot);

        $conversation->$method($tg, $entity);

        $state = $this->normalizeState($conversation->getState());

        $entity->setState($state);

        $this->logger->debug(__METHOD__, [
            'conv_hash' => $entity->getHash(),
            'conv_deleted_at' => $entity->getDeletedAt(),
            'conv_expire_at' => $entity->getExpireAt(),
            'state' => $state,
        ]);

        return $conversation;
    }
}