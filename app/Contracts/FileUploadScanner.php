<?php

namespace App\Contracts;

interface FileUploadScanner
{
    public function scan(string $filePath): bool;

    public function scanStream(string $stream): bool;
}
