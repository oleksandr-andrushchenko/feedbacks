<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Telegram\Bot;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotConversation;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Entity\User\User;
use App\Enum\Feedback\Rating;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use App\Service\Telegram\Bot\TelegramBotChatProvider;
use App\Service\Telegram\Bot\TelegramBotKeyboardFactory;
use App\Service\Telegram\Bot\TelegramBotUserProvider;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures;
use App\Tests\Traits\EntityManagerProviderTrait;
use App\Tests\Traits\Feedback\SearchTermParserProviderTrait;
use App\Tests\Traits\Intl\CountryProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserRepositoryProviderTrait;
use App\Tests\Traits\SerializerProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotAwareHelperProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotChatProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotConversationRepositoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotKeyboardFactoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotMessageSenderProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotRegistryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotRepositoryProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateFixtureProviderTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUpdateHandlerTrait;
use App\Tests\Traits\Telegram\Bot\TelegramBotUserProviderTrait;
use App\Tests\Traits\TranslatorProviderTrait;
use App\Tests\Traits\User\UserRepositoryProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class TelegramBotCommandFunctionalTestCase extends DatabaseTestCase
{
    use TelegramBotUpdateHandlerTrait;
    use TelegramBotUpdateFixtureProviderTrait;
    use TelegramBotRegistryProviderTrait;
    use TelegramBotMessageSenderProviderTrait;
    use TranslatorProviderTrait;
    use TelegramBotKeyboardFactoryProviderTrait;
    use TelegramBotConversationRepositoryProviderTrait;
    use EntityManagerProviderTrait;
    use MessengerUserRepositoryProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use ArraySubsetAsserts;
    use SearchTermParserProviderTrait;
    use SerializerProviderTrait;
    use TelegramBotAwareHelperProviderTrait;
    use TelegramBotUserProviderTrait;
    use TelegramBotChatProviderTrait;
    use TelegramBotRepositoryProviderTrait;
    use CountryProviderTrait;
    use UserRepositoryProviderTrait;

    protected ?TelegramBot $bot;
    protected ?TelegramBotAwareHelper $tg;
    protected ?Update $update;
    protected TranslatorInterface $translator;
    protected TelegramBotKeyboardFactory $keyboardFactory;
    protected TelegramBotUserProvider $userProvider;
    protected TelegramBotChatProvider $chatProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->bot = null;
        $this->tg = null;
        $this->update = null;
        $this->translator = $this->getTranslator();
        $this->keyboardFactory = $this->getTelegramBotKeyboardFactory();
        $this->userProvider = $this->getTelegramBotUserProvider();
        $this->chatProvider = $this->getTelegramBotChatProvider();
    }

    protected function getBot(): TelegramBot
    {
        if ($this->bot === null) {
            $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
            $this->bot = $this->getTelegramBotRegistry()->getTelegramBot($bot);
        }

        return $this->bot;
    }

    protected function getTg(): TelegramBotAwareHelper
    {
        if ($this->tg === null) {
            $this->tg = $this->getTelegramBotAwareHelper()->withTelegramBot($this->getBot());
        }

        return $this->tg;
    }

    protected function getUpdate(): Update
    {
        if ($this->update === null) {
            $this->update = $this->getTelegramMessageUpdateFixture([
                'text' => 'any',
            ]);
        }

        return $this->update;
    }

    protected static function getContainer(): Container
    {
        return parent::getContainer();
    }

    protected function getUpdateUserId(): ?int
    {
        return $this->userProvider->getTelegramUserByUpdate($this->getBot()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateChatId(): ?int
    {
        return $this->chatProvider->getTelegramChatByUpdate($this->getBot()->getUpdate() ?? $this->getUpdate())?->getId();
    }

    protected function getUpdateMessengerUser(): ?MessengerUser
    {
        return $this->getBot()->getMessengerUser() ?? $this->getMessengerUserRepository()->findOneByMessengerAndIdentifier(Messenger::telegram, (string) $this->getUpdateUserId());
    }

    protected function getUpdateUser(): ?User
    {
        $messengerUser = $this->getUpdateMessengerUser();
        if ($messengerUser === null) {
            return null;
        }
        return $this->getUserRepository()->find($messengerUser->getUserId());
    }

    protected function createConversation(string $class, TelegramBotConversationState $state): TelegramBotConversation
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getBot()->getEntity()->getId();

        $conversation = new TelegramBotConversation(
            $messengerUserId . '-' . $chatId . '-' . $botId,
            $messengerUserId,
            (string) $chatId,
            $botId,
            $class,
            $this->getSerializer()->normalize($state)
        );
        $this->getEntityManager()->persist($conversation);
        $this->getEntityManager()->flush();

        return $conversation;
    }

    protected function createPaymentMethod(TelegramBotPaymentMethodName $name): TelegramBotPaymentMethod
    {
        $paymentMethod = new TelegramBotPaymentMethod(
            $this->getBot()->getEntity(),
            $name,
            'any',
            ['USD', 'EUR', 'UAH'],
        );
        $this->getEntityManager()->persist($paymentMethod);
        $this->getEntityManager()->flush();

        return $paymentMethod;
    }

    protected function typeText(string $command): static
    {
        $this->bot = null;
        $this->tg = null;
        $this->update = $this->getTelegramMessageUpdateFixture([
            'text' => $command,
        ]);
        $this->getBot()->setUpdate(null);
        $this->getBot()->setMessengerUser(null);
        $this->handleTelegramBotUpdate($this->getBot()->getEntity(), $this->getUpdate());

        return $this;
    }

    protected function typeLocation(string $latitude, string $longitude): static
    {
        $this->bot = null;
        $this->tg = null;
        $this->update = $this->getTelegramMessageUpdateFixture([
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ]);
        $this->getBot()->setUpdate(null);
        $this->getBot()->setMessengerUser(null);
        $this->handleTelegramBotUpdate($this->getBot()->getEntity(), $this->getUpdate());

        return $this;
    }

    protected function shouldSeeActiveConversation(string $expectedClass, TelegramBotConversationState $expectedState): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, true);
    }

    protected function shouldNotSeeActiveConversation(string $expectedClass = null, TelegramBotConversationState $expectedState = null): static
    {
        return $this->shouldSeeConversation($expectedClass, $expectedState, false);
    }

    protected function getUser(): ?User
    {
        return $this->getUpdateUser();
    }

    protected function getConversation(): ?TelegramBotConversation
    {
        $messengerUserId = $this->getUpdateMessengerUser()->getId();
        $chatId = $this->getUpdateChatId();
        $botId = $this->getBot()->getEntity()->getId();

        return $this->getTelegramBotConversationRepository()->findOneNonDeletedByHash($messengerUserId . '-' . $chatId . '-' . $botId);
    }

    protected function shouldSeeConversation(?string $expectedClass, ?TelegramBotConversationState $expectedState, bool $active): static
    {
        $conversation = $this->getConversation();

        if ($active) {
            $this->assertConversationActive($conversation);
        } else {
            $this->assertConversationInactive($conversation);
        }

        if ($conversation !== null) {
            if ($expectedClass !== null) {
                $this->assertEquals($expectedClass, $conversation->getClass());
            }
            if ($expectedState !== null) {
                $this->assertEquals(
                    $expectedState,
                    $this->getSerializer()->denormalize($conversation->getState(), get_class($expectedState))
                );
            }
        }

        return $this;
    }

    protected function shouldSeeStateStep(TelegramBotConversation $conversation, ?int $shouldSeeStep): static
    {
        if ($shouldSeeStep !== null) {
            $this->assertEquals($shouldSeeStep, $conversation->getState()['step']);
        }

        return $this;
    }

    protected function shouldSeeReply(string ...$shouldSeeReplies): static
    {
        /** @var string[] $actualReplies */
        /** @var string[] $shouldSeeReplies */
        $actualReplies = array_map(
            fn (array $call) => $call[2],
            $this->getTelegramBotMessageSender()->getCalls()
        );

        // todo: check order
        foreach ($shouldSeeReplies as $expectedReply) {
            $contains = false;
            foreach ($actualReplies as $actualReply) {
                if (str_contains($actualReply, $expectedReply)) {
                    $contains = true;
                    break;
                }
            }
            if ($contains) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false, sprintf('"%s" was not found in [%s]', $expectedReply, '"' . join('", "', $actualReplies) . '"'));
            }
        }

        return $this;
    }

    protected function shouldSeeButtons(...$shouldSeeButtons): static
    {
        /** @var Keyboard[] $actualKeyboards */
        /** @var KeyboardButton[]|string[] $shouldSeeButtons */
        $actualKeyboards = array_map(
            fn (array $call) => $call[3],
            $this->getTelegramBotMessageSender()->getCalls()
        );
        $actualKeyboards = array_values(array_filter($actualKeyboards));

        $actualButtons = [];

        foreach ($actualKeyboards as $actualKeyboard) {
            foreach ($actualKeyboard->getRawData()['keyboard'] as $row) {
                foreach ($row as $button) {
                    /** @var KeyboardButton $button */
                    $actualButtons[] = $button->getText();
                }
            }
        }

        $shouldSeeButtons = array_map(
            fn ($button) => is_string($button) ? $button : $button->getText(),
            $shouldSeeButtons
        );

        // todo: check order
        foreach ($shouldSeeButtons as $expectedButton) {
            $contains = false;
            foreach ($actualButtons as $actualButton) {
                if (str_contains($actualButton, $expectedButton)) {
                    $contains = true;
                    break;
                }
            }
            if ($contains) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false, sprintf('"%s" was not found in [%s]', $expectedButton, '"' . join('", "', $actualButtons) . '"'));
            }
        }
        return $this;
    }

    protected function chooseActionReplies(): array
    {
        return [
            'query.action',
        ];
    }

    protected function chooseActionButtons(
        bool $extended = false,
        bool $subscribe = true
    ): array
    {
        $buttons = [
            $this->command('create'),
            $this->command('search'),
            $this->command('contact'),
        ];

        if ($extended) {
            $buttons = array_merge($buttons, [
//                $this->command('lookup'),
                $subscribe ? $this->command('subscribe') : $this->command('subscriptions'),
                $this->command('country'),
                $this->command('locale'),
                $this->command('purge'),
                $this->command('donate'),
                $this->command('commands'),
                $this->command('limits'),
                $this->command('restart'),
                'keyboard.less',
            ]);
        } else {
            $buttons[] = 'keyboard.more';
        }

        return $buttons;
    }

    protected function cancelReplies(): array
    {
        return [
            'reply.canceled',
        ];
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param SearchTermType|null $expectedType
     * @return SearchTermTransfer
     */
    protected function addSearchTermPossibleTypes(SearchTermTransfer $searchTerm, SearchTermType $expectedType = null): SearchTermTransfer
    {
        $this->getSearchTermParser()->parseWithGuessType($searchTerm);

        if ($expectedType === null) {
            return $searchTerm;
        }

        $possibleTypes = $searchTerm->getTypes() ?? [];

        if (!in_array($expectedType, $possibleTypes, true)) {
            $searchTerm->addType($expectedType);
        }

        return $searchTerm;
    }

    protected function prevButton(): string
    {
        return '⬅️ keyboard.prev';
    }

    protected function nextButton(): string
    {
        return 'keyboard.next ➡️';
    }

    protected function helpButton(): string
    {
        return '🚨 keyboard.help';
    }

    protected function cancelButton(): string
    {
        return '❌ keyboard.cancel';
    }

    protected function yesButton(): string
    {
        return '✅ keyboard.yes';
    }

    protected function noButton(): string
    {
        return '⭕️ keyboard.no';
    }

    protected function command(string $name): string
    {
        return $name . ' ' . $name;
    }

    protected function commandButton(string $name): string
    {
        return $name . ' ' . $name;
    }

    protected function removeButton(string $text): string
    {
        return '❌ ' . $text;
    }

    protected function ratingButton(Rating $rating): string
    {
        return $rating->name . ' ' . $rating->name;
    }

    protected function searchTermTypeButton(SearchTermType $type): string
    {
        return $type->name . ' ' . $type->name;
    }

    protected function searchTermTypeTrans(SearchTermType $type): string
    {
        return $type->name;
    }

    protected function selectedText(string $text): string
    {
        return '*' . $text;
    }

    protected function assertConversationActive(?TelegramBotConversation $conversation): void
    {
        $this->assertNotNull($conversation);
        $this->assertNotNull($this->getTelegramBotConversationRepository()->findOneNonDeletedByHash($conversation->getHash()));
    }

    protected function assertConversationInactive(?TelegramBotConversation $conversation): void
    {
        if ($conversation === null) {
            $this->assertNull(null);
        } else {
            $this->assertNull($this->getTelegramBotConversationRepository()->findOneNonDeletedByHash($conversation->getHash()));
        }
    }

    protected function okReplies(): array
    {
        return [
            'reply.ok',
        ];
    }

    protected function wrongReplies(): array
    {
        return [
            'reply.wrong',
        ];
    }
}