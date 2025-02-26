<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DoNotContactFilterQueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    public function testGetServiceId(): void
    {
        Assert::assertSame('mautic.lead.query.builder.special.dnc', DoNotContactFilterQueryBuilder::getServiceId());
    }

    /**
     * @dataProvider dataApplyQuery
     */
    public function testApplyQuery(string $operator, string $parameterValue, string $expectedQuery): void
    {
        $queryBuilder = new QueryBuilder($this->createConnection());
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter             = $this->createFilter($operator, $parameterValue);
        $filterQueryBuilder = new DoNotContactFilterQueryBuilder(new RandomParameterName(), new EventDispatcher());

        Assert::assertSame($queryBuilder, $filterQueryBuilder->applyQuery($queryBuilder, $filter));
        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataApplyQuery(): iterable
    {
        yield ['eq', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['eq', '0', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '1', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '0', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQueryWithBatchLimiters(): iterable
    {
        yield [['minId' => 1, 'maxId' => 1], 'eq', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'eq', '0', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'neq', '1', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'neq', '0', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id BETWEEN 1 and 1))'];

        yield [['minId' => 1], 'eq', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id >= 1))'];
        yield [['minId' => 1], 'eq', '0', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id >= 1))'];
        yield [['minId' => 1], 'neq', '1', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id >= 1))'];
        yield [['minId' => 1], 'neq', '0', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id >= 1))'];

        yield [['maxId' => 1], 'eq', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id <= 1))'];
        yield [['maxId' => 1], 'eq', '0', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id <= 1))'];
        yield [['maxId' => 1], 'neq', '1', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id <= 1))'];
        yield [['maxId' => 1], 'neq', '0', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id <= 1))'];

        yield [['lead_id' => 1], 'eq', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id = 1))'];
        yield [['lead_id' => 1], 'eq', '0', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id = 1))'];
        yield [['lead_id' => 1], 'neq', '1', 'SELECT 1 FROM leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id = 1))'];
        yield [['lead_id' => 1], 'neq', '0', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par0.lead_id FROM lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\') AND (par0.lead_id = 1))'];
    }

    /**
     * @dataProvider dataApplyQueryWithBatchLimiters
     *
     * @param array<string, mixed> $batchLimiters
     */
    public function testApplyQueryWithBatchLimiters(array $batchLimiters, string $operator, string $parameterValue, string $expectedQuery): void
    {
        $queryBuilder = new QueryBuilder($this->createConnection());
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter             = $this->createFilter($operator, $parameterValue, $batchLimiters);
        $filterQueryBuilder = new DoNotContactFilterQueryBuilder(new RandomParameterName(), new EventDispatcher());

        Assert::assertSame($queryBuilder, $filterQueryBuilder->applyQuery($queryBuilder, $filter));
        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    private function createConnection(): Connection
    {
        return new class() extends Connection {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
            }
        };
    }

    /**
     * @dataProvider dataApplyQueryWithBatchLimitersMinMaxBoth
     *
     *  @param array<string, mixed> $batchLimiters
     */
    private function createFilter(string $operator, string $parameterValue, array $batchLimiters = []): ContactSegmentFilter
    {
        return new class($operator, $parameterValue, $batchLimiters) extends ContactSegmentFilter {
            /**
             * @var string
             */
            private $operator;

            /**
             * @var string
             */
            private $parameterValue;

            /**
             * @var array<string, mixed>
             */
            private $batchLimiters;

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(string $operator, string $parameterValue, array $batchLimiters)
            {
                $this->operator       = $operator;
                $this->parameterValue = $parameterValue;
                $this->batchLimiters  = $batchLimiters;
            }

            public function getDoNotContactParts()
            {
                return new DoNotContactParts('dnc_unsubscribed');
            }

            public function getOperator()
            {
                return $this->operator;
            }

            public function getParameterValue()
            {
                return $this->parameterValue;
            }

            public function getGlue()
            {
                return 'and';
            }

            public function getBatchLimiters(): array
            {
                return $this->batchLimiters;
            }
        };
    }
}
