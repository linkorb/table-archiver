<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Dto;

use DateTimeImmutable;

class ArchiveDto
{
    public const YEAR = 0;
    public const YEAR_MONTH = 1;
    public const YEAR_MONTH_DAY = 2;

    public string $pdoDsn;
    public string $tableName;
    public int $archiveMode;
    public string $stampColumnName;
    public bool $isTimestamp = false;
    public ?string $maxStamp = null;

    public function getStampDateTime(): ?DateTimeImmutable
    {
        return $this->maxStamp !== null ?
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->maxStamp) :
            $this->maxStamp;
    }
}
