<?php

namespace Abacaphiliac\Doctrine;

class PsrSqlParamsLogger extends PsrSqlLogger
{
    protected function getStartQueryContext($sql, array $params = null, array $types = null)
    {
        return \array_merge(parent::getStartQueryContext($sql, $params, $types), [
            'params' => $params,
        ]);
    }
}
