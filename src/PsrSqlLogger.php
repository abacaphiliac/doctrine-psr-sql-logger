<?php

namespace Abacaphiliac\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PsrSqlLogger implements SQLLogger
{
    /** @var callable */
    private $logger;

    /** @var float */
    private $start;

    /** @var string */
    private $queryId;

    /**
     * PsrSqlLogger constructor.
     * @param LoggerInterface $logger
     * @param string $level
     */
    public function __construct(LoggerInterface $logger, $level = LogLevel::INFO)
    {
        $callable = [$logger, $level];

        if (!\is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf(
                '%s::%" is not callable',
                LoggerInterface::class,
                $level
            ));
        }

        $this->logger = $callable;
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->queryId = \uniqid('', true);

        $this->start = \microtime(true);

        \call_user_func($this->logger, 'Query started', \array_merge(
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

        \call_user_func($this->logger, 'Query finished', [
            'query_id' => $this->queryId,
            'start' => $this->start,
            'stop' => $stop,
            'duration_μs' => $stop - $this->start,
        ]);
    }
}
