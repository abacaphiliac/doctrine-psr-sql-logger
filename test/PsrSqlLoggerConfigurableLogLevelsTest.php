<?php

namespace AbacaphiliacTest\Doctrine;

use Abacaphiliac\Doctrine\LogLevelConfiguration;
use Abacaphiliac\Doctrine\PsrSqlLoggerConfigurableLogLevels;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use TypeError;
use stdClass;
use function usleep;

/**
 * @covers \Abacaphiliac\Doctrine\PsrSqlLoggerConfigurableLogLevels
 */
class PsrSqlLoggerConfigurableLogLevelsTest extends TestCase
{
    /**
     * @var TestLogger
     */
    private $logger;
    
    /** @var string */
    private $sql = 'SELECT * FROM users WHERE id = :id';

    public function testLogLevel() : void
    {
        $defaultLogLevel = LogLevel::DEBUG;
        $logLevelAfterReachingThreshold = LogLevel::INFO;
        $thresholdInMilliseconds = 25;
        $psrSqlLoggerConfigurableLogLevels = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                $logLevelAfterReachingThreshold => $thresholdInMilliseconds,
            ]),
            $defaultLogLevel
        );

        $psrSqlLoggerConfigurableLogLevels->startQuery($this->sql);
        $psrSqlLoggerConfigurableLogLevels->stopQuery();

        self::assertSame($defaultLogLevel, (string) $this->getRecordByIndex(0)->level);
        //No threshold is reached yet: default log level should be used
        self::assertSame($defaultLogLevel, (string) $this->getRecordByIndex(1)->level);

        $psrSqlLoggerConfigurableLogLevels->startQuery($this->sql);
        usleep($thresholdInMilliseconds * 1000); //Sleep to simulate query execution and reach the threshold
        $psrSqlLoggerConfigurableLogLevels->stopQuery();

        self::assertSame($defaultLogLevel, (string) $this->getRecordByIndex(2)->level);
        self::assertSame($logLevelAfterReachingThreshold, (string) $this->getRecordByIndex(3)->level);
    }

    private function getRecordByIndex(int $index): stdClass
    {
        $record = $this->logger->records[$index];

        self::assertInternalType('array', $record);

        return (object) $record;
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

    public function testFallbackToDefaultLogLevelWhenNoThresholdIsReached() : void
    {
        $defaultLogLevel = LogLevel::DEBUG;
        $psrSqlLoggerConfigurableLogLevels = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                LogLevel::CRITICAL => 1000 * 60, //Use a huge threshold of one minute which should never be reached
            ]),
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

    public function testInvalidLogLevelUsedInConfiguration() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $loggerWhichWillFailToInitialize = new PsrSqlLoggerConfigurableLogLevels(
            $this->logger,
            new LogLevelConfiguration([
                'SOME_INVALID_LOG_LEVEL' => 100,
            ])
        );
    }

    public function testInvalidDefaultLogLevel() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new PsrSqlLoggerConfigurableLogLevels(
            new class implements LoggerInterface {
                public function emergency($message, array $context = array())
                {
                }

                public function alert($message, array $context = array())
                {
                }

                public function critical($message, array $context = array())
                {
                }

                public function error($message, array $context = array())
                {
                }

                public function warning($message, array $context = array())
                {
                }

                public function notice($message, array $context = array())
                {
                }

                public function info($message, array $context = array())
                {
                }

                public function debug($message, array $context = array())
                {
                }

                public function log($level, $message, array $context = array())
                {
                }
            },
            new LogLevelConfiguration([
            ]),
            'InvalidLevel'
        );
    }

    protected function setUp()
    {
        $this->logger = new TestLogger();
    }
}
