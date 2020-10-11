<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Connector\Connector;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use parallel\Channel;
use PDO;

class ArchiverWorker
{
    private OutputWriter $writer;
    private Connector $connector;

    public function __construct(OutputWriter $writer, Connector $connector)
    {
        $this->writer = $writer;
        $this->connector = $connector;
    }

    public function __invoke(string $query, ArchiveDto $dto, Channel $channel): void
    {
        $pdo = $this->connector->getPdo($this->connector->getConfig($dto->pdoDsn));

        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->writer->setArchiveMode($dto->archiveMode);

        $pdoStatement = $pdo->query($query);

        $rowsCount = 0;
        while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC)) {
            ++$rowsCount;
            $this->writer->write($row, $dto);
        }

        $channel->send($rowsCount);
    }
}
