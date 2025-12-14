<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\Level1RegionNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotGroupNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\Level1RegionProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Channel\TelegramChannelInfoProvider;
use App\Service\Telegram\Channel\TelegramChannelUpdater;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelUpdateCommand extends Command
{
    public function __construct(
        private readonly TelegramChannelRepository $telegramChannelRepository,
        private readonly TelegramChannelUpdater $telegramChannelUpdater,
        private readonly EntityManager $entityManager,
        private readonly TelegramChannelInfoProvider $telegramChannelInfoProvider,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly Level1RegionProvider $level1RegionProvider,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group (inner name)')
            ->addOption('name', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Name')
            ->addOption('country', mode: InputOption::VALUE_REQUIRED, description: 'Country code')
            ->addOption('locale', mode: InputOption::VALUE_REQUIRED, description: 'Locale code')
            ->addOption('level-1-region', mode: InputOption::VALUE_REQUIRED, description: 'Google Administrative area level 1 short name')
            ->addOption('no-level-1-region', mode: InputOption::VALUE_NONE, description: 'Whether to unset level 1 region')
            ->addOption('chat-id', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Chat id (in case Channel is private, e.g. username starts with "+")')
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a channel primary or not, primary channels are unique across group, country, locale and address', default: true)
            ->setDescription('Update telegram channel (inner)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $channel = $this->telegramChannelRepository->findOneNonDeletedByUsername($username);

        if ($channel === null) {
            throw new TelegramBotNotFoundException($username);
        }

        $channelTransfer = new TelegramChannelTransfer($username);

        $groupName = $input->getOption('group');

        if ($groupName !== null) {
            $group = TelegramBotGroupName::fromName($groupName);

            if ($group === null) {
                throw new TelegramBotGroupNotFoundException($groupName);
            }

            $channelTransfer->setGroup($group);
        }

        $name = $input->getOption('name');

        if ($name !== null) {
            $channelTransfer->setName($name);
        }

        $countryCode = $input->getOption('country');

        if ($countryCode !== null) {
            $country = $this->countryProvider->getCountry($countryCode);

            if ($country === null) {
                throw new CountryNotFoundException($countryCode);
            }

            $channelTransfer->setCountry($country);
        }

        $localeCode = $input->getOption('locale');

        if ($localeCode !== null) {
            $locale = $this->localeProvider->getLocale($localeCode);

            if ($locale === null) {
                throw new LocaleNotFoundException($localeCode);
            }

            $channelTransfer->setLocale($locale);
        }

        if ($input->hasOption('level-1-region')) {
            $level1RegionName = $input->getOption('level-1-region');
            $level1Region = $this->level1RegionProvider->getLevel1RegionByCountryAndName(
                $country->getCode(),
                $level1RegionName
            );

            if ($level1Region === null) {
                throw new Level1RegionNotFoundException($level1RegionName);
            }

            $channelTransfer->setLevel1Region($level1Region);
        }

        if ($input->getOption('no-level-1-region')) {
            $channelTransfer->setLevel1Region(null);
        }

        if ($input->hasOption('chat-id')) {
            $channelTransfer->setChatId($input->getOption('chat-id'));
        }

        if ($input->hasOption('primary')) {
            $channelTransfer->setPrimary($input->getOption('primary'));
        }

        $this->telegramChannelUpdater->updateTelegramChannel($channel, $channelTransfer);

        $this->entityManager->flush();

        $row = $this->telegramChannelInfoProvider->getTelegramChannelInfo($channel);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram channel has been updated', $channel->getUsername()));

        return Command::SUCCESS;
    }
}