<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Tests\Fixtures;
use App\Tests\Traits\Feedback\SearchTermParserProviderTrait;
use App\Tests\Traits\Messenger\MessengerUserProfileUrlProviderTrait;
use App\Transfer\Feedback\SearchTermTransfer;
use App\Transfer\Messenger\MessengerUserTransfer;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SearchTermParserTest extends KernelTestCase
{
    use SearchTermParserProviderTrait;
    use MessengerUserProfileUrlProviderTrait;
    use ArraySubsetAsserts;

    /**
     * @param string $text
     * @param SearchTermTransfer $expectedSearchTerm
     * @return void
     * @dataProvider parseWithGuessTypeDataProvider
     */
    public function testParseWithGuessType(string $text, SearchTermTransfer $expectedSearchTerm): void
    {
        $searchTerm = new SearchTermTransfer($text);

        $this->getSearchTermParser()->parseWithGuessType($searchTerm);

        $this->assertEquals($expectedSearchTerm->getText(), $searchTerm->getText());
        $this->assertEquals($expectedSearchTerm->getType(), $searchTerm->getType());
        $this->assertEquals($expectedSearchTerm->getNormalizedText(), $searchTerm->getNormalizedText());
        $this->assertEquals($expectedSearchTerm->getMessengerUser(), $searchTerm->getMessengerUser());

        $expectedTypes = $expectedSearchTerm->getTypes() ?? [];
        $types = $searchTerm->getTypes() ?? [];

        foreach ($expectedTypes as $expectedType) {
            $this->assertTrue(
                in_array($expectedType, $types, true),
                sprintf('%s not in %s', $expectedType->name, implode(', ', array_map(static fn (SearchTermType $type): string => $type->name, $types)))
            );
        }
    }

    public function parseWithGuessTypeDataProvider(): Generator
    {
        foreach ([
                     Fixtures::INSTAGRAM_USERNAME_1,
                     '@' . Fixtures::INSTAGRAM_USERNAME_2,
                 ] as $text) {
            yield 'instagram username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::instagram_username]),
            ];
        }

        foreach ([
                     Fixtures::INSTAGRAM_USERNAME_1,
                     Fixtures::INSTAGRAM_USERNAME_2,
                     Fixtures::INSTAGRAM_USERNAME_3,
                 ] as $username) {
            yield 'instagram username: ' . ($text = $this->profileUrl(Messenger::instagram, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::instagram_username, normalizedText: $username),
            ];
        }

        foreach ([
                     Fixtures::TELEGRAM_USERNAME_1,
                     '@' . Fixtures::TELEGRAM_USERNAME_2,
                 ] as $text) {
            yield 'telegram username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::telegram_username]),
            ];
        }

        foreach ([
                     Fixtures::TELEGRAM_USERNAME_1,
                     Fixtures::TELEGRAM_USERNAME_2,
                     Fixtures::TELEGRAM_USERNAME_3,
                 ] as $username) {
            yield 'telegram username: ' . ($text = $this->profileUrl(Messenger::telegram, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::telegram_username, normalizedText: $username),
            ];
        }

        foreach ([
                     'wild.snowgirl',
                     '@wild.snowgirl',
                 ] as $text) {
            yield 'facebook username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::facebook_username]),
            ];
        }

        foreach ([
                     'wild.snowgirl',
                     'spfedorov',
                 ] as $username) {
            yield 'facebook username: ' . ($text = $this->profileUrl(Messenger::facebook, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::facebook_username, normalizedText: $username),
            ];
        }

        foreach ([
                     'ert_234QWE',
                     'renato.minichiello.5',
                 ] as $username) {
            yield 'facebook username: ' . ($text = 'https://www.facebook.com/' . $username . '?comment_id=Y29tbWVudDoxNDg4NTEzMTE1MzEwMzMxXzEzNDEwNTI5MzY4MzE5MDM%3D') => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::facebook_username, normalizedText: $username),
            ];
        }

        foreach ([
                     '100024549702670',
                     '549702670',
                 ] as $username) {
            yield 'facebook username: ' . ($text = $this->profileUrl(Messenger::facebook, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer(
                    $text,
                    type: SearchTermType::facebook_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::facebook, $username)
                ),
            ];
        }

        foreach ([
                     '100029799303653',
                     '7000297653',
                 ] as $username) {
            yield 'facebook username: ' . ($text = 'https://www.facebook.com/profile.php?id=' . $username . '&comment_id=Y29tbWVudDoxMzMyNzg0NjU0Mjc4NjQwXzI0MzYwNjI1OTY1NzMxOTE%3D') => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer(
                    $text,
                    type: SearchTermType::facebook_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::facebook, $username)
                ),
            ];
        }

        foreach ([
                     'Hatherence',
                     '@rexultibrexpiprazole',
                 ] as $text) {
            yield 'reddit username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::reddit_username]),
            ];
        }

        foreach ([
                     'certain-sick',
                     '2u4a_q',
                 ] as $username) {
            yield 'reddit username: ' . ($text = $this->profileUrl(Messenger::reddit, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::reddit_username, normalizedText: $username),
            ];
        }

        foreach ([
                     'hot-tatty',
                     '@ollienibsfreepage',
                 ] as $text) {
            yield 'onlyfans username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::onlyfans_username]),
            ];
        }

        foreach ([
                     'mary_lopez',
                     '2u4a_q',
                 ] as $username) {
            yield 'onlyfans username: ' . ($text = $this->profileUrl(Messenger::onlyfans, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::onlyfans_username, normalizedText: $username),
            ];
        }

        foreach ([
                     Fixtures::TIKTOK_USERNAME_1,
                     '@ol_li.enibsfreepage',
                 ] as $text) {
            yield 'tiktok username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::tiktok_username]),
            ];
        }

        foreach ([
                     'mary_lop.d-ez',
                     '2u4a_q',
                 ] as $username) {
            yield 'tiktok username: ' . ($text = $this->profileUrl(Messenger::tiktok, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::tiktok_username, normalizedText: $username),
            ];
        }

        foreach ([
                     Fixtures::TWITTER_USERNAME_1,
                     '@KeatonJ_3',
                 ] as $text) {
            yield 'twitter username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::twitter_username]),
            ];
        }

        foreach ([
                     'OleksandrA1988',
                     '2u4a_q',
                 ] as $username) {
            yield 'twitter username: ' . ($text = $this->profileUrl(Messenger::twitter, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::twitter_username, normalizedText: $username),
            ];
        }

        foreach ([
                     Fixtures::YOUTUBE_USERNAME_1,
                     '@KeatonJ_3',
                     'UCtqVs1nwW_sadnAZPkGqpSA',
                 ] as $text) {
            yield 'youtube username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::youtube_username]),
            ];
        }

        foreach ([
                     'OleksandrAndrushchenko1988',
                     'UCtqVs1nwW_sadnAZPkGqpSA',
                 ] as $username) {
            yield 'youtube username: ' . ($text = $this->profileUrl(Messenger::youtube, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::youtube_username, normalizedText: $username),
            ];
        }

        foreach ([
                     Fixtures::VKONTAKTE_USERNAME_1,
                     'id10362344',
                     '@' . Fixtures::VKONTAKTE_USERNAME_1,
                 ] as $text) {
            yield 'vkontakte username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::vkontakte_username]),
            ];
        }

        foreach ([
                     'id10362344',
                     'id1036944',
                 ] as $username) {
            yield 'vkontakte username: ' . ($text = $this->profileUrl(Messenger::vkontakte, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer(
                    $text,
                    type: SearchTermType::vkontakte_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::vkontakte, substr($username, 2), username: $username)
                ),
            ];
        }

        foreach ([
                     'OleksandrA1988',
                     '2u4a_q',
                 ] as $username) {
            yield 'vkontakte username: ' . ($text = $this->profileUrl(Messenger::vkontakte, $username)) => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::vkontakte_username, normalizedText: $username),
            ];
        }

        foreach ([
                     'https://unknown.com/me',
                     'https://medium.com/@kseniatoloknova',
                 ] as $text) {
            yield 'messenger profile url: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::messenger_profile_url]),
            ];
        }

        foreach ([
                     'me',
                     '2u4a_q',
                 ] as $text) {
            yield 'messenger username: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::messenger_username]),
            ];
        }

        foreach ([
                     'https://example.com',
                     'https://example.com?q=1',
                 ] as $text) {
            yield 'url: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::url]),
            ];
        }

        foreach ([
                     'example@gmail.com',
                     'example+3@gmail.com',
                 ] as $text) {
            yield 'email: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, type: SearchTermType::email),
            ];
        }

        foreach ([
                     '+1 (561) 314-5672',
                     '1.561.314.5672',
                     '380969603102',
                     '+380969603102',
                     '0969603102',
                     '(380)96-960-3102',
                     '3(80)96-960-3102',
                     '456-7890',
                     '212-456-7890',
                     '+1-212-456-7890',
                     '1-212-456-7890',
                     '001-212-456-7890',
                     '191-212-456-7890',
                     '(212)456-7890',
                     '+380 (98) 485 67 35',
                 ] as $text) {
            yield 'phone number: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::phone_number]),
            ];
        }

        foreach ([
                     'John Smith',
                     'Олександр Андрущенко',
                     'Дмитрий Сергеевич',
                 ] as $text) {
            yield 'person name: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::person_name]),
            ];
        }

        foreach ([
                     'United Nations Children’s Fund (UNICEF)',
                     'United Nations Education Scientific & Cultural Organization (UNESCO)',
                     'Apple Inc. (AAPL)',
                     'Saudi Aramco (2222.SR)',
                     'Теле-канал «Україна»',
                     'McDonald`s',
                     '«Сургутнефтегаз», ПАО',
                     'En+ Group',
                     '«Аэрофлот - Российские авиалинии»',
                     '«Бэст Прайс» (сеть магазинов Fix Price)',
                 ] as $text) {
            yield 'organization name: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::organization_name]),
            ];
        }

        foreach ([
                     '2901 N Federal Hwy, Boca Raton, FL 33431',
                     'Бориса Гмирїі 9в, 55',
                     'пер. Луначарского-Быкова 134/1, 1 подьезд, кв. 14',
                 ] as $text) {
            yield 'place name: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::place_name]),
            ];
        }

        foreach ([
                     'нu34123ЧW',
                     'АА 1234 АВ',
                     '22 АН 0880',
                     'Т1 АН 0880',
                     'СН АА 0001',
                     '01 АА 0001',
                     'D 036 036',
                     'ПЕТРО',
                     'Т2 42 42 АА',
                     '22 42 42 АА',
                     'АК КА 8698',
                     'ПЕТРО 201',
                     '24500 АА',
                     'CDP 123',
                     '209-15ТВ',
                     '1-254АП',
                     'с227НА69',
                     'В555РХ39',
                     'АО 365 78',
                     '3733ММ55',
                     'АА7711333',
                     '0245 ок 43rus',
                     'Т АО 002 78',
                     'К ММ 976 39',
                     'ERS 8579',
                     'GMNS5699',
                     '682 GKS',
                     '861573',
                     '693-KLS',
                 ] as $text) {
            yield 'car number: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::car_number]),
            ];
        }

        foreach ([
                     '111-22-3333',
                     '111223333',
                     '1234567891',
                     '12345678910',
                 ] as $text) {
            yield 'tax number: ' . $text => [
                'text' => $text,
                'expectedSearchTerm' => new SearchTermTransfer($text, types: [SearchTermType::tax_number]),
            ];
        }
    }

    /**
     * @param SearchTermTransfer $searchTerm
     * @param array $context
     * @param SearchTermTransfer $expectedSearchTerm
     * @return void
     * @dataProvider parseWithKnownTypeDataProvider
     */
    public function testParseWithKnownType(SearchTermTransfer $searchTerm, array $context, SearchTermTransfer $expectedSearchTerm): void
    {
        $this->getSearchTermParser()->parseWithKnownType($searchTerm, context: $context);

        $this->assertEquals($expectedSearchTerm->getText(), $searchTerm->getText());
        $this->assertEquals($expectedSearchTerm->getType(), $searchTerm->getType());
        $this->assertEquals($expectedSearchTerm->getNormalizedText(), $searchTerm->getNormalizedText());
        $this->assertEquals($expectedSearchTerm->getMessengerUser(), $searchTerm->getMessengerUser());

        $expectedTypes = $expectedSearchTerm->getTypes() ?? [];
        $types = $searchTerm->getTypes() ?? [];

        foreach ($expectedTypes as $expectedType) {
            $this->assertTrue(
                in_array($expectedType, $types, true),
                sprintf('%s not in %s', $expectedType->name, implode(', ', array_map(static fn (SearchTermType $type): string => $type->name, $types)))
            );
        }
    }

    public function parseWithKnownTypeDataProvider(): Generator
    {
        yield 'instagram username: ' . ($text = Fixtures::INSTAGRAM_USERNAME_1) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::instagram_username),
            'context' => [],
            'expectedSearchTerm' => clone $searchTerm,
        ];

        yield 'instagram username: ' . ($text = '@' . ($username = Fixtures::INSTAGRAM_USERNAME_2)) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::instagram_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     Fixtures::INSTAGRAM_USERNAME_1,
                     Fixtures::INSTAGRAM_USERNAME_2,
                     Fixtures::INSTAGRAM_USERNAME_3,
                 ] as $username) {
            yield 'instagram username: ' . ($text = $this->profileUrl(Messenger::instagram, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::instagram_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'telegram username: ' . ($text = Fixtures::TELEGRAM_USERNAME_1) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::telegram_username),
            'context' => [],
            'expectedSearchTerm' => clone $searchTerm,
        ];

        yield 'telegram username: ' . ($text = '@' . ($username = Fixtures::TELEGRAM_USERNAME_2)) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::telegram_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     Fixtures::TELEGRAM_USERNAME_1,
                     Fixtures::TELEGRAM_USERNAME_2,
                     Fixtures::TELEGRAM_USERNAME_3,
                 ] as $username) {
            yield 'telegram username: ' . ($text = $this->profileUrl(Messenger::telegram, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::telegram_username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'facebook username: ' . ($text = 'wild.snowgirl') => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::facebook_username),
            'context' => [],
            'expectedSearchTerm' => clone $searchTerm,
        ];

        yield 'facebook username: ' . ($text = '@' . ($username = 'wild.snowgirl')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::facebook_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     'wild.snowgirl',
                     'spfedorov',
                 ] as $username) {
            yield 'facebook username: ' . ($text = $this->profileUrl(Messenger::facebook, $username)) => [
                'serchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::facebook_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'ert_234QWE',
                     'renato.minichiello.5',
                 ] as $username) {
            yield 'facebook username: ' . ($text = 'https://www.facebook.com/' . $username . '?comment_id=Y29tbWVudDoxNDg4NTEzMTE1MzEwMzMxXzEzNDEwNTI5MzY4MzE5MDM%3D') => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::facebook_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     '100024549702670',
                     '549702670',
                 ] as $username) {
            yield 'facebook username: ' . ($text = $this->profileUrl(Messenger::facebook, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer(
                    $text,
                    type: SearchTermType::facebook_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::facebook, $username)
                ),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     '100029799303653',
                     '7000297653',
                 ] as $username) {
            yield 'facebook username: ' . ($text = 'https://www.facebook.com/profile.php?id=' . $username . '&comment_id=Y29tbWVudDoxMzMyNzg0NjU0Mjc4NjQwXzI0MzYwNjI1OTY1NzMxOTE%3D') => [
                'searchTerm' => $searchTerm = new SearchTermTransfer(
                    $text,
                    type: SearchTermType::facebook_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::facebook, $username)
                ),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'reddit username: ' . ($text = '@' . ($username = 'rexultibrexpiprazole')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::reddit_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     'Hatherence',
                     'certain-sick',
                     '2u4a_q',
                 ] as $username) {
            yield 'reddit username: ' . ($text = $this->profileUrl(Messenger::reddit, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::reddit_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'onlyfans username: ' . ($text = '@' . ($username = 'ollienibsfreepage')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::onlyfans_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     'hot-tatty',
                     'mary_lopez',
                     '2u4a_q',
                 ] as $username) {
            yield 'onlyfans username: ' . ($text = $this->profileUrl(Messenger::onlyfans, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::onlyfans_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'tiktok username: ' . ($text = Fixtures::TIKTOK_USERNAME_1) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::tiktok_username),
            'context' => [],
            'expectedSearchTerm' => clone $searchTerm,
        ];

        yield 'tiktok username: ' . ($text = '@' . ($username = 'ol_li.enibsfreepage')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::tiktok_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     'mary_lop.d-ez',
                     '2u4a_q',
                 ] as $username) {
            yield 'tiktok username: ' . ($text = $this->profileUrl(Messenger::tiktok, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::tiktok_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'twitter username: ' . ($text = Fixtures::TWITTER_USERNAME_1) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::twitter_username),
            'context' => [],
            'expectedSearchTerm' => clone $searchTerm,
        ];

        yield 'twitter username: ' . ($text = '@' . ($username = 'KeatonJ_3')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::twitter_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     'OleksandrA1988',
                     '2u4a_q',
                 ] as $username) {
            yield 'twitter username: ' . ($text = $this->profileUrl(Messenger::twitter, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::twitter_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'youtube username: ' . ($text = '@' . ($username = 'KeatonJ_3')) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::youtube_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     Fixtures::YOUTUBE_USERNAME_1,
                     'UCtqVs1nwW_sadnAZPkGqpSA',
                 ] as $text) {
            yield 'youtube username: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::youtube_username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'OleksandrAndrushchenko1988',
                     'UCtqVs1nwW_sadnAZPkGqpSA',
                 ] as $username) {
            yield 'youtube username: ' . ($text = $this->profileUrl(Messenger::youtube, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::youtube_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        yield 'vkontakte username: ' . ($text = '@' . ($username = Fixtures::VKONTAKTE_USERNAME_1)) => [
            'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::vkontakte_username),
            'context' => [],
            'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($username),
        ];

        foreach ([
                     Fixtures::VKONTAKTE_USERNAME_1,
                 ] as $text) {
            yield 'vkontakte username: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::vkontakte_username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'id10362344',
                     'id1036944',
                 ] as $username) {
            yield 'vkontakte username: ' . ($text = $this->profileUrl(Messenger::vkontakte, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer(
                    $text,
                    type: SearchTermType::vkontakte_username,
                    normalizedText: $username,
                    messengerUser: new MessengerUserTransfer(Messenger::vkontakte, substr($username, 2), username: $username)
                ),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'OleksandrA1988',
                     '2u4a_q',
                 ] as $username) {
            yield 'vkontakte username: ' . ($text = $this->profileUrl(Messenger::vkontakte, $username)) => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::vkontakte_username, normalizedText: $username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'https://unknown.com/me',
                     'https://medium.com/@kseniatoloknova',
                 ] as $text) {
            yield 'messenger profile url: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::messenger_profile_url),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'me',
                     '2u4a_q',
                 ] as $text) {
            yield 'messenger username: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::messenger_username),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'https://example.com',
                     'https://example.com?q=1',
                 ] as $text) {
            yield 'url: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::url),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'example@gmail.com',
                     'example+3@gmail.com',
                 ] as $text) {
            yield 'email: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::email),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     '+1 (561) 314-5672' => [[], '15613145672'],
                     '1.561.314.5672' => [[], '15613145672'],
                     '380969603102' => [[], null],
                     '+380969603102' => [[], '380969603102'],
                     '380969603103' => [['country_codes' => ['ua']], null],
                     '+380969603103' => [['country_codes' => ['ua']], '380969603103'],
                     '0969603102' => [['country_codes' => ['ua']], '380969603102'],
                     '0969603103' => [['country_codes' => ['ua', 'ru']], '380969603103'],
                     '70969603103' => [['country_codes' => ['ua', 'ru']], null],
                     '+70969603103' => [['country_codes' => ['ua', 'ru']], '70969603103'],
                     '38(096)0222607' => [['country_codes' => ['ua', 'ru']], '380960222607'],
                     '380(96)0222607' => [['country_codes' => ['ua', 'ru']], '380960222607'],
                     '(380)96-960-3102' => [[], '380969603102'],
                     '3(80)96-960-3102' => [[], '380969603102'],
                     '456-7890' => [[], '4567890'],
                     '212-456-7890' => [[], '2124567890'],
                     '+1-212-456-7890' => [[], '12124567890'],
                     '1-212-456-7890' => [[], '12124567890'],
                     '001-212-456-7890' => [[], '0012124567890'],
                     '191-212-456-7890' => [[], '1912124567890'],
                     '(212)456-7890' => [[], '2124567890'],
                 ] as $text => [$context, $normalizedText]) {
            yield 'phone number: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer((string) $text, type: SearchTermType::phone_number),
                'context' => $context,
                'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($normalizedText),
            ];
        }

        foreach ([
                     'John Smith',
                     'Олександр Андрущенко',
                     'Дмитрий Сергеевич',
                 ] as $text) {
            yield 'person name: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::person_name),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'United Nations Children’s Fund (UNICEF)',
                     'United Nations Education Scientific & Cultural Organization (UNESCO)',
                     'Apple Inc. (AAPL)',
                     'Saudi Aramco (2222.SR)',
                     'Теле-канал «Україна»',
                     'McDonald`s',
                     '«Сургутнефтегаз», ПАО',
                     'En+ Group',
                     '«Аэрофлот - Российские авиалинии»',
                     '«Бэст Прайс» (сеть магазинов Fix Price)',
                 ] as $text) {
            yield 'organization name: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::organization_name),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     '2901 N Federal Hwy, Boca Raton, FL 33431',
                     'Бориса Гмирїі 9в, 55',
                     'пер. Луначарского-Быкова 134/1, 1 подьезд, кв. 14',
                 ] as $text) {
            yield 'place name: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer($text, type: SearchTermType::place_name),
                'context' => [],
                'expectedSearchTerm' => clone $searchTerm,
            ];
        }

        foreach ([
                     'нu34123ЧW' => null,
                     'АА 1234 АВ' => 'АА1234АВ',
                     '22 АН 0880' => '22АН0880',
                     'Т1 АН 0880' => 'Т1АН0880',
                     'СН АА 0001' => 'СНАА0001',
                     '01 АА 0001' => '01АА0001',
                     'D 036 036' => 'D036036',
                     'ПЕТРО' => null,
                     'Т2 42 42 АА' => 'Т24242АА',
                     '22 42 42 АА' => '224242АА',
                     'АК КА 8698' => 'АККА8698',
                     'ПЕТРО 201' => 'ПЕТРО201',
                     '24500 АА' => '24500АА',
                     'CDP 123' => 'CDP123',
                     '209-15ТВ' => '20915ТВ',
                     '1-254АП' => '1254АП',
                     'с227НА69' => null,
                     'В555РХ39' => null,
                     'АО 365 78' => 'АО36578',
                     '3733ММ55' => null,
                     'АА7711333' => null,
                     '0245 ок 43rus' => '0245ок43rus',
                     'Т АО 002 78' => 'ТАО00278',
                     'К ММ 976 39' => 'КММ97639',
                     'ERS 8579' => 'ERS8579',
                     'GMNS5699' => null,
                     '682 GKS' => '682GKS',
                     '861573' => null,
                     '693-KLS' => '693KLS',
                 ] as $text => $normalizedText) {
            yield 'car number: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer((string) $text, type: SearchTermType::car_number),
                'context' => [],
                'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($normalizedText),
            ];
        }

        foreach ([
                     '111-22-3333' => '111223333',
                     '111223333' => null,
                     '1234567891' => null,
                     '12345678910' => null,
                 ] as $text => $normalizedText) {
            yield 'tax number: ' . $text => [
                'searchTerm' => $searchTerm = new SearchTermTransfer((string) $text, type: SearchTermType::tax_number),
                'context' => [],
                'expectedSearchTerm' => (clone $searchTerm)->setNormalizedText($normalizedText),
            ];
        }
    }

    protected function profileUrl(Messenger $messenger, string $username): string
    {
        return $this->getMessengerUserProfileUrlProvider()->getMessengerUserProfileUrl($messenger, $username);
    }
}