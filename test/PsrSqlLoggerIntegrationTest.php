<?php

namespace AbacaphiliacTest\Doctrine;

use Abacaphiliac\Doctrine\PsrSqlLogger;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlLogger
 */
class PsrSqlLoggerIntegrationTest extends TestCase
{
    /** @var TestLogger */
    private $logger;

    /** @var Connection */
    private $connection;

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setUp()
    {
        $this->logger = new TestLogger();

        $config = new \Doctrine\DBAL\Configuration();
        $config->setSQLLogger(new PsrSqlLogger($this->logger));

        $this->connection = \Doctrine\DBAL\DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            $config
        );
    }

    private function getRecordByIndex(int $index): \stdClass
    {
        $record = $this->logger->records[$index];

        self::assertInternalType('array', $record);

        return (object) $record;
    }

    public function testLogsQuery()
    {
        self::assertCount(0, $this->logger->records);

        $schema = $this->connection->getSchemaManager();

        // Generates 2 logs with a query_id:
        $schema->listTables();

        self::assertCount(2, $this->logger->records);

        $log = $this->getRecordByIndex(0);

        $queryId = $log->context['query_id'];

        self::assertCount(2, \array_filter($this->logger->records, function ($record) use ($queryId) {
            return $record['context']['query_id'] === $queryId;
        }));
        self::assertSame('Query started', $this->getRecordByIndex(0)->message);
        self::assertSame('Query finished', $this->getRecordByIndex(1)->message);

        // Generates 2 more logs with a new query_id:
        $schema->listTables();

        self::assertCount(4, $this->logger->records);

        self::assertCount(2, \array_filter($this->logger->records, function ($record) use ($queryId) {
            return $record['context']['query_id'] === $queryId;
        }));
    }
}
