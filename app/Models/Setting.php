<?php

namespace App\Models;

use Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';

    public function get(string $key, ?int $siteId = null, mixed $default = null): mixed
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $params = ['key' => $key];

        if ($siteId !== null) {
            $sql .= " AND site_id = :site_id";
            $params['site_id'] = $siteId;
        } else {
            $sql .= " AND site_id IS NULL";
        }

        $result = $this->query($sql, $params)->fetch();
        return $result ? $result['setting_value'] : $default;
    }

    public function set(string $key, mixed $value, ?int $siteId = null): bool
    {
        $existing = $this->query(
            "SELECT id FROM settings WHERE setting_key = :key AND " . ($siteId ? "site_id = :site_id" : "site_id IS NULL"),
            $siteId ? ['key' => $key, 'site_id' => $siteId] : ['key' => $key]
        )->fetch();

        if ($existing) {
            return $this->update($existing['id'], ['setting_value' => $value]);
        }

        return $this->create([
            'setting_key' => $key,
            'setting_value' => $value,
            'site_id' => $siteId,
        ]) !== false;
    }

    public function getAllForSite(?int $siteId = null): array
    {
        if ($siteId !== null) {
            return $this->query(
                "SELECT * FROM settings WHERE site_id = :site_id OR site_id IS NULL ORDER BY setting_key",
                ['site_id' => $siteId]
            )->fetchAll();
        }

        return $this->query(
            "SELECT * FROM settings WHERE site_id IS NULL ORDER BY setting_key"
        )->fetchAll();
    }
}
