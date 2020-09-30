<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Manager;

use BadFunctionCallException;
use InvalidArgumentException;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Factory\QueryFactory;
use Linkorb\TableArchiver\Services\OutputArchiver;
use Linkorb\TableArchiver\Services\Supervisor;
use LogicException;
use PDO;

class TableArchiver
{
    private QueryFactory $queryFactory;

    private Supervisor $supervisor;

    private OutputArchiver $outputArchiver;

    private int $batchSize;

    public function __construct(
        QueryFactory $queryFactory,
        Supervisor $supervisor,
        OutputArchiver $outputArchiver,
        int $batchSize
    ) {
        $this->queryFactory = $queryFactory;
        $this->supervisor = $supervisor;
        $this->outputArchiver = $outputArchiver;
        $this->batchSize = $batchSize;
    }

    public function archive(PDO $pdo, ArchiveDto $dto): int
    {
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

        $this->spawnWorkers($dto, (int)$count);

        if ($count !== $this->supervisor->waitForFinish()) {
            throw new LogicException('Number of found and processed rows isn\'t match');
        }

        return $count;
    }

    public function archiveExportedFiles(): void
    {
        $this->outputArchiver->archive();
    }

    public function flushArchived(PDO $pdo, ArchiveDto $dto, int $rowsArchived): void
    {
        $pdo->beginTransaction();

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

        if ($count !== $rowsArchived) {
            throw new InvalidArgumentException('Number of archived rows and marked for deletion mismatch');
        }

        $pdo->exec(
            $this->queryFactory->buildDeleteQuery(
                $dto->tableName,
                $dto->stampColumnName,
                $dto->maxStamp,
                $dto->isTimestamp
            )
        );

        $pdo->commit();
    }

    private function spawnWorkers(ArchiveDto $dto, int $count): void
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
