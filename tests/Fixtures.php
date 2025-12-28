<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Intl\Level1Region;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Entity\Telegram\TelegramBotUpdate;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\ORM\EntityManager;
use DateTimeImmutable;
use RuntimeException;

class Fixtures
{
    public const string BOT_USERNAME_1 = 'any_bot';
    public const int INSTAGRAM_USER_ID_1 = 1;
    public const string INSTAGRAM_USERNAME_1 = '1dmy.tro2811';
    public const int INSTAGRAM_USER_ID_2 = 2;
    public const string INSTAGRAM_USERNAME_2 = 'wild_sss';
    public const int INSTAGRAM_USER_ID_3 = 3;
    public const string INSTAGRAM_USERNAME_3 = 'instasd';
    public const int TELEGRAM_USER_ID_1 = 409525390;
    public const string TELEGRAM_USERNAME_1 = 'Gatu_za1';
    public const int TELEGRAM_USER_ID_2 = 2;
    public const string TELEGRAM_USERNAME_2 = 'tg2';
    public const int TELEGRAM_USER_ID_3 = 3;
    public const string TELEGRAM_USERNAME_3 = 'tg3';
    public const int TELEGRAM_CHAT_ID_1 = 409525390;
    public const int TIKTOK_USER_ID_1 = 2;
    public const string TIKTOK_USERNAME_1 = '4dm.yt_ro2811';
    public const int TWITTER_USER_ID_1 = 3;
    public const string TWITTER_USERNAME_1 = '6dm_ytr.o2811';
    public const int YOUTUBE_USER_ID_1 = 4;
    public const string YOUTUBE_USERNAME_1 = '6dm_ytr.o2811';
    public const int VKONTAKTE_USER_ID_1 = 4;
    public const string VKONTAKTE_USERNAME_1 = '6dm_ytr.o2811';
    public const int UNKNOWN_USER_ID_1 = 5;
    public const string UNKNOWN_USERNAME_1 = 'unknown';

    public const string TG_BOT_1 = 'telegram_bot_1';
    public const string LEVEL_1_REGION_1_UA_KYIV = 'level_1_region_1_ua_kyiv';
    public const string LEVEL_1_REGION_2_UA_KYIV_OBLAST = 'level_1_region_2_ua_kyiv_oblast';
    public const string LEVEL_1_REGION_3_UA_LVIV_OBLAST = 'level_1_region_3_ua_lviv_oblast';
    public const string TG_BOT_PAYMENT_METHOD_1 = 'telegram_payment_method_1';
    public const string TG_BOT_UPDATE_1 = 'telegram_bot_update_1';
    public const string TG_BOT_UPDATE_2 = 'telegram_bot_update_2';
    public const string MESSENGER_USER_1_TELEGRAM = 'messenger_user_1_telegram';
    public const string MESSENGER_USER_1_INSTAGRAM = 'messenger_user_1_instagram';
    public const string MESSENGER_USER_2_TELEGRAM = 'messenger_user_2_telegram';
    public const string MESSENGER_USER_2_INSTAGRAM = 'messenger_user_2_instagram';
    public const string MESSENGER_USER_3_TELEGRAM = 'messenger_user_3_telegram';
    public const string MESSENGER_USER_3_INSTAGRAM = 'messenger_user_3_instagram';
    public const string USER_1 = 'user_1';
    public const string USER_2 = 'user_2';
    public const string USER_3 = 'user_3';
    public const string SEARCH_TERM_INSTAGRAM_PROFILE_URL_3 = 'search_term_instagram_profile_url_3';
    public const string SEARCH_TERM_INSTAGRAM_USERNAME_3 = 'search_term_instagram_username_3';
    public const string FEEDBACK_13_TELEGRAM_INSTAGRAM_PROFILE_URL = 'feedback_13_telegram_instagram_profile_url';
    public const string FEEDBACK_23_TELEGRAM_INSTAGRAM_USERNAME = 'feedback_23_telegram_instagram_username';
    public const string FEEDBACK_LOOKUP_13_TELEGRAM_INSTAGRAM_PROFILE_URL = 'feedback_lookup_13_telegram_instagram_profile_url';
    public const string FEEDBACK_LOOKUP_23_TELEGRAM_INSTAGRAM_USERNAME = 'feedback_lookup_23_telegram_instagram_username';
    public const string FEEDBACK_SEARCH_13_TELEGRAM_INSTAGRAM_PROFILE_URL = 'feedback_search_13_telegram_instagram_profile_url';
    public const string FEEDBACK_SEARCH_23_TELEGRAM_INSTAGRAM_USERNAME = 'feedback_search_23_telegram_instagram_username';
    public const string TG_BOT_CONVERSATION_1 = 'telegram_bot_conversation_1';


