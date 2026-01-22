<?php

declare(strict_types=1);

namespace App\Command\Dynamodb;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Service\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @see DynamodbOdmTestsRunCommand
 */
class DynamodbOdmTestsRunCommand extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly UserRepository $userRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Test dynamodb\'s EM/UoW');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->testFindOne($io);
        $this->testFindMany($io);

        return Command::SUCCESS;
    }

    private function testFindOne(SymfonyStyle $io): void
    {
        $em = $this->entityManager->getDynamodb();
        $repo = $this->userRepository->getDynamodb();

        $id = 'test' . mt_rand(0, 999);
        $user1 = new User($id);
        $em->persist($user1);

        $user2 = $repo->find($id);
        $phone = '0969600231';
        $user1->setPhoneNumber($phone);
        $name = 'test';
        $user2->setName($name);

        if ($user1->getName() === $name) {
            $io->success('[OK] find one (name)');
        } else {
            $io->error('[FAIL] find one (name)');
        }

        if ($user2->getPhoneNumber() === $phone) {
            $io->success('[OK] find one (phone number)');
        } else {
            $io->error('[FAIL] find one (phone number)');
        }
    }

    private function testFindMany(SymfonyStyle $io): void
    {
        $em = $this->entityManager->getDynamodb();
        $repo = $this->userRepository->getDynamodb();

        $id1 = 'test' . mt_rand(0, 999);
        $user1 = new User($id1);
        $em->persist($user1);

        $id2 = 'test' . mt_rand(0, 999);
        $user2 = new User($id2);
        $em->persist($user2);

        $phone = '0969600231';
        $name = 'test';
        $tz = 'tz';

        $users = $repo->findByIds([$id1, $id2]);
        foreach ($users as $user) {
            if ($user->getId() === $id1) {
                $user1->setPhoneNumber($phone);
                if ($user->getPhoneNumber() === $phone) {
                    $io->success('[OK] find many (phone number)');
                } else {
                    $io->error('[FAIL] find many (phone number)');
                }
            } elseif ($user->getId() === $id2) {
                $user2->setName($name);
                if ($user->getName() === $name) {
                    $io->success('[OK] find many (name)');
                } else {
                    $io->error('[FAIL] find many (name)');
                }
            }
            $user->setTimezone($tz);
        }

        if ($user1->getTimezone() === $tz) {
            $io->success('[OK] find many (tz 1)');
        } else {
            $io->error('[FAIL] find many (tz 1)');
        }
        if ($user2->getTimezone() === $tz) {
            $io->success('[OK] find many (tz 2)');
        } else {
            $io->error('[FAIL] find many (tz 2)');
        }
    }
}