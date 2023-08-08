<?php

namespace AbacaphiliacTest\Doctrine;

use Abacaphiliac\Doctrine\PsrSqlParamsLogger;
use Beste\Psr\Log\Record;
use Beste\Psr\Log\TestLogger;
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

    protected function setUp(): void
    {
        $this->logger = TestLogger::create();

        $this->sut = new PsrSqlParamsLogger($this->logger);
    }

    private function getRecordByIndex(int $index): Record
    {
        return $this->logger->records->all()[$index];
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
        self::assertNotEmpty($log->context->data['query_id']);
        self::assertSame($this->sql, $log->context->data['sql']);
        self::assertSame([':id' => 1234], $log->context->data['params']);
        self::assertSame([':id' => \PDO::PARAM_INT], $log->context->data['types']);
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
        self::assertNotEmpty($log->context->data['query_id']);
        self::assertIsFloat($log->context->data['start']);
        self::assertIsFloat($log->context->data['stop']);
        self::assertIsFloat($log->context->data['duration_s']);
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

        $queryId = $startLog->context->data['query_id'];
        self::assertNotEmpty($queryId);


        $stopLog = $this->getRecordByIndex(1);

        self::assertSame($queryId, $stopLog->context->data['query_id']);
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


        $queryId = $firstLog->context->data['query_id'];
        self::assertNotEmpty($queryId);

        $secondLog = $this->getRecordByIndex(1);

        self::assertNotEmpty($secondLog->context->data['query_id']);
        self::assertNotEquals($queryId, $secondLog->context->data['query_id']);
    }

    public function testInvalidLogLevel()
    {
        self:: expectException(\InvalidArgumentException::class);

        new PsrSqlParamsLogger(new NullLogger(), 'InvalidLevel');
    }
}
