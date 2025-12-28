<?php

declare(strict_types=1);

namespace App\Tests\Traits\Messenger;

use App\Service\Messenger\MessengerUserService;

trait MessengerUserServiceProviderTrait
{
    public function getMessengerUserService(): MessengerUserService
    {
        return static::getContainer()->get('app.messenger_user_service');
    }
}