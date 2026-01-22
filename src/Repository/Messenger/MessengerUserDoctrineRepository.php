<?php

declare(strict_types=1);

namespace App\Repository\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Enum\Messenger\Messenger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessengerUser>
 * @method array<MessengerUser> findAll()
 */
class MessengerUserDoctrineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessengerUser::class);
    }

    public function findOneByMessengerAndIdentifier(Messenger $messenger, string $identifier, bool $withUser = false): ?MessengerUser
    {
        if ($withUser) {
            $users = $this->createQueryBuilder('mu')
                ->select('mu', 'u')
                ->innerJoin('mu.user', 'u')
                ->andWhere('mu.identifier = :identifier')
                ->setParameter('identifier', $identifier)
                ->setMaxResults(1)
                ->getQuery()
                ->getResult()
            ;
        } else {
            $users = $this->findBy([
                'identifier' => $identifier,
            ]);
        }

        foreach ($users as $user) {
            if ($user->getMessenger() === $messenger) {
                return $user;
            }
        }

        return null;
    }

    public function findOneByMessengerAndUsername(Messenger $messenger, string $username): ?MessengerUser
    {
        $users = $this->findBy([
            'username' => $username,
        ]);

        foreach ($users as $user) {
            if ($user->getMessenger() === $messenger) {
                return $user;
            }
        }

        return null;
    }

    public function findByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
        ]);
    }
}
