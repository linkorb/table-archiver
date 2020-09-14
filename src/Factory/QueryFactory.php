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
        ?DateTimeImmutable $maxStamp,
        bool $isTimestamp
    ): string {
        $query = 'SELECT * FROM `%s`';
        $params = [$tableName];

        if ($maxStamp) {
            $query .= ' WHERE `%s` < \'%s\'';
            $params = [...$params, $stampColumnName, $isTimestamp ? $maxStamp->getTimestamp() : $maxStamp];
        }

        if (!is_null($limit)) {
            $query .= ' LIMIT %d';
            $params = [...$params, $limit];
        }

        if (!is_null($offset)) {
            $query .= ' OFFSET %d';
            $params = [...$params, $offset];
        }

        return sprintf($query, ...$params);
    }

    public function buildCountQuery(
        string $tableName,
        string $stampColumnName,
        ?DateTimeImmutable $maxStamp,
        bool $isTimestamp
    ): string {
        $query = 'SELECT COUNT(*) FROM `%s`';
        $params = [$tableName];

        if ($maxStamp) {
            $query .= ' WHERE `%s` < \'%s\'';
            $params = [...$params, $stampColumnName, $isTimestamp ? $maxStamp->getTimestamp() : $maxStamp];
        }

        return sprintf($query, ...$params);
    }

    public function buildTestQuery(string $tableName, string $stampColumnName): string
    {
        return sprintf('SELECT `%s` FROM `%s` WHERE `%s` IS NOT NULL', $stampColumnName, $tableName, $stampColumnName);
    }
}
