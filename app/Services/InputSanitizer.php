<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class InputSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,i,u,em,strong,ul,ol,li,a[href],h1,h2,h3,h4,h5,h6,table,tr,td,th');
        $config->set('HTML.Attr.ForbiddenClasses', ['*']);
        $config->set('URI.DisableExternalResources', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(string $content): string
    {
        $sanitized = $this->purifier->purify($content);

        // Add rel="noopener noreferrer" to external links
        return preg_replace('/(<a\s+[^>]*href=["\']https?:\/\/[^>]*)/', '$1 rel="noopener noreferrer"', $sanitized);
    }
}
