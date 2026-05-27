<?php
declare(strict_types=1);

namespace App\Tests\Traits\Messenger;

use App\Repository\Messenger\MessengerUserRepository;

trait MessengerUserRepositoryProviderTrait
{
    public function getMessengerUserRepository(): MessengerUserRepository
    {
        return static::getContainer()->get('app.messenger_user_repository');
    }
}