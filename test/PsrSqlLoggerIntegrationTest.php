<?php

namespace AbacaphiliacTest\Doctrine;

use Abacaphiliac\Doctrine\PsrSqlLogger;
use Beste\Psr\Log\Record;
use Beste\Psr\Log\TestLogger;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

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
    protected function setUp(): void
    {
        $this->logger = TestLogger::create();

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

    private function getRecordByIndex(int $index): Record
    {
        return $this->logger->records->all()[$index];
    }

    public function testLogsQuery()
    {
        self::assertCount(0, $this->logger->records);

        $schema = $this->connection->getSchemaManager();

        // Generates 2 logs with a query_id:
        $schema->listTables();

        self::assertCount(2, $this->logger->records);

        $log = $this->getRecordByIndex(0);

        $queryId = $log->context->data['query_id'];

        self::assertCount(2, \array_filter($this->logger->records->all(), function (Record $record) use ($queryId) {
            return $record->context->data['query_id'] === $queryId;
        }));
        self::assertSame('Query started', (string) $this->getRecordByIndex(0)->message);
        self::assertSame('Query finished', (string) $this->getRecordByIndex(1)->message);

        // Generates 2 more logs with a new query_id:
        $schema->listTables();

        self::assertCount(4, $this->logger->records);

        self::assertCount(2, \array_filter($this->logger->records->all(), function (Record $record) use ($queryId) {
            return $record->context->data['query_id'] === $queryId;
        }));
    }
}
