<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Entity\Intl\Level1Region;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\LocaleTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use Generator;

class LocaleTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    /**
     * @param string|null $countryCode
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider startSuccessDataProvider
     */
    public function testStartSuccess(
        ?string $countryCode,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->bootFixtures([
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->getUser()
            ->setCountryCode($countryCode)
            ->setLocaleCode($localeCode)
        ;

        $this
            ->typeText($input)
            ->shouldSeeStateStep($this->getConversation(), $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
            ->shouldSeeComponents($countryCode, $localeCode)
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'countryCode' => 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->getCountryProvider()->getCountryIconByCode('ua') . ' locale',
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];

        yield 'command' => [
            'countryCode' => 'ua',
            'localeCode' => $locale = 'uk',
            'input' => FeedbackTelegramBotGroup::LOCALE,
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];
    }

    /**
     * @param string|null $countryCode
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @return void
     * @dataProvider changeConfirmStepDataProvider
     */
    public function testChangeConfirmStep(
        ?string $countryCode,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->test(
            $countryCode,
            $localeCode,
            LocaleTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $countryCode,
            $localeCode,
        );
    }

    public function changeConfirmStepDataProvider(): Generator
    {
        yield 'yes & guess' => [
            'countryCode' => 'ua',
            'localeCode' => 'ru',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'query.locale',
            ],
            'shouldSeeButtons' => [
                ...[
                    $this->locale('uk', 'ua'),
                    $this->locale('ru', 'ru'),
                ],
                $this->otherButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_GUESS_LOCALE_QUERIED,
        ];

        yield 'yes & no guess' => [
            'countryCode' => 'ro',
            'localeCode' => 'en',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'query.locale',
            ],
            'shouldSeeButtons' => [
                ...[
                    $this->locale('en', 'us'),
                    $this->locale('uk', 'ua'),
                    $this->locale('ru', 'ru'),
                ],
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_LOCALE_QUERIED,
        ];

        yield 'no' => [
            'countryCode' => 'ua',
            'localeCode' => 'uk',
            'input' => $this->noButton(),
            'shouldSeeReplies' => [
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'help' => [
            'countryCode' => 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'countryCode' => 'ua',
            'localeCode' => 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
        ];
    }

    /**
     * @param string|null $countryCode
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider guessLocaleStepDataProvider
     */
    public function testGuessLocaleStep(
        ?string $countryCode,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $localeCode,
            LocaleTelegramBotConversation::STEP_GUESS_LOCALE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLocaleCode,
        );
    }

    public function guessLocaleStepDataProvider(): Generator
    {
        yield 'select locale' => [
            'countryCode' => $country = 'ua',
            'localeCode' => 'ru',
            'input' => $this->locale($locale = 'uk', 'ua'),
            'shouldSeeReplies' => [
                ...$this->okReplies(),
                ...$this->currentReplies($locale),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'other' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'ua',
            'input' => $this->otherButton(),
            'shouldSeeReplies' => [
                'query.locale',
            ],
            'shouldSeeButtons' => [
                ...[
                    $this->locale('en', 'us'),
                    $this->locale('uk', 'ua'),
                    $this->locale('ru', 'ru'),
                ],
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_LOCALE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.locale',
            ],
            'shouldSeeButtons' => [
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_GUESS_LOCALE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    /**
     * @param string|null $countryCode
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider localeStepDataProvider
     */
    public function testLocaleStep(
        ?string $countryCode,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $localeCode,
            LocaleTelegramBotConversation::STEP_LOCALE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLocaleCode,
        );
    }

    public function localeStepDataProvider(): Generator
    {
        yield 'select locale' => [
            'countryCode' => $country = 'ua',
            'localeCode' => 'ru',
            'input' => $this->locale($locale = 'uk', 'ua'),
            'shouldSeeReplies' => [
                ...$this->okReplies(),
                ...$this->currentReplies($locale),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'prev' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'ua',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.locale',
            ],
            'shouldSeeButtons' => [
                ...[
                    $this->locale('uk', 'ua'),
                    $this->locale('ru', 'ru'),
                ],
                $this->otherButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_GUESS_LOCALE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.locale',
            ],
            'shouldSeeButtons' => [
                ...[
                    $this->locale('en', 'us'),
                    $this->locale('uk', 'ua'),
                    $this->locale('ru', 'ru'),
                ],
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => LocaleTelegramBotConversation::STEP_LOCALE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($locale),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    protected function test(
        ?string $countryCode,
        ?string $localeCode,
        ?int $stateStep,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->bootFixtures([
            Level1Region::class,
            User::class,
            MessengerUser::class,
            TelegramBot::class,
        ]);

        $this->getUser()
            ->setCountryCode($countryCode)
            ->setLocaleCode($localeCode)
        ;

        $conversation = $this->createConversation(
            LocaleTelegramBotConversation::class,
            (new TelegramBotConversationState())
                ->setStep($stateStep)
        );

        $this
            ->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    protected function shouldSeeComponents(
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $user = $this->getUser();

        $this->assertEquals($shouldSeeCountryCode, $user->getCountryCode());
        $this->assertEquals($shouldSeeLocaleCode, $user->getLocaleCode());
    }

    private function otherButton(): string
    {
        return '🌎 keyboard.other';
    }

    protected function currentReplies(
        string $locale,
    ): array
    {
        return [
            'reply.current_locale',
            $locale,
        ];
    }

    protected function locale(string $localeCode, string $countryCode): string
    {
        return $this->getCountryProvider()->getCountryIconByCode($countryCode) . ' ' . $localeCode;
    }
}
