<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Manager;

use Linkorb\TableArchiver\Manager\TableArchiverManager;
use Linkorb\TableArchiver\Tests\TestHelpers\DbSetupAwareTrait;
use PDO;
use PHPUnit\Framework\TestCase;

class TableArchiverManagerTest extends TestCase
{
    use DbSetupAwareTrait;

    private TableArchiverManager $manager;

    private PDO $pdo;

    public function setUp(): void
    {
        $this->pdo = $this->setUpDb();
        $this->manager = new TableArchiverManager(2);
    }

    public function test(): void
    {
        $this->manager->archive(
            $this->pdo,
            $this->getTableName(),
            TableArchiverManager::YEAR,
            $this->getTimestampName(),
            null
        );
        $this->assertTrue(true);
    }
}
