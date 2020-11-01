<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Manager;

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

    private int $processingThreadsNumber;

    public function __construct(
        QueryFactory $queryFactory,
        Supervisor $supervisor,
        OutputArchiver $outputArchiver,
        int $processingThreadsNumber
    ) {
        $this->queryFactory = $queryFactory;
        $this->supervisor = $supervisor;
        $this->outputArchiver = $outputArchiver;
        $this->processingThreadsNumber = $processingThreadsNumber;
    }

    public function archive(PDO $pdo, ArchiveDto $dto): int
    {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->detectColumnType($pdo, $dto);

        $count = (int)$pdo->query(
            $this->queryFactory->buildCountQuery(
                $dto->tableName,
                $dto->stampColumnName,
                $dto->getStampDateTime(),
                $dto->isTimestamp
            ),
            PDO::FETCH_COLUMN,
            0
        )->fetch();

        $this->spawnWorkers($dto);

        $pdoStatement = $pdo->query($this->queryFactory->buildFetchQuery(
            $dto->tableName,
            $dto->stampColumnName,
            $dto->getStampDateTime(),
            $dto->isTimestamp
        ));

        while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
            $this->supervisor->delegate($row);
        }

        if ($count !== $this->supervisor->terminateThreads()) {
            throw new LogicException('Number of found and processed rows aren\'t match');
        }

        return $count;
    }

    public function archiveExportedFiles(): void
    {
        $this->outputArchiver->archive();
    }

    public function flushArchived(PDO $pdo, ArchiveDto $dto): void
    {
        $pdo->exec(
            $this->queryFactory->buildDeleteQuery(
                $dto->tableName,
                $dto->stampColumnName,
                $dto->getStampDateTime(),
                $dto->isTimestamp
            )
        );
    }

    private function spawnWorkers(ArchiveDto $dto): void
    {
        for ($i = 0; $i < $this->processingThreadsNumber; $i++) {
            $this->supervisor->spawnProcessing([$dto]);
        }
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
