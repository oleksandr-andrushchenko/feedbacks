<?php

declare(strict_types=1);

namespace App\Service\Feedback\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use App\Message\Event\ActivityEvent;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Model\Telegram\TelegramBotCommandHandler;
use App\Model\Telegram\TelegramBotErrorHandler;
use App\Model\Telegram\TelegramBotFallbackHandler;
use App\Model\Telegram\TelegramBotMyChatMemberHandler;
use App\Service\ContactOptionsFactory;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\Feedback\Telegram\Bot\Chat\ChooseActionTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\Chat\StartTelegramCommandHandler;
use App\Service\Feedback\Telegram\Bot\Chat\SubscriptionsTelegramChatSender;
use App\Service\Feedback\Telegram\Bot\Conversation\CountryTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\CreateFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\LocaleTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\LookupFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\PurgeConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\RestartConversationTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\SearchFeedbackTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\Conversation\SubscribeTelegramBotConversation;
use App\Service\Feedback\Telegram\Bot\View\SubscriptionTelegramViewProvider;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationFactory;
use App\Service\Telegram\Bot\Group\TelegramBotGroup;
use App\Service\Telegram\Bot\Group\TelegramBotGroupInterface;
use App\Service\Telegram\Bot\TelegramBotAwareHelper;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class FeedbackTelegramBotGroup extends TelegramBotGroup implements TelegramBotGroupInterface
{
    public const START = '/start';
    public const CREATE = '/add';
    public const SEARCH = '/find';
    public const LOOKUP = '/lookup';
    public const SUBSCRIBE = '/subscribe';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const LOCALE = '/locale';
    public const LIMITS = '/limits';
    public const PURGE = '/purge';
    public const DONATE = '/donate';
    public const CONTACT = '/contact';
    public const COMMANDS = '/commands';
    public const RESTART = '/restart';

    public const SUPPORTS = [
        'create' => self::CREATE,
        'search' => self::SEARCH,
        'lookup' => self::LOOKUP,
        'subscribe' => self::SUBSCRIBE,
        'subscriptions' => self::SUBSCRIPTIONS,
        'country' => self::COUNTRY,
        'locale' => self::LOCALE,
        'limits' => self::LIMITS,
        'purge' => self::PURGE,
        'donate' => self::DONATE,
        'contact' => self::CONTACT,
        'commands' => self::COMMANDS,
        'restart' => self::RESTART,
    ];

    public function __construct(
        TelegramBotAwareHelper $telegramBotAwareHelper,
        TelegramBotConversationFactory $telegramBotConversationFactory,
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
        private readonly SubscriptionsTelegramChatSender $subscriptionsTelegramChatSender,
        private readonly ChooseActionTelegramChatSender $chooseActionTelegramChatSender,
        private readonly StartTelegramCommandHandler $startTelegramCommandHandler,
        private readonly SubscriptionTelegramViewProvider $subscriptionTelegramViewProvider,
        private readonly FeedbackCommandOptions $feedbackCreateCommandOptions,
        private readonly FeedbackCommandOptions $feedbackSearchCommandOptions,
        private readonly FeedbackCommandOptions $feedbackLookupCommandOptions,
        private readonly MessageBusInterface $eventBus,
        private readonly ContactOptionsFactory $contactOptionsFactory,
        private readonly array $crypto,
    )
    {
        parent::__construct($telegramBotAwareHelper, $telegramBotConversationFactory);
    }

    protected function getHandlers(TelegramBotAwareHelper $tg): iterable
    {
        yield new TelegramBotMyChatMemberHandler(fn (): null => $this->myChatMemberHandler($tg));

        yield new TelegramBotCommandHandler(self::START, fn (): null => $this->start($tg), menu: false);
        yield new TelegramBotCommandHandler(self::CREATE, fn (): null => $this->create($tg), menu: true, key: 'create', force: true);
        yield new TelegramBotCommandHandler(self::SEARCH, fn (): null => $this->search($tg), menu: true, key: 'search', force: true);
        yield new TelegramBotCommandHandler(self::LOOKUP, fn (): null => $this->lookup($tg), menu: true, key: 'lookup', force: true);
        yield new TelegramBotCommandHandler(self::SUBSCRIBE, fn (): null => $this->subscribe($tg), menu: true, key: 'subscribe', force: true);
        yield new TelegramBotCommandHandler(self::SUBSCRIPTIONS, fn (): null => $this->subscriptions($tg), menu: true, key: 'subscriptions', force: true);
        yield new TelegramBotCommandHandler(self::COUNTRY, fn (): null => $this->country($tg), menu: true, key: 'country', force: true);
        yield new TelegramBotCommandHandler(self::LOCALE, fn (): null => $this->locale($tg), menu: true, key: 'locale', force: true);
        yield new TelegramBotCommandHandler(self::LIMITS, fn (): null => $this->limits($tg), menu: true, key: 'locale', force: true);
        yield new TelegramBotCommandHandler(self::PURGE, fn (): null => $this->purge($tg), menu: true, key: 'purge', force: true);
        yield new TelegramBotCommandHandler(self::DONATE, fn (): null => $this->donate($tg), menu: true, key: 'donate', force: true);
        yield new TelegramBotCommandHandler(self::CONTACT, fn (): null => $this->contact($tg), menu: true, key: 'contact', force: true);
        yield new TelegramBotCommandHandler(self::COMMANDS, fn (): null => $this->commands($tg), menu: true, key: 'commands', force: true);
        yield new TelegramBotCommandHandler(self::RESTART, fn (): null => $this->restart($tg), menu: true, key: 'restart', force: true);

        yield new TelegramBotFallbackHandler(fn (): null => $this->fallback($tg));
        yield new TelegramBotErrorHandler(fn (Throwable $exception): null => $this->exception($tg));
    }

    public function fallback(TelegramBotAwareHelper $tg): null
    {
        return match (true) {
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getCreateButtonTexts($tg), true) => $this->create($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getSearchButtonTexts($tg), true) => $this->search($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getLookupButtonTexts($tg), true) => $this->lookup($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getSubscribeButtonTexts($tg), true) => $this->subscribe($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getSubscriptionsButtonTexts($tg), true) => $this->subscriptions($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getCountryButtonTexts($tg), true) => $this->country($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getLocaleButtonTexts($tg), true) => $this->locale($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getLimitsButtonTexts($tg), true) => $this->limits($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getPurgeButtonTexts($tg), true) => $this->purge($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getDonateButtonTexts($tg), true) => $this->donate($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getContactButtonTexts($tg), true) => $this->contact($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getCommandsButtonTexts($tg), true) => $this->commands($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getRestartButtonTexts($tg), true) => $this->restart($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getShowLessButtonTexts($tg), true) => $this->less($tg),
            in_array($tg->getInput(), $this->chooseActionTelegramChatSender->getShowMoreButtonTexts($tg), true) => $this->more($tg),
            default => $this->wrong($tg)
        };
    }

    public function supportsUpdate(TelegramBotAwareHelper $tg): bool
    {
        $update = $tg->getBot()->getUpdate();

        if ($update->getMyChatMember() !== null) {
            return true;
        }

        return $update->getMessage()?->getChat()->getType() === 'private';
    }

    public function myChatMemberHandler(TelegramBotAwareHelper $tg): null
    {
        $messengerUser = $tg->getBot()->getMessengerUser();
        $newChatMemberStatus = $tg->getBot()->getUpdate()->getMyChatMember()?->getNewChatMember()?->getStatus();

        if ($newChatMemberStatus === 'kicked') {
            $messengerUser->removeBotId($tg->getBot()->getEntity()->getId());
            $tg->stopCurrentConversation();

            $this->eventBus->dispatch(new ActivityEvent(entity: $messengerUser, action: 'left a bot'));
        }

        return null;
    }

    public function exception(TelegramBotAwareHelper $tg): null
    {
        $message = implode("\n▫️", [
            $tg->queryText($tg->trans('reply.fail')) . ':',
            $tg->trans('reply.fail_1'),
            $tg->trans('reply.fail_2', [
                'contact_command' => $tg->command('contact', html: true, link: true),
            ]),
            $tg->trans('reply.fail_3'),
        ]);
        $message = $tg->failText($message);

        $tg->reply($message);

        $tg->stopCurrentConversation();

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function start(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        return $this->startTelegramCommandHandler->handleStart($tg);
    }

    public function create(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CreateFeedbackTelegramBotConversation::class)->null();
    }

    public function search(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(SearchFeedbackTelegramBotConversation::class)->null();
    }

    public function lookup(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LookupFeedbackTelegramBotConversation::class)->null();
    }

    public function subscribe(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $messengerUser = $tg->getBot()->getMessengerUser();
        $activeSubscription = $this->feedbackSubscriptionManager->getActiveSubscription($messengerUser);

        if ($activeSubscription === null) {
            return $tg->startConversation(SubscribeTelegramBotConversation::class)->null();
        }

        $message = $tg->trans('reply.subscribe.already_have');

        $tg->reply($message);

        $message = $this->subscriptionTelegramViewProvider->getSubscriptionTelegramView($tg, $activeSubscription);

        $tg->reply($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function subscriptions(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $this->subscriptionsTelegramChatSender->sendFeedbackSubscriptions($tg);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function acceptPayment(TelegramBotPayment $payment, TelegramBotAwareHelper $tg): void
    {
        $this->feedbackSubscriptionManager->createFeedbackUserSubscriptionByTelegramPayment($payment);

        $tg->stopCurrentConversation();

        $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function country(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(CountryTelegramBotConversation::class)->null();
    }

    public function locale(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(LocaleTelegramBotConversation::class)->null();
    }

    public function limits(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('limits', [
            'commands' => [
                'create' => $this->feedbackCreateCommandOptions->getLimits(),
                'search' => $this->feedbackSearchCommandOptions->getLimits(),
                'lookup' => $this->feedbackLookupCommandOptions->getLimits(),
            ],
        ]);

        $tg->reply($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function purge(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(PurgeConversationTelegramBotConversation::class)->null();
    }

    public function donate(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('donate', ["crypto" => $this->crypto]);

        $tg->reply($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function contact(TelegramBotAwareHelper $tg): null
    {
//        return $tg->stopCurrentConversation()->startConversation(ContactTelegramBotConversation::class)->null();

        $tg->stopCurrentConversation();

        $contacts = $this->contactOptionsFactory->createContactOptionsByTelegramBot($tg->getBot()->getEntity());

        $message = $tg->view('contact', [
            'contacts' => $contacts,
        ]);

        $tg->reply($message, protectContent: true);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function commands(TelegramBotAwareHelper $tg): null
    {
        $tg->stopCurrentConversation();

        $message = $tg->view('commands', context: ['commands' => self::SUPPORTS]);

        $tg->reply($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function restart(TelegramBotAwareHelper $tg): null
    {
        return $tg->stopCurrentConversation()->startConversation(RestartConversationTelegramBotConversation::class)->null();
    }

    public function more(TelegramBotAwareHelper $tg): null
    {
        $tg->getBot()->getMessengerUser()->setShowExtendedKeyboard(true);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function less(TelegramBotAwareHelper $tg): null
    {
        $tg->getBot()->getMessengerUser()->setShowExtendedKeyboard(false);

        return $this->chooseActionTelegramChatSender->sendActions($tg);
    }

    public function wrong(TelegramBotAwareHelper $tg): ?string
    {
        $message = $tg->trans('reply.wrong');
        $message = $tg->wrongText($message);

        return $this->chooseActionTelegramChatSender->sendActions($tg, text: $message, appendDefault: true);
    }
}