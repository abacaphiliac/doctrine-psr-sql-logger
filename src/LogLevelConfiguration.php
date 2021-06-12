<?php

namespace Abacaphiliac\Doctrine;

use InvalidArgumentException;
use Psr\Log\LogLevel;
use ReflectionClass;
use function asort;
use function array_keys;
use function array_search;
use function array_values;
use function print_r;
use function count;

final class LogLevelConfiguration
{
    /** @var array<string, int> */
    private $logLevelMapping = [];

    public function __construct(array $logLevelMapping)
    {
        foreach ($logLevelMapping as $logLevel => $durationThresholdInMilliseconds) {
            $this->addLogLevelThreshold($logLevel, $durationThresholdInMilliseconds);
        }
    }

    private function addLogLevelThreshold(string $logLevel, int $durationThresholdInMilliseconds) : void
    {
        $this->validateLogLevel($logLevel);
        $this->logLevelMapping[$logLevel] = $durationThresholdInMilliseconds;
    }

    private function validateLogLevel(string $logLevel): void
    {
        if (! $this->isAllowedLogLevel($logLevel)) {
            throw new InvalidArgumentException(sprintf(
                'invalid LogLevel detected: "%s", please choose from: "%s"',
                $logLevel,
                print_r($this->getAllowedLogLevels(), true)
            ));
        }
    }
    
    private function isAllowedLogLevel(string $logLevel): bool
    {
        return in_array($logLevel, $this->getAllowedLogLevels(), true);
    }

    private function getAllowedLogLevels(): array
    {
        static $allowedConstants;

        return $allowedConstants ?: $allowedConstants = (new ReflectionClass(LogLevel::class))->getConstants();
    }

    public function getApplicableLogLevel(float $durationInSeconds): ?string
    {
        if ($durationInSeconds <= 0) {
            return null;
        }

        return count($this->logLevelMapping) > 0 ? $this->determineApplicableLogLevel($durationInSeconds) : null;
    }

    private function determineApplicableLogLevel(float $durationInSeconds) : ?string
    {
        $durationInMilliseconds = $durationInSeconds * 1000;

        //Acquire a common / non-associative array with all the thresholds
        $durationThresholds = array_values($this->logLevelMapping);

        //Append the incoming query duration in milliseconds to the array of duration thresholds
        $durationThresholds[] = $durationInMilliseconds;

        //Sort the array from low to high: the provided duration will end up somewhere between the thresholds
        asort($durationThresholds, SORT_NUMERIC);

        //A re-index is required after sorting
        $durationThresholds = array_values($durationThresholds);

        //Determine at which position the duration ended up after sorting
        $key = array_search($durationInMilliseconds, $durationThresholds, true);

        $logLevels = array_keys($this->logLevelMapping);

        return $logLevels[$key - 1] ?? null;
    }
}
