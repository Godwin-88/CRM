<?php

namespace App\Helpers;

class AssistantPromptVersioning
{
    private static function versionsPath(): string
    {
        return resource_path('prompts/assistant/versions.json');
    }

    public static function current(): array
    {
        $data = [];
        $path = self::versionsPath();
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true) ?: [];
        }
        return $data;
    }

    public static function canary(float $percentage = 10.0): array
    {
        $versions = self::current();
        if (empty($versions)) {
            return ['version' => 'v1', 'label' => 'v1'];
        }
        $latest = $versions[0];
        return [
            'version' => $latest['version'] ?? 'v1',
            'label' => $latest['label'] ?? 'v1',
            'canary_percentage' => $percentage,
        ];
    }
}
