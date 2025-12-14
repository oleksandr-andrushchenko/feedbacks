<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Site;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Site\SitePage;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\ContactOptionsFactory;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

class TelegramSiteViewResponseFactory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly ContactOptionsFactory $contactOptionsFactory,
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function createViewResponse(SitePage $page, string $username, bool $switcher = false): Response
    {
        $bot = $this->telegramBotRepository->findOneNonDeletedByUsername($username);

        if ($bot === null) {
            throw new NotFoundHttpException();
        }

        $countryCode = $bot->getCountryCode();
        $localeCode = $bot->getLocaleCode();
        $group = $bot->getGroup();

        $this->localeSwitcher->setLocale($localeCode);

        $contacts = $this->contactOptionsFactory->createContactOptionsByTelegramBot($bot);

        if ($page === SitePage::PRIVACY_POLICY || $page === SitePage::TERMS_OF_USE) {
            $template = sprintf('%s.tg.site.%s.%s.html.twig', $group->name, $page->value, $countryCode);
        } else {
            $template = sprintf('%s.tg.site.%s.html.twig', $group->name, $page->value);
        }

        if ($switcher) {
            $botMap = fn (TelegramBot $bot): array => [
                'username' => $bot->getUsername(),
                'name' => $bot->getName(),
                'country_icon' => $this->countryProvider->getCountryIconByCode($bot->getCountryCode()),
                'locale_icon' => $this->localeProvider->getLocaleIcon($this->localeProvider->getLocale($bot->getLocaleCode())),
            ];

            $bots = $this->telegramBotRepository->findNonDeletedByGroup($group);
        } else {
            $botMap = static fn (TelegramBot $bot): array => [
                'username' => $bot->getUsername(),
            ];

            $bots = [];
        }

        return new Response($this->twig->render($template, [
            'pages' => array_diff(array_map(static fn ($page): string => $page->value, SitePage::cases()), [SitePage::INDEX->value]),
            'page' => $page->value,
            'contacts' => $contacts,
            'bots' => array_map($botMap, $bots),
            'bot' => $botMap($bot),
            'locale' => $localeCode,
            'switcher' => $switcher,
        ]));
    }
}