<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Telegram\TelegramBot;
use App\Exception\ContactOptionsNotFoundException;
use App\Model\ContactOptions;
use App\Service\Telegram\TelegramLinkProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactOptionsFactory
{
    public function __construct(
        private readonly array $options,
        private readonly TranslatorInterface $translator,
        private readonly TelegramLinkProvider $telegramLinkProvider,
    )
    {
    }

    /**Provider $telegramLinkProvider,
     * @param TelegramBot $bot
     * @return ContactOptions
     * @throws ContactOptionsNotFoundException
     */
    public function createContactOptionsByTelegramBot(TelegramBot $bot): ContactOptions
    {
        $group = $bot->getGroup();

        if (!array_key_exists($group->name, $this->options)) {
            throw new ContactOptionsNotFoundException($group->name);
        }

        $options = $this->options[$group->name];
        $domain = sprintf('%s.contact', $group->name);

        $localeCode = $bot->getLocaleCode();

        return new ContactOptions(
            $this->translator->trans('company', domain: $domain, locale: $localeCode),
            $this->translator->trans('address', domain: $domain, locale: $localeCode),
            $this->translator->trans('tax', domain: $domain, locale: $localeCode),
            $bot->getUsername(),
            $bot->getName(),
            $this->telegramLinkProvider->getTelegramLink($bot->getUsername()),
            $options['website'],
            $this->translator->trans('phone', domain: $domain, locale: $localeCode),
            $this->translator->trans('email', domain: $domain, locale: $localeCode),
            $options['telegram'],
            $options['instagram'],
            $options['github'],
            $options['linkedin'],
        );
    }
}