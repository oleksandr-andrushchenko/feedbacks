<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Site\SitePage;
use App\Service\Site\SiteUrlGenerator;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramBotDescriptionsSyncer
{
    public function __construct(
        private readonly TelegramBotRegistry $registry,
        private readonly SiteUrlGenerator $siteUrlGenerator,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function syncTelegramDescriptions(TelegramBot $botEntity): void
    {
        $bot = $this->registry->getTelegramBot($botEntity);

        $bot->setMyName([
            'name' => $this->getMyName($botEntity),
        ]);

        $bot->setMyDescription([
            'description' => $this->getMyDescription($botEntity),
        ]);

        $bot->setMyShortDescription([
            'short_description' => $this->getMyShortDescription($botEntity),
        ]);

        $bot->getEntity()->setDescriptionsSynced(true);
    }

    private function getMyName(TelegramBot $bot): string
    {
        return $bot->getName();
    }

    private function getMyDescription(TelegramBot $botEntity): string
    {
        $localeCode = $botEntity->getLocaleCode();
        $domain = 'tg.descriptions';

        $privacyPolicyLink = $this->siteUrlGenerator->generate(
            'app.telegram_site_page',
            [
                'username' => $botEntity->getUsername(),
                'page' => SitePage::PRIVACY_POLICY->value,
            ],
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $privacyPolicy = $this->translator->trans('privacy_policy', domain: $domain, locale: $localeCode);

        $termsOfUseLink = $this->siteUrlGenerator->generate(
            'app.telegram_site_page',
            [
                'username' => $botEntity->getUsername(),
                'page' => SitePage::TERMS_OF_USE->value,
            ],
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
        $termsOfUse = $this->translator->trans('terms_of_use', domain: $domain, locale: $localeCode);


        $myDescription = "\n";
        $myDescription .= 'ℹ️ ';
        $myDescription .= $this->getMyShortDescription($botEntity);
        $myDescription .= "\n\n";
        $myDescription .= '‼️ ';
        $myDescription .= $this->translator->trans('agreement', domain: $domain, locale: $localeCode);

        $myDescription .= "\n\n";
        $myDescription .= '🔹 ';
        $myDescription .= $privacyPolicy;
        $myDescription .= ':';
        $myDescription .= "\n";
        $myDescription .= $privacyPolicyLink;

        $myDescription .= "\n\n";
        $myDescription .= '🔹 ';
        $myDescription .= $termsOfUse;
        $myDescription .= ':';
        $myDescription .= "\n";
        $myDescription .= $termsOfUseLink;

        return $myDescription;
    }

    private function getMyShortDescription(TelegramBot $botEntity): string
    {
        $group = $botEntity->getGroup();
        $localeCode = $botEntity->getLocaleCode();

        return $this->translator->trans(
            'short',
            domain: sprintf('%s.tg.descriptions', $group->name),
            locale: $localeCode
        );
    }
}