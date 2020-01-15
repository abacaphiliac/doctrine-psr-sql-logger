<?php

namespace Abacaphiliac\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PsrSqlLogger implements SQLLogger
{
    /** @var LoggerInterface */
    private $logger;

    /** @var float */
    private $start;

    /** @var callable */
    private $startQueryCallable;

    /** @var string */
    private $queryId;

    /** @var string */
    private $defaultLogLevel;

    /** @var array|null */
    private $logLevelMapping;

    public function __construct(LoggerInterface $logger, string $defaultLogLevel = LogLevel::INFO, array $logLevelMapping = null)
    {
        $this->logger = $logger;
        $this->defaultLogLevel = $defaultLogLevel;
        $this->logLevelMapping = $logLevelMapping;
        $this->startQueryCallable = $this->getStartQueryCallable($defaultLogLevel);
    }

    private function getStartQueryCallable(string $level) : callable
    {
        $callable = $this->getLoggerCallable($level);

        if (!\is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf(
                '%s::%s" is not callable',
                LoggerInterface::class,
                $this->defaultLogLevel
            ));
        }

        return $callable;
    }

    private function getLoggerCallable(string $level) : array
    {
        return [$this->logger, $level];
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->queryId = \uniqid('', true);

        $this->start = \microtime(true);

        call_user_func($this->startQueryCallable, 'Query started', \array_merge(
            $this->getStartQueryContext($sql, $params, $types),
            [
                'query_id' => $this->queryId,
            ]
        ));
    }

    protected function getStartQueryContext($sql, array $params = null, array $types = null)
    {
        return [
            'sql' => $sql,
            'types' => $types,
        ];
    }

    public function stopQuery()
    {
        $stop = \microtime(true);
        $durationInSeconds = $stop - $this->start;

        \call_user_func($this->getStopQueryCallable($durationInSeconds), 'Query finished', [
            'query_id' => $this->queryId,
            'start' => $this->start,
            'stop' => $stop,
            'duration_μs' => $durationInSeconds,
        ]);
    }

    private function getStopQueryCallable(float $durationInSeconds): callable
    {
        return $this->getLoggerCallable($this->getApplicableLogLevel($durationInSeconds));
    }

    private function getApplicableLogLevel(float $durationInSeconds): string
    {
        return is_array($this->logLevelMapping) ? $this->determineApplicableLogLevel($durationInSeconds) : $this->defaultLogLevel;
    }

    private function determineApplicableLogLevel(float $durationInSeconds) : string
    {
        $durationInMilliseconds = $durationInSeconds * 1000;
        $durations = array_values($this->logLevelMapping); //Acquire a common / non-associative array
        $durations[] = $durationInMilliseconds; //Append the incoming query duration in milliseconds to the array of duration thresholds

        asort($durations, SORT_NUMERIC); //Sort the array from low to high: the provided duration will end up somewhere between the thresholds
        $durations = array_values($durations); //A re-index is required after sorting

        $key = array_search($durationInMilliseconds, $durations, true); //Determine at which position the duration ended up after sorting

        $logLevels = array_keys($this->logLevelMapping);

        return $logLevels[$key - 1]; //Now take the "previous" key
    }
}
