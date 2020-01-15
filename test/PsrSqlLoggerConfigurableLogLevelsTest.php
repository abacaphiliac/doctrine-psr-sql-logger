<?php

namespace AbacaphiliacTest\test;

use Abacaphiliac\Doctrine\PsrSqlLogger;
use Gamez\Psr\Log\Record;
use Gamez\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlLogger
 */
class PsrSqlLoggerConfigurableLogLevelsTest extends TestCase
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
        $logLevelsForQueryDurationsInMilliseconds = [
            LogLevel::INFO => 0,
            LogLevel::NOTICE => 50,
            LogLevel::WARNING => 100,
            LogLevel::CRITICAL => 500
        ];
        $this->sut = new PsrSqlLogger($this->logger, LogLevel::DEBUG, $logLevelsForQueryDurationsInMilliseconds);
    }

    private function getRecordByIndex(int $index): Record
    {
        $record = $this->logger->log[$index];

        self::assertInstanceOf(Record::class, $record);

        return $record;
    }

    public function testLogLevel()
    {
        $this->sut->startQuery($this->sql);
        $this->sut->stopQuery();

        self::assertSame(LogLevel::DEBUG, (string) $this->getRecordByIndex(0)->level);
        self::assertSame(LogLevel::INFO, (string) $this->getRecordByIndex(1)->level);

        $this->sut->startQuery($this->sql);
        \usleep(50 * 1000); //Sleep 50 milliseconds to simulate query execution
        $this->sut->stopQuery();

        self::assertSame(LogLevel::DEBUG, (string) $this->getRecordByIndex(2)->level);
        self::assertSame(LogLevel::NOTICE, (string) $this->getRecordByIndex(3)->level);
    }
}
