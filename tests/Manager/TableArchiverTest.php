<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Manager;

use Linkorb\TableArchiver\Factory\QueryFactory;
use Linkorb\TableArchiver\Manager\TableArchiver;
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

    public function test(): void
    {
        $this->manager->archive(
            $this->pdo,
            $this->getTableName(),
            TableArchiver::YEAR,
            $this->getTimestampName(),
            null
        );
        $this->assertTrue(true);
    }
}
