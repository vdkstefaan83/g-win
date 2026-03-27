<?php

namespace App\Models;

use Core\Model;

class Site extends Model
{
    protected string $table = 'sites';

    public function getPages(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM pages WHERE site_id = :site_id ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getMenus(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM menus WHERE site_id = :site_id",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function findByDomain(string $domain): array|false
    {
        return $this->findBy('domain', $domain);
    }

    public function findFirst(): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM sites WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Find a site by one of its linked domains (site_domains table).
     * Returns site data + domain-specific default_lang.
     */
    public function findByLinkedDomain(string $domain): array|false
    {
        // Strip www and port
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = explode(':', $domain)[0];

        $stmt = $this->db->prepare(
            "SELECT s.*, sd.default_lang AS domain_default_lang, sd.is_primary AS domain_is_primary
             FROM site_domains sd
             INNER JOIN sites s ON s.id = sd.site_id
             WHERE (sd.domain = :domain OR sd.domain = :domain_www)
             AND s.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([
            'domain' => $domain,
            'domain_www' => 'www.' . $domain,
        ]);
        return $stmt->fetch();
    }

    /**
     * Get all domains for a site.
     */
    public function getDomains(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM site_domains WHERE site_id = :site_id ORDER BY is_primary DESC, domain ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    /**
     * Sync domains for a site.
     * $domains = [['domain' => 'example.com', 'default_lang' => 'nl', 'is_primary' => 1], ...]
     */
    public function syncDomains(int $siteId, array $domains): void
    {
        $this->query("DELETE FROM site_domains WHERE site_id = :id", ['id' => $siteId]);

        foreach ($domains as $d) {
            $domain = trim($d['domain'] ?? '');
            if ($domain === '') continue;

            $this->query(
                "INSERT INTO site_domains (site_id, domain, default_lang, is_primary) VALUES (:site_id, :domain, :default_lang, :is_primary)",
                [
                    'site_id' => $siteId,
                    'domain' => $domain,
                    'default_lang' => $d['default_lang'] ?? 'nl',
                    'is_primary' => (int)($d['is_primary'] ?? 0),
                ]
            );
        }

        // Update the legacy domain field on sites table with the primary domain
        $primary = null;
        foreach ($domains as $d) {
            if (!empty($d['is_primary']) && trim($d['domain'] ?? '') !== '') {
                $primary = trim($d['domain']);
                break;
            }
        }
        if (!$primary && !empty($domains)) {
            $primary = trim($domains[0]['domain'] ?? '');
        }
        if ($primary) {
            $this->update($siteId, ['domain' => $primary]);
        }
    }
}
