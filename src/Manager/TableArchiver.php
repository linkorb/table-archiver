<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Manager;

use DateTimeImmutable;
use PDO;

class TableArchiverManager
{
    public const YEAR = 0;
    public const YEAR_MONTH = 1;
    public const YEAR_MONTH_DAY = 2;

    private int $batchSize;

    public function __construct(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function archive(
        PDO $pdo,
        string $tableName,
        int $archiveMode,
        string $stampColumnName,
        ?DateTimeImmutable $maxStamp
    ): void {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $offset = 0;
        $pdo->exec('')

        foreach (
            $pdo->query(
                $this->buildQuery($tableName, $stampColumnName, $offset, $this->batchSize, $maxStamp)
            ) as $row
        ) {
        }

        while ($result->)
    }

    private function buildQuery(
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

        return sprintf($query, $params);
    }
}
