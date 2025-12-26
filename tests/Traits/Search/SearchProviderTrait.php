<?php

declare(strict_types=1);

namespace App\Tests\Traits\Search;

use App\Entity\Feedback\SearchTerm;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\Search\Provider\SearchProviderInterface;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Exception;

trait SearchProviderTrait
{
    use ArraySubsetAsserts;

    /**
     * @param SearchTermType $type
     * @param string $term
     * @param array $context
     * @param bool $expected
     * @return void
     * @dataProvider supportsDataProvider
     */
    public function testSupports(SearchTermType $type, string $term, array $context, bool $expected): void
    {
        $provider = $this->getSearchProvider(self::$searchProviderName);
        $searchTerm = new SearchTerm($term, $term, $type);

        $actual = $provider->supports($searchTerm, $context);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param SearchTermType $type
     * @param string $term
     * @param array $context
     * @param mixed $expected
     * @return void
     * @throws Exception
     * @dataProvider searchDataProvider
     */
    public function testSearch(SearchTermType $type, string $term, array $context, mixed $expected): void
    {
        $this->skipSearchTest(__CLASS__);

        $provider = $this->getSearchProvider(self::$searchProviderName);
        $searchTerm = new SearchTerm('fake', $term, $term, $type);

        $actual = $provider->search($searchTerm, $context);

        foreach ($expected as $index => $e) {
            if (is_object($e) && method_exists($e, 'getItems')) {
                $this->assertEquals(get_class($e), get_class($actual[$index]));
                $this->assertArraySubset($e->getItems(), $actual[$index]->getItems());
            } elseif ($e === null) {
                $this->assertNull($actual[$index]);
            } else {
                $this->assertEquals($e, $actual[$index]);
            }
        }

        if (count($expected) === 0) {
            $this->assertEmpty($actual);
        }
    }

    public function getSearchProvider(SearchProviderName $providerName): SearchProviderInterface
    {
        return static::getContainer()->get('app.search_provider_' . $providerName->name);
    }

    public function skipSearchTest(string $class): void
    {
        $force = $_ENV['FORCE_SKIPPED'] ?? false;

        if ($force) {
            return;
        }

        $this->markTestSkipped($class);
    }
}