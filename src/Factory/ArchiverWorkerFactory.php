<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Factory;

use Connector\Connector;
use Linkorb\TableArchiver\Services\ArchiverWorker;
use Linkorb\TableArchiver\Services\OutputWriter;

class ArchiverWorkerFactory
{
    public function createFactoryMethod(OutputWriter $writer, Connector $connector): callable
    {
        return function () use ($writer, $connector) {
            return new ArchiverWorker($writer, $connector);
        };
    }
}
