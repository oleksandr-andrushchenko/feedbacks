<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversation;
use App\Model\Location;
use App\Model\Telegram\TelegramBotConversationState;
use App\Service\Telegram\Bot\Api\TelegramBotChatActionSenderInterface;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\Conversation\TelegramBotConversationManager;
use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TelegramBotAwareHelper
{
    private TelegramBot $bot;

    public function __construct(
        private readonly TelegramBotKeyboardFactory $telegramBotKeyboardFactory,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly TranslatorInterface $translator,
        private readonly TelegramBotConversationManager $telegramBotConversationManager,
        private readonly TelegramBotChatActionSenderInterface $telegramBotChatActionSender,
        private readonly TelegramBotChatProvider $telegramBotChatProvider,
        private readonly TelegramBotInputProvider $telegramBotInputProvider,
        private readonly Environment $twig,
    )
    {
    }

    public function withTelegramBot(TelegramBot $bot): static
    {
        $new = clone $this;
        $new->bot = $bot;

        return $new;
    }

    public function getBot(): TelegramBot
    {
        return $this->bot;
    }

    public function getInput(): ?string
    {
        $input = $this->telegramBotInputProvider->getTelegramInputByUpdate($this->getBot()->getUpdate());

        if ($input === null) {
            return null;
        }

        // replace multi spaces with single space
        $input = preg_replace('/ +/', ' ', $input);

        // remove empty lines
        return implode("\n", array_filter(explode("\n", $input), static fn (string $line): bool => !in_array($line, ['', ' '], true)));
    }

    public function matchInput(?string $text): bool
    {
        return $this->getInput() === $text;
    }

    public function getChatId(): ?int
    {
        return $this->telegramBotChatProvider->getTelegramChatByUpdate($this->getBot()->getUpdate())?->getId();
    }

    public function getLocation(): ?Location
    {
        $locationResponse = $this->getBot()->getUpdate()->getMessage()?->getLocation();

        if ($locationResponse === null) {
            return null;
        }

        return new Location($locationResponse->getLatitude(), $locationResponse->getLongitude());
    }

    public function getLocaleCode(): ?string
    {
        return $this->getBot()->getUser()?->getLocaleCode();
    }

    public function getCountryCode(): ?string
    {
        return $this->getBot()->getUser()?->getCountryCode();
    }

    public function getCurrencyCode(): ?string
    {
        return $this->getBot()->getUser()?->getCurrencyCode();
    }

    public function getTimezone(): ?string
    {
        return $this->getBot()->getUser()?->getTimezone();
    }

    public function startConversation(string $class): static
    {
        $this->telegramBotConversationManager->startTelegramConversation($this->getBot(), $class);

        return $this;
    }

    public function executeConversation(string $class, TelegramBotConversationState $state, string $method): static
    {
        $this->telegramBotConversationManager->executeTelegramConversation($this->getBot(), $class, $state, $method);

        return $this;
    }

    public function stopCurrentConversation(): static
    {
        $this->telegramBotConversationManager->stopCurrentTelegramConversation($this->getBot());

        return $this;
    }

    public function stopConversation(TelegramBotConversation $conversation): static
    {
        $this->telegramBotConversationManager->stopTelegramConversation($conversation);

        return $this;
    }

    public function view(string $template, array $context = []): string
    {
        $group = $this->getBot()->getEntity()->getGroup()->name;

        return $this->twig->render(sprintf('%s.tg.%s.html.twig', $group, $template), $context);
    }

    public function reply(
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true,
        bool $keepKeyboard = false
    ): static
    {
        $this->telegramBotChatActionSender->sendChatAction(
            $this->getBot(),
            $this->getChatId(),
            ChatAction::TYPING
        );
        $this->telegramBotMessageSender->sendTelegramMessage(
            $this->getBot()->getEntity(),
            $this->getChatId(),
            $text,
            keyboard: $keyboard,
            parseMode: $parseMode,
            replyToMessageId: $replyToMessageId,
            protectContent: $protectContent,
            disableWebPagePreview: $disableWebPagePreview,
            keepKeyboard: $keepKeyboard
        );

        return $this;
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, string $locale = null): string
    {
        $group = $this->getBot()->getEntity()->getGroup()->name;
        $prefix = $group . '.tg';
        $domain = $domain === null ? $prefix : ($prefix . '.' . $domain);

        return $this->translator->trans($id, $parameters, $domain, $locale ?? $this->getLocaleCode());
    }

    public function okText(string $text): string
    {
        return '🫡 ' . $text;
    }

    public function failText(string $text): string
    {
        return '🤕 ' . $text;
    }

    public function attentionText(string $text): string
    {
        return '‼️ ' . $text;
    }

    public function wrongText(string $text): string
    {
        return '🤔 ' . $text;
    }

    public function upsetText(string $text): string
    {
        return '😐 ' . $text;
    }

    public function infoText(string $text): string
    {
        return 'ℹ️ ' . $text;
    }

    public function selectedText(string $text): string
    {
        return '*' . $text;
    }

    public function queryText(string $text, bool $optional = false): string
    {
//        return '<u><b>' . $text . ($optional ? (' [ ' . $this->trans('query.optional') . ' ]') : '') . '</b></u>';
        return '<b>' . $text . ($optional ? (' [ ' . $this->trans('query.optional') . ' ]') : '') . '</b>';
    }

    public function queryTipText(string $text): string
    {
        return "\n\n" . '<i>' . $text . '</i>';
    }

    public function alreadyAddedText(string $text): string
    {
        return "\n\n" . $this->queryText($this->trans('query.already_added')) . ':' . "\n" . $text;
    }

    public function forbiddenText(string $text): string
    {
        return '⛔️ ' . $text;
//        return '⚠️ ' . $text;
    }

    public function replyWarning(string $text): self
    {
        $message = $this->forbiddenText($text);

        $this->reply($message);

        return $this;
    }

    public function replyWrong(bool $useInput): self
    {
        $message = $this->trans('reply.wrong');
        $message .= "\n";
        $message .= $this->useText($useInput);
        $message = $this->wrongText($message);

        $this->reply($message);

        return $this;
    }

    public function useText(bool $useInput): string
    {
        return '↘️ ' . ($useInput ? $this->trans('help.use_input') : $this->trans('help.use_keyboard'));
    }

    public function keyboard(...$buttons): Keyboard
    {
        return $this->telegramBotKeyboardFactory->createTelegramKeyboard(...$buttons);
    }

    public function button(string $text): KeyboardButton
    {
        return $this->telegramBotKeyboardFactory->createTelegramButton($text);
    }

    public function locationButton(string $text): KeyboardButton
    {
        return $this->telegramBotKeyboardFactory->createTelegramButton($text, requestLocation: true);
    }

    public function yesButton(): KeyboardButton
    {
        return $this->button('✅ ' . $this->trans('keyboard.yes'));
    }

    public function noButton(): KeyboardButton
    {
        return $this->button('⭕️ ' . $this->trans('keyboard.no'));
    }

    public function prevButton(): KeyboardButton
    {
        return $this->button('⬅️ ' . $this->trans('keyboard.prev'));
    }

    public function nextButton(): KeyboardButton
    {
        return $this->button($this->trans('keyboard.next') . ' ➡️');
    }

    public function helpButton(): KeyboardButton
    {
        return $this->button('🚨 ' . $this->trans('keyboard.help'));
    }

    public function removeButton(string $text): KeyboardButton
    {
        return $this->button('❌ ' . $text);
    }

    public function cancelButton(): KeyboardButton
    {
        return $this->button('❌ ' . $this->trans('keyboard.cancel'));
    }

    public function command(string $name, string $icon = null, bool $html = false, bool $link = false): string
    {
        return $this->view('command', [
            'name' => $name,
            'icon' => $icon,
            'link' => $link,
            'html' => $html,
        ]);
    }

    public function null(): null
    {
        return null;
    }
}