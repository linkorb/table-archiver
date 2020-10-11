<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Command;

use Connector\Connector;
use DateTimeImmutable;
use DateTimeInterface;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Manager\TableArchiver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class TableArchiveCommand extends Command
{
    protected static $defaultName = 'linkorb:table:archive';

    private TableArchiver $archiver;

    private Connector $connector;

    public function __construct(TableArchiver $archiver, Connector $connector)
    {
        $this->archiver = $archiver;
        $this->connector = $connector;

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dto = $this->createDto($input, $output);

        $pdo = $this->connector->getPdo($this->connector->getConfig($dto->pdoDsn));

        $count = $this->archiver->archive($pdo, $dto);
        $this->archiver->archiveExportedFiles();

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('All records archived. Do you want to proceed with removal? (y/N)', false);

        $output->writeln(sprintf('<fg=green>%d records have been processed</>', $count));
        if ($helper->ask($input, $output, $question)) {
            $this->archiver->flushArchived($pdo, $dto, $count);
        }

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
            $output->write('<error>This archive mode is not allowed</error>');
            return -1;
        }

        $dto->archiveMode = $archiveModeMap[$input->getArgument('mode')];
        $dto->stampColumnName = $input->getArgument('columnName');

        $datetime = $input->getArgument('maxStamp') ?
            DateTimeImmutable::createFromFormat('Ymd', $input->getArgument('maxStamp')) : null;
        if ($datetime === false) {
            $output->write('<error>Incorrect max stamp passed</error>');
            return -1;
        }
        $dto->maxStamp = $datetime instanceof DateTimeInterface ?
            $datetime->setTime(23, 59, 59, 999999)->format('Y-m-d H:i:s') :
            $datetime;

        return $dto;
    }
}
