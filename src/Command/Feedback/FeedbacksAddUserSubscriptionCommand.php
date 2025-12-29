<?php

declare(strict_types=1);

namespace App\Command\Feedback;

use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Exception\Feedback\FeedbackSubscriptionPlanNotFoundException;
use App\Exception\User\UserNotFoundException;
use App\Repository\User\UserRepository;
use App\Service\Doctrine\DryRunner;
use App\Service\Feedback\Subscription\FeedbackSubscriptionManager;
use App\Service\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class FeedbacksAddUserSubscriptionCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FeedbackSubscriptionManager $feedbackSubscriptionManager,
        private readonly DryRunner $dryRunner,
        private readonly EntityManager $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user', InputArgument::REQUIRED, 'User id')
            ->addArgument('plan', InputArgument::REQUIRED, 'Plan name')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Add feedback user subscription')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws FeedbackSubscriptionPlanNotFoundException
     * @throws Throwable
     * @throws UserNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument('user');
        $user = $this->userRepository->find($userId);

        if ($user === null) {
            throw new UserNotFoundException($userId);
        }

        $planName = $input->getArgument('plan');
        $plan = FeedbackSubscriptionPlanName::fromName($planName);

        if ($plan === null) {
            throw new FeedbackSubscriptionPlanNotFoundException($planName);
        }

        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(
                    sprintf(
                        'Are you sure you want to add "%s" feedback user subscription for "%s" user?',
                        $plan->name,
                        $user->getName()
                    ),
                    true
                )
            );

            if (!$confirmed) {
                $io->warning('Feedback user subscription adding has been cancelled');

                return Command::SUCCESS;
            }
        }

        $func = fn () => $this->feedbackSubscriptionManager->createFeedbackUserSubscription($user, $plan);

        if ($dryRun) {
            $this->dryRunner->dryRun($func);
        } else {
            $func();
            $this->entityManager->flush();
        }

        $io->success(sprintf('"%s" Feedback user subscription has been added for "%s" user', $plan->name, $user->getName()));

        return Command::SUCCESS;
    }
}