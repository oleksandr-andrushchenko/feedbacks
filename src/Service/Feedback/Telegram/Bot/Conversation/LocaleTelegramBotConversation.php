<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Model\Intl\Locale;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\LocaleProvider;
use App\Service\Telegram\Bot\Chat\TelegramBotMatchesChatSender;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotLocaleSwitcher;
use Longman\TelegramBot\Entities\KeyboardButton;

class LocaleTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_CHANGE_CONFIRM_QUERIED = 5;
    public const STEP_GUESS_LOCALE_QUERIED = 10;
    public const STEP_LOCALE_QUERIED = 20;
    public const STEP_CANCEL_PRESSED = 30;

    public function __construct(
        private readonly LocaleProvider $localeProvider,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly TelegramBotLocaleSwitcher $telegramBotLocaleSwitcher,
        private readonly TelegramBotMatchesChatSender $telegramBotMatchesChatSender,
    )
    {
        parent::__construct(new TelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CHANGE_CONFIRM_QUERIED => $this->gotChangeConfirm($tg, $entity),
            self::STEP_GUESS_LOCALE_QUERIED => $this->gotLocale($tg, $entity, true),
            self::STEP_LOCALE_QUERIED => $this->gotLocale($tg, $entity, false),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        return $this->queryChangeConfirm($tg);
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return Locale[]
     */
    public function getGuessLocales(TelegramBotAwareHelper $tg): array
    {
        return $this->localeProvider->getLocales(countryCode: $tg->getCountryCode());
    }

    /**
     * @return Locale[]
     */
    public function getLocales(): array
    {
        return $this->localeProvider->getLocales();
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'locale');
        $message .= "\n\n";
        $message .= $this->getCurrentLocaleReply($tg);
        $message = $tg->upsetText($message);
        $message .= "\n";

        $tg->stopConversation($entity);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function getCurrentLocaleReply(TelegramBotAwareHelper $tg): string
    {
        $localeCode = $tg->getLocaleCode();
        $locale = $localeCode === null ? null : $this->localeProvider->getLocale($localeCode);
        $localeName = sprintf('<u>%s</u>', $this->localeProvider->getLocaleComposeName($locale));
        $parameters = [
            'locale' => $localeName,
        ];

        return $tg->trans('reply.current_locale', $parameters, domain: 'locale');
    }

    public function getChangeConfirmQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $message = $this->getCurrentLocaleReply($tg);
        $message .= "\n\n";

        $query = $tg->trans('query.change_confirm', domain: 'locale');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('locale_change_confirm_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        $message .= $query;

        return $message;
    }

    public function queryChangeConfirm(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_CHANGE_CONFIRM_QUERIED);

        $message = $this->getChangeConfirmQuery($tg, $help);

        $buttons = [];
        $buttons[] = [$tg->yesButton(), $tg->noButton()];
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotChangeConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryChangeConfirm($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryChangeConfirm($tg);
        }

        $locales = $this->getGuessLocales($tg);

        if (count($locales) === 0) {
            return $this->queryLocale($tg);
        }

        return $this->queryGuessLocale($tg);
    }

    public function getLocaleQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $tg->trans('query.locale', domain: 'locale');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('locale_locale_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryGuessLocale(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_GUESS_LOCALE_QUERIED);

        $message = $this->getLocaleQuery($tg, $help);

        $buttons = $this->getLocaleButtons($this->getGuessLocales($tg), $tg);
        $buttons[] = $this->getOtherLocaleButton($tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function queryLocale(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_LOCALE_QUERIED);

        $message = $this->getLocaleQuery($tg, $help);

        $buttons = $this->getLocaleButtons($this->getLocales(), $tg);
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function getGotLocaleReply(TelegramBotAwareHelper $tg): string
    {
        $message = $tg->trans('reply.ok', domain: 'locale');
        $message = $tg->okText($message);
        $message .= "\n\n";
        $message .= $this->getCurrentLocaleReply($tg);

        return $message;
    }

    public function gotLocale(TelegramBotAwareHelper $tg, Entity $entity, bool $guess): null
    {
        if (!$guess && $tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryGuessLocale($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            if ($guess) {
                return $this->queryGuessLocale($tg, true);
            }

            return $this->queryLocale($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($guess && $tg->matchInput($this->getOtherLocaleButton($tg)->getText())) {
            return $this->queryLocale($tg);
        }

        if ($tg->matchInput(null)) {
            $locale = null;
        } else {
            $locales = $guess ? $this->getGuessLocales($tg) : $this->getLocales();
            $locale = $this->getLocaleByButton($tg->getInput(), $locales, $tg);
        }

        if ($locale === null) {
            $tg->replyWrong(false);

            return $guess ? $this->queryGuessLocale($tg) : $this->queryLocale($tg);
        }

        if ($locale->getCode() !== $tg->getLocaleCode()) {
            $tg->getBot()->getUser()
                ->setLocaleCode($locale->getCode())
            ;
            $this->telegramBotLocaleSwitcher->switchLocale($locale);
        }

        $tg->stopConversation($entity);

        $message = $this->getGotLocaleReply($tg);
        $message .= "\n";

        $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);

        $keyboard = $this->chooseActionTelegramChatSender->getKeyboard($tg);
        $this->telegramBotMatchesChatSender->sendTelegramBotMatchesIfNeed($tg, $keyboard);

        return null;
    }

    /**
     * @param Locale[]|null $locales
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getLocaleButtons(array $locales, TelegramBotAwareHelper $tg): array
    {
        return array_map(fn (Locale $locale): KeyboardButton => $this->getLocaleButton($locale, $tg), $locales);
    }

    public function getLocaleButton(Locale $locale, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->localeProvider->getLocaleComposeName($locale));
    }

    public function getLocaleByButton(string $button, array $locales, TelegramBotAwareHelper $tg): ?Locale
    {
        foreach ($locales as $locale) {
            if ($this->getLocaleButton($locale, $tg)->getText() === $button) {
                return $locale;
            }
        }

        return null;
    }

    public function getOtherLocaleButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        $icon = $this->localeProvider->getUnknownLocaleIcon();
        $name = $tg->trans('keyboard.other');

        return $tg->button($icon . ' ' . $name);
    }
}