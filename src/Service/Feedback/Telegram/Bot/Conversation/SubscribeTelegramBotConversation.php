<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversation as Entity;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Exception\Telegram\Bot\Payment\TelegramBotInvalidCurrencyBotException;
use App\Exception\ValidatorException;
use App\Model\Feedback\Telegram\Bot\SubscribeTelegramBotConversationState;
use App\Model\Feedback\Telegram\FeedbackSubscriptionPlan;
use App\Model\Intl\Currency;
use App\Model\Money;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Intl\CurrencyProvider;
use App\Service\MoneyFormatter;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversation;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentManager;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotUserProvider;
use App\Service\Validator\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;

/**
 * @property SubscribeTelegramBotConversationState $state
 */
class SubscribeTelegramBotConversation extends TelegramBotConversation
{
    public const int STEP_CURRENCY_QUERIED = 10;
    public const int STEP_SUBSCRIPTION_PLAN_QUERIED = 20;
    public const int STEP_PAYMENT_METHOD_QUERIED = 30;
    public const int STEP_PAYMENT_QUERIED = 40;
    public const int STEP_CANCEL_PRESSED = 50;

    public function __construct(
        private readonly Validator $validator,
        private readonly FeedbackSubscriptionPlanProvider $feedbackSubscriptionPlanProvider,
        private readonly TelegramBotPaymentMethodRepository $telegramBotPaymentMethodRepository,
        private readonly TelegramBotPaymentManager $telegramBotPaymentManager,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly CurrencyProvider $currencyProvider,
        private readonly MoneyFormatter $moneyFormatter,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotUserProvider $telegramBotUserProvider,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct(new SubscribeTelegramBotConversationState());
    }

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): void
    {
        match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CURRENCY_QUERIED => $this->gotCurrency($tg, $entity),
            self::STEP_SUBSCRIPTION_PLAN_QUERIED => $this->gotSubscriptionPlan($tg, $entity),
            self::STEP_PAYMENT_METHOD_QUERIED => $this->gotPaymentMethod($tg, $entity),
        };
    }

    private function getStep(int $num): string
    {
        $originalNum = $num;
        $total = 4;

        if (!$this->state->currencyStep()) {
            if ($originalNum > 1) {
                $num--;
            }
            $total--;
        }

        if (!$this->state->paymentMethodStep()) {
            if ($originalNum > 2) {
                $num--;
            }
            $total--;
        }

        return sprintf('[%d/%d] ', $num, $total);
    }

    private function start(TelegramBotAwareHelper $tg): ?string
    {
        $this->state->setCurrencyStep($tg->getCurrencyCode() === null && count($this->getCurrencies($tg)) > 0);
        $this->state->setPaymentMethodStep(count($this->getPaymentMethods($tg)) > 1);

        if ($this->state->currencyStep()) {
            return $this->queryCurrency($tg);
        }

        return $this->querySubscriptionPlan($tg);
    }

    /**
     * @return Currency[]
     */
    private function getCurrencies(TelegramBotAwareHelper $tg): array
    {
        $currencyCodes = [];

        foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
            $currencyCodes = array_merge($currencyCodes, $paymentMethod->getCurrencyCodes());
        }

        return $this->currencyProvider->getCurrencies(currencyCodes: $currencyCodes);
    }

    /**
     * @return TelegramBotPaymentMethod[]
     */
    private function getPaymentMethods(TelegramBotAwareHelper $tg): array
    {
        return $this->telegramBotPaymentMethodRepository->findActiveByBot($tg->getBot()->getEntity());
    }

    private function queryCurrency(TelegramBotAwareHelper $tg): ?string
    {
        $this->state->setStep(self::STEP_CURRENCY_QUERIED);

        $message = $this->getStep(1);
        $message .= $tg->t('currency', [], 'subscribe');
        $message = $tg->queryText($message);
        $message .= $tg->queryTipText($tg->useText(false));

        $buttons = array_map(
            fn (Currency $currency): KeyboardButton => $this->getCurrencyButton($currency, $tg),
            $this->getCurrencies($tg)
        );
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getCurrencyButton(Currency $currency, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->currencyProvider->getCurrencyComposeName($currency));
    }

    private function querySubscriptionPlan(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_QUERIED);

        $message = $this->getStep(2);
        $message .= $tg->t('subscription_plan', [], 'subscribe');
        $message = $tg->queryText($message);
        $message .= $tg->queryTipText($tg->useText(false));

        $buttons = array_map(
            fn (FeedbackSubscriptionPlan $subscriptionPlan): KeyboardButton => $this->getSubscriptionPlanButton($subscriptionPlan, $tg),
            $this->getSubscriptionPlans($tg)
        );

        if ($this->state->currencyStep()) {
            $buttons[] = $tg->prevButton();
        } else {
            $buttons[] = $this->getChangeCurrencyButton($tg);
        }

        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getSubscriptionPlanButton(FeedbackSubscriptionPlan $subscriptionPlan, TelegramBotAwareHelper $tg): KeyboardButton
    {
        $price = $this->getPrice($subscriptionPlan, $tg);

        $text = $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName($subscriptionPlan->getName());
        $text .= ' - ';
        $text .= $this->moneyFormatter->formatMoney($price, native: true);

        return $tg->button($text);
    }

    private function getPrice(FeedbackSubscriptionPlan $subscriptionPlan, TelegramBotAwareHelper $tg): Money
    {
        $usdPrice = $subscriptionPlan->getPrice($tg->getCountryCode());

        if ($this->state->getCurrency() === null) {
            $currencyCode = $tg->getBot()->getUser()?->getCurrencyCode() ?? 'USD';
            $currency = $this->currencyProvider->getCurrency($currencyCode);
        } else {
            $currency = $this->state->getCurrency();
        }

        return new Money(ceil($usdPrice / $currency->getRate()), $currency->getCode());
    }

    private function getSubscriptionPlans(TelegramBotAwareHelper $tg): array
    {
        return $this->feedbackSubscriptionPlanProvider->getSubscriptionPlans(country: $tg->getCountryCode());
    }

    private function getChangeCurrencyButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('💱 ' . $tg->t('change_currency', [], 'subscribe'));
    }

    private function gotCurrency(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchInput(null)) {
            $currency = null;
        } else {
            $currency = null;
            foreach ($this->getCurrencies($tg) as $currency) {
                if ($this->getCurrencyButton($currency, $tg)->getText() === $tg->getText()->getRawValue()) {
                    break;
                }
            }
        }

        if ($currency === null) {
            $tg->replyWrong(false);

            return $this->queryCurrency($tg);
        }

        $this->state->setCurrency($currency);

        try {
            $this->validator->validate($this->state);
            $tg->getBot()->getUser()->setCurrencyCode($currency->getCode());
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryCurrency($tg);
        }

        return $this->querySubscriptionPlan($tg);
    }

    private function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->t('canceled', [], 'subscribe');
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    private function gotSubscriptionPlan(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->currencyStep() && $tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryCurrency($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchInput($this->getChangeCurrencyButton($tg)->getText())) {
            $this->state->setCurrencyStep(true);

            return $this->queryCurrency($tg);
        }

        if ($tg->matchInput(null)) {
            $subscriptionPlan = null;
        } else {
            $subscriptionPlan = null;
            foreach ($this->getSubscriptionPlans($tg) as $subscriptionPlan) {
                if ($this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText() === $tg->getText()->getRawValue()) {
                    break;
                }
            }
        }

        if ($subscriptionPlan === null) {
            $tg->replyWrong(false);

            return $this->querySubscriptionPlan($tg);
        }

        $this->state->setSubscriptionPlan($subscriptionPlan);

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->querySubscriptionPlan($tg);
        }

        if (!$tg->getBot()->getEntity()->acceptPayments() || count($this->getPaymentMethods($tg)) === 0) {
            $tg->stopConversation($entity);

            $parameters = [
                'contact_command' => $tg->command('contact', html: true, link: true),
            ];
            $message = $tg->t('not_accept_payments', $parameters, 'subscribe');
            $message = $tg->failText($message);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message);
        }

        if ($this->state->paymentMethodStep()) {
            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($this->getPaymentMethods($tg)[0]);

        return $this->queryPayment($tg, $entity);
    }

    private function queryPaymentMethod(TelegramBotAwareHelper $tg): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_QUERIED);

        $message = $this->getStep(3);
        $message .= $tg->t('payment_method', [], 'subscribe');
        $message = $tg->queryText($message);

        $message .= $tg->queryTipText($tg->useText(false));

        $buttons = array_map(
            fn (TelegramBotPaymentMethod $paymentMethod): KeyboardButton => $this->getPaymentMethodButton($paymentMethod, $tg),
            $this->getPaymentMethods($tg)
        );
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    private function getPaymentMethodButton(TelegramBotPaymentMethod $paymentMethod, TelegramBotAwareHelper $tg): KeyboardButton
    {
        $text = $tg->t(sprintf('payment_method.%s', $paymentMethod->getName()->name), [], 'payment-method');

        return $tg->button($text);
    }

    private function queryPayment(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_PAYMENT_QUERIED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();
        $price = $this->getPrice($subscriptionPlan, $tg);

        $message = $this->getStep(4);
        $tgLocaleCode = $this->telegramBotUserProvider->getTelegramUserByUpdate($tg->getBot()->getUpdate())?->getLanguageCode();
        $parameters = [
            'price' => $this->moneyFormatter->formatMoneyAsTelegramButton($price),
        ];
        $payButton = $tg->t('pay_button', $parameters, 'subscribe', locale: $tgLocaleCode);
        $payButton = sprintf('<u>%s</u>', $payButton);
        $parameters = [
            'pay_button' => $payButton,
        ];
        $message .= $tg->t('payment', $parameters, 'subscribe');

        $message = $tg->queryText($message);

        $this->chooseActionTelegramChatSender->sendActions($tg, text: $message);

        $commandNames = array_map(static fn ($command): string => $tg->command($command), ['create', 'search', 'lookup']);
        $bot = $tg->getBot()->getEntity();
        $bots = $this->telegramBotRepository->findNonDeletedByGroupAndCountry($bot->getGroup(), $bot->getCountryCode());
        $botNames = array_map(static fn (TelegramBot $bot): string => '@' . $bot->getUsername(), $bots);

        $parameters = [
            'plan' => $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName($this->state->getSubscriptionPlan()->getName()),
            'limited_commands' => '"' . join('", "', $commandNames) . '"',
            'bots' => join(', ', $botNames),
        ];

        try {
            $this->telegramBotPaymentManager->sendPaymentRequest(
                $tg->getBot(),
                $tg->getBot()->getMessengerUser(),
                (string) $tg->getChatId(),
                $this->state->getPaymentMethod(),
                $tg->t('payment_invoice_title', $parameters, 'subscribe'),
                $tg->t('payment_invoice_description', $parameters, 'subscribe'),
                $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
                $subscriptionPlan->getName()->name,
                [],
                $price
            );
        } catch (TelegramBotInvalidCurrencyBotException $exception) {
            $this->logger->error($exception);

            $currencyName = sprintf('<u>%s</u>', $exception->getCurrency());
            $parameters = [
                'currency' => $currencyName,
            ];
            $message = $tg->t('invalid_currency', $parameters, 'subscribe');

            $tg->reply($tg->failText($message));

            return $this->queryCurrency($tg);
        }

        return $tg->stopConversation($entity)->null();
    }

    private function gotPaymentMethod(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->paymentMethodStep() && $tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryPaymentMethod($tg);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchInput(null)) {
            $paymentMethod = null;
        } else {
            $paymentMethod = null;
            foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
                if ($this->getPaymentMethodButton($paymentMethod, $tg)->getText() === $tg->getText()->getRawValue()) {
                    break;
                }
            }
        }

        if ($paymentMethod === null) {
            $tg->replyWrong(false);

            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($paymentMethod);

        try {
            $this->validator->validate($this->state);
        } catch (ValidatorException $exception) {
            $tg->replyWarning($tg->queryText($exception->getFirstMessage()));

            return $this->queryPaymentMethod($tg);
        }

        return $this->queryPayment($tg, $entity);
    }
}