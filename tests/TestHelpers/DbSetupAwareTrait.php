<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\TestHelpers;

use DateTime;
use PDO;

trait DbSetupAwareTrait
{
    protected function getDbData(): array
    {
        return [
            ['a', new DateTime('2020-08-01 15:15:34')],
            ['b', new DateTime('2000-11-10 13:10:00')],
            ['c', new DateTime('2019-11-25 15:00:00')],
            ['d', new DateTime('1999-01-01 00:13:56')],
            ['e', new DateTime('2020-08-02 11:13:37')],
            ['f', new DateTime('2020-08-03 09:57:34')],
            ['g', new DateTime('2019-08-03 09:57:34')],
        ];
    }

    protected function getTableName(): string
    {
        return 'target_table';
    }

    protected function getTimestampName(): string
    {
        return 'timestamp';
    }

    private function setUpDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(
            sprintf(
                'CREATE table %s(
             id INTEGER PRIMARY KEY AUTOINCREMENT,
             test VARCHAR( 255 ) NOT NULL, 
             `%s` TIMESTAMP NOT NULL)',
                $this->getTableName(),
                $this->getTimestampName()
            )
        );

        $values = implode(
            ',',
            array_map(
                function (array $dbRow): string {
                    /** @var DateTime $dateTime */
                    [$test, $dateTime] = $dbRow;
                    return sprintf('(\'%s\', \'%s\')', $test, $dateTime->format('Y-m-d H:i:s'));
                },
                $this->getDbData()
            )
        );

        $pdo->exec(
            sprintf(
                'INSERT INTO %s (test, `%s`) VALUES %s;',
                $this->getTableName(),
                $this->getTimestampName(),
                $values
            )
        );

        return $pdo;
    }
}
