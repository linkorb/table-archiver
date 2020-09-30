<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Services;

use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Services\ArchiverWorker;
use Linkorb\TableArchiver\Services\OutputWriter;
use Linkorb\TableArchiver\Tests\TestHelpers\DbSetupAwareTrait;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiverWorkerTest extends TestCase
{
    use DbSetupAwareTrait;

    /** @var OutputWriter|MockObject */
    private OutputWriter $writer;
    private PDO $pdo;
    private ArchiverWorker $worker;

    public function setUp(): void
    {
        $this->pdo = $this->setUpDb();
        $this->writer = $this->createPartialMock(OutputWriter::class, ['outputPath']);
        $this->writer->method('outputPath')->willReturn('php://memory');

        $this->worker = $this->createPartialMock(ArchiverWorker::class, ['createPDO']);
        $this->worker->__construct($this->writer);
        $this->worker->method('createPDO')->willReturn($this->pdo);
    }

    public function testInvokeDateTimeYearMonthDay()
    {
        $dto = new ArchiveDto();
        $dto->isTimestamp = false;
        $dto->tableName = $this->getTableName();
        $dto->stampColumnName = $this->getDateTimeName();
        $dto->archiveMode = ArchiveDto::YEAR_MONTH_DAY;

        $this->writer
            ->expects($this->exactly(2))
            ->method('outputPath')
            ->withConsecutive(['20200801.ndjson'], ['20001110.ndjson']);

        ($this->worker)('SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 0', $dto);
    }
}
