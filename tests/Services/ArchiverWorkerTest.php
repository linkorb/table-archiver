<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Services;

use DateTime;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Services\ArchiverWorker;
use Linkorb\TableArchiver\Services\OutputWriter;
use Linkorb\TableArchiver\Tests\TestHelpers\DbSetupAwareTrait;
use parallel\Channel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiverWorkerTest extends TestCase
{
    use DbSetupAwareTrait;

    /** @var OutputWriter|MockObject */
    private OutputWriter $writer;
    private ArchiverWorker $worker;

    public function setUp(): void
    {
        $this->writer = $this->createPartialMock(OutputWriter::class, ['outputPath']);
        $this->writer->method('outputPath')->willReturn('php://memory');

        $this->worker = new ArchiverWorker($this->writer);
    }

    public function testInvokeDateTimeYearMonthDay()
    {
        $channel = new Channel(10);

        $channel->send(['name' => 'a', $this->getDateTimeName() => '2020-08-01 15:15:34']);
        $channel->send(['name' => 'b', $this->getDateTimeName() => '2000-11-10 12:34:56']);
        $channel->close();

        $this->writer
            ->expects($this->exactly(2))
            ->method('outputPath')
            ->withConsecutive(['20200801.ndjson'], ['20001110.ndjson']);

        $this->assertEquals(2, ($this->worker)($this->getDto(ArchiveDto::YEAR_MONTH_DAY), $channel));
    }

    public function testInvokeDateTimeYearMonth()
    {
        $channel = new Channel(10);

        $channel->send(['name' => 'a', $this->getDateTimeName() => '2000-01-01 11:15:45']);
        $channel->send(['name' => 'b', $this->getDateTimeName() => '2000-11-10 13:34:37']);
        $channel->send(['name' => 'c', $this->getDateTimeName() => '2000-01-10 23:58:58']);
        $channel->close();

        $this->writer
            ->expects($this->exactly(2))
            ->method('outputPath')
            ->withConsecutive(['200001.ndjson'], ['200011.ndjson']);

        $this->assertEquals(3, ($this->worker)($this->getDto(ArchiveDto::YEAR_MONTH), $channel));
    }

    public function testInvokeDateTimeYear()
    {
        $channel = new Channel(10);

        $channel->send([$this->getDateTimeName() => '2000-01-01 00:00:00']);
        $channel->close();

        $this->writer->expects($this->exactly(1))->method('outputPath')->with('2000.ndjson');

        $this->assertEquals(1, ($this->worker)($this->getDto(ArchiveDto::YEAR), $channel));
    }

    public function testInvokeCacheDisabled()
    {
        $channel = new Channel(10);

        $this->writer->disableCache();

        $channel->send([$this->getDateTimeName() => '2020-08-01 15:15:34']);
        $channel->send([$this->getDateTimeName() => '2020-08-01 15:15:35']);
        $channel->close();

        $this->writer
            ->expects($this->exactly(2))
            ->method('outputPath')
            ->withConsecutive(['20200801.ndjson'], ['20200801.ndjson']);

        $this->assertEquals(2, ($this->worker)($this->getDto(ArchiveDto::YEAR_MONTH_DAY), $channel));
    }

    public function testInvokeTimestamp()
    {
        $channel = new Channel(10);

        $channel->send([$this->getTimestampName() => (new DateTime('1999-11-13 00:01:22'))->getTimestamp()]);
        $channel->send([$this->getTimestampName() => (new DateTime('1970-12-12 13:11:06'))->getTimestamp()]);
        $channel->send([$this->getTimestampName() => (new DateTime('2002-07-08 17:09:00'))->getTimestamp()]);
        $channel->close();

        $this->writer
            ->expects($this->exactly(3))
            ->method('outputPath')
            ->withConsecutive(['19991113.ndjson'], ['19701212.ndjson'], ['20020708.ndjson']);

        $this->assertEquals(3, ($this->worker)($this->getDto(ArchiveDto::YEAR_MONTH_DAY, true), $channel));
    }

    private function getDto(int $mode, bool $timestamp = false): ArchiveDto
    {
        $dto = new ArchiveDto();
        $dto->isTimestamp = $timestamp;
        $dto->tableName = $this->getTableName();
        $dto->stampColumnName = $timestamp ? $this->getTimestampName() : $this->getDateTimeName();
        $dto->archiveMode = $mode;

        return $dto;
    }
}
