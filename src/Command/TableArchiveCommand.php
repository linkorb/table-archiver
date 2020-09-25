<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Command;

use Connector\Connector;
use Linkorb\TableArchiver\Manager\TableArchiver;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TableArchiveCommand extends Command
{
    protected static $defaultName = 'linkorb:table:arcive';

    private TableArchiver $archiver;

    private Connector $connector;

    public function __construct(TableArchiver $archiver, Connector $connector)
    {
        $this->archiver = $archiver;
        $this->connector = $connector;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->connector->getConfig($input->getArgument('dsn'));
        $pdo = $this->connector->getPdo($config);
    }
}
