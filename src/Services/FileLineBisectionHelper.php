<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use DateTimeInterface;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use LogicException;
use SplFileObject;

class FileLineBisectionHelper
{
    public static function getLine(
        SplFileObject $fileObject,
        DateTimeInterface $dateTime,
        ArchiveDto $dto,
        int $sectorLength = null,
        int $offset = 0,
        int $direction = 0
    ): int
    {
        if ($sectorLength === null) {
            $fileObject->fseek($fileObject->getSize());
            $sectorLength = $fileObject->key();

            if (!is_null($pos = static::tryOptimistic($fileObject, $dateTime, $dto))) {
                return $pos;
            }
        }

        $sectorLength = (int) ceil($sectorLength / 2);
        $positionCandidate = $offset + ($direction <=> 0) * $sectorLength;

        try {
            $fileObject->seek($positionCandidate - 1);

            $prevVal = static::strToDate($fileObject->fgets()?: null, $dto);
        } catch (LogicException $e) {
            $prevVal = null;
            $fileObject->seek(0);
        }

        $nextVal = !$fileObject->eof() ? static::strToDate($fileObject->fgets()?: null, $dto) : null;

        switch (true) {
            case $prevVal && $nextVal && $nextVal < $dateTime:
                return static::getLine($fileObject, $dateTime, $dto, $sectorLength, $positionCandidate, 1);
            case $prevVal && $nextVal && $prevVal > $dateTime:
                return static::getLine($fileObject, $dateTime, $dto, $sectorLength, $positionCandidate, -1);
            case !$prevVal && !$nextVal:
                return 0;
            default:
                return $positionCandidate;
        }
    }

    private static function tryOptimistic(SplFileObject $file, DateTimeInterface $dateTime, ArchiveDto $dto): ?int
    {
        $endValue = static::strToDate($file->current()?: null, $dto);
        if ($dateTime > $endValue) {
            return $file->key();
        }

        return null;
    }

    private static function strToDate(?string $str, ArchiveDto $dto): ?DateTimeInterface
    {
        return $str ? DateTimeHelper::fetchDateTime(json_decode($str), $dto) : null;
    }
}
