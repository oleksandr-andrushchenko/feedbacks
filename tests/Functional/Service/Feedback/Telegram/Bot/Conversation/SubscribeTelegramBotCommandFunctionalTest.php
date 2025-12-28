<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Model\Feedback\Telegram\Bot\SubscribeTelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\SubscribeTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Feedback\FeedbackLookupRepositoryProviderTrait;
use App\Tests\Traits\Feedback\FeedbackSubscriptionPlanProviderTrait;
use App\Tests\Traits\Intl\CurrencyProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotInvoiceSenderProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotPaymentRepositoryProviderTrait;
use Generator;

class SubscribeTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use FeedbackLookupRepositoryProviderTrait;
    use CurrencyProviderTrait;
    use FeedbackSubscriptionPlanProviderTrait;
    use TelegramBotPaymentRepositoryProviderTrait;
    use TelegramBotInvoiceSenderProviderTrait;

    /**
     * @param array $paymentMethodNames
     * @param string $input
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @return void
     * @dataProvider startDataProvider
     */
    public function testStart(
        array $paymentMethodNames,
        string $input,
        array $shouldSeeReply,
        array $shouldSeeButtons
    ): void
    {
        $this->bootFixtures([
            Fixtures::USER_1,
            Fixtures::USER_2,
            Fixtures::USER_3,
            Fixtures::MESSENGER_USER_1_TELEGRAM,
            Fixtures::MESSENGER_USER_1_INSTAGRAM,
            Fixtures::MESSENGER_USER_2_TELEGRAM,
            Fixtures::MESSENGER_USER_2_INSTAGRAM,
            Fixtures::MESSENGER_USER_3_TELEGRAM,
            Fixtures::MESSENGER_USER_3_INSTAGRAM,
            Fixtures::TG_BOT_1,
        ]);

        array_map(
            fn (TelegramBotPaymentMethodName $name): TelegramBotPaymentMethod => $this->createPaymentMethod($name),
            $paymentMethodNames
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep(
                $this->getConversation(),
                SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED
            )
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function startDataProvider(): Generator
    {
        yield 'button & one payment method' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
            ],
            'input' => $this->command('subscribe'),
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
        ];

        yield 'button & two payment methods' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
                TelegramBotPaymentMethodName::liqpay,
            ],
            'input' => $this->command('subscribe'),
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
        ];

        yield 'command & one payment method' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
            ],
            'input' => FeedbackTelegramBotGroup::SUBSCRIBE,
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
        ];

        yield 'command & two payment methods' => [
            'paymentMethodNames' => [
                TelegramBotPaymentMethodName::portmone,
                TelegramBotPaymentMethodName::liqpay,
            ],
            'input' => FeedbackTelegramBotGroup::SUBSCRIBE,
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
        ];
    }

    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $input
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider currencyStepDataProvider
     */
    public function testCurrencyStep(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $input,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            Fixtures::USER_1,
            Fixtures::USER_2,
            Fixtures::USER_3,
            Fixtures::MESSENGER_USER_1_TELEGRAM,
            Fixtures::MESSENGER_USER_1_INSTAGRAM,
            Fixtures::MESSENGER_USER_2_TELEGRAM,
            Fixtures::MESSENGER_USER_2_INSTAGRAM,
            Fixtures::MESSENGER_USER_3_TELEGRAM,
            Fixtures::MESSENGER_USER_3_INSTAGRAM,
            Fixtures::TG_BOT_1,
            Fixtures::TG_BOT_PAYMENT_METHOD_1,
        ]);

        $conversation = $this->createConversation(
            SubscribeTelegramBotConversation::class,
            (new SubscribeTelegramBotConversationState())
                ->setCurrencyStep($currencyStep)
                ->setPaymentMethodStep($paymentMethodStep)
                ->setStep(SubscribeTelegramBotConversation::STEP_CURRENCY_QUERIED)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function currencyStepDataProvider(): Generator
    {
        yield 'select currency & no currency step & no payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => false,
            'input' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'input' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                'keyboard.change_currency',
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'input' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];

        yield 'select currency & currency step & no payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => false,
            'input' => '🇺🇸 USD',
            'shouldSeeReply' => [
                'query.subscription_plan',
            ],
            'shouldSeeButtons' => [
                ...array_map(
                    fn (FeedbackSubscriptionPlanName $plan) => $plan->name,
                    FeedbackSubscriptionPlanName::cases()
                ),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED,
        ];
    }

    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $input
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider subscriptionPlanStepDataProvider
     */
    public function testSubscriptionPlanStep(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $input,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            Fixtures::USER_1,
            Fixtures::USER_2,
            Fixtures::USER_3,
            Fixtures::MESSENGER_USER_1_TELEGRAM,
            Fixtures::MESSENGER_USER_1_INSTAGRAM,
            Fixtures::MESSENGER_USER_2_TELEGRAM,
            Fixtures::MESSENGER_USER_2_INSTAGRAM,
            Fixtures::MESSENGER_USER_3_TELEGRAM,
            Fixtures::MESSENGER_USER_3_INSTAGRAM,
            Fixtures::TG_BOT_1,
            Fixtures::TG_BOT_PAYMENT_METHOD_1,
        ]);

        $conversation = $this->createConversation(
            SubscribeTelegramBotConversation::class,
            (new SubscribeTelegramBotConversationState())
                ->setCurrencyStep($currencyStep)
                ->setPaymentMethodStep($paymentMethodStep)
                ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                ->setStep(SubscribeTelegramBotConversation::STEP_SUBSCRIPTION_PLAN_QUERIED)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    public function subscriptionPlanStepDataProvider(): Generator
    {
        yield 'select plan & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'input' => 'one_year - $20,00',
            'shouldSeeReply' => [
                'query.payment_method',
            ],
            'shouldSeeButtons' => [
                'payment_method.portmone',
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED,
        ];

        yield 'select plan & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'input' => 'one_year - $20,00',
            'shouldSeeReply' => [
                'query.payment_method',
            ],
            'shouldSeeButtons' => [
                'payment_method.portmone',
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED,
        ];
    }


    /**
     * @param bool $currencyStep
     * @param bool $paymentMethodStep
     * @param string $input
     * @param array $shouldSeeReply
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider paymentMethodStepDataProvider
     */
    public function testPaymentMethodStep(
        bool $currencyStep,
        bool $paymentMethodStep,
        string $input,
        array $shouldSeeReply,
        array $shouldSeeButtons,
        ?int $shouldSeeStep
    ): void
    {
        $this->bootFixtures([
            Fixtures::USER_1,
            Fixtures::USER_2,
            Fixtures::USER_3,
            Fixtures::MESSENGER_USER_1_TELEGRAM,
            Fixtures::MESSENGER_USER_1_INSTAGRAM,
            Fixtures::MESSENGER_USER_2_TELEGRAM,
            Fixtures::MESSENGER_USER_2_INSTAGRAM,
            Fixtures::MESSENGER_USER_3_TELEGRAM,
            Fixtures::MESSENGER_USER_3_INSTAGRAM,
            Fixtures::TG_BOT_1,
            Fixtures::TG_BOT_PAYMENT_METHOD_1,
        ]);

        $conversation = $this->createConversation(
            SubscribeTelegramBotConversation::class,
            (new SubscribeTelegramBotConversationState())
                ->setCurrencyStep($currencyStep)
                ->setPaymentMethodStep(true)
                ->setCurrency($this->getCurrencyProvider()->getCurrency('USD'))
                ->setSubscriptionPlan($this->getFeedbackSubscriptionPlanProvider()->getSubscriptionPlan(FeedbackSubscriptionPlanName::one_year))
                ->setStep(SubscribeTelegramBotConversation::STEP_PAYMENT_METHOD_QUERIED)
        );

        $paymentRepository = $this->getTelegramBotPaymentRepository();
        $previousPaymentCount = $paymentRepository->count([]);

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReply)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;

        $this->assertEquals($previousPaymentCount + 1, $paymentRepository->count([]));
        $this->assertCount(1, $this->getTelegramBotInvoiceSender()->getCalls());
    }

    public function paymentMethodStepDataProvider(): Generator
    {
        yield 'select method & no currency step & payment method step' => [
            'currencyStep' => false,
            'paymentMethodStep' => true,
            'input' => 'payment_method.portmone',
            'shouldSeeReply' => [
                'query.payment',
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_QUERIED,
        ];

        yield 'select method & currency step & payment method step' => [
            'currencyStep' => true,
            'paymentMethodStep' => true,
            'input' => 'payment_method.portmone',
            'shouldSeeReply' => [
                'query.payment',
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => SubscribeTelegramBotConversation::STEP_PAYMENT_QUERIED,
        ];
    }
}
