<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Closure;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use parallel\Channel;
use parallel\Future;
use parallel\Runtime;

class Supervisor
{
    /**
     * @var Future[]
     */
    private array $processingFutures = [];

    private Closure $processingWorkerFactory;

    private Channel $channel;

    private Runtime $runtime;

    public function __construct(callable $processingWorkerFactory, int $threadsNumber)
    {
        $this->processingWorkerFactory = Closure::fromCallable($processingWorkerFactory);
        $this->channel = Channel::make('data_transfer', $threadsNumber);
        $this->runtime = new Runtime(__DIR__ . '/../../vendor/autoload.php');
    }

    public function spawnProcessing(array $args): void
    {
        $this->processingFutures[] = $this->runProcessingWorker($args);
    }

    public function delegate(array $row): void
    {
        $this->channel->send($row);
    }

    public function terminateThreads(): int
    {
        $this->channel->close();

        $processedRows = 0;

        foreach ($this->processingFutures as $future) {
            $processedRows += $future->value();
        }

        return $processedRows;
    }

    private function runProcessingWorker(array $args): Future
    {
        $workerFactory = $this->processingWorkerFactory;

        return $this->runtime->run(
            function (ArchiveDto $dto, Channel $channel) use ($workerFactory) {
                return ($workerFactory())($dto, $channel);
            },
            [...$args, $this->channel]
        );
    }
}
