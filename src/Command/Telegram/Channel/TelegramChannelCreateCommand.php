<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\Intl\CountryNotFoundException;
use App\Exception\Intl\Level1RegionNotFoundException;
use App\Exception\Intl\LocaleNotFoundException;
use App\Exception\Telegram\Bot\TelegramBotGroupNotFoundException;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\Level1RegionProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Channel\TelegramChannelCreator;
use App\Service\Telegram\Channel\TelegramChannelInfoProvider;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelCreateCommand extends Command
{
    public function __construct(
        private readonly TelegramChannelCreator $telegramChannelCreator,
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
        $this->addArgument('group', InputArgument::REQUIRED, 'Telegram Group (inner name)')
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->addArgument('name', InputArgument::REQUIRED, 'Telegram Name')
            ->addArgument('country', InputArgument::REQUIRED, 'Country code')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale code')
            ->addOption('level-1-region', mode: InputOption::VALUE_REQUIRED, description: 'Google Administrative area level 1 short name')
            ->addOption('chat-id', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Chat id (in case Channel is private, e.g. username starts with "+")')
            ->addOption('primary', mode: InputOption::VALUE_NEGATABLE, description: 'Whether to make a channel primary or not, primary channels are unique across group, country, locale and address', default: true)
            ->setDescription('Create telegram channel (inner)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channelTransfer = new TelegramChannelTransfer($input->getArgument('username'));

        $groupName = $input->getArgument('group');
        $group = TelegramBotGroupName::fromName($groupName);

        if ($group === null) {
            throw new TelegramBotGroupNotFoundException($groupName);
        }

        $channelTransfer->setGroup($group);
        $channelTransfer->setName($input->getArgument('name'));

        $countryCode = $input->getArgument('country');
        $country = $this->countryProvider->getCountry($countryCode);

        if ($country === null) {
            throw new CountryNotFoundException($countryCode);
        }

        $channelTransfer->setCountry($country);

        $localeCode = $input->getArgument('locale');
        $locale = $this->localeProvider->getLocale($localeCode);

        if ($locale === null) {
            throw new LocaleNotFoundException($localeCode);
        }

        $channelTransfer->setLocale($locale);

        $level1Region = null;

        if ($input->hasOption('level-1-region')) {
            $level1RegionName = $input->getOption('level-1-region');
            $level1Region = $this->level1RegionProvider->getLevel1RegionByCountryAndName(
                $country->getCode(),
                $level1RegionName
            );

            if ($level1Region === null) {
                throw new Level1RegionNotFoundException($level1RegionName);
            }
        }

        $channelTransfer->setLevel1Region($level1Region);
        $channelTransfer->setChatId($input->getOption('chat-id'));
        $channelTransfer->setPrimary($input->getOption('primary'));

        $channel = $this->telegramChannelCreator->createTelegramChannel($channelTransfer);

        $this->entityManager->flush();

        $row = $this->telegramChannelInfoProvider->getTelegramChannelInfo($channel);

        $io->createTable()
            ->setHeaders(array_keys($row))
            ->setRows([$row])
            ->setVertical()
            ->render()
        ;

        $io->newLine();
        $io->success(sprintf('"%s" Telegram channel has been created', $channel->getUsername()));

        return Command::SUCCESS;
    }
}