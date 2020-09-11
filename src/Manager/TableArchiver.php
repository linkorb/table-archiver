<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Manager;

use DateTimeImmutable;
use Linkorb\TableArchiver\Factory\QueryFactory;
use Linkorb\TableArchiver\Services\Supervisor;
use PDO;

class TableArchiver
{
    public const YEAR = 0;
    public const YEAR_MONTH = 1;
    public const YEAR_MONTH_DAY = 2;

    private QueryFactory $queryFactory;

    private Supervisor $supervisor;

    private int $batchSize;

    public function __construct(QueryFactory $queryFactory, Supervisor $supervisor, int $batchSize)
    {
        $this->queryFactory = $queryFactory;
        $this->supervisor = $supervisor;
        $this->batchSize = $batchSize;
    }

    public function archive(
        PDO $pdo,
        string $tableName,
        int $archiveMode,
        string $stampColumnName,
        ?DateTimeImmutable $maxStamp
    ): void {
        $count = $pdo->query(
            $this->queryFactory->buildCountQuery($tableName, $stampColumnName, $maxStamp),
            PDO::FETCH_COLUMN
        );

        for ($offset = 0; $offset < $count; $offset += $this->batchSize) {
            $this->supervisor->spawn(
                [
                    $pdo,
                    $this->queryFactory->buildFetchQuery(
                        $tableName,
                        $stampColumnName,
                        $offset,
                        $this->batchSize,
                        $maxStamp
                    ),
                    $archiveMode
                ]
            );
        }

        $this->supervisor->spawn(
            [
                $pdo,
                $this->queryFactory->buildFetchQuery(
                    $tableName,
                    $stampColumnName,
                    $offset,
                    null,
                    $maxStamp
                ),
                $archiveMode
            ]
        );

        $this->supervisor->waitForFinish();
    }
}
