<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Factory;

use Linkorb\TableArchiver\Services\ArchiverWorker;
use Linkorb\TableArchiver\Services\OutputWriter;

class ArchiverWorkerFactory
{
    public function createFactoryMethod(OutputWriter $writer): callable
    {
        return function () use ($writer) {
            return new ArchiverWorker($writer);
        };
    }
}
