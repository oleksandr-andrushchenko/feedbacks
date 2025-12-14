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
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationInterface;
use App\Service\Telegram\Bot\Payment\TelegramBotPaymentManager;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotUserProvider;
use App\Service\Validator\Validator;
use Longman\TelegramBot\Entities\KeyboardButton;
use Psr\Log\LoggerInterface;

/**
 * @property SubscribeTelegramBotConversationState $state
 */
class SubscribeTelegramBotConversation extends TelegramBotConversation implements TelegramBotConversationInterface
{
    public const STEP_CURRENCY_QUERIED = 10;
    public const STEP_SUBSCRIPTION_PLAN_QUERIED = 20;
    public const STEP_PAYMENT_METHOD_QUERIED = 30;
    public const STEP_PAYMENT_QUERIED = 40;
    public const STEP_CANCEL_PRESSED = 50;

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

    public function invoke(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        return match ($this->state->getStep()) {
            default => $this->start($tg),
            self::STEP_CURRENCY_QUERIED => $this->gotCurrency($tg, $entity),
            self::STEP_SUBSCRIPTION_PLAN_QUERIED => $this->gotSubscriptionPlan($tg, $entity),
            self::STEP_PAYMENT_METHOD_QUERIED => $this->gotPaymentMethod($tg, $entity),
        };
    }

    public function start(TelegramBotAwareHelper $tg): ?string
    {
        $this->state->setCurrencyStep($tg->getCurrencyCode() === null && count($this->getCurrencies($tg)) > 0);
        $this->state->setPaymentMethodStep(count($this->getPaymentMethods($tg)) > 1);

        if ($this->state->currencyStep()) {
            return $this->queryCurrency($tg);
        }

        return $this->querySubscriptionPlan($tg);
    }

    public function gotCancel(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_CANCEL_PRESSED);

        $message = $tg->trans('reply.canceled', domain: 'subscribe');
        $message = $tg->upsetText($message);

