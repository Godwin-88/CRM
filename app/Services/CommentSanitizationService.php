<?php

namespace App\Services;

class CommentSanitizationService
{
    public function __construct(
        protected InputSanitizer $sanitizer
    ) {}

    public function clean(string $body): string
    {
        return $this->sanitizer->sanitize($body);
    }

    public function extractMentions(string $body): array
    {
        preg_match_all('/@\[([^\]]+)\]\(([^)]+)\)/', $body, $matches);

        return array_map('intval', $matches[2] ?? []);
    }
}