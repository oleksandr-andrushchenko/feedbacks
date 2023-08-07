<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use App\Service\Telegram\TelegramCommand;
use App\Service\Telegram\TelegramMyCommands;
use App\Service\Telegram\TelegramMyCommandsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramCommandsUpdater
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TelegramMyCommandsProvider $telegramMyCommandsProvider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramCommands(Telegram $telegram): void
    {
        $this->myCommands = [];

        foreach ($this->telegramMyCommandsProvider->getTelegramMyCommands($telegram) as $myCommands) {
            if (!in_array($myCommands->getLocaleCode(), $telegram->getOptions()->getLocaleCodes(), true)) {
                continue;
            }

            $data = [];

            $data['language_code'] = $myCommands->getLocaleCode();
            $data['scope'] = $myCommands->getScope()->jsonSerialize();
            $data['commands'] = array_map(
                fn (TelegramCommand $command) => [
                    'command' => $command->getName(),
                    'description' => $this->getDescription($telegram, $command, $myCommands),
                ],
                $myCommands->getCommands()
            );

            $this->myCommands[] = $myCommands;

            $telegram->setMyCommands($data);
        }
    }

    /**
     * @return array|null|TelegramMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }

    private function getDescription(
        Telegram $telegram,
        TelegramCommand $command,
        TelegramMyCommands $myCommands
    ): string
    {
        $domain = sprintf('tg.%s', $telegram->getGroup()->name);
        $locale = $myCommands->getLocaleCode();

        $icon = $this->translator->trans(sprintf('icon.%s', $command->getKey()), domain: $domain, locale: $locale);
        $name = $this->translator->trans(sprintf('command.%s', $command->getKey()), domain: $domain, locale: $locale);

        return sprintf('%s %s', $icon, $name);
    }
}