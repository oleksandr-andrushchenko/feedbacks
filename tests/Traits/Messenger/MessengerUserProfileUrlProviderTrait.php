<?php
declare(strict_types=1);

namespace App\Tests\Traits\Messenger;

use App\Service\Messenger\MessengerUserProfileUrlProvider;

trait MessengerUserProfileUrlProviderTrait
{
    public function getMessengerUserProfileUrlProvider(): MessengerUserProfileUrlProvider
    {
        return static::getContainer()->get('app.messenger_user_profile_url_provider');
    }
}