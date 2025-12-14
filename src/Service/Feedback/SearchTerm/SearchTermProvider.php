<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Entity\Feedback\SearchTerm;
use App\Service\Messenger\MessengerUserService;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;

class SearchTermProvider
{
    public function __construct(
        private MessengerUserService $messengerUserService,
    )
    {
    }

    public function getFeedbackSearchTermTransfer(SearchTerm $feedbackSearchTerm): SearchTermTransfer
    {
        $transfer = new SearchTermTransfer(
            $feedbackSearchTerm->getText(),
            type: $feedbackSearchTerm->getType(),
            normalizedText: $feedbackSearchTerm->getNormalizedText()
        );

        $messengerUser = $feedbackSearchTerm->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

        if ($messengerUser !== null) {
            $transfer->setMessengerUser(new MessengerUserTransfer(
                $messengerUser->getMessenger(),
                $messengerUser->getIdentifier(),
                username: $messengerUser->getUsername(),
                name: $messengerUser->getName(),
                countryCode: $user->getCountryCode(),
                localeCode: $user->getLocaleCode(),
                currencyCode: $user->getCurrencyCode()
            ));
        }

        return $transfer;
    }
}