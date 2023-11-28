<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service\Search\Provider;

use App\Entity\Search\Clarity\ClarityEdr;
use App\Entity\Search\Clarity\ClarityEdrsRecord;
use App\Entity\Search\Clarity\ClarityPersonCourt;
use App\Entity\Search\Clarity\ClarityPersonCourtsRecord;
use App\Entity\Search\Clarity\ClarityPersonDebtor;
use App\Entity\Search\Clarity\ClarityPersonDebtorsRecord;
use App\Entity\Search\Clarity\ClarityPersonDeclaration;
use App\Entity\Search\Clarity\ClarityPersonDeclarationsRecord;
use App\Entity\Search\Clarity\ClarityPersonEdr;
use App\Entity\Search\Clarity\ClarityPersonEdrsRecord;
use App\Entity\Search\Clarity\ClarityPersonEnforcement;
use App\Entity\Search\Clarity\ClarityPersonEnforcementsRecord;
use App\Entity\Search\Clarity\ClarityPersonSecurity;
use App\Entity\Search\Clarity\ClarityPersonSecurityRecord;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Tests\Traits\Search\SearchProviderTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Generator;
use DateTimeImmutable;

class ClaritySearchProviderTest extends KernelTestCase
{
    use SearchProviderTrait;

    protected static SearchProviderName $searchProviderName = SearchProviderName::clarity;