    private array $map;

    public function __construct(
        private EntityManager $em,
    )
    {
        $this->map = [];
        $this->mapTelegramBots();
        $this->mapLevel1Regions();
        $this->mapTelegramBotPaymentMethods();
        $this->mapTelegramBotUpdates();
        $this->mapMessengerUsers();
        $this->mapUsers();
        $this->mapSearchTerms();
        $this->mapFeedbacks();
        $this->mapFeedbackLookups();
        $this->mapFeedbackSearches();
        $this->mapTelegramBotConversations();
    }

    public function get(string $ref): object
    {
        if (!isset($this->map[$ref])) {
            throw new RuntimeException(sprintf(
                'Fixture with reference "%s" is not registered',
                $ref
            ));
        }

        if (is_callable($this->map[$ref])) {
            $this->map[$ref] = ($this->map[$ref])();
            $this->em->persist($this->map[$ref]);
            $this->em->flush();
        }

        return $this->map[$ref];
    }

    public function bootFixtures(array $refs): void
    {
        foreach ($refs as $ref) {
            $this->get($ref);
        }
    }

    private function mapTelegramBots(): void
    {
        $this->map[self::TG_BOT_1] = fn () => new TelegramBot(
            id: self::TG_BOT_1,
            username: self::BOT_USERNAME_1,
            group: TelegramBotGroupName::feedbacks,
            name: 'Any Bot',
            token: '0:any',
            countryCode: 'ua',
            localeCode: 'uk',
            checkUpdates: true,
            checkRequests: true,
            acceptPayments: true,
            adminIds: [],
            adminOnly: false,
            primary: true,
            descriptionsSynced: false,
            webhookSynced: false,
            commandsSynced: false,
        );
    }

    private function mapLevel1Regions(): void
    {
        $this->map[self::LEVEL_1_REGION_1_UA_KYIV] = fn () => new Level1Region(
            id: self::LEVEL_1_REGION_1_UA_KYIV,
            countryCode: 'ua',
            name: 'ua_kyiv',
            timezone: 'Europe/Kiev',
        );
        $this->map[self::LEVEL_1_REGION_2_UA_KYIV_OBLAST] = fn () => new Level1Region(
            id: self::LEVEL_1_REGION_2_UA_KYIV_OBLAST,
            countryCode: 'ua',
            name: 'ua_kyiv_oblast',
            timezone: 'Europe/Kiev',
        );
        $this->map[self::LEVEL_1_REGION_3_UA_LVIV_OBLAST] = fn () => new Level1Region(
            id: self::LEVEL_1_REGION_3_UA_LVIV_OBLAST,
            countryCode: 'ua',
            name: 'ua_lviv_oblast',
            timezone: 'Europe/Uzhgorod',
        );
    }

    private function mapTelegramBotPaymentMethods(): void
    {
        $this->map[self::TG_BOT_PAYMENT_METHOD_1] = fn () => new TelegramBotPaymentMethod(
            id: self::TG_BOT_PAYMENT_METHOD_1,
            telegramBot: $this->get(self::TG_BOT_1),
            name: TelegramBotPaymentMethodName::portmone,
            token: 'any',
            currencyCodes: ['USD', 'EUR', 'UAH'],
        );
    }

    private function mapTelegramBotUpdates(): void
    {
        $this->map[self::TG_BOT_UPDATE_1] = fn () => new TelegramBotUpdate(
            id: self::TG_BOT_UPDATE_1,
            data: [],
            telegramBot: $this->get(self::TG_BOT_1),
        );
        $this->map[self::TG_BOT_UPDATE_2] = fn () => new TelegramBotUpdate(
            id: self::TG_BOT_UPDATE_2,
            data: [],
            telegramBot: $this->get(self::TG_BOT_1),
        );
    }

