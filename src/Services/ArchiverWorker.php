<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use PDO;

class ArchiverWorker
{
    private OutputWriter $writer;

    public function __construct(OutputWriter $writer)
    {
        $this->writer = $writer;
    }

    public function __invoke(string $query, ArchiveDto $dto): void
    {
        $pdo = $this->createPDO($dto);

        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->writer->setArchiveMode($dto->archiveMode);

        $pdoStatement = $pdo->query($query);

        while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
            $this->writer->write($row, $this->fetchDateTime($row, $dto));
        }
    }

    protected function createPDO(ArchiveDto $dto): PDO
    {
        return new PDO($dto->pdoDsn, $dto->pdoUsername, $dto->pdoPassword);
    }

    private function fetchDateTime(array $row, ArchiveDto $dto): DateTimeInterface
    {
        $dateValue = $row[$dto->stampColumnName];

        if ($dto->isTimestamp) {
            return (new DateTimeImmutable())->setTimestamp($dateValue);
        }

        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateValue);
    }
}
