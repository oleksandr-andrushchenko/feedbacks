<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotInvalidUpdateException;
use Longman\TelegramBot\Entities\Update;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TelegramBotUpdateFactory
{
    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param TelegramBot $bot
     * @param Request $request
     * @return Update
     * @throws TelegramBotInvalidUpdateException
     */
    public function createUpdate(TelegramBot $bot, Request $request): Update
    {
        $input = $request->getContent();

        $data = json_decode($input, true);

        if (!is_array($data)) {
            $this->logger->debug('Invalid telegram update received, processing aborted', [
                'name' => $bot->getEntity()->getGroup()->name,
                'input' => $input,
            ]);

            throw new TelegramBotInvalidUpdateException($input);
        }

        $this->logger->info('Telegram update received', [
            'name' => $bot->getEntity()->getGroup()->name,
            'json' => $input,
        ]);

        return new Update($data, $bot->getEntity()->getUsername());
    }
}