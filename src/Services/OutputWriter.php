<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use DateTimeInterface;
use Exception;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use SplFileObject;

class OutputWriter
{
    private string $basePath;
    private int $archiveMode;
    /** @var SplFileObject[] */
    private array $fileResources = [];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function __destruct()
    {
        foreach ($this->fileResources as $key => $fileResource) {
            unset($this->fileResources[$key]);
        }
    }

    public function write(array $row, ArchiveDto $dto): void
    {
        $dateTime = DateTimeHelper::fetchDateTime($row, $dto);
        $name = $this->getFileName($dateTime);

        if (!isset($this->fileResources[$name])) {
            $fp = new SplFileObject($this->outputPath($name), 'a');
            $this->fileResources[$name] = $fp;
        }

        $this->fileResources[$name]->fwrite(json_encode($row) . "\n");
    }

    public function setArchiveMode(int $archiveMode): void
    {
        $this->archiveMode = $archiveMode;
    }

    private function getFileName(DateTimeInterface $dateTime): string
    {
        switch ($this->archiveMode) {
            case ArchiveDto::YEAR:
                return sprintf('%04d.ndjson', $dateTime->format('Y'));
            case ArchiveDto::YEAR_MONTH:
                return sprintf('%04d%02d.ndjson', $dateTime->format('Y'), $dateTime->format('m'));
            case ArchiveDto::YEAR_MONTH_DAY:
                return sprintf(
                    '%04d%02d%02d.ndjson',
                    $dateTime->format('Y'),
                    $dateTime->format('m'),
                    $dateTime->format('d')
                );
            default:
                throw new Exception('Invalid archive mode');
        }
    }

    protected function outputPath(string $name): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $name;
    }
}
