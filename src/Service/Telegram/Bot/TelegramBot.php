<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot as TelegramBotEntity;
use App\Entity\User\User;
use App\Exception\Telegram\Bot\TelegramBotException;
use App\Service\Messenger\MessengerUserService;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\InvalidBotTokenException;
use Longman\TelegramBot\Exception\TelegramException as InnerTelegramException;
use Longman\TelegramBot\Request as TelegramRequest;
use Longman\TelegramBot\Telegram as TelegramClient;
use Longman\TelegramBot\TelegramLog;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @method ServerResponse setWebhook(string $url, array $data = [])
 * @method ServerResponse getMyCommands(array $data)
 * @method ServerResponse setMyCommands(array $data)
 * @method ServerResponse deleteMyCommands(array $data)
 * @method ServerResponse getWebhookInfo()
 * @method ServerResponse deleteWebhook(array $data = [])
 * @method ServerResponse sendMessage(array $data)
 * @method ServerResponse deleteMessage(array $data)
 * @method ServerResponse emptyResponse()
 * @method ServerResponse getUserProfilePhotos(array $data)
 * @method ServerResponse getMyName(array $data)
 * @method ServerResponse setMyName(array $data)
 * @method ServerResponse getMyDescription(array $data)
 * @method ServerResponse setMyDescription(array $data)
 * @method ServerResponse getMyShortDescription(array $data)
 * @method ServerResponse setMyShortDescription(array $data)
 * @method ServerResponse setChatMenuButton(array $data)
 * @method ServerResponse sendChatAction(array $data)
 * @method ServerResponse sendInvoice(array $data)
 * @method ServerResponse createInvoiceLink(array $data)
 * @method ServerResponse answerPreCheckoutQuery(array $data)
 * @method ServerResponse leaveChat(array $data)
 */
class TelegramBot
{
    private ?Update $update;
    private ?MessengerUser $messengerUser;

    public function __construct(
        private readonly TelegramBotEntity $telegramBot,
        private readonly TelegramBotClientRegistry $telegramBotClientRegistry,
        private readonly TelegramBotRequestChecker $telegramBotRequestChecker,
        private readonly LoggerInterface $logger,
        private readonly MessengerUserService $messengerUserService,
    )
    {
        $this->update = null;
        $this->messengerUser = null;
    }

    public function getEntity(): TelegramBotEntity
    {
        return $this->telegramBot;
    }

    public function getUpdate(): ?Update
    {
        return $this->update;
    }

    public function setUpdate(?Update $update): static
    {
        $this->update = $update;

        return $this;
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getUser(): ?User
    {
        return $this->messengerUserService->getUser($this->messengerUser);
    }

    public function setMessengerUser(?MessengerUser $messengerUser): static
    {
        $this->messengerUser = $messengerUser;

        return $this;
    }

    public function primary(): bool
    {
        return $this->getEntity()->primary();
    }

    public function deleted(): bool
    {
        return $this->getEntity()->getDeletedAt() !== null;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws TelegramBotException
     */
    public function __call(string $name, array $arguments): mixed
    {
        $request = $this->telegramBotRequestChecker->checkTelegramRequest($this, $name, $arguments[0] ?? null);
        $this->logger->debug(__METHOD__, [
            'name' => $name,
            'arguments' => $arguments,
        ]);
        $response = $this->request($name, $arguments);

        if ($response instanceof ServerResponse) {
            $request?->setResponse($response->getRawData());

            if (!$response->isOk()) {
                throw new TelegramBotException(
                    sprintf('Error: %d %s', $response->getErrorCode(), $response->getDescription())
                );
            }
        } else {
            $this->logger->info(sprintf('unknown "%s" response object', get_class($response)));
        }

        return $response;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws TelegramBotException
     */
    private function request(string $name, array $arguments): mixed
    {
        try {
            if (method_exists($this->getClient(), $name)) {
                return $this->getClient()->{$name}(...$arguments);
            }

            try {
                return TelegramRequest::{$name}(...$arguments);
            } catch (InnerTelegramException $exception) {
                if (str_contains($exception->getMessage(), 'action') && str_contains($exception->getMessage(), 'doesn\'t exist')) {
                    // copied from TelegramRequest::send
                    $raw_response = TelegramRequest::execute($name, $arguments[0]);
                    $response = json_decode($raw_response, true);

                    if (null === $response) {
                        TelegramLog::debug($raw_response);
                        throw new InnerTelegramException('Telegram returned an invalid response!');
                    }

                    $response = new ServerResponse($response, $this->getEntity()->getUsername());

                    if (!$response->isOk() && $response->getErrorCode() === 401 && $response->getDescription() === 'Unauthorized') {
                        throw new InvalidBotTokenException();
                    }

                    return $response;
                }

                throw $exception;
            }
        } catch (TelegramBotException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            $message = sprintf('Failed to "%s" for "%s" telegram bot', $name, $this->getEntity()->getUsername());

            throw new TelegramBotException($message, 0, $exception);
        }
    }

    private function getClient(): TelegramClient
    {
        return $this->telegramBotClientRegistry->getTelegramClient($this->getEntity());
    }
}