    private function mapMessengerUsers(): void
    {
        $this->map[self::MESSENGER_USER_1_TELEGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_1_TELEGRAM,
            messenger: Messenger::telegram,
            identifier: (string) self::TELEGRAM_USER_ID_1,
            username: self::TELEGRAM_USERNAME_1,
            name: 'Tg 1 First Last',
            user: $this->get(self::USER_1),
            showExtendedKeyboard: false,
        );
        $this->map[self::MESSENGER_USER_1_INSTAGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_1_INSTAGRAM,
            messenger: Messenger::instagram,
            identifier: (string) self::INSTAGRAM_USER_ID_1,
            username: self::INSTAGRAM_USERNAME_1,
            name: 'Inst 1 First Last',
            user: $this->get(self::USER_1),
            showExtendedKeyboard: false,
        );
        $this->map[self::MESSENGER_USER_2_TELEGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_2_TELEGRAM,
            messenger: Messenger::telegram,
            identifier: (string) self::TELEGRAM_USER_ID_2,
            username: self::TELEGRAM_USERNAME_2,
            name: 'Tg 2 First Last',
            user: $this->get(self::USER_2),
            showExtendedKeyboard: false,
        );
        $this->map[self::MESSENGER_USER_2_INSTAGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_2_INSTAGRAM,
            messenger: Messenger::instagram,
            identifier: (string) self::INSTAGRAM_USER_ID_2,
            username: self::INSTAGRAM_USERNAME_2,
            name: 'Inst 2 First Last',
            user: $this->get(self::USER_2),
            showExtendedKeyboard: false,
        );
        $this->map[self::MESSENGER_USER_3_TELEGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_3_TELEGRAM,
            messenger: Messenger::telegram,
            identifier: (string) self::TELEGRAM_USER_ID_3,
            username: self::TELEGRAM_USERNAME_3,
            name: 'Tg 3 First Last',
            user: $this->get(self::USER_3),
            showExtendedKeyboard: false,
        );
        $this->map[self::MESSENGER_USER_3_INSTAGRAM] = fn () => new MessengerUser(
            id: self::MESSENGER_USER_3_INSTAGRAM,
            messenger: Messenger::instagram,
            identifier: (string) self::INSTAGRAM_USER_ID_3,
            username: self::INSTAGRAM_USERNAME_3,
            name: 'Inst 3 First Last',
            user: $this->get(self::USER_3),
            showExtendedKeyboard: false,
        );
    }

    public function mapUsers(): void
    {
        $this->map[self::USER_1] = fn () => new User(
            id: self::USER_1,
            username: self::USER_1,
            name: 'User 1 First Last',
            countryCode: 'ua',
            level1RegionId: $this->get(self::LEVEL_1_REGION_1_UA_KYIV)->getId(),
            localeCode: 'uk',
            currencyCode: 'UAH',
            timezone: 'Europe/Kiev',
            phoneNumber: null,
            email: null,
        );
        $this->map[self::USER_2] = fn () => new User(
            id: self::USER_2,
            username: self::USER_2,
            name: 'User 2 First Last',
            countryCode: 'ua',
            level1RegionId: $this->get(self::LEVEL_1_REGION_2_UA_KYIV_OBLAST)->getId(),
            localeCode: 'uk',
            currencyCode: 'UAH',
            timezone: 'Europe/Kiev',
            phoneNumber: null,
            email: null,
        );
        $this->map[self::USER_3] = fn () => new User(
            id: self::USER_3,
            username: self::USER_3,
            name: 'User 3 First Last',
            countryCode: 'ua',
            level1RegionId: $this->get(self::LEVEL_1_REGION_3_UA_LVIV_OBLAST)->getId(),
            localeCode: 'uk',
            currencyCode: 'UAH',
            timezone: 'Europe/Kiev',
            phoneNumber: null,
            email: null,
        );
    }

    private function mapSearchTerms(): void
    {
        $this->map[self::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3] = fn () => new SearchTerm(
            id: self::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3,
            text: 'https://instagram.com/' . self::INSTAGRAM_USERNAME_3,
            normalizedText: self::INSTAGRAM_USERNAME_3,
            type: SearchTermType::instagram_username,
            messengerUser: null,
        );
        $this->map[self::SEARCH_TERM_INSTAGRAM_USERNAME_3] = fn () => new SearchTerm(
            id: self::SEARCH_TERM_INSTAGRAM_USERNAME_3,
            text: self::INSTAGRAM_USERNAME_3,
            normalizedText: self::INSTAGRAM_USERNAME_3,
            type: SearchTermType::instagram_username,
            messengerUser: null,
        );
    }

