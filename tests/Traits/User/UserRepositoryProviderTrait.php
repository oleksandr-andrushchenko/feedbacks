<?php
declare(strict_types=1);

namespace App\Tests\Traits\User;

use App\Repository\User\UserRepository;

trait UserRepositoryProviderTrait
{
    public function getUserRepository(): UserRepository
    {
        return static::getContainer()->get('app.user_repository');
    }
}