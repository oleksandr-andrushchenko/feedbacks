<?php
declare(strict_types=1);

namespace App\Service\Feedback\Telegram\View;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTerm\SearchTermTypeProvider;
use App\Service\Messenger\MessengerUserProfileUrlProvider;
use App\Service\Modifier;
use App\Transfer\Feedback\SearchTermTransfer;

class SearchTermTelegramViewProvider
{
    public function __construct(
        private readonly SearchTermTypeProvider $searchTermTypeProvider,
        private readonly MessengerUserProfileUrlProvider $messengerUserProfileUrlProvider,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly Modifier $modifier,
    )
    {
    }

    public function getSearchTermTelegramView(
        SearchTermTransfer $searchTerm,
        bool $addSecrets = false,
        bool $forceType = true,
        string $localeCode = null
    ): string
    {
        $m = $this->modifier;
        $modifier = $m->create();

        $skipTypes = [
            SearchTermType::person_name,
            SearchTermType::email,
            SearchTermType::url,
            ...SearchTermType::known_messengers,
        ];

        if ($searchTerm->getType() !== null && ($forceType || !in_array($searchTerm->getType(), $skipTypes, true))) {
            $modifier->add($m->bracketsModifier($this->searchTermTypeProvider->getSearchTermTypeName($searchTerm->getType(), $localeCode)));
        }

        return $modifier->apply($this->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets));
    }

    public function getSearchTermTelegramMainView(SearchTermTransfer $searchTerm, bool $addSecrets = false): string
    {
        $m = $this->modifier;

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());
        // todo: add search term formatter & implement it here and everywhere
        $text = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

        if (!in_array($messenger, [null, Messenger::unknown])) {
            $message = $m->create()
                ->add($m->linkModifier($this->messengerUserProfileUrlProvider->getMessengerUserProfileUrl($messenger, $text)))
                ->apply($text)
            ;
        } elseif (in_array($searchTerm->getType(), [SearchTermType::url, SearchTermType::messenger_profile_url], true)) {
            $message = $m->create()
                ->add($m->linkModifier($searchTerm->getText()))
                ->apply($text)
            ;
        } else {
            $secretTypes = [
                SearchTermType::phone_number,
                SearchTermType::email,
                SearchTermType::car_number,
            ];

            if ($addSecrets && in_array($searchTerm->getType(), $secretTypes, true)) {
                if ($searchTerm->getType() === SearchTermType::phone_number) {
                    $position = 4;
                } else {
                    $position = 2;
                }

                $message = $m->create()
                    ->add($m->secretsModifier(position: $position))
                    ->apply($text)
                ;
            } else {
                $message = $text;
            }
        }

        return $m->create()
            ->add($m->boldModifier())
            ->apply($message)
        ;
    }

    public function getSearchTermTelegramReverseView(
        SearchTermTransfer $searchTerm,
        bool $addSecrets = false,
        string $localeCode = null
    ): string
    {
        $m = $this->modifier;

        return $m->create()
            ->add($m->bracketsModifier($this->getSearchTermTelegramMainView($searchTerm, addSecrets: $addSecrets)))
            ->apply($this->searchTermTypeProvider->getSearchTermTypeComposeName($searchTerm->getType(), localeCode: $localeCode))
        ;
    }
}