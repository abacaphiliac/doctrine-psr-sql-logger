<?php

namespace AbacaphiliacTest\Doctrine;

use Abacaphiliac\Doctrine\LogLevelConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @covers \Abacaphiliac\Doctrine\LogLevelConfiguration
 */
class LogLevelConfigurationTest extends TestCase
{
    /** @var LogLevelConfiguration */
    private $sut;

    protected function setUp()
    {
        $this->sut = new LogLevelConfiguration([
            LogLevel::DEBUG => 0,
            LogLevel::INFO => 10,
            LogLevel::NOTICE => 20,
            LogLevel::WARNING => 30,
            LogLevel::ERROR => 40,
            LogLevel::CRITICAL => 50,
            LogLevel::ALERT => 60,
            LogLevel::EMERGENCY => 70,
        ]);
    }

    public static function dataValidLevels(): array
    {
        return [
            [-1, null],
            [0, null],
            [1, LogLevel::DEBUG],
            [9, LogLevel::DEBUG],
            [10, LogLevel::INFO],
            [11, LogLevel::INFO],
            [19, LogLevel::INFO],
            [20, LogLevel::NOTICE],
            [21, LogLevel::NOTICE],
            [29, LogLevel::NOTICE],
            [30, LogLevel::WARNING],
            [31, LogLevel::WARNING],
            [39, LogLevel::WARNING],
            [40, LogLevel::ERROR],
            [41, LogLevel::ERROR],
            [49, LogLevel::ERROR],
            [50, LogLevel::CRITICAL],
            [51, LogLevel::CRITICAL],
            [59, LogLevel::CRITICAL],
            [60, LogLevel::ALERT],
            [61, LogLevel::ALERT],
            [69, LogLevel::ALERT],
            [70, LogLevel::EMERGENCY],
            [71, LogLevel::EMERGENCY],
            [79, LogLevel::EMERGENCY],
        ];
    }

    /**
     * @dataProvider dataValidLevels
     * @param float $durationMs
     * @param string | null $expected
     */
    public function testValidLevels(
        float $durationMs,
        ?string $expected
    ): void {
        $actual = $this->sut->getApplicableLogLevel($durationMs / 1000);

        self::assertSame($expected, $actual);
    }

    public function testInvalidLogLevel(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('invalid LogLevel detected: "InvalidLevel", please choose from: "' . print_r([
            'EMERGENCY' => 'emergency',
            'ALERT' => 'alert',
            'CRITICAL' => 'critical',
            'ERROR' => 'error',
            'WARNING' => 'warning',
            'NOTICE' => 'notice',
            'INFO' => 'info',
            'DEBUG' => 'debug',
        ], true) . '"');

        new LogLevelConfiguration([
            'InvalidLevel' => 1000,
        ]);
    }
}
