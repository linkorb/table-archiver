<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Tests\Command;

use Connector\Connector;
use InvalidArgumentException;
use Linkorb\TableArchiver\Command\TableArchiveCommand;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Manager\TableArchiver;
use Linkorb\TableArchiver\Services\OutputWriter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class TableArchiveCommandCreateDtoTest extends TestCase
{
    private int $reporting;

    public function setUp(): void
    {
        // Symfony 5.1 and PHPUnit 9 emit PHP 8.5 signature deprecations as these
        // classes load. With the bridge helper off, PHPUnit turns those into
        // errors. The mask keeps the pin on createDto's own result.
        $this->reporting = error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    public function tearDown(): void
    {
        error_reporting($this->reporting);
    }

    public function testUnknownArchiveModeIsRejected(): void
    {
        try {
            $this->createDto('WEEK', null);
            $this->fail('An unknown archive mode must be rejected');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('This archive mode is not allowed', $exception->getMessage());
        }
    }

    public function testOmittedMaxStampStaysNull(): void
    {
        $dto = $this->createDto('YEAR', null);

        $this->assertSame('mysql:host=db;dbname=app', $dto->pdoDsn);
        $this->assertSame('audit_log', $dto->tableName);
        $this->assertSame(ArchiveDto::YEAR, $dto->archiveMode);
        $this->assertSame('created_at', $dto->stampColumnName);
        $this->assertFalse($dto->isTimestamp);
        $this->assertNull($dto->maxStamp);
    }

    public function testUnparseableMaxStampIsRejected(): void
    {
        try {
            $this->createDto('YEAR', 'not-a-date');
            $this->fail('An unparseable max stamp must be rejected');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Incorrect max stamp passed', $exception->getMessage());
        }
    }

    public function testParsedMaxStampIsEndOfThatDay(): void
    {
        $dto = $this->createDto('YEAR', '20200115');

        $this->assertSame(ArchiveDto::YEAR, $dto->archiveMode);
        $this->assertSame('created_at', $dto->stampColumnName);
        $this->assertSame('2020-01-15 23:59:59', $dto->maxStamp);
    }

    private function createDto(string $mode, ?string $maxStamp): ArchiveDto
    {
        $command = new TableArchiveCommand(
            $this->createMock(TableArchiver::class),
            $this->createMock(Connector::class),
            $this->createMock(OutputWriter::class)
        );

        $arguments = [
            'dsn' => 'mysql:host=db;dbname=app',
            'tableName' => 'audit_log',
            'mode' => $mode,
            'columnName' => 'created_at',
        ];
        if ($maxStamp !== null) {
            $arguments['maxStamp'] = $maxStamp;
        }

        $method = new ReflectionMethod(TableArchiveCommand::class, 'createDto');

        return $method->invoke($command, new ArrayInput($arguments, $command->getDefinition()), new NullOutput());
    }
}
