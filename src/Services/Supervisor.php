<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Closure;
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

    public function __construct(callable $workerFactory)
    {
        $this->workerFactory = Closure::fromCallable($workerFactory);
        $this->channel = new Channel();
    }

    public function spawn(array $args): void
    {
        $this->futures[] = $this->runWorker($args);
    }

    public function waitForFinish(): int
    {
        $totalRows = 0;

        while (count($this->futures) > 0) {
            foreach ($this->futures as $key => $future) {
                if ($future->done()) {
                    $totalRows += $this->channel->recv();
                    unset($this->futures[$key]);
                }
            }
        }

        $this->channel->close();

        return $totalRows;
    }

    private function runWorker(array $args): Future
    {
        $future = new Runtime(__DIR__ . '/../../vendor/autoload.php');

        return $future->run(Closure::fromCallable($this->workerFactory->call($this)), [...$args, $this->channel]);
    }
}
