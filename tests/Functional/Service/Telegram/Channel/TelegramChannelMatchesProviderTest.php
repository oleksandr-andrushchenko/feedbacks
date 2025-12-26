<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramBot;
use App\Entity\Telegram\TelegramChannel;
use App\Entity\User\User;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Telegram\Channel\TelegramChannelMatchesProvider;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TelegramChannelMatchesProviderTest extends TestCase
{
    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponents
     * @param array $channelAddressComponents
     * @param int $expectedPoints
     * @return void
     * @dataProvider calculateTelegramChannelPointsDataProvider
     */
    public function testCalculateTelegramChannelPoints(
        array $userAddressComponents,
        array $botAddressComponents,
        array $channelAddressComponents,
        int $expectedPoints,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bot = $this->makeBot(...$botAddressComponents);
        $channel = $this->makeChannel(...$channelAddressComponents);
        $provider = new TelegramChannelMatchesProvider($this->createMock(TelegramChannelRepository::class), new NullLogger());

        $actualPoints = $provider->calculateTelegramChannelPoints($bot, $user, $channel);
        $this->assertEquals($expectedPoints, $actualPoints);
    }

    public function calculateTelegramChannelPointsDataProvider(): Generator
    {
        yield 'c & cc & ccc' => [
            'user' => ['us'],
            'bot' => ['au'],
            'channel' => ['gb'],
            'expected' => 0,
        ];

        yield 'null & c & c' => [
            'user' => [],
            'bot' => ['us'],
            'channel' => ['us'],
            'expected' => 1,
        ];

        yield 'c & c & c' => [
            'user' => ['us'],
            'bot' => ['us'],
            'channel' => ['us'],
            'expected' => 1,
        ];

        yield 'c & cc & cc' => [
            'user' => ['us'],
            'bot' => ['ua'],
            'channel' => ['ua'],
            'expected' => 1,
        ];

        yield 'c+a1 & cc & cc' => [
            'user' => ['ua', 1],
            'bot' => ['au'],
            'channel' => ['au'],
            'expected' => 1,
        ];

        yield 'c+a1 & c & c' => [
            'user' => ['ua', 1],
            'bot' => ['ua'],
            'channel' => ['ua'],
            'expected' => 1,
        ];

        yield 'c+a1 & c & c+a1' => [
            'user' => ['ua', 1],
            'bot' => ['ua'],
            'channel' => ['ua', 1],
            'expected' => 1 + 2,
        ];

        yield 'c & c & c+a1' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channel' => ['ua', 1],
            'expected' => 0,
        ];
    }

    /**
     * @param array $userAddressComponents
     * @param array $botAddressComponents
     * @param array $channelAddressComponentsStack
     * @param array $expectedChannelIds
     * @return void
     * @dataProvider getTelegramChannelMatchesDataProvider
     */
    public function testGetTelegramChannelMatches(
        array $userAddressComponents,
        array $botAddressComponents,
        array $channelAddressComponentsStack,
        array $expectedChannelIds,
    ): void
    {
        $user = $this->makeUser(...$userAddressComponents);
        $bot = $this->makeBot(...$botAddressComponents);
        $channels = array_map(function (array $channelAddressComponents) {
            $id = array_shift($channelAddressComponents);

            return $this->makeChannel(...$channelAddressComponents, id: $id);
        }, $channelAddressComponentsStack);
        $repository = $this->createMock(TelegramChannelRepository::class);
        $repository
            ->expects($this->once())
            ->method('findPrimaryNonDeletedByGroupAndCountry')
            ->willReturn($channels)
        ;

        $provider = new TelegramChannelMatchesProvider($repository, new NullLogger());
        $actualChannels = $provider->getTelegramChannelMatches($user, $bot);
        $actualChannelIds = array_map(static fn (TelegramChannel $channel): string => $channel->getId(), $actualChannels);

        $this->assertEquals($expectedChannelIds, $actualChannelIds);
    }

    public function getTelegramChannelMatchesDataProvider(): Generator
    {
        $notMatchedChannels = [
            [5, 'au'],
        ];

        yield 'c & cc & ccc' => [
            'user' => ['ua'],
            'bot' => ['us'],
            'channels' => [
                [1, 'ru'],
                ...$notMatchedChannels,
            ],
            'expected' => [],
        ];

        yield 'c & c & cc' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'us'],
                ...$notMatchedChannels,
            ],
            'expected' => [],
        ];

        yield 'c & c & c' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c & c & c, c+a1' => [
            'user' => ['ua'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 1],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+a1 & c & c, c+a1' => [
            'user' => ['ua', 1],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 1],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
                2,
            ],
        ];

        yield 'c+a1 & c & c, c+a1a1, c+a1a1' => [
            'user' => ['ua', 1],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 2],
                [3, 'ua', 2],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c & cc & cc, cc+a1a1' => [
            'user' => ['us'],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 1],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];

        yield 'c+a1 & cc & cc, cc+a1a1' => [
            'user' => ['us', 1],
            'bot' => ['ua'],
            'channels' => [
                [1, 'ua'],
                [2, 'ua', 2],
                ...$notMatchedChannels,
            ],
            'expected' => [
                1,
            ],
        ];
    }

    private function makeUser(
        string $countryCode = null,
        int|string $level1RegionId = null,
    ): User
    {
        return $this->createConfiguredMock(User::class, [
            'getCountryCode' => $countryCode,
            'getLevel1RegionId' => $level1RegionId === null ? null : (string) $level1RegionId,
        ]);
    }

    private function makeBot(
        string $countryCode = '',
        int $id = null
    ): TelegramBot
    {
        return $this->createConfiguredMock(TelegramBot::class, [
            'getId' => $id,
            'getGroup' => TelegramBotGroupName::default,
            'getCountryCode' => $countryCode,
        ]);
    }

    private function makeChannel(
        string $countryCode = '',
        int|string $level1RegionId = null,
        int $id = null
    ): TelegramChannel
    {
        return $this->createConfiguredMock(TelegramChannel::class, [
            'getId' => $id,
            'getCountryCode' => $countryCode,
            'getLevel1RegionId' => $level1RegionId === null ? null : (string) $level1RegionId,
        ]);
    }
}