<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorBlogger;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorBloggers;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorFeedback;
use App\Model\Search\TwentySecondFloor\TwentySecondFloorFeedbacks;
use App\Tests\Traits\Search\SearchProviderTrait;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TwentySecondFloorSearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::twenty_second_floor;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::person_name,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'supported type & not ukr' => [
            'type' => SearchTermType::instagram_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'instagram & ok' => [
            'type' => SearchTermType::instagram_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'telegram & ok' => [
            'type' => SearchTermType::telegram_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];
        yield 'facebook & ok' => [
            'type' => SearchTermType::facebook_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'tiktok & ok' => [
            'type' => SearchTermType::tiktok_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'youtube & ok' => [
            'type' => SearchTermType::youtube_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'messenger & ok' => [
            'type' => SearchTermType::messenger_username,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'instagram & many matches' => [
            'type' => SearchTermType::instagram_username,
            'term' => 'lastanislavsk',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new TwentySecondFloorBloggers([
                    new TwentySecondFloorBlogger(
                        'lastanislavska',
                        'https://22flr.com/account/lastanislavska/',
                        photo: null,
                        desc: 'Київ, Україна знімаю відео, мотивую, люблю Україну Щодо співпраці @stanislavska.cooperation Мій телеграм канал',
                        followers: 47
                    ),
                ]),
            ],
        ];

        yield 'instagram & many matches & full match found & black list' => [
            'type' => SearchTermType::instagram_username,
            'term' => 'tomka_an',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new TwentySecondFloorFeedbacks([
                    new TwentySecondFloorFeedback(
                        'Не називає росію агресором, вважає що це війна політиків, завела телеграм канал, щоб продовжувати спілкуватись з рос.аудиторією з перспективою далі продавати в росію свій товар. Наголошує, що продажів у росіє не буде ТИМЧАСОВО',
                        header: 'Iгнорування війни або згадка тільки в російських формулюваннях',
                        mark: -1,
                        author: null,
                        date: null
                    ),
                    new TwentySecondFloorFeedback(
                        'Вона абсолютно ніяк не висвітлює війну в Україні. Жодних натяків на те, що росія напала на Україну, ба навіть більше, вважає,що на політичній арені страждають мирні люди..дуже неоднозначно. Завела ТГ канал, де більшість росіянців, вона їх там підтримує морально і співчуває та отримує московські солнечниє прівєтікі і т.і. Виїхала з чоловіком і сином призовного віку в Турцію і звідти вєщає про #мизамір і #нетвойне. Дно!',
                        header: null,
                        mark: -1,
                        author: 'Аноним',
                        // this one is null, coz of fake translator
                        date: null
                    ),
                ]),
            ],
        ];
    }
}