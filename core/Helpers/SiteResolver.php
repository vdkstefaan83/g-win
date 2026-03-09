<?php

namespace Core\Helpers;

class SiteResolver
{
    public static function resolve(string $host): array
    {
        $sites = require dirname(__DIR__, 2) . '/app/Config/sites.php';

        // Try exact match first
        if (isset($sites[$host])) {
            return $sites[$host];
        }

        // Try without www
        $hostWithoutWww = preg_replace('/^www\./', '', $host);
        if (isset($sites[$hostWithoutWww])) {
            return $sites[$hostWithoutWww];
        }

        // Try with port stripped
        $hostNoPort = explode(':', $host)[0];
        if (isset($sites[$hostNoPort])) {
            return $sites[$hostNoPort];
        }

        // Fallback to default
        $default = $_ENV['SITE_DEFAULT'] ?? 'gwin';
        foreach ($sites as $siteHost => $config) {
            if ($config['slug'] === $default) {
                return $config;
            }
        }

        // Last resort
        return reset($sites) ?: ['slug' => 'gwin', 'name' => 'G-Win', 'layout' => 'gwin'];
    }
}
