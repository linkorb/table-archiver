<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use PDO;

class ArchiverWorker
{
    public function __call(PDO $pdo, string $query, int $archiveMode): void
    {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }
}
