<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use DateTimeInterface;
use Exception;
use Linkorb\TableArchiver\Manager\TableArchiver;

class OutputWriter
{
    private string $basePath;
    private int $archiveMode;
    /** @var resource[] */
    private array $fileResources = [];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function __destruct()
    {
        foreach ($this->fileResources as $fileResource) {
            fclose($fileResource);
        }
    }

    public function write(array $row, DateTimeInterface $dateTime): void
    {
        $name = $this->getFileName($dateTime);

        if (!isset($this->fileResources[$name])) {
            $fp = fopen($this->outputPath($name), 'a');
            $this->fileResources[$name] = $fp;
        }

        fwrite($this->fileResources[$name], json_encode($row) . "\n");
    }

    public function setArchiveMode(int $archiveMode): void
    {
        $this->archiveMode = $archiveMode;
    }

    private function getFileName(DateTimeInterface $dateTime): string
    {
        switch ($this->archiveMode) {
            case TableArchiver::YEAR:
                return sprintf('%04d.ndjson', $dateTime->format('Y'));
            case TableArchiver::YEAR_MONTH:
                return sprintf('%04d%02d.ndjson', $dateTime->format('Y'), $dateTime->format('m'));
            case TableArchiver::YEAR_MONTH_DAY:
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