        $tg->stopConversation($entity);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }

    public function getCurrencyQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(1);
        $query .= $tg->trans('query.currency', domain: 'subscribe');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('subscribe_currency_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryCurrency(TelegramBotAwareHelper $tg, bool $help = false): ?string
    {
        $this->state->setStep(self::STEP_CURRENCY_QUERIED);

        $message = $this->getCurrencyQuery($tg, $help);

        $buttons = $this->getCurrencyButtons($tg);
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotCurrency(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryCurrency($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchInput(null)) {
            $currency = null;
        } else {
            $currency = $this->getCurrencyByButton($tg->getInput(), $tg);
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

    public function getSubscriptionPlanQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(2);
        $query .= $tg->trans('query.subscription_plan', domain: 'subscribe');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('subscribe_subscription_plan_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function querySubscriptionPlan(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_SUBSCRIPTION_PLAN_QUERIED);

        $message = $this->getSubscriptionPlanQuery($tg, $help);

        $buttons = $this->getSubscriptionPlanButtons($tg);

        if ($this->state->currencyStep()) {
            $buttons[] = $tg->prevButton();
        } else {
            $buttons[] = $this->getChangeCurrencyButton($tg);
        }

        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotSubscriptionPlan(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->currencyStep() && $tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryCurrency($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->querySubscriptionPlan($tg, true);
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
            $subscriptionPlan = $this->getSubscriptionPlanByButton($tg->getInput(), $tg);
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
            $message = $tg->trans('reply.not_accept_payments', parameters: $parameters);
            $message = $tg->failText($message);

            return $this->chooseActionTelegramChatSender->sendActions($tg, $message);
        }

        if ($this->state->paymentMethodStep()) {
            return $this->queryPaymentMethod($tg);
        }

        $this->state->setPaymentMethod($this->getPaymentMethods($tg)[0]);

        return $this->queryPayment($tg, $entity);
    }

    public function getPaymentMethodQuery(TelegramBotAwareHelper $tg, bool $help = false): string
    {
        $query = $this->getStep(3);
        $query .= $tg->trans('query.payment_method', domain: 'subscribe');
        $query = $tg->queryText($query);

        if ($help) {
            $query = $tg->view('subscribe_payment_method_help', [
                'query' => $query,
            ]);
        } else {
            $query .= $tg->queryTipText($tg->useText(false));
        }

        return $query;
    }

    public function queryPaymentMethod(TelegramBotAwareHelper $tg, bool $help = false): null
    {
        $this->state->setStep(self::STEP_PAYMENT_METHOD_QUERIED);

        $message = $this->getPaymentMethodQuery($tg, $help);

        $buttons = $this->getPaymentMethodButtons($tg);
        $buttons[] = $tg->prevButton();
        $buttons[] = $tg->helpButton();
        $buttons[] = $tg->cancelButton();

        return $tg->reply($message, $tg->keyboard(...$buttons))->null();
    }

    public function gotPaymentMethod(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        if ($this->state->paymentMethodStep() && $tg->matchInput($tg->prevButton()->getText())) {
            return $this->queryPaymentMethod($tg);
        }

        if ($tg->matchInput($tg->helpButton()->getText())) {
            return $this->queryPaymentMethod($tg, true);
        }

        if ($tg->matchInput($tg->cancelButton()->getText())) {
            return $this->gotCancel($tg, $entity);
        }

        if ($tg->matchInput(null)) {
            $paymentMethod = null;
        } else {
            $paymentMethod = $this->getPaymentMethodByButton($tg->getInput(), $tg);
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

    public function getPaymentQuery(TelegramBotAwareHelper $tg, Money $price): string
    {
        $query = $this->getStep(4);
        $price = $this->moneyFormatter->formatMoneyAsTelegramButton($price);
        $tgLocaleCode = $this->telegramBotUserProvider->getTelegramUserByUpdate($tg->getBot()->getUpdate())?->getLanguageCode();
        $parameters = [
            'price' => $price,
        ];
        $payButton = $tg->trans('query.pay_button', $parameters, domain: 'subscribe', locale: $tgLocaleCode);
        $payButton = sprintf('<u>%s</u>', $payButton);
        $parameters = [
            'pay_button' => $payButton,
        ];
        $query .= $tg->trans('query.payment', $parameters, domain: 'subscribe');

        return $tg->queryText($query);
    }

    public function getPaymentInvoiceParameters(TelegramBotAwareHelper $tg): array
    {
        $commandNames = array_map(static fn ($command): string => $tg->command($command), ['create', 'search', 'lookup']);
        $bot = $tg->getBot()->getEntity();
        $bots = $this->telegramBotRepository->findNonDeletedByGroupAndCountry($bot->getGroup(), $bot->getCountryCode());
        $botNames = array_map(static fn (TelegramBot $bot): string => '@' . $bot->getUsername(), $bots);

        return [
            'plan' => $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName($this->state->getSubscriptionPlan()->getName()),
            'limited_commands' => '"' . join('", "', $commandNames) . '"',
            'bots' => join(', ', $botNames),
        ];
    }

    public function getInvalidCurrencyReply(TelegramBotAwareHelper $tg, string $currency): string
    {
        $currencyName = sprintf('<u>%s</u>', $currency);
        $parameters = [
            'currency' => $currencyName,
        ];
        $message = $tg->trans('reply.invalid_currency', $parameters, domain: 'subscribe');

        return $tg->failText($message);
    }

    public function queryPayment(TelegramBotAwareHelper $tg, Entity $entity): null
    {
        $this->state->setStep(self::STEP_PAYMENT_QUERIED);

        $subscriptionPlan = $this->state->getSubscriptionPlan();
        $price = $this->getPrice($subscriptionPlan, $tg);

        $message = $this->getPaymentQuery($tg, $price);

        $this->chooseActionTelegramChatSender->sendActions($tg, text: $message);

        $parameters = $this->getPaymentInvoiceParameters($tg);

        try {
            $this->telegramBotPaymentManager->sendPaymentRequest(
                $tg->getBot(),
                $tg->getBot()->getMessengerUser(),
                (string) $tg->getChatId(),
                $this->state->getPaymentMethod(),
                $tg->trans('query.payment_invoice_title', $parameters, domain: 'subscribe'),
                $tg->trans('query.payment_invoice_description', $parameters, domain: 'subscribe'),
                $this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText(),
                $subscriptionPlan->getName()->name,
                [],
                $price
            );
        } catch (TelegramBotInvalidCurrencyBotException $exception) {
            $this->logger->error($exception);

            $message = $this->getInvalidCurrencyReply($tg, $exception->getCurrency());

            $tg->reply($message);

            return $this->queryCurrency($tg);
        }

        return $tg->stopConversation($entity)->null();
    }

    public function getCurrencyButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (Currency $currency): KeyboardButton => $this->getCurrencyButton($currency, $tg),
            $this->getCurrencies($tg)
        );
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getSubscriptionPlanButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (FeedbackSubscriptionPlan $subscriptionPlan): KeyboardButton => $this->getSubscriptionPlanButton($subscriptionPlan, $tg),
            $this->getSubscriptionPlans($tg)
        );
    }

    public function getPrice(FeedbackSubscriptionPlan $subscriptionPlan, TelegramBotAwareHelper $tg): Money
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

    public function getCurrencyButton(Currency $currency, TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button($this->currencyProvider->getCurrencyComposeName($currency));
    }

    public function getCurrencyByButton(string $button, TelegramBotAwareHelper $tg): ?Currency
    {
        foreach ($this->getCurrencies($tg) as $currency) {
            if ($this->getCurrencyButton($currency, $tg)->getText() === $button) {
                return $currency;
            }
        }

        return null;
    }

    public function getSubscriptionPlanButton(
        FeedbackSubscriptionPlan $subscriptionPlan,
        TelegramBotAwareHelper $tg
    ): KeyboardButton
    {
        $price = $this->getPrice($subscriptionPlan, $tg);

        $text = $this->feedbackSubscriptionPlanProvider->getSubscriptionPlanName($subscriptionPlan->getName());
        $text .= ' - ';
        $text .= $this->moneyFormatter->formatMoney($price, native: true);

        return $tg->button($text);
    }

    public function getSubscriptionPlanByButton(string $button, TelegramBotAwareHelper $tg): ?FeedbackSubscriptionPlan
    {
        foreach ($this->getSubscriptionPlans($tg) as $subscriptionPlan) {
            if ($this->getSubscriptionPlanButton($subscriptionPlan, $tg)->getText() === $button) {
                return $subscriptionPlan;
            }
        }

        return null;
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return KeyboardButton[]
     */
    public function getPaymentMethodButtons(TelegramBotAwareHelper $tg): array
    {
        return array_map(
            fn (TelegramBotPaymentMethod $paymentMethod): KeyboardButton => $this->getPaymentMethodButton($paymentMethod, $tg),
            $this->getPaymentMethods($tg)
        );
    }

    public function getPaymentMethodButton(
        TelegramBotPaymentMethod $paymentMethod,
        TelegramBotAwareHelper $tg
    ): KeyboardButton
    {
        $text = $tg->trans(sprintf('payment_method.%s', $paymentMethod->getName()->name), domain: 'tg.payment_method');

        return $tg->button($text);
    }

    public function getPaymentMethodByButton(string $button, TelegramBotAwareHelper $tg): ?TelegramBotPaymentMethod
    {
        foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
            if ($this->getPaymentMethodButton($paymentMethod, $tg)->getText() === $button) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function getChangeCurrencyButton(TelegramBotAwareHelper $tg): KeyboardButton
    {
        return $tg->button('💱 ' . $tg->trans('keyboard.change_currency', domain: 'subscribe'));
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return TelegramBotPaymentMethod[]
     */
    public function getPaymentMethods(TelegramBotAwareHelper $tg): array
    {
        return $this->telegramBotPaymentMethodRepository->findActiveByBot($tg->getBot()->getEntity());
    }

    /**
     * @param TelegramBotAwareHelper $tg
     * @return Currency[]
     */
    public function getCurrencies(TelegramBotAwareHelper $tg): array
    {
        $currencyCodes = [];

        foreach ($this->getPaymentMethods($tg) as $paymentMethod) {
            $currencyCodes = array_merge($currencyCodes, $paymentMethod->getCurrencyCodes());
        }

        return $this->currencyProvider->getCurrencies(currencyCodes: $currencyCodes);
    }

    public function getSubscriptionPlans(TelegramBotAwareHelper $tg): array
    {
        return $this->feedbackSubscriptionPlanProvider->getSubscriptionPlans(country: $tg->getCountryCode());
    }

    public function getStep(int $num): string
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
}