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

    public function __call(PDO $pdo, string $query, ArchiveDto $dto): void
    {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->writer->setArchiveMode($dto->archiveMode);

        foreach ($pdo->query($query)->fetch() as $row) {
            $this->writer->write($row, $this->fetchDateTime($row, $dto));
        }
    }

    private function fetchDateTime(array $row, ArchiveDto $dto): DateTimeInterface
    {
        $dateValue = $row[$dto->stampColumnName];

        if ($dto->isTimestamp) {
            return (new DateTimeImmutable())->setTimestamp($dateValue);
        }

        return DateTimeImmutable::createFromFormat('Y-m-d h:i:s', $dateValue);
    }
}