    private function mapFeedbacks(): void
    {
        $this->map[self::FEEDBACK_13_TELEGRAM_INSTAGRAM_PROFILE_URL] = fn () => new Feedback(
            id: self::FEEDBACK_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            user: $this->get(self::USER_1),
            countryCode: 'ua',
            localeCode: 'uk',
            hasActiveSubscription: false,
            messengerUser: $this->get(self::MESSENGER_USER_1_TELEGRAM),
            searchTerms: [$this->get(self::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3)],
            rating: Rating::neutral,
            text: 'neutral',
            telegramChannelMessageIds: null,
            telegramBot: null,
        );
        $this->map[self::FEEDBACK_23_TELEGRAM_INSTAGRAM_USERNAME] = fn () => new Feedback(
            id: self::FEEDBACK_23_TELEGRAM_INSTAGRAM_USERNAME,
            user: $this->get(self::USER_2),
            countryCode: 'ua',
            localeCode: 'uk',
            hasActiveSubscription: false,
            messengerUser: $this->get(self::MESSENGER_USER_2_TELEGRAM),
            searchTerms: [$this->get(self::SEARCH_TERM_INSTAGRAM_USERNAME_3)],
            rating: Rating::satisfied,
            text: 'awesome',
            telegramChannelMessageIds: null,
            telegramBot: null,
        );
    }

    private function mapFeedbackLookups(): void
    {
        $this->map[self::FEEDBACK_LOOKUP_13_TELEGRAM_INSTAGRAM_PROFILE_URL] = fn () => new FeedbackLookup(
            id: self::FEEDBACK_LOOKUP_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            searchTerm: $this->get(Fixtures::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3),
            user: $this->get(Fixtures::USER_1),
            hasActiveSubscription: false,
            countryCode: 'ua',
            localeCode: 'uk',
            messengerUser: $this->get(Fixtures::MESSENGER_USER_1_TELEGRAM),
            telegramBot: null,
            createdAt: new DateTimeImmutable('-3 months'),
        );
        $this->map[self::FEEDBACK_LOOKUP_23_TELEGRAM_INSTAGRAM_USERNAME] = fn () => new FeedbackLookup(
            id: self::FEEDBACK_LOOKUP_23_TELEGRAM_INSTAGRAM_USERNAME,
            searchTerm: $this->get(Fixtures::SEARCH_TERM_INSTAGRAM_USERNAME_3),
            user: $this->get(Fixtures::USER_2),
            hasActiveSubscription: false,
            countryCode: 'ua',
            localeCode: 'uk',
            messengerUser: $this->get(Fixtures::MESSENGER_USER_2_TELEGRAM),
            telegramBot: null,
            createdAt: new DateTimeImmutable('-2 months'),
        );
    }

    private function mapFeedbackSearches(): void
    {
        $this->map[self::FEEDBACK_SEARCH_13_TELEGRAM_INSTAGRAM_PROFILE_URL] = fn () => new FeedbackSearch(
            id: self::FEEDBACK_SEARCH_13_TELEGRAM_INSTAGRAM_PROFILE_URL,
            searchTerm: $this->get(Fixtures::SEARCH_TERM_INSTAGRAM_PROFILE_URL_3),
            user: $this->get(Fixtures::USER_1),
            hasActiveSubscription: false,
            countryCode: 'ua',
            localeCode: 'uk',
            messengerUser: $this->get(Fixtures::MESSENGER_USER_1_TELEGRAM),
            telegramBot: null,
        );
        $this->map[self::FEEDBACK_SEARCH_23_TELEGRAM_INSTAGRAM_USERNAME] = fn () => new FeedbackSearch(
            id: self::FEEDBACK_SEARCH_23_TELEGRAM_INSTAGRAM_USERNAME,
            searchTerm: $this->get(Fixtures::SEARCH_TERM_INSTAGRAM_USERNAME_3),
            user: $this->get(Fixtures::USER_2),
            hasActiveSubscription: false,
            countryCode: 'ua',
            localeCode: 'uk',
            messengerUser: $this->get(Fixtures::MESSENGER_USER_2_TELEGRAM),
            telegramBot: null,
        );
    }

    private function mapTelegramBotConversations(): void
    {
        $this->map[self::TG_BOT_CONVERSATION_1] = fn () => new TelegramBotConversation(
            hash: md5('1-' . self::TELEGRAM_CHAT_ID_1 . '-1'),
            messengerUserId: '1',
            chatId: (string) self::TELEGRAM_CHAT_ID_1,
            telegramBotId: '1',
            class: CreateFeedbackTelegramBotConversation::class,
            state: ['step' => CreateFeedbackTelegramBotConversation::STEP_SEARCH_TERM_QUERIED]
        );
    }
}