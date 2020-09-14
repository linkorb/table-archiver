<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Closure;
use parallel\Future;
use parallel\Runtime;

class Supervisor
{
    /**
     * @var Future[]
     */
    private array $futures = [];

    private Closure $workerFactory;

    public function __construct(callable $workerFactory)
    {
        $this->workerFactory = Closure::fromCallable($workerFactory);
    }

    public function spawn(array $args): void
    {
        $this->futures[] = $this->runWorker($args);
    }

    public function waitForFinish(): void
    {
        while (count($this->futures) > 0) {
            foreach ($this->futures as $key => $future) {
                if ($future->done()) {
                    unset($this->futures[$key]);
                }
            }
        }
    }

    private function runWorker(array $args): Future
    {
        $future = new Runtime(__DIR__ . '/../../vendor/autoload.php');

        return $future->run(
            $this->workerFactory->call($this),
            $args
        );
    }
}
