<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Manager;

use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Factory\QueryFactory;
use Linkorb\TableArchiver\Manager\TableArchiver;
use Linkorb\TableArchiver\Services\OutputArchiver;
use Linkorb\TableArchiver\Services\Supervisor;
use Linkorb\TableArchiver\Tests\TestHelpers\DbSetupAwareTrait;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TableArchiverTest extends TestCase
{
    use DbSetupAwareTrait;

    private TableArchiver $manager;
    private PDO $pdo;
    private int $threadsNumber = 5;
    private QueryFactory $factory;

    /** @var MockObject|Supervisor */
    private MockObject $supervisor;
    /** @var MockObject|OutputArchiver */
    private MockObject $archiver;

    public function setUp(): void
    {
        $this->pdo = $this->setUpDb();
        $this->supervisor = $this->createMock(Supervisor::class);
        $this->archiver = $this->createMock(OutputArchiver::class);
        $this->factory = $this->createTestProxy(QueryFactory::class);
        $this->manager = new TableArchiver($this->factory, $this->supervisor, $this->archiver, $this->threadsNumber);
    }

    public function testArchiveTimestamp(): void
    {
        $dto = new ArchiveDto();
        $dto->archiveMode = ArchiveDto::YEAR;
        $dto->stampColumnName = $this->getTimestampName();
        $dto->tableName = $this->getTableName();

        $this->supervisor
            ->expects($this->exactly($this->threadsNumber))
            ->method('spawnProcessing')
            ->with([$dto]);

        $this->factory
            ->expects($this->once())
            ->method('buildFetchQuery');

        $this->factory
            ->expects($this->once())
            ->method('buildCountQuery');

        $this->supervisor->expects($this->exactly(count($this->getDbData())))->method('delegate');
        $this->supervisor->method('terminateThreads')->willReturn(count($this->getDbData()));

        $this->assertEquals(count($this->getDbData()), $this->manager->archive($this->pdo, $dto));
        $this->assertTrue($dto->isTimestamp);
    }

    public function testArchiveDateTime(): void
    {
        $dto = new ArchiveDto();
        $dto->archiveMode = ArchiveDto::YEAR_MONTH_DAY;
        $dto->stampColumnName = $this->getDateTimeName();
        $dto->tableName = $this->getTableName();

        $this->supervisor->method('terminateThreads')->willReturn(count($this->getDbData()));

        $this->manager->archive($this->pdo, $dto);
        $this->assertFalse($dto->isTimestamp);
    }

    public function testArchiveMaxTimestamp(): void
    {
        $dto = new ArchiveDto();
        $dto->archiveMode = ArchiveDto::YEAR_MONTH_DAY;
        $dto->stampColumnName = $this->getDateTimeName();
        $dto->tableName = $this->getTableName();
        list($dto->maxStamp, $count) = $this->getMaxTimestampData();

        $this->supervisor->method('terminateThreads')->willReturn($count);

        $this->assertEquals($count, $this->manager->archive($this->pdo, $dto));
    }

    public function testFlushArchived()
    {
        $dto = new ArchiveDto();
        $dto->archiveMode = ArchiveDto::YEAR_MONTH_DAY;
        $dto->stampColumnName = $this->getDateTimeName();
        $dto->tableName = $this->getTableName();

        $this->factory->expects($this->once())->method('buildDeleteQuery');
        $this->assertNull($this->manager->flushArchived($this->pdo, $dto));
    }

    public function testArchiveExportedFiles()
    {
        $this->archiver->expects($this->once())->method('archive');
        $this->assertNull($this->manager->archiveExportedFiles());
    }
}
