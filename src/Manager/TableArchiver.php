<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Manager;

use BadFunctionCallException;
use Linkorb\TableArchiver\Dto\ArchiveDto;
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

    public function archive(ArchiveDto $dto): void
    {
        $pdo = $this->createPDO($dto);

        if (false === $pdo->query('SELECT 1')->fetch()) {
            throw new BadFunctionCallException('Something is wrong with your PDO connection');
        }

        $this->detectColumnType($pdo, $dto);

        $count = $pdo->query(
            $this->queryFactory->buildCountQuery(
                $dto->tableName,
                $dto->stampColumnName,
                $dto->maxStamp,
                $dto->isTimestamp
            ),
            PDO::FETCH_COLUMN,
            0
        )->fetch();

        $this->spawnWorkers($pdo, $dto, (int) $count);

        $this->supervisor->waitForFinish();
    }

    protected function createPDO(ArchiveDto $dto): PDO
    {
        return new PDO($dto->pdoDsn, $dto->pdoUsername, $dto->pdoPassword);
    }

    private function spawnWorkers(PDO $pdo, ArchiveDto $dto, int $count): void
    {
        for ($offset = 0; $offset < $count - $this->batchSize; $offset += $this->batchSize) {
            $this->supervisor->spawn(
                [
                    $this->queryFactory->buildFetchQuery(
                        $dto->tableName,
                        $dto->stampColumnName,
                        $offset,
                        $this->batchSize,
                        $dto->maxStamp,
                        $dto->isTimestamp
                    ),
                    $dto
                ]
            );
        }

        $this->supervisor->spawn(
            [
                $this->queryFactory->buildFetchQuery(
                    $dto->tableName,
                    $dto->stampColumnName,
                    $offset,
                    null,
                    $dto->maxStamp,
                    $dto->isTimestamp
                ),
                $dto
            ]
        );
    }

    private function detectColumnType(PDO $pdo, ArchiveDto $dto): void
    {
        $testStampColumnValue = $pdo->query(
            $this->queryFactory->buildTestQuery($dto->tableName, $dto->stampColumnName),
            PDO::FETCH_COLUMN,
            0
        )->fetch();

        $dto->isTimestamp = is_numeric($testStampColumnValue);
    }
}
