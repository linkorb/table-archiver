<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Linkorb\TableArchiver\Dto\ArchiveDto;
use parallel\Channel;
use parallel\Channel\Error\Closed;

class ArchiverWorker
{
    private OutputWriter $writer;

    public function __construct(OutputWriter $writer)
    {
        $this->writer = $writer;
    }

    public function __invoke(ArchiveDto $dto, Channel $channel): int
    {
        $rowsProcessed = 0;

        $this->writer->setArchiveMode($dto->archiveMode);

        try {
            while ($row = $channel->recv()) {
                $this->writer->write($row, $dto);
                $rowsProcessed++;
            }
        } catch (Closed $e) {
            // channel is closed worker should be stopped
            return $rowsProcessed;
        }

        return $rowsProcessed;
    }
}
