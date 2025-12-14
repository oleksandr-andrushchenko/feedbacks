<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Site\SitePage;
use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Site\TelegramSiteViewResponseFactory;
use App\Service\Telegram\Bot\TelegramBotUpdateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TelegramController
{
    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotUpdateHandler $telegramBotUpdateHandler,
        private readonly EntityManager $entityManager,
        private readonly TelegramSiteViewResponseFactory $telegramSiteViewResponseFactory,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function page(SitePage $page, string $username, Request $request): Response
    {
        $switcher = $request->query->has('switcher');

        if ($switcher) {
            $request->getSession()->set('switcher', true);
        } else {
            $switcher = $request->getSession()->get('switcher', false);
        }

        return $this->telegramSiteViewResponseFactory->createViewResponse($page, $username, $switcher);
    }

    public function webhook(string $username, Request $request): Response
    {
        try {
            $this->logger->debug(__METHOD__);
            $bot = $this->telegramBotRepository->findOneByUsername($username);

            if ($bot === null) {
                throw new TelegramBotNotFoundException($username);
            }

            // todo: push to ordered queue (amqp)
            // todo: use command bus
            $this->telegramBotUpdateHandler->handleTelegramBotUpdate($bot, $request);
            $this->entityManager->flush();

            return new Response('ok');
        } catch (TelegramBotNotFoundException $exception) {
            $this->logger->error($exception);

            return new Response('failed', Response::HTTP_NOT_FOUND);
        } catch (Throwable $exception) {
            $this->logger->error($exception);

            return new Response('failed');
        }
    }
}
