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

    private static array $settingMeta = [
        'site_description' => ['label' => 'Site beschrijving', 'group' => 'Site', 'description' => 'Korte beschrijving voor in de footer.'],
        'contact_address' => ['label' => 'Adres', 'group' => 'Contact', 'type' => 'textarea', 'description' => 'Adres in de footer. Gebruik een nieuwe lijn voor postcode/stad.'],
        'contact_phone' => ['label' => 'Telefoon', 'group' => 'Contact', 'description' => 'Telefoonnummer in de footer.'],
        'contact_email' => ['label' => 'E-mail', 'group' => 'Contact', 'description' => 'E-mailadres in de footer.'],
        'appointment_max_months' => ['label' => 'Max maanden vooruit boeken', 'group' => 'Afspraken', 'description' => 'Hoeveel maanden op voorhand klanten een afspraak kunnen boeken (standaard 24).'],
        'appointment_deposit_amount' => ['label' => 'Voorschotbedrag (€)', 'group' => 'Afspraken - Betaling', 'description' => 'Bedrag dat de klant moet betalen als voorschot (bijv. 50.00).'],
        'appointment_payment_deadline_days' => ['label' => 'Betaaltermijn (werkdagen)', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal werkdagen waarbinnen de klant moet betalen na boeking (standaard 3).'],
        'appointment_reminder_extra_days' => ['label' => 'Extra dagen na herinnering', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal dagen na de herinneringsmail voordat de afspraak wordt geannuleerd (standaard 2).'],
        'appointment_pre_reminder_days' => ['label' => 'Herinnering dagen voor afspraak', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal dagen voor de afspraak om een herinnering te sturen (standaard 3).'],
    ];

    public function getAllForSite(?int $siteId = null): array
    {
        if ($siteId !== null) {
            $rows = $this->query(
                "SELECT * FROM settings WHERE site_id = :site_id OR site_id IS NULL ORDER BY setting_key",
                ['site_id' => $siteId]
            )->fetchAll();
        } else {
            $rows = $this->query(
                "SELECT * FROM settings WHERE site_id IS NULL ORDER BY setting_key"
            )->fetchAll();
        }

        // Index existing rows by key
        $existingKeys = [];
        foreach ($rows as $row) {
            $existingKeys[$row['setting_key']] = true;
        }

        // Add all defined settings that don't exist in DB yet
        foreach (self::$settingMeta as $key => $meta) {
            if (!isset($existingKeys[$key])) {
                $rows[] = ['id' => 0, 'site_id' => $siteId, 'setting_key' => $key, 'setting_value' => ''];
            }
        }

        // Only show settings defined in $settingMeta
        $rows = array_filter($rows, fn($row) => isset(self::$settingMeta[$row['setting_key']]));

        // Enrich with labels and metadata
        foreach ($rows as &$row) {
            $key = $row['setting_key'];
            $meta = self::$settingMeta[$key];
            $row['key'] = $key;
            $row['value'] = $row['setting_value'];
            $row['label'] = $meta['label'] ?? $key;
            $row['group'] = $meta['group'] ?? 'Overig';
            $row['description'] = $meta['description'] ?? null;
            $row['type'] = $meta['type'] ?? null;
        }

        // Sort by group then label
        usort($rows, function($a, $b) {
            $groupOrder = ['Site' => 0, 'Contact' => 1, 'Afspraken' => 2, 'Afspraken - Betaling' => 3, 'Overig' => 9];
            $ga = $groupOrder[$a['group']] ?? 9;
            $gb = $groupOrder[$b['group']] ?? 9;
            return $ga === $gb ? strcmp($a['label'], $b['label']) : $ga - $gb;
        });

        return $rows;
    }
}
