<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Linkorb\TableArchiver\Dto\ArchiveDto;

class DateTimeHelper
{
    public static function fetchDateTime(array $row, ArchiveDto $dto): DateTimeInterface
    {
        $dateValue = $row[$dto->stampColumnName];

        if ($dto->isTimestamp) {
            return (new DateTimeImmutable())->setTimestamp($dateValue);
        }

        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateValue);
    }
}
