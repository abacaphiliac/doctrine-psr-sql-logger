<?php

namespace AbacaphiliacTest\test;

use Abacaphiliac\Doctrine\PsrSqlParamsLogger;
use Gamez\Psr\Log\Record;
use Gamez\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlParamsLogger
 */
class PsrSqlParamsLoggerTest extends TestCase
{
    /** @var PsrSqlParamsLogger */
    private $sut;

    /** @var TestLogger */
    private $logger;

    /** @var string */
    private $sql = 'SELECT * FROM users WHERE id = :id';

    protected function setUp()
    {
        $this->logger = new TestLogger();

        $this->sut = new PsrSqlParamsLogger($this->logger);
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

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 1234,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        self::assertCount(1, $this->logger->log);

        $log = $this->getRecordByIndex(0);

        self::assertInstanceOf(Record::class, $log);
        self::assertSame(LogLevel::INFO, (string) $log->level);
        self::assertSame('Query started', (string) $log->message);
        self::assertNotEmpty($log->context->get('query_id'));
        self::assertSame($this->sql, $log->context->get('sql'));
        self::assertSame([':id' => 1234], $log->context->get('params'));
        self::assertSame([':id' => \PDO::PARAM_INT], $log->context->get('types'));
    }

    public function testLogsDuration()
    {
        self::assertCount(0, $this->logger->log);

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 1234,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        $this->sut->stopQuery();

        self::assertCount(2, $this->logger->log);

        $log = $this->getRecordByIndex(1);

        self::assertInstanceOf(Record::class, $log);
        self::assertSame(LogLevel::INFO, (string) $log->level);
        self::assertSame('Query finished', (string) $log->message);
        self::assertNotEmpty($log->context->get('query_id'));
        self::assertInternalType('float', $log->context->get('start'));
        self::assertInternalType('float', $log->context->get('stop'));
        self::assertInternalType('float', $log->context->get('duration_s'));
    }

    public function testSharedQueryId()
    {
        self::assertCount(0, $this->logger->log);

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 1234,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        $this->sut->stopQuery();

        self::assertCount(2, $this->logger->log);

        $startLog = $this->getRecordByIndex(0);

        self::assertInstanceOf(Record::class, $startLog);

        $queryId = $startLog->context->get('query_id');
        self::assertNotEmpty($queryId);


        $stopLog = $this->getRecordByIndex(1);

        self::assertInstanceOf(Record::class, $stopLog);
        self::assertSame($queryId, $stopLog->context->get('query_id'));
    }

    public function testQueryIdChanges()
    {
        self::assertCount(0, $this->logger->log);

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 1234,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 2345,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        self::assertCount(2, $this->logger->log);

        $firstLog = $this->getRecordByIndex(0);

        self::assertInstanceOf(Record::class, $firstLog);

        $queryId = $firstLog->context->get('query_id');
        self::assertNotEmpty($queryId);

        $secondLog = $this->getRecordByIndex(1);

        self::assertInstanceOf(Record::class, $secondLog);
        self::assertNotEmpty($secondLog->context->get('query_id'));
        self::assertNotEquals($queryId, $secondLog->context->get('query_id'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLogLevel()
    {
        new PsrSqlParamsLogger(new NullLogger(), 'InvalidLevel');
    }
}
