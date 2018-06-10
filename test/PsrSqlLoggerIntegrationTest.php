<?php

namespace AbacaphiliacTest\test;

use Abacaphiliac\Doctrine\PsrSqlLogger;
use Doctrine\DBAL\Connection;
use Gamez\Psr\Log\Record;
use Gamez\Psr\Log\TestLogger;
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

    /**
     * @param integer $index
     * @return Record
     */
    private function getRecordByIndex($index)
    {
        $record = $this->logger->log[$index];

        self::assertInstanceOf(Record::class, $record);

        return $record;
    }

    public function testLogsQuery()
    {
        self::assertCount(0, $this->logger->log);

        $schema = $this->connection->getSchemaManager();

        // Generates 2 logs with a query_id:
        $schema->listTables();

        self::assertCount(2, $this->logger->log);

        $log = $this->getRecordByIndex(0);

        $queryId = $log->context->get('query_id');

        self::assertSame(2, $this->logger->log->onlyWithContextKeyAndValue('query_id', $queryId)->count());
        self::assertTrue($this->logger->log->hasRecordsWithMessage('Query started'));
        self::assertTrue($this->logger->log->hasRecordsWithMessage('Query finished'));

        // Generates 2 more logs with a new query_id:
        $schema->listTables();

        self::assertCount(4, $this->logger->log);

        self::assertSame(2, $this->logger->log->onlyWithContextKeyAndValue('query_id', $queryId)->count());
    }
}
