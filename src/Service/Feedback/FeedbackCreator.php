<?php
declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Factory\Feedback\FeedbackFactory;
use App\Factory\Feedback\SearchTermFeedbackFactory;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Model\Telegram\TelegramMedia;
use App\Model\Telegram\TelegramPhoto;
use App\Model\Telegram\TelegramVideo;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTerm\SearchTermUpserter;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use App\Service\Telegram\TelegramMediaCreator;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly EntityManager $entityManager,
        private readonly Validator $validator,
        private readonly SearchTermUpserter $searchTermUpserter,
        private readonly MessageBusInterface $eventBus,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserService $messengerUserService,
        private readonly SearchTermFeedbackFactory $searchTermFeedbackFactory,
        private readonly FeedbackFactory $feedbackFactory,
        private readonly FeedbackService $feedbackService,
        private readonly TelegramMediaCreator $telegramMediaCreator,
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly NormalizerInterface $telegramMediaNormalizer,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->feedbackCommandOptions;
    }

    /**
     * @throws FeedbackOnOneselfException
     * @throws ValidatorException
     * @throws ExceptionInterface
     */
    public function createFeedback(FeedbackTransfer $transfer): Feedback
    {
        $this->validator->validate($transfer);

        $this->checkSearchTermUser($transfer);

        $feedback = $this->constructFeedback($transfer);
        $this->entityManager->persist($feedback);

        if ($this->entityManager->getConfig()->isDynamodb()) {
            $searchTerms = $this->feedbackService->getSearchTerms($feedback);
            foreach ($searchTerms as $searchTerm) {
                $extraSearchTerms = array_values(array_filter($searchTerms, static fn ($otherSearchTerm) => $otherSearchTerm->getId() !== $searchTerm->getId()));
                $searchTermFeedback = $this->searchTermFeedbackFactory->createSearchTermFeedback($searchTerm, $feedback, empty($extraSearchTerms) ? null : $extraSearchTerms);
                $this->entityManager->persist($searchTermFeedback);
            }
        }

        $this->eventBus->dispatch(new ActivityEvent(entity: $feedback, action: 'created'));
        $this->eventBus->dispatch(new FeedbackCreatedEvent(feedback: $feedback));

        return $feedback;
    }

    /**
     * @throws FeedbackOnOneselfException
     */
    private function checkSearchTermUser(FeedbackTransfer $transfer): void
    {
        $messengerUser = $transfer->getMessengerUser();

        foreach ($transfer->getSearchTerms()->getItemsAsArray() as $searchTerm) {
            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && strcmp(mb_strtolower($messengerUser->getUsername()), mb_strtolower($searchTerm->getNormalizedText() ?? $searchTerm->getText())) === 0
                && $messengerUser->getMessenger() === $messenger
            ) {
                throw new FeedbackOnOneselfException($messengerUser);
            }
        }
    }

    public function constructFeedback(FeedbackTransfer $transfer): Feedback
    {
        $messengerUser = $transfer->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

        $searchTerms = [];
        foreach ($transfer->getSearchTerms()->getItemsAsArray() as $searchTerm) {
            $searchTerm = $this->searchTermUpserter->upsertSearchTerm($searchTerm);
            $searchTerm->setMessengerUser($messengerUser);
            $searchTerms[] = $searchTerm;
        }

        return $this->feedbackFactory->createFeedback(
            $user,
            $messengerUser,
            $searchTerms,
            $transfer->getRating(),
            $transfer->getDescription(),
            $this->createMedia($transfer),
            $transfer->getTelegramBot()
        );
    }

    private function createMedia(FeedbackTransfer $transfer): ?array
    {
        $media = $transfer->getMedia();
        $telegramBot = $transfer->getTelegramBot();

        if (empty($media)) {
            return null;
        }

        if ($telegramBot === null) {
            return $this->normalizeMedia($media);
        }

        $bot = $this->telegramBotRegistry->getTelegramBot($telegramBot);
        $result = [];

        foreach ($media as $item) {
            $result[] = $item instanceof TelegramPhoto || $item instanceof TelegramVideo
                ? $this->telegramMediaCreator->createTelegramMedia($bot, $item)
                : $item;
        }

        return $this->normalizeMedia($result);
    }

    private function normalizeMedia(array $media): ?array
    {
        return array_values(array_filter(array_map(
            fn (mixed $item): ?array => match (true) {
                $item instanceof TelegramMedia => $this->telegramMediaNormalizer->normalize($item),
                is_array($item) => $item,
                default => null,
            },
            $media
        ))) ?: null;
    }
}
