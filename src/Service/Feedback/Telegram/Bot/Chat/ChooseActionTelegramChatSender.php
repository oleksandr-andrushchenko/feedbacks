<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Chat;

use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseActionTelegramChatSender
{
    public function __construct(
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
    )
    {
    }

    public function sendActions(TelegramBotAwareHelper $tg, string $text = null, bool $appendDefault = false): null
    {
        $tg->reply(
            $this->getQuery($tg, text: $text, appendDefault: $appendDefault),
            $this->getKeyboard($tg)
        );

        return null;
    }

    public function getQuery(TelegramBotAwareHelper $tg, string $text = null, bool $appendDefault = false): string
    {
        if ($text === null) {
            return $this->getActionQuery($tg);
        }

        if ($appendDefault) {
            return $text . "\n" . $this->getActionQuery($tg);
        }

        return $text;
    }

    public function getKeyboard(TelegramBotAwareHelper $tg): Keyboard
    {
        $buttons = [];
        $buttons[] = $this->getSearchButton($tg);
        $buttons[] = $this->getCreateButton($tg);
        $buttons[] = $this->getContactButton($tg);

        $messengerUser = $tg->getBot()->getMessengerUser();

        if ($messengerUser?->showExtendedKeyboard()) {
//            $buttons[] = $this->getLookupButton($tg);

//            if (!$this->feedbackSubscriptionManager->hasActiveSubscription($messengerUser)) {
//                $buttons[] = $this->getSubscribeButton($tg);
//            } elseif ($this->feedbackSubscriptionManager->hasSubscription($messengerUser)) {
//                $buttons[] = $this->getSubscriptionsButton($tg);
//            }

            $buttons[] = $this->getCountryButton($tg);
            $buttons[] = $this->getLocaleButton($tg);
//            $buttons[] = $this->getCommandsButton($tg);
//            $buttons[] = $this->getLimitsButton($tg);
//            $buttons[] = $this->getRestartButton($tg);
//            $buttons[] = $this->getDonateButton($tg);
//            $buttons[] = $this->getPurgeButton($tg);
            $buttons[] = $this->getShowLessButton($tg);
        } else {
            $buttons[] = $this->getShowMoreButton($tg);
        }

        return $tg->keyboard(...array_chunk($buttons, 2));
    }

    public function getActionQuery(TelegramBotAwareHelper $tg): string
    {
        return $tg->trans('query.action');
    }

    public function getCreateButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('create'));
    }

    public function getSearchButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('search'));
    }

    public function getCreateButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getCreateButton($tg)->getText(),
            $tg->command('create_old_1'),
            $tg->command('create_old_2'),
        ];
    }

    public function getSearchButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getSearchButton($tg)->getText(),
            $tg->command('search_old_1'),
        ];
    }

    public function getLookupButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getLookupButton($tg)->getText(),
            $tg->command('lookup_old_1'),
        ];
    }

    public function getCountryButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getCountryButton($tg)->getText(),
            $tg->command('country_old_1'),
            $tg->command('country_old_2'),
        ];
    }

    public function getLocaleButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getLocaleButton($tg)->getText(),
            $tg->command('locale_old_1'),
        ];
    }

    public function getLimitsButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getLimitsButton($tg)->getText(),
            $tg->command('limits_old_1'),
        ];
    }

    public function getLookupButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('lookup'));
    }

    public function getSubscribeButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('subscribe'));
    }

    public function getSubscribeButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getSubscribeButton($tg)->getText(),
        ];
    }

    public function getSubscriptionsButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $hasActiveSubscription = $this->feedbackSubscriptionManager->hasActiveSubscription($tg->getBot()->getMessengerUser());
        $key = 'subscriptions';

        $text = $tg->trans($hasActiveSubscription ? $key : 'subscribe', domain: 'command_icon', locale: 'en');
        $text .= ' ';
        $text .= $tg->trans($key, domain: 'command');

        return $tg->button($text);
    }

    public function getSubscriptionsButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getSubscriptionsButton($tg)->getText(),
        ];
    }

    public function getCountryButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $countryCode = $tg->getCountryCode();

        if ($countryCode === null) {
            return $tg->button($tg->command('country'));
        }

        $text = $this->countryProvider->getCountryIconByCode($countryCode);
        $text .= ' ';
        $text .= $tg->trans('country', domain: 'command');

        return $tg->button($text);
    }

    public function getLocaleButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            return $tg->button($tg->command('locale'));
        }

        $text = $this->localeProvider->getLocaleIcon($locale);
        $text .= ' ';
        $text .= $tg->trans('locale', domain: 'command');

        return $tg->button($text);
    }

    public function getLimitsButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('limits'));
    }

    public function getPurgeButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('purge'));
    }

    public function getPurgeButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getPurgeButton($tg)->getText(),
        ];
    }

    public function getDonateButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('donate'));
    }

    public function getDonateButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getDonateButton($tg)->getText(),
        ];
    }

    public function getContactButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('contact'));
    }

    public function getContactButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getContactButton($tg)->getText(),
            $tg->command('contact_old_1'),
        ];
    }

    public function getCommandsButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('commands'));
    }

    public function getCommandsButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getCommandsButton($tg)->getText(),
        ];
    }

    public function getRestartButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->command('restart'));
    }

    public function getRestartButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getRestartButton($tg)->getText(),
        ];
    }

    public function getShowMoreButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.more'));
    }

    public function getShowMoreButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getShowMoreButton($tg)->getText(),
            '⬇️ ' . $tg->trans('keyboard.more_old_1'),
        ];
    }

    public function getShowLessButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.less'));
    }

    public function getShowLessButtonTexts(TelegramBotAwareHelper $tg): array
    {
        return [
            $this->getShowLessButton($tg)->getText(),
            '⬆️ ' . $tg->trans('keyboard.less_old_1'),
        ];
    }
}
