<?php

declare(strict_types=1);

namespace App\Repository\Feedback;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedbackUserSubscription>
 */
class FeedbackUserSubscriptionDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedbackUserSubscription::class);
    }

    public function findByMessengerUser(MessengerUser $messengerUser): array
    {
        return $this->findBy([
            'messengerUser' => $messengerUser,
        ]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
        ]);
    }
}
