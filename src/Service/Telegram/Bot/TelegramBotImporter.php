<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Enum\Telegram\TelegramBotGroupName;
use App\Model\ImportResult;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\CsvFileWalker;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\Bot\Api\TelegramBotDescriptionsSyncer;
use App\Service\Telegram\Bot\Api\TelegramBotWebhookSyncer;
use App\Transfer\Telegram\TelegramBotTransfer;
use Throwable;

class TelegramBotImporter
{
    public const int MODE_DROP_EXISTING = 1;
    public const int MODE_SYNC_DESCRIPTIONS = 2;
    public const int MODE_SYNC_WEBHOOKS = 3;
    public const int MODE_UNDO_REMOVE_FOR_UPDATED = 4;

    public function __construct(
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TelegramBotCreator $telegramBotCreator,
        private readonly TelegramBotUpdater $telegramBotUpdater,
        private readonly TelegramBotRemover $telegramBotRemover,
        private readonly TelegramBotDescriptionsSyncer $telegramBotDescriptionsSyncer,
        private readonly TelegramBotWebhookSyncer $telegramBotWebhookSyncer,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly CsvFileWalker $csvFileWalker,
        private readonly EntityManager $entityManager,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramBots(string $filename, int $mode, callable $logger = null): ImportResult
    {
        $result = new ImportResult();
        $logger = $logger ?? static fn (string $message): null => null;

        if ($mode & self::MODE_DROP_EXISTING) {
            $bots = $this->telegramBotRepository->findAll();
            $usernames = $this->getUsernames($filename);
            foreach ($bots as $bot) {
                if (!in_array($bot->getUsername(), $usernames, true) && !$this->telegramBotRemover->telegramBotRemoved($bot)) {
                    $this->telegramBotRemover->removeTelegramBot($bot);
                    $message = $bot->getUsername();
                    $message .= ': [🟢 OK] deleted';
                    $result->incDeletedCount();
                    $logger($message);
                }
            }

            $this->entityManager->flush();
        }

        $this->walk($filename, function ($data) use ($result, $mode, $logger): void {
            $transfer = (new TelegramBotTransfer($data['username']))
                ->setGroup(TelegramBotGroupName::fromName($data['group']))
                ->setName($data['name'])
                ->setToken($data['token'])
                ->setCountry($this->countryProvider->getCountry($data['country']))
                ->setLocale($this->localeProvider->getLocale($data['locale']))
                ->setPrimary($data['primary'] === '1')
                ->setAdminIds(empty($data['admin_id']) ? null : [$data['admin_id']])
                ->setAdminOnly($data['admin_only'] === '1')
            ;

            $bot = $this->telegramBotRepository->findOneByUsername($transfer->getUsername());

            $message = $transfer->getUsername();

            if ($bot === null) {
                try {
                    $bot = $this->telegramBotCreator->createTelegramBot($transfer);
                    $result->incCreatedCount();
                    $message .= ': [🟢 OK] created';
                } catch (Throwable $exception) {
                    $message .= '; [🔴 FAIL] create - ' . $exception->getMessage();
                }
            } else {
                try {
                    $this->telegramBotUpdater->updateTelegramBot($bot, $transfer);
                    $result->incUpdatedCount();
                    $message .= ': [🟢 OK] updated';

                    if ($this->telegramBotRemover->telegramBotRemoved($bot) && $mode & self::MODE_UNDO_REMOVE_FOR_UPDATED) {
                        $this->telegramBotRemover->undoTelegramBotRemove($bot);
                        $message .= '; [🟢 OK] restored';
                        $result->incRestoredCount();
                    }
                } catch (Throwable $exception) {
                    $message .= '; [🔴 FAIL] update - ' . $exception->getMessage();
                }
            }

            if (
                $bot !== null
                && !$bot->descriptionsSynced()
                && !$this->telegramBotRemover->telegramBotRemoved($bot)
                && $mode & self::MODE_SYNC_DESCRIPTIONS
            ) {
                try {
                    $this->telegramBotDescriptionsSyncer->syncTelegramDescriptions($bot);
                    $message .= '; [🟢 OK] descriptions';
                } catch (Throwable $exception) {
                    $message .= '; [🔴 FAIL] descriptions - ' . $exception->getMessage();
                }
            }
            if (
                $bot !== null
                && !$bot->webhookSynced()
                && !$this->telegramBotRemover->telegramBotRemoved($bot)
                && $mode & self::MODE_SYNC_WEBHOOKS
            ) {
                try {
                    $this->telegramBotWebhookSyncer->syncTelegramWebhook($bot);
                    $message .= '; [🟢 OK] webhook';
                } catch (Throwable $exception) {
                    $message .= '; [🔴 FAIL] webhook - ' . $exception->getMessage();
                }
            }

            $logger($message);
        });

        return $result;
    }

    private function getUsernames(string $filename): array
    {
        $usernames = [];

        $this->walk($filename, static function (array $data) use (&$usernames): void {
            $usernames[] = $data['username'];
        });

        return $usernames;
    }

    private function walk(string $filename, callable $func): void
    {
        $mandatoryColumns = [
            'skip',
            'group',
            'username',
            'name',
            'token',
            'stage',
            'country',
            'locale',
            'primary',
            'admin_id',
            'admin_only',
        ];

        $this->csvFileWalker->walk($filename, function (array $data) use ($func): void {
            if ($data['stage'] !== $this->stage) {
                return;
            }

            if ($data['skip'] === '1') {
                return;
            }

            $func($data);
        }, mandatoryColumns: $mandatoryColumns);
    }
}