<?php

declare(strict_types=1);

namespace App\Service\Telegram\Chat;

use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\TelegramAwareHelper;
use Longman\TelegramBot\Entities\KeyboardButton;

class ChooseActionTelegramChatSender
{
    public function __construct(
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly CountryProvider $countryProvider,
    )
    {
    }

    public function sendActions(TelegramAwareHelper $tg): null
    {
        $keyboards = [];
        $keyboards[] = $this->getCreateButton($tg);
        $keyboards[] = $this->getSearchButton($tg);

        if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
            $keyboards[] = $this->getSubscriptionsButton($tg);
        } elseif ($tg->getTelegram()->getOptions()->acceptPayments()) {
            $keyboards[] = $this->getPremiumButton($tg);
        }

        if ($tg->getTelegram()->getMessengerUser()?->isShowExtendedKeyboard()) {
            $keyboards[] = $this->getCountryButton($tg);
            $keyboards[] = $this->getHintsButton($tg);
            $keyboards[] = $this->getPurgeButton($tg);
            $keyboards[] = $this->getMessageButton($tg);
            $keyboards[] = $this->getRestartButton($tg);
            $keyboards[] = $this->getShowLessButton($tg);
        } else {
            $keyboards[] = $this->getShowMoreButton($tg);
        }

        $keyboards = array_chunk($keyboards, 2);

        return $tg->reply($this->getActionAsk($tg), $tg->keyboard(...$keyboards))->null();
    }

    public static function getActionAsk(TelegramAwareHelper $tg): string
    {
        return $tg->trans('ask.action.action');
    }

    public static function getCreateButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'create'));
    }

    public static function getSearchButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'search'));
    }

    public static function getPremiumButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'premium'));
    }

    public static function getSubscriptionsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'subscriptions'));
    }

    public function getCountryButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $countryCode = $tg->getTelegram()?->getMessengerUser()->getUser()->getCountryCode();
        $country = $countryCode === null ? null : $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            return $tg->button(self::command($tg, 'country'));
        }

        $domain = sprintf('tg.%s', $tg->getTelegram()->getName()->name);

        return $tg->button(
            join(' ', [
                $this->countryProvider->getCountryIcon($country),
                $tg->trans('command.country', domain: $domain),
            ])
        );
    }

    public static function getHintsButton(TelegramAwareHelper $tg): KeyboardButton
    {
        $isShowHints = $tg->getTelegram()->getMessengerUser()->isShowHints();
        $domain = sprintf('tg.%s', $tg->getTelegram()->getName()->name);

        return $tg->button(
            join(' ', [
                $tg->trans('icon.hints', domain: $domain),
                $tg->trans(sprintf('keyboard.hints.turn_%s', $isShowHints ? 'off' : 'on')),
            ])
        );
    }

    public static function getPurgeButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'purge'));
    }

    public static function getMessageButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'message'));
    }

    public static function getRestartButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button(self::command($tg, 'restart'));
    }

    public static function getShowMoreButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.more'));
    }

    public static function getShowLessButton(TelegramAwareHelper $tg): KeyboardButton
    {
        return $tg->button($tg->trans('keyboard.less'));
    }

    private static function command($tg, string $key): string
    {
        $domain = sprintf('tg.%s', $tg->getTelegram()->getName()->name);

        return join(' ', [
            $tg->trans(sprintf('icon.%s', $key), domain: $domain),
            $tg->trans(sprintf('command.%s', $key), domain: $domain),
        ]);
    }
}