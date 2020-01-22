<?php

namespace AbacaphiliacTest\test;

use Abacaphiliac\Doctrine\PsrSqlLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlLogger
 */
class PsrSqlLoggerTest extends TestCase
{
    /** @var PsrSqlLogger */
    private $sut;

    /** @var TestLogger */
    private $logger;

    /** @var string */
    private $sql = 'SELECT * FROM users WHERE id = :id';

    protected function setUp()
    {
        $this->logger = new TestLogger();

        $this->sut = new PsrSqlLogger($this->logger);
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

        $this->sut->startQuery(
            $this->sql,
            [
                ':id' => 1234,
            ],
            [
                ':id' => \PDO::PARAM_INT,
            ]
        );

        self::assertCount(1, $this->logger->records);

        $log = $this->getRecordByIndex(0);

        self::assertSame(LogLevel::INFO, (string) $log->level);
        self::assertSame('Query started', (string) $log->message);
        self::assertNotEmpty($log->context['query_id']);
        self::assertSame($this->sql, $log->context['sql']);
        self::assertSame([':id' => \PDO::PARAM_INT], $log->context['types']);
    }

    public function testLogsDuration()
    {
        self::assertCount(0, $this->logger->records);

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

        self::assertCount(2, $this->logger->records);

        $log = $this->getRecordByIndex(1);

        self::assertSame(LogLevel::INFO, (string) $log->level);
        self::assertSame('Query finished', (string) $log->message);
        self::assertNotEmpty($log->context['query_id']);
        self::assertInternalType('float', $log->context['start']);
        self::assertInternalType('float', $log->context['stop']);
        self::assertInternalType('float', $log->context['duration_s']);
    }

    public function testSharedQueryId()
    {
        self::assertCount(0, $this->logger->records);

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

        self::assertCount(2, $this->logger->records);

        $startLog = $this->getRecordByIndex(0);

        self::assertInstanceOf(\stdClass::class, $startLog);

        $queryId = $startLog->context['query_id'];
        self::assertNotEmpty($queryId);

        $stopLog = $this->getRecordByIndex(1);

        self::assertSame($queryId, $stopLog->context['query_id']);
    }

    public function testQueryIdChanges()
    {
        self::assertCount(0, $this->logger->records);

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

        self::assertCount(2, $this->logger->records);

        $firstLog = $this->getRecordByIndex(0);

        $queryId = $firstLog->context['query_id'];
        self::assertNotEmpty($queryId);

        $secondLog = $this->getRecordByIndex(1);

        self::assertNotEmpty($secondLog->context['query_id']);
        self::assertNotEquals($queryId, $secondLog->context['query_id']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLogLevel()
    {
        new PsrSqlLogger(new NullLogger(), 'InvalidLevel');
    }
}
