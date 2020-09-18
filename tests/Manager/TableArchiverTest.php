<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Manager;

use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Factory\QueryFactory;
use Linkorb\TableArchiver\Manager\TableArchiver;
use Linkorb\TableArchiver\Services\Supervisor;
use Linkorb\TableArchiver\Tests\TestHelpers\DbSetupAwareTrait;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TableArchiverTest extends TestCase
{
    use DbSetupAwareTrait;

    private TableArchiver $manager;
    private PDO $pdo;

    /** @var MockObject|Supervisor */
    private MockObject $supervisor;

    public function setUp(): void
    {
        $this->pdo = $this->setUpDb();
        $this->supervisor = $this->createMock(Supervisor::class);
        $this->manager = $this->createPartialMock(TableArchiver::class, ['createPDO']);

        $this->manager->__construct(new QueryFactory(), $this->supervisor, 2);
    }

    public function testIncorrectDbConnection()
    {
        $statement = $this->createConfiguredMock(PDOStatement::class, ['fetch' => false]);
        $pdo = $this->createConfiguredMock(PDO::class, ['query' => $statement]);
        $this->manager->method('createPDO')->willReturn($pdo);
        $dto = new ArchiveDto();

        $this->expectExceptionMessage('Something is wrong with your PDO connection');

        $this->manager->archive($dto);
    }

    public function testArchiveTimestamp(): void
    {
        $this->manager->method('createPDO')->willReturn($this->pdo);

        $dto = new ArchiveDto();
        $dto->archiveMode = TableArchiver::YEAR;
        $dto->stampColumnName = $this->getTimestampName();
        $dto->tableName = $this->getTableName();

        $this->supervisor
            ->expects($this->exactly(4))
            ->method('spawn')
            ->withConsecutive(
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 0', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 2', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 4', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` OFFSET 6', $dto]],
            );

        $this->manager->archive($dto);
        $this->assertTrue($dto->isTimestamp);
    }

    public function testArchiveDateTime(): void
    {
        $this->manager->method('createPDO')->willReturn($this->pdo);

        $dto = new ArchiveDto();
        $dto->archiveMode = TableArchiver::YEAR_MONTH_DAY;
        $dto->stampColumnName = $this->getDateTimeName();
        $dto->tableName = $this->getTableName();

        $this->supervisor
            ->expects($this->exactly(4))
            ->method('spawn')
            ->withConsecutive(
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 0', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 2', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 4', $dto]],
                [['SELECT * FROM `' . $this->getTableName() . '` OFFSET 6', $dto]],
            );

        $this->manager->archive($dto);
        $this->assertFalse($dto->isTimestamp);
    }
}
