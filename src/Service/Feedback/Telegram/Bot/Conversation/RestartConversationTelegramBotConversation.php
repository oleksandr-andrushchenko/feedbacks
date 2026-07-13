<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\Chat\StartTelegramCommandHandler;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotLocaleSwitcher;

class RestartConversationTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_CONFIRM_QUERIED = 10;
    public const int STEP_CANCEL_PRESSED = 20;

    public function __construct(
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly StartTelegramCommandHandler $startTelegramCommandHandler,
        private readonly CountryProvider $countryProvider,
        private readonly TelegramBotLocaleSwitcher $telegramBotLocaleSwitcher,
    )
    {
        parent::__construct(new TelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->queryConfirm($tg),
            self::STEP_CONFIRM_QUERIED => $this->gotConfirm($tg, $entity),
        };
    }

    private function queryConfirm(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_CONFIRM_QUERIED);

        $query = $tg->t('confirm', [], 'restart');
        $query = $tg->queryText($query);
        $query .= $tg->queryTipText($tg->useText(false));

        $message = $query;

        $buttons = [
            $tg->yesButton(),
            $tg->noButton(),
            $tg->cancelButton(),
        ];

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function gotConfirm(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->noButton()->getText())) {
            $tg->stopConversation($entity);

            return $this->chooseActionTelegramChatSender->sendActions($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if (!$tg->matchInput($tg->yesButton()->getText())) {
            $tg->replyWrong(false);

            return $this->queryConfirm($tg);
        }

        $countryCode = $tg->getBot()->getEntity()->getCountryCode();
        $country = $this->countryProvider->getCountry($countryCode);

        $tg->getBot()->getMessengerUser()
            ?->setShowExtendedKeyboard(false)
        ;
        $tg->getBot()->getUser()
            ?->setCountryCode($country->getCode())
            ?->setLocaleCode($tg->getBot()->getEntity()->getLocaleCode())
            ?->setCurrencyCode($country->getCurrencyCode())
            ?->setTimezone($country->getTimezones()[0] ?? null)
        ;

        $tg->stopConversation($entity);

        $this->telegramBotLocaleSwitcher->switchLocale($tg->getLocaleCode());

        $message = $tg->t('ok', [], 'restart');
        $message = $tg->okText($message);

        $tg->reply($message);

        return $this->startTelegramCommandHandler->handleStart($tg);
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $tg->stopConversation($entity);

        $message = $tg->t('canceled', [], 'restart');
        $message = $tg->upsetText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }
}