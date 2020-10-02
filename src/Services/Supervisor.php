<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Closure;
use Linkorb\TableArchiver\Dto\ArchiveDto;
use Linkorb\TableArchiver\Factory\ArchiverWorkerFactory;
use parallel\Channel;
use parallel\Future;
use parallel\Runtime;

class Supervisor
{
    /**
     * @var Future[]
     */
    private array $futures = [];

    private Closure $workerFactory;

    private Channel $channel;

    private Runtime $runtime;

    public function __construct(callable $workerFactory)
    {
        $this->workerFactory = Closure::fromCallable($workerFactory);
        $this->channel = new Channel();
        $this->runtime  = new Runtime(__DIR__ . '/../../vendor/autoload.php');
    }

    public function spawn(array $args): void
    {
        $this->futures[] = $this->runWorker($args);
    }

    public function waitForFinish(): int
    {
        $totalRows = 0;

        foreach ($this->futures as $future) {
            $totalRows += $this->channel->recv();
            $future->value();
        }

        $this->channel->close();
        $this->futures = [];

        return $totalRows;
    }

    private function runWorker(array $args): Future
    {
        $workerFactory = $this->workerFactory;

        return $this->runtime->run(
            function (string $query, ArchiveDto $dto, Channel $channel) use ($workerFactory) {
                return ($workerFactory->call(new ArchiverWorkerFactory()))($query, $dto, $channel);
            },
            [...$args, $this->channel]
        );
    }
}
