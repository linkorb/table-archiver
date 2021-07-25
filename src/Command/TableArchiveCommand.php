<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Command;

use Connector\Connector;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Manager\TableArchiver;
use Linkorb\TableArchiver\Services\OutputWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

final class TableArchiveCommand extends Command
{
    protected static $defaultName = 'linkorb:table:archive';

    private TableArchiver $archiver;

    private Connector $connector;

    private OutputWriter $writer;

    public function __construct(TableArchiver $archiver, Connector $connector, OutputWriter $writer)
    {
        $this->archiver = $archiver;
        $this->connector = $connector;
        $this->writer = $writer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('dsn', InputArgument::REQUIRED, 'PDO dsn for DB')
            ->addArgument('tableName', InputArgument::REQUIRED, 'Table name to archive')
            ->addArgument(
                'mode',
                InputArgument::REQUIRED,
                'Date range for which archived cluster will be created. Allowed values: YEAR, YEAR_MONTH, YEAR_MONTH_DAY'
            )
            ->addArgument(
                'columnName',
                InputArgument::REQUIRED,
                'Column which contains date information. It may be a date, datetime or int (unix timestamp) column, this is auto-detected'
            )
            ->addArgument(
                'maxStamp',
                InputArgument::OPTIONAL,
                'Archive records which older than specified date. Data newer than this date is not archived, and kept in the database'
            )
            ->addOption(
                'no-cache',
                null,
                InputOption::VALUE_NONE,
                'Disables caching of file resource descriptors. Could be used in case of memory limit exceeded / memory leakage. Affects performance'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $dto = $this->createDto($input, $output);
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return -1;
        }

        $pdo = $this->connector->getPdo($this->connector->getConfig($dto->pdoDsn));

        if ($input->getOption('no-cache')) {
            $this->writer->disableCache();
        }

        $pdo->beginTransaction();
        $count = $this->archiver->archive($pdo, $dto);
        $this->archiver->archiveExportedFiles();

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('All records archived. Do you want to proceed with removal? (y/N)', false);

        $output->writeln(sprintf('<fg=green>%d records have been processed</>', $count));
        if ($helper->ask($input, $output, $question)) {
            $this->archiver->flushArchived($pdo, $dto);
        }
        $pdo->commit();

        return 0;
    }

    private function createDto(InputInterface $input, OutputInterface $output): ArchiveDto
    {
        $archiveModeMap = [
            'YEAR' => ArchiveDto::YEAR,
            'YEAR_MONTH' => ArchiveDto::YEAR_MONTH,
            'YEAR_MONTH_DAY' => ArchiveDto::YEAR_MONTH_DAY,
        ];

        $dto = new ArchiveDto();
        $dto->pdoDsn = $input->getArgument('dsn');
        $dto->tableName = $input->getArgument('tableName');

        if (!isset($archiveModeMap[$input->getArgument('mode')])) {
            throw new InvalidArgumentException('This archive mode is not allowed');
        }

        $dto->archiveMode = $archiveModeMap[$input->getArgument('mode')];
        $dto->stampColumnName = $input->getArgument('columnName');

        $datetime = $input->getArgument('maxStamp') ?
            DateTimeImmutable::createFromFormat('Ymd', $input->getArgument('maxStamp')) : null;
        if ($datetime === false) {
            throw new InvalidArgumentException('Incorrect max stamp passed');
        }
        $dto->maxStamp = $datetime instanceof DateTimeInterface ?
            $datetime->setTime(23, 59, 59, 999999)->format('Y-m-d H:i:s') :
            $datetime;

        return $dto;
    }
}
