<?php

declare(strict_types=1);

namespace Linkorb\TableArchiver\Services;

use Exception;

class OutputArchiver
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function archive(): void
    {
        if (!is_dir($this->basePath) || !($dh = opendir($this->basePath))) {
            throw new Exception('Base path isn\'t a dir');
        }

        while (($file = readdir($dh)) !== false) {
            $filePath = $this->basePath . DIRECTORY_SEPARATOR . $file;

            if (!is_file($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'ndjson') {
                continue;
            }

            $this->gzCompressFile($filePath);
            unlink($filePath);
        }

        closedir($dh);
    }

    // TODO: consider moving compression to threads
    private function gzCompressFile(string $source, $level = 9): void
    {
        $dest = $source . '.gz';
        $mode = 'wb' . $level;
        if (!$fpOut = gzopen($dest, $mode)) {
            throw new Exception('Unable to open archive');
        }

        if (!$fpIn = fopen($source, 'rb')) {
            throw new Exception('Base path hasn\'t been accessible for read');
        }

        while (!feof($fpIn)) {
            gzwrite($fpOut, fread($fpIn, 1024 * 512));
        }
        fclose($fpIn);

        gzclose($fpOut);
    }
}
