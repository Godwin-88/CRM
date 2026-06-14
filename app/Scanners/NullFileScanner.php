<?php

namespace App\Scanners;

use App\Contracts\FileUploadScanner;

class NullFileScanner implements FileUploadScanner
{
    public function scan(string $filePath): bool
    {
        // No scanner configured - allow upload
        return true;
    }

    public function scanStream(string $stream): bool
    {
        return true;
    }
}
