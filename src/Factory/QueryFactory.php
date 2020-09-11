<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Factory;

use DateTimeImmutable;

class QueryFactory
{
    public function buildFetchQuery(
        string $tableName,
        string $stampColumnName,
        ?int $offset,
        ?int $limit,
        ?DateTimeImmutable $maxStamp
    ): string {
        $query = 'SELECT * FROM `%s`';
        $params = [$tableName];

        if ($maxStamp) {
            $query .= ' WHERE `%s` < \'%s\'';
            $params = [...$params, $stampColumnName, $maxStamp];
        }

        if ($limit) {
            $query .= ' LIMIT %d';
            $params = [...$params, $limit];
        }

        if ($offset) {
            $query .= ' OFFSET %d';
            $params = [...$params, $offset];
        }

        return sprintf($query, $params);
    }

    public function buildCountQuery(string $tableName, string $stampColumnName, ?DateTimeImmutable $maxStamp): string
    {
        $query = 'SELECT COUNT(*) FROM `%s`';
        $params = [$tableName];

        if ($maxStamp) {
            $query .= ' WHERE `%s` < \'%s\'';
            $params = [...$params, $stampColumnName, $maxStamp];
        }

        return sprintf($query, $params);
    }
}
