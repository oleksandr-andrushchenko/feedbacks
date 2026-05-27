<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;

class SearchTermMessengerProvider
{
    public function getSearchTermMessenger(SearchTermType $searchTermType): ?Messenger
    {
        return match ($searchTermType) {
            SearchTermType::instagram_username => Messenger::instagram,
            SearchTermType::facebook_username => Messenger::facebook,
            SearchTermType::reddit_username => Messenger::reddit,
            SearchTermType::onlyfans_username => Messenger::onlyfans,
            SearchTermType::telegram_username => Messenger::telegram,
            SearchTermType::tiktok_username => Messenger::tiktok,
            SearchTermType::twitter_username => Messenger::twitter,
            SearchTermType::youtube_username => Messenger::youtube,
            SearchTermType::vkontakte_username => Messenger::vkontakte,
            SearchTermType::messenger_username, SearchTermType::messenger_profile_url => Messenger::unknown,
            default => null,
        };
    }
}