    public function supportsDataProvider(): Generator
    {
        yield 'not supported type' => [
            'type' => SearchTermType::facebook_username,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & not ukr' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'person name & one word' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & not cyrillic' => [
            'type' => SearchTermType::person_name,
            'term' => 'any word',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'person name & ok' => [
            'type' => SearchTermType::person_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'org name & not ukr' => [
            'type' => SearchTermType::organization_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'org name & not cyrillic' => [
            'type' => SearchTermType::organization_name,
            'term' => 'any word',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'org name & ok' => [
            'type' => SearchTermType::organization_name,
            'term' => 'слово перше',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

        yield 'tax number & not ukr' => [
            'type' => SearchTermType::tax_number,
            'term' => '12341234',
            'context' => [
                'countryCode' => 'us',
            ],
            'expected' => false,
        ];

        yield 'tax number & not numeric' => [
            'type' => SearchTermType::tax_number,
            'term' => 'any',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'tax number & not edrpou' => [
            'type' => SearchTermType::tax_number,
            'term' => '1234',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => false,
        ];

        yield 'tax number & ok' => [
            'type' => SearchTermType::tax_number,
            'term' => '12341234',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => true,
        ];

//        yield 'phone number & not ukr' => [
//            'type' => SearchTermType::phone_number,
//            'term' => '15613145672',
//            'context' => [],
//            'expected' => false,
//        ];
//
//        yield 'phone number & ok' => [
//            'type' => SearchTermType::phone_number,
//            'term' => '380969603103',
//            'context' => [],
//            'expected' => true,
//        ];
    }

    public function searchDataProvider(): Generator
    {
        yield 'person name & single match' => [
            'type' => SearchTermType::person_name,
            'term' => 'АНДРУЩЕНКО СЕРГІЙ МИКОЛАЙОВИЧ',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new ClarityPersonSecurityRecord([
                    new ClarityPersonSecurity(
                        'АНДРУЩЕНКО СЕРГІЙ МИКОЛАЙОВИЧ',
                        bornAt: new DateTimeImmutable('1974-06-10'),
                        category: 'особа, зникла безвісти',
                        region: 'ДОНЕЦЬКА, ВОЛНОВАСЬКИЙ, ПАВЛІВКА',
                        absentAt: new DateTimeImmutable('2022-11-05'),
                        archive: null,
                        accusation: null,
                        precaution: null
                    ),
                ]),
                new ClarityPersonCourtsRecord([
                    new ClarityPersonCourt(
                        '635/4369/21',
                        state: null,
                        side: 'відповідач',
                        desc: 'позовна заява про розірвання шлюбу',
                        place: 'Харківський районний суд Харківської області'
                    ),
                ]),
                new ClarityPersonDebtorsRecord([
                    new ClarityPersonDebtor(
                        'АНДРУЩЕНКО СЕРГІЙ МИКОЛАЙОВИЧ',
                        bornAt: new DateTimeImmutable('1983-03-02'),
                        category: 'Заборгованість по аліментах',
                        actualAt: new DateTimeImmutable('2023-08-31')
                    ),
                ]),
                new ClarityPersonEnforcementsRecord([
                    new ClarityPersonEnforcement(
                        '71732548',
                        openedAt: new DateTimeImmutable('2023-05-04'),
                        collector: 'ДЕРЖАВА',
                        debtor: 'АНДРУЩЕНКО СЕРГІЙ МИКОЛАЙОВИЧ',
                        bornAt: new DateTimeImmutable('1992-08-04'),
                        state: 'Відкрито'
                    ),
                ]),
                new ClarityPersonEdrsRecord([
                    new ClarityPersonEdr(
                        'РЕЛІГІЙНА ГРОМАДА ХРИСТИЯН ВІРИ ЄВАНГЕЛЬСЬКОЇ СМТ.ЧОРНУХИ',
                        type: '(Історичні дані)',
                        href: 'https://clarity-project.info/edr/25976682',
                        number: '25976682',
                        active: true,
                        address: null
                    ),
                ]),
                new ClarityPersonDeclarationsRecord([
                    new ClarityPersonDeclaration(
                        'АНДРУЩЕНКО СЕРГІЙ МИКОЛАЙОВИЧ',
                        href: 'https://clarity-project.infohttps://declarations.com.ua/declaration/nacp_124708ff-618b-4b46-8c96-89bf811c0e7a',
                        year: '2016',
                        position: 'молодший інспектор відділу нагляду і безпеки, Державна установа "Біленьківська виправна колонія (№ 99)"'
                    ),
                ]),
            ],
        ];

        yield 'person name & many matches' => [
            'type' => SearchTermType::person_name,
            'term' => 'КРОЛЕВЕЦЬ СЕРГІЙ ВІКТОРОВИЧ',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new ClarityPersonEdrsRecord([
                    new ClarityPersonEdr(
                        'СІЛЬСЬКОГОСПОДАРСЬКИЙ ВИРОБНИЧИЙ КООПЕРАТИВ "СУПІЙ"',
                        type: 'Засновник (Історичні дані)',
                        href: 'https://clarity-project.info/edr/03756388',
                        number: '03756388',
                        active: false,
                        address: 'Положаї'
                    ),
                ]),
            ],
        ];

        yield 'person name & no matches & direct person found' => [
            'type' => SearchTermType::person_name,
            'term' => 'Солтис Денис Миколайович',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new ClarityPersonCourtsRecord([
                    new ClarityPersonCourt(
                        '752/2460/20',
                        state: null,
                        side: 'обвинувачений',
                        desc: null,
                        place: 'Голосіївський районний суд міста Києва'
                    ),
                ]),
                new ClarityPersonEnforcementsRecord([
                    new ClarityPersonEnforcement(
                        '67242704',
                        openedAt: new DateTimeImmutable('2021-10-25'),
                        collector: 'ГОЛОВНЕ УПРАВЛІННЯ ДПС У М.КИЄВІ #44116011',
                        debtor: 'СОЛТИС ДЕНИС МИКОЛАЙОВИЧ',
                        bornAt: new DateTimeImmutable('1988-11-02'),
                        state: 'Завершено'
                    ),
                    new ClarityPersonEnforcement(
                        '60748938',
                        openedAt: new DateTimeImmutable('2019-12-03'),
                        collector: 'ДНІПРОВСЬКИЙ РАЙОННИЙ СУД МІСТА КИЄВА #02896696',
                        debtor: 'СОЛТИС ДЕНИС МИКОЛАЙОВИЧ',
                        bornAt: new DateTimeImmutable('1988-11-02'),
                        state: 'Завершено'
                    ),
                ]),
                new ClarityPersonEdrsRecord([
                    new ClarityPersonEdr(
                        'СОЛТИС ДЕНИС МИКОЛАЙОВИЧ',
                        type: 'ФОП',
                        href: 'https://clarity-project.info/fop/b26232bdf69675680f0b154f4cca4147',
                        number: null,
                        active: false,
                        address: 'Київ'
                    ),
                ]),
            ],
        ];

        yield 'phone number & nothing found' => [
            'type' => SearchTermType::phone_number,
            'term' => '0504439147',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [null],
        ];

        yield 'tax number & many matches' => [
            'type' => SearchTermType::phone_number,
            'term' => '19420704',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new ClarityEdrsRecord([
                    new ClarityEdr(
                        'Мале приватне підприємство фірма "ЕРІДОН"',
                        type: null,
                        href: 'https://clarity-project.info/edr/19420704',
                        number: '19420704',
                        active: null,
                        address: '08143, Україна, Київська область, Княжичі, Воздвиженська, 46'
                    ),
                    new ClarityEdr(
                        'Мале приватне підприємство фірма "Ерідон"',
                        type: null,
                        href: 'https://clarity-project.info/edr/19420704194207010139',
                        number: '19420704',
                        active: null,
                        address: '08143, Україна, Київська область, с. Княжичі, Київська обл., Києво - Святошинський р-н, с. Княжичі, вул. Леніна, 46'
                    ),
                ]),
            ],
        ];

        yield 'org name & many matches' => [
            'type' => SearchTermType::organization_name,
            'term' => 'ерідон',
            'context' => [
                'countryCode' => 'ua',
            ],
            'expected' => [
                new ClarityEdrsRecord([
                    new ClarityEdr(
                        'Мале приватне підприємство фірма "ЕРІДОН"',
                        type: null,
                        href: 'https://clarity-project.info/edr/19420704',
                        number: '19420704',
                        active: null,
                        address: '08143, Україна, Київська область, Княжичі, Воздвиженська, 46'
                    ),
                    new ClarityEdr(
                        'ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ "ЕРІДОН"',
                        type: null,
                        href: 'https://clarity-project.info/edr/36656183',
                        number: '36656183',
                        active: null,
                        address: 'М. КИЇВ, ВУЛ. СОЛОМ\'ЯНСЬКА, БУД. 33'
                    ),
                ]),
            ],
        ];
    }
}