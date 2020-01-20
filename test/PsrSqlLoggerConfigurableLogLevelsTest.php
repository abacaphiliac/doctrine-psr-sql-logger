<?php

namespace AbacaphiliacTest\test;

use Abacaphiliac\Doctrine\LogLevelConfiguration;
use Abacaphiliac\Doctrine\PsrSqlLoggerConfigurableLogLevels;
use Gamez\Psr\Log\Record;
use Gamez\Psr\Log\TestLogger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use TypeError;
use function usleep;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlLogger
 */
class PsrSqlLoggerConfigurableLogLevelsTest extends TestCase
{
    /** @var PsrSqlLoggerConfigurableLogLevels */
    private $sut;

    /** @var TestLogger */
    private $logger;

    /** @var string */
    private $sql = 'SELECT * FROM users WHERE id = :id';

    public function testLogLevel() : void
    {
        $this->sut->startQuery($this->sql);
        $this->sut->stopQuery();

        self::assertSame(LogLevel::DEBUG, (string) $this->getRecordByIndex(0)->level);
        self::assertSame(LogLevel::INFO, (string) $this->getRecordByIndex(1)->level);

        $this->sut->startQuery($this->sql);
        usleep(50 * 1000); //Sleep 50 milliseconds to simulate query execution
        $this->sut->stopQuery();

        self::assertSame(LogLevel::DEBUG, (string) $this->getRecordByIndex(2)->level);
        self::assertSame(LogLevel::NOTICE, (string) $this->getRecordByIndex(3)->level);
    }

    private function getRecordByIndex(int $index): Record
    {
        $record = $this->logger->log[$index];

        self::assertInstanceOf(Record::class, $record);

        return $record;
    }

    public function testFallbackToDefaultLogLevel() : void
    {
        $defaultLogLevel = LogLevel::CRITICAL;
        $psrSqlLoggerConfigurableLogLevels = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([]),
            $defaultLogLevel
        );

        $psrSqlLoggerConfigurableLogLevels->startQuery($this->sql);
        $psrSqlLoggerConfigurableLogLevels->stopQuery();

        self::assertSame($defaultLogLevel, (string) $this->getRecordByIndex(0)->level);
        self::assertSame($defaultLogLevel, (string) $this->getRecordByIndex(1)->level);
    }

    public function testInvalidConfiguration() : void
    {
        $this->expectException(TypeError::class);
        $loggerWhichWillFailToInitialize = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                0.12345 => LogLevel::DEBUG, //Inverted key / value tuple
            ])
        );
    }

    public function testInvalidLogLevelUsedInConfiration() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $loggerWhichWillFailToInitialize = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                'SOME_INVALID_LOG_LEVEL' => 100,
            ])
        );
    }

    protected function setUp()
    {
        $this->logger = new TestLogger();
        $this->sut = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                LogLevel::INFO => 0,
                LogLevel::NOTICE => 50,
                LogLevel::WARNING => 100,
                LogLevel::CRITICAL => 500
            ]),
            LogLevel::DEBUG,
        );
    }
}
