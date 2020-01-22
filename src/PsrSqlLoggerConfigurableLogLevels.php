<?php

namespace Abacaphiliac\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use function array_merge;
use function call_user_func;
use function is_callable;
use function microtime;
use function uniqid;

final class PsrSqlLoggerConfigurableLogLevels implements SQLLogger
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

    /** @var LogLevelConfiguration|null */
    private $logLevelConfiguration;

    public function __construct(
        LoggerInterface $logger,
        LogLevelConfiguration $logLevelConfiguration,
        string $defaultLogLevel = LogLevel::INFO
    ) {
        $this->logger                = $logger;
        $this->logLevelConfiguration = $logLevelConfiguration;
        $this->defaultLogLevel       = $defaultLogLevel;
        $this->startQueryCallable    = $this->getStartQueryCallable($defaultLogLevel);
    }

    private function getStartQueryCallable(string $level): callable
    {
        $callable = $this->getLoggerCallable($level);

        if (!is_callable($callable)) {
            throw new InvalidArgumentException(sprintf(
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

    public function startQuery($sql, array $params = null, array $types = null) : void
    {
        $this->queryId = uniqid('', true);

        $this->start = microtime(true);

        call_user_func($this->startQueryCallable, 'Query started', array_merge(
            $this->getStartQueryContext($sql, $params, $types),
            [
                'query_id' => $this->queryId,
            ]
        ));
    }

    protected function getStartQueryContext($sql, array $params = null, array $types = null) : array
    {
        return [
            'sql' => $sql,
            'types' => $types,
        ];
    }

    public function stopQuery() : void
    {
        $stop = microtime(true);
        $durationInSeconds = $stop - $this->start;

        call_user_func($this->getStopQueryCallable($durationInSeconds), 'Query finished', [
            'query_id' => $this->queryId,
            'start' => $this->start,
            'stop' => $stop,
            'duration_s' => $durationInSeconds,
        ]);
    }

    private function getStopQueryCallable(float $durationInSeconds): callable
    {
        return $this->getLoggerCallable($this->getApplicableLogLevel($durationInSeconds) ?? $this->defaultLogLevel);
    }

    private function getApplicableLogLevel(float $durationInSeconds): ?string
    {
        return $this->logLevelConfiguration instanceof LogLevelConfiguration
            ? $this->logLevelConfiguration->getApplicableLogLevel($durationInSeconds)
            : $this->defaultLogLevel;
    }
}
