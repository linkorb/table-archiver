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

    /**
     * @var MockObject|Supervisor
     */
    private MockObject $supervisor;

    public function setUp(): void
    {
        $this->pdo = $this->setUpDb();
        $this->supervisor = $this->createMock(Supervisor::class);
        $this->manager = new TableArchiver(new QueryFactory(), $this->supervisor, 2);
    }

    public function testIncorrectDbConnection()
    {
        $statement = $this->createConfiguredMock(PDOStatement::class, ['fetch' => false]);
        $pdo = $this->createConfiguredMock(PDO::class, ['query' => $statement]);
        $dto = new ArchiveDto();

        $this->expectExceptionMessage('Something is wrong with your PDO connection');

        $this->manager->archive($pdo, $dto);
    }

    public function testAcrchive(): void
    {
        $dto = new ArchiveDto();
        $dto->archiveMode = TableArchiver::YEAR;
        $dto->stampColumnName = $this->getTimestampName();
        $dto->tableName = $this->getTableName();

        $this->supervisor
            ->expects($this->exactly(4))
            ->method('spawn')
            ->withConsecutive(
                [[$this->pdo, 'SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 0', $dto]],
                [[$this->pdo, 'SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 2', $dto]],
                [[$this->pdo, 'SELECT * FROM `' . $this->getTableName() . '` LIMIT 2 OFFSET 4', $dto]],
                [[$this->pdo, 'SELECT * FROM `' . $this->getTableName() . '` OFFSET 6', $dto]],
            );

        $this->manager->archive($this->pdo, $dto);
    }
}
