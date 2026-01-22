<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\Telegram\Bot\Conversation;

use App\Model\Address\Address;
use App\Model\Location;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Feedback\Telegram\Bot\Conversation\CountryTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Fake\Service\FakeAddressGeocoder;
use App\Tests\Fake\Service\FakeTimezoneGeocoder;
use App\Tests\Fixtures;
use App\Tests\Functional\Service\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Intl\Level1RegionRepositoryProviderTrait;
use Generator;

class CountryTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use Level1RegionRepositoryProviderTrait;

    /**
     * @param string|null $countryCode
     * @param string|null $level1RegionId
     * @param string|null $timezone
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
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->bootFixtures([
            Fixtures::LEVEL_1_REGION_1_UA_KYIV,
            Fixtures::LEVEL_1_REGION_2_UA_KYIV_OBLAST,
            Fixtures::LEVEL_1_REGION_3_UA_LVIV_OBLAST,
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

        $this->getUser()
            ->setCountryCode($countryCode)
            ->setLevel1RegionId($level1RegionId)
            ->setTimezone($timezone)
        ;

        $this->typeText($input)
            ->shouldSeeStateStep($this->getConversation(), $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
            ->shouldSeeComponents($countryCode, $level1RegionId, $timezone, $localeCode)
        ;
    }

    public function startSuccessDataProvider(): Generator
    {
        yield 'button' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => 'uk',
            'input' => $this->getCountryProvider()->getCountryIconByCode('ua') . ' country',
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];

        yield 'command' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => 'uk',
            'input' => FeedbackTelegramBotGroup::COUNTRY,
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];
    }

    /**
     * @param string|null $countryCode
     * @param string|null $level1RegionId
     * @param string|null $timezone
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
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->test(
            $countryCode,
            $level1RegionId,
            $timezone,
            $localeCode,
            CountryTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents($countryCode, $level1RegionId, $timezone, $localeCode);
    }

    public function changeConfirmStepDataProvider(): Generator
    {
        yield 'yes & guess' => [
            'countryCode' => 'ua',
            'level1RegionId' => 'ua_kyiv',
            'timezone' => 'kyiv/tz',
            'localeCode' => 'uk',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'query.country',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (string $country): string => $this->country($country), ['ua']),
                $this->otherButton(),
                $this->requestLocationButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'yes & no guess' => [
            'countryCode' => 'ua',
            'level1RegionId' => 'ua_kyiv',
            'timezone' => 'kyiv/tz',
            'localeCode' => 'en',
            'input' => $this->yesButton(),
            'shouldSeeReplies' => [
                'query.country',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (string $country): string => $this->country($country), ['au', 'ca', 'us', 'nz']),
                $this->otherButton(),
                $this->requestLocationButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => null,
        ];

        yield 'no' => [
            'countryCode' => 'ua',
            'level1RegionId' => 'ua_kyiv',
            'timezone' => 'kyiv/tz',
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
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                'query.change_confirm',
            ],
            'shouldSeeButtons' => [
                $this->yesButton(),
                $this->noButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_CHANGE_CONFIRM_QUERIED,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
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
     * @param string|null $level1RegionId
     * @param string|null $timezone
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLevel1RegionId
     * @param string|null $shouldSeeTimezone
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider guessCountryStepDataProvider
     */
    public function testGuessCountryStep(
        ?string $countryCode,
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLevel1RegionId,
        ?string $shouldSeeTimezone,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $level1RegionId,
            $timezone,
            $localeCode,
            CountryTelegramBotConversation::STEP_GUESS_COUNTRY_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLevel1RegionId,
            $shouldSeeTimezone,
            $shouldSeeLocaleCode,
        );
    }

    public function guessCountryStepDataProvider(): Generator
    {
        yield 'select country & same' => [
            'countryCode' => $country = 'ru',
            'level1RegionId' => $region1 = 'any',
            'timezone' => $tz = 'any',
            'localeCode' => $locale = 'ru',
            'input' => $this->country($country),
            'shouldSeeReplies' => [
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'select country & change' => [
            'countryCode' => 'ru',
            'level1RegionId' => 'any',
            'timezone' => 'any',
            'localeCode' => $locale = 'ru',
            'input' => $this->country($country = 'ua'),
            'shouldSeeReplies' => [
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                ...[
                    'ua_kyiv',
                    'ua_kyiv_oblast',
                    'ua_lviv_oblast',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => null,
            'shouldSeeTimezone' => 'Europe/Kiev',
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'other' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'Europe/Kiev',
            'localeCode' => $locale = 'ua',
            'input' => $this->otherButton(),
            'shouldSeeReplies' => [
                'query.country',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (string $country): string => $this->country($country), ['af', 'zm']),
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.country',
            ],
            'shouldSeeButtons' => [
                $this->requestLocationButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_GUESS_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    public function testGuessCountryStepRequestLocation(): void
    {
        $this->testRequestLocation(CountryTelegramBotConversation::STEP_GUESS_COUNTRY_QUERIED);
    }

    /**
     * @param string|null $countryCode
     * @param string|null $level1RegionId
     * @param string|null $timezone
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLevel1RegionId
     * @param string|null $shouldSeeTimezone
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider countryStepDataProvider
     */
    public function testCountryStep(
        ?string $countryCode,
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLevel1RegionId,
        ?string $shouldSeeTimezone,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $level1RegionId,
            $timezone,
            $localeCode,
            CountryTelegramBotConversation::STEP_COUNTRY_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLevel1RegionId,
            $shouldSeeTimezone,
            $shouldSeeLocaleCode,
        );
    }

    public function countryStepDataProvider(): Generator
    {
        yield 'select country & same' => [
            'countryCode' => $country = 'ru',
            'level1RegionId' => $region1 = 'any',
            'timezone' => $tz = 'any',
            'localeCode' => $locale = 'ru',
            'input' => $this->country($country),
            'shouldSeeReplies' => [
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'select country & change' => [
            'countryCode' => 'ru',
            'level1RegionId' => 'any',
            'timezone' => 'any',
            'localeCode' => $locale = 'ru',
            'input' => $this->country($country = 'ua'),
            'shouldSeeReplies' => [
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                ...[
                    'ua_kyiv',
                    'ua_kyiv_oblast',
                    'ua_lviv_oblast',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => null,
            'shouldSeeTimezone' => 'Europe/Kiev',
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'other' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'Europe/Kiev',
            'localeCode' => $locale = 'ua',
            'input' => $this->otherButton(),
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.country',
            ],
            'shouldSeeButtons' => [
                ...array_map(fn (string $country): string => $this->country($country), ['af', 'zm']),
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'prev' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'Europe/Kiev',
            'localeCode' => $locale = 'ua',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.country',
            ],
            'shouldSeeButtons' => [
                $this->requestLocationButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_GUESS_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.country',
            ],
            'shouldSeeButtons' => [
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    public function testCountryStepRequestLocation(): void
    {
        $this->testRequestLocation(CountryTelegramBotConversation::STEP_COUNTRY_QUERIED);
    }

    /**
     * @param string|null $countryCode
     * @param string|null $level1RegionId
     * @param string|null $timezone
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLevel1RegionId
     * @param string|null $shouldSeeTimezone
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider level1RegionStepDataProvider
     */
    public function testLevel1RegionStep(
        ?string $countryCode,
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLevel1RegionId,
        ?string $shouldSeeTimezone,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $level1RegionId,
            $timezone,
            $localeCode,
            CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLevel1RegionId,
            $shouldSeeTimezone,
            $shouldSeeLocaleCode,
        );
    }

    public function level1RegionStepDataProvider(): Generator
    {
        yield 'select region & same' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $region1,
            'shouldSeeReplies' => [
                'query.timezone',
            ],
            'shouldSeeButtons' => [
                ...[
                    'Europe/Kiev',
                    'Europe/Uzhgorod',
                    'Europe/Zaporozhye',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_TIMEZONE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'select region & change' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => 'any',
            'timezone' => 'any',
            'localeCode' => $locale = 'uk',
            'input' => $region1 = 'ua_lviv_oblast',
            'shouldSeeReplies' => [
                ...$this->okReplies(),
                ...$this->currentReplies($country, $region1, $tz = 'Europe/Uzhgorod'),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'unknown' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'Europe/Kiev',
            'localeCode' => $locale = 'ua',
            'input' => 'wrong',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                ...[
                    'ua_kyiv',
                    'ua_kyiv_oblast',
                    'ua_lviv_oblast',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'prev' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.country',
            ],
            'shouldSeeButtons' => [
                $this->country($country),
                $this->otherButton(),
                $this->requestLocationButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_GUESS_COUNTRY_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                ...[
                    'ua_kyiv',
                    'ua_kyiv_oblast',
                    'ua_lviv_oblast',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->currentReplies($country, $region1, $tz),
                ...$this->cancelReplies(),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    public function testLevel1RegionStepRequestLocation(): void
    {
        $this->testRequestLocation(CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED);
    }

    /**
     * @param string|null $countryCode
     * @param string|null $level1RegionId
     * @param string|null $timezone
     * @param string|null $localeCode
     * @param string $input
     * @param array $shouldSeeReplies
     * @param array $shouldSeeButtons
     * @param int|null $shouldSeeStep
     * @param string|null $shouldSeeCountryCode
     * @param string|null $shouldSeeLevel1RegionId
     * @param string|null $shouldSeeTimezone
     * @param string|null $shouldSeeLocaleCode
     * @return void
     * @dataProvider timezoneStepDataProvider
     */
    public function testTimezoneStep(
        ?string $countryCode,
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLevel1RegionId,
        ?string $shouldSeeTimezone,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $this->test(
            $countryCode,
            $level1RegionId,
            $timezone,
            $localeCode,
            CountryTelegramBotConversation::STEP_TIMEZONE_QUERIED,
            $input,
            $shouldSeeReplies,
            $shouldSeeButtons,
            $shouldSeeStep,
        );

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLevel1RegionId,
            $shouldSeeTimezone,
            $shouldSeeLocaleCode,
        );
    }

    public function timezoneStepDataProvider(): Generator
    {
        yield 'select region & same' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => 'any',
            'localeCode' => $locale = 'uk',
            'input' => $tz = 'Europe/Kiev',
            'shouldSeeReplies' => [
                ...$this->okReplies(),
                ...$this->currentReplies($country, $region1, $tz),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'unknown' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'Europe/Kiev',
            'localeCode' => $locale = 'ua',
            'input' => 'wrong',
            'shouldSeeReplies' => [
                ...$this->wrongReplies(),
                'query.timezone',
            ],
            'shouldSeeButtons' => [
                ...[
                    'Europe/Kiev',
                    'Europe/Uzhgorod',
                    'Europe/Zaporozhye',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_TIMEZONE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'prev' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->prevButton(),
            'shouldSeeReplies' => [
                'query.level_1_region',
            ],
            'shouldSeeButtons' => [
                ...[
                    'ua_kyiv',
                    'ua_kyiv_oblast',
                    'ua_lviv_oblast',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_LEVEL_1_REGION_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'help' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => $tz = 'kyiv/tz',
            'localeCode' => $locale = 'uk',
            'input' => $this->helpButton(),
            'shouldSeeReplies' => [
                'title',
                'query.timezone',
            ],
            'shouldSeeButtons' => [
                ...[
                    'Europe/Kiev',
                    'Europe/Uzhgorod',
                    'Europe/Zaporozhye',
                ],
                $this->requestLocationButton(),
                $this->prevButton(),
                $this->helpButton(),
                $this->cancelButton(),
            ],
            'shouldSeeStep' => CountryTelegramBotConversation::STEP_TIMEZONE_QUERIED,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];

        yield 'cancel' => [
            'countryCode' => $country = 'ua',
            'level1RegionId' => $region1 = 'ua_kyiv',
            'timezone' => 'Europe/Uzhgorod',
            'localeCode' => $locale = 'uk',
            'input' => $this->cancelButton(),
            'shouldSeeReplies' => [
                ...$this->cancelReplies(),
                ...$this->currentReplies($country, $region1, $tz = 'Europe/Kiev'),
                ...$this->chooseActionReplies(),
            ],
            'shouldSeeButtons' => [
                ...$this->chooseActionButtons(),
            ],
            'shouldSeeStep' => null,
            'shouldSeeCountryCode' => $country,
            'shouldSeeLevel1RegionId' => $region1,
            'shouldSeeTimezone' => $tz,
            'shouldSeeLocaleCode' => $locale,
        ];
    }

    public function testTimezoneStepRequestLocation(): void
    {
        $this->testRequestLocation(CountryTelegramBotConversation::STEP_TIMEZONE_QUERIED);
    }

    protected function test(
        ?string $countryCode,
        ?string $level1RegionId,
        ?string $timezone,
        ?string $localeCode,
        ?int $stateStep,
        string $input,
        array $shouldSeeReplies,
        array $shouldSeeButtons,
        ?int $shouldSeeStep,
    ): void
    {
        $this->bootFixtures([
            Fixtures::LEVEL_1_REGION_1_UA_KYIV,
            Fixtures::LEVEL_1_REGION_2_UA_KYIV_OBLAST,
            Fixtures::LEVEL_1_REGION_3_UA_LVIV_OBLAST,
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

        $this->getUser()
            ->setCountryCode($countryCode)
            ->setLevel1RegionId($level1RegionId)
            ->setTimezone($timezone)
            ->setLocaleCode($localeCode)
        ;

        $conversation = $this->createConversation(CountryTelegramBotConversation::class,
            (new TelegramBotConversationState())
                ->setStep($stateStep)
        );

        $this->typeText($input)
            ->shouldSeeStateStep($conversation, $shouldSeeStep)
            ->shouldSeeReply(...$shouldSeeReplies)
            ->shouldSeeButtons(...$shouldSeeButtons)
        ;
    }

    protected function testRequestLocation(int $step): void
    {
        $this->bootFixtures([
            Fixtures::LEVEL_1_REGION_1_UA_KYIV,
            Fixtures::LEVEL_1_REGION_2_UA_KYIV_OBLAST,
            Fixtures::LEVEL_1_REGION_3_UA_LVIV_OBLAST,
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

        $location = new Location('1.0', '2.0');

        $countryCode = 'ru';
        $level1RegionId = 'any';
        $timezone = 'any';
        $localeCode = 'ru';
        $input = [
            $location->getLatitude(),
            $location->getLongitude(),
        ];

        $shouldSeeLevel1Region = $this->getLevel1RegionRepository()->find(Fixtures::LEVEL_1_REGION_1_UA_KYIV);
        $shouldSeeLevel1Region->setTimezone(null);
        $this->getEntityManager()->flush();

        $shouldSeeAddress = new Address($shouldSeeLevel1Region->getCountryCode(), $shouldSeeLevel1Region->getName());
        FakeAddressGeocoder::setAddressMock($shouldSeeAddress);

        $shouldSeeCountryCode = $shouldSeeLevel1Region->getCountryCode();
        $shouldSeeLevel1RegionId = $shouldSeeLevel1Region->getId();

        $shouldSeeTimezone = FakeTimezoneGeocoder::timezoneMock();
        $shouldSeeLocaleCode = $localeCode;

        $shouldSeeReplies = [
            ...$this->okReplies(),
            ...$this->currentReplies(
                $shouldSeeCountryCode,
                $shouldSeeLevel1RegionId,
                $shouldSeeTimezone
            ),
            ...$this->chooseActionReplies(),
        ];
        $shouldSeeButtons = [
            ...$this->chooseActionButtons(),
        ];

        $this->getUser()
            ->setCountryCode($countryCode)
            ->setLevel1RegionId($level1RegionId)
            ->setTimezone($timezone)
            ->setLocaleCode($localeCode)
        ;

        $state = (new TelegramBotConversationState())
            ->setStep($step)
        ;

        $this->createConversation(CountryTelegramBotConversation::class, $state);

        $this->typeLocation(...$input)
            // todo: uncomment & fix
//            ->shouldSeeReply(...$shouldSeeReplies)
//            ->shouldSeeButtons(...$shouldSeeButtons)
        ;

        $this->shouldSeeComponents(
            $shouldSeeCountryCode,
            $shouldSeeLevel1RegionId,
            $shouldSeeTimezone,
            $shouldSeeLocaleCode,
        );

        $this->assertEquals($shouldSeeTimezone, $shouldSeeLevel1Region->getTimezone());
    }

    protected function shouldSeeComponents(
        ?string $shouldSeeCountryCode,
        ?string $shouldSeeLevel1RegionId,
        ?string $shouldSeeTimezone,
        ?string $shouldSeeLocaleCode,
    ): void
    {
        $user = $this->getUser();

        $this->assertEquals($shouldSeeCountryCode, $user->getCountryCode());
        $this->assertEquals($shouldSeeLevel1RegionId, $user->getLevel1RegionId());
        $this->assertEquals($shouldSeeTimezone, $user->getTimezone());
        $this->assertEquals($shouldSeeLocaleCode, $user->getLocaleCode());
    }

    private function otherButton(): string
    {
        return '🌎 keyboard.other';
    }

    private function requestLocationButton(): string
    {
        return '📍 keyboard.request_location';
    }

    protected function currentReplies(
        string $country,
        string $region1,
        string $tz,
    ): array
    {
        return [
            'reply.current_country',
            $this->country($country),
            'reply.current_region',
            $region1,
            'reply.current_timezone',
            $tz,
        ];
    }

    protected function country(string $countryCode): string
    {
        return $this->getCountryProvider()->getCountryIconByCode($countryCode) . ' ' . $countryCode;
    }
}
