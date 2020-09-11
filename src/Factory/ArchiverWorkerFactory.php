<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Factory;

use Linkorb\TableArchiver\Services\ArchiverWorker;

class ArchiverWorkerFactory
{
    public function createFactoryMethod(): callable
    {
        return function () {
            return new ArchiverWorker();
        };
    }
}
