<?php

namespace Core\Helpers;

use App\Models\Site;

class SiteResolver
{
    private static ?string $domainDefaultLang = null;

    public static function resolve(string $host): array
    {
        // Try database lookup via site_domains table
        try {
            $siteModel = new Site();
            $site = $siteModel->findByLinkedDomain($host);

            if ($site) {
                self::$domainDefaultLang = $site['domain_default_lang'] ?? 'nl';
                return [
                    'slug' => $site['slug'],
                    'name' => $site['name'],
                    'layout' => $site['layout'],
                ];
            }
        } catch (\Throwable $e) {
            // DB not available yet (e.g. during migration), fall through to config
        }

        // Fallback to static config file
        return self::resolveFromConfig($host);
    }

    private static function resolveFromConfig(string $host): array
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

    /**
     * Get the default language for the resolved domain, or null if not set via DB.
     */
    public static function getDomainDefaultLang(): ?string
    {
        return self::$domainDefaultLang;
    }
}
