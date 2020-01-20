<?php

namespace Abacaphiliac\Doctrine;

class LogLevelConfiguration
{
    /** @var array */
    private $logLevelMapping = [];

    public function __construct(array $logLevelMapping)
    {
        foreach ($logLevelMapping as $logLevelPriority => $durationThresholdInMilliseconds) {
            $this->addLogLevelThreshold($logLevelPriority, $durationThresholdInMilliseconds);
        }
    }

    private function addLogLevelThreshold(string $logLevelPriority, int $durationThresholdInMilliseconds) : void
    {
        $this->logLevelMapping[$logLevelPriority] = $durationThresholdInMilliseconds;
    }

    public function getApplicableLogLevel(float $durationInSeconds): ?string
    {
        return count($this->logLevelMapping) > 0 ? $this->determineApplicableLogLevel($durationInSeconds) : null;
    }

    private function determineApplicableLogLevel(float $durationInSeconds) : string
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

        return $logLevels[$key - 1]; //Now take the "previous" key
    }
}
