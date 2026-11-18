<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Services;

use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Services\OutputWriter;
use PHPUnit\Framework\TestCase;

class OutputWriterWriteTest extends TestCase
{
    private string $basePath;

    public function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'output-writer-write-' . bin2hex(random_bytes(4));
        mkdir($this->basePath);
    }

    public function tearDown(): void
    {
        if (!is_dir($this->basePath)) {
            return;
        }

        foreach (glob($this->basePath . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($this->basePath);
    }

    public function testWriteAppendsNdjsonThroughTheCachedHandle()
    {
        $writer = $this->writer(ArchiveDto::YEAR_MONTH_DAY);
        $first = ['name' => 'a', 'datetime' => '2020-08-01 15:15:34'];
        $second = ['name' => 'b', 'datetime' => '2020-08-01 18:00:01'];

        $writer->write($first, $this->dto());
        $writer->write($second, $this->dto());
        unset($writer);

        $this->assertSame(
            "{\"name\":\"a\",\"datetime\":\"2020-08-01 15:15:34\"}\n"
            . "{\"name\":\"b\",\"datetime\":\"2020-08-01 18:00:01\"}\n",
            file_get_contents($this->basePath . DIRECTORY_SEPARATOR . '20200801.ndjson')
        );
        $this->assertSame(['20200801.ndjson'], $this->fileNames());
    }

    public function testWriteWithCacheDisabledAppendsThroughANewHandle()
    {
        $writer = $this->writer(ArchiveDto::YEAR_MONTH_DAY);
        $writer->disableCache();
        $first = ['name' => 'a', 'datetime' => '2020-08-01 15:15:34'];
        $second = ['name' => 'b', 'datetime' => '2020-08-01 15:15:35'];

        $writer->write($first, $this->dto());
        $writer->write($second, $this->dto());
        unset($writer);

        $this->assertSame(
            "{\"name\":\"a\",\"datetime\":\"2020-08-01 15:15:34\"}\n"
            . "{\"name\":\"b\",\"datetime\":\"2020-08-01 15:15:35\"}\n",
            file_get_contents($this->basePath . DIRECTORY_SEPARATOR . '20200801.ndjson')
        );
        $this->assertSame(['20200801.ndjson'], $this->fileNames());
    }

    private function writer(int $mode): OutputWriter
    {
        $writer = new OutputWriter($this->basePath);
        $writer->setArchiveMode($mode);

        return $writer;
    }

    private function dto(): ArchiveDto
    {
        $dto = new ArchiveDto();
        $dto->stampColumnName = 'datetime';
        $dto->archiveMode = ArchiveDto::YEAR_MONTH_DAY;
        $dto->isTimestamp = false;

        return $dto;
    }

    /**
     * @return list<string>
     */
    private function fileNames(): array
    {
        $names = [];
        foreach (glob($this->basePath . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            $names[] = basename($file);
        }
        sort($names);

        return $names;
    }
}
