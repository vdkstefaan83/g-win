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
        'company_name' => ['label' => 'Bedrijfsnaam', 'group' => 'Bedrijf'],
        'company_tagline' => ['label' => 'Tagline', 'group' => 'Bedrijf'],
        'company_owner' => ['label' => 'Eigenaar', 'group' => 'Bedrijf'],
        'company_address' => ['label' => 'Adres', 'group' => 'Bedrijf'],
        'company_city' => ['label' => 'Stad', 'group' => 'Bedrijf'],
        'company_phone' => ['label' => 'Telefoon', 'group' => 'Bedrijf'],
        'company_mobile' => ['label' => 'GSM', 'group' => 'Bedrijf'],
        'company_email' => ['label' => 'E-mail', 'group' => 'Bedrijf'],
        'company_vat' => ['label' => 'BTW-nummer', 'group' => 'Bedrijf'],
        'social_facebook' => ['label' => 'Facebook URL', 'group' => 'Sociaal'],
        'social_linkedin' => ['label' => 'LinkedIn URL', 'group' => 'Sociaal'],
        'social_sketchfab' => ['label' => 'Sketchfab URL', 'group' => 'Sociaal'],
        'appointment_max_months' => ['label' => 'Max maanden vooruit boeken', 'group' => 'Afspraken', 'description' => 'Hoeveel maanden op voorhand klanten een afspraak kunnen boeken (standaard 24).'],
        'blocked_dates' => ['label' => 'Geblokkeerde datums (JSON)', 'group' => 'Afspraken', 'type' => 'textarea', 'description' => 'JSON array van datums, bijv. ["2026-04-01","2026-04-02"]'],
        'appointment_deposit_amount' => ['label' => 'Voorschotbedrag (€)', 'group' => 'Afspraken - Betaling', 'description' => 'Bedrag dat de klant moet betalen als voorschot (bijv. 50.00).'],
        'appointment_payment_deadline_days' => ['label' => 'Betaaltermijn (werkdagen)', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal werkdagen waarbinnen de klant moet betalen na boeking (standaard 3).'],
        'appointment_reminder_extra_days' => ['label' => 'Extra dagen na herinnering', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal dagen na de herinneringsmail voordat de afspraak wordt geannuleerd (standaard 2).'],
        'appointment_pre_reminder_days' => ['label' => 'Herinnering dagen voor afspraak', 'group' => 'Afspraken - Betaling', 'description' => 'Aantal dagen voor de afspraak om een herinnering te sturen (standaard 3).'],
        'clicksend_api_username' => ['label' => 'ClickSend gebruikersnaam', 'group' => 'SMS'],
        'clicksend_api_key' => ['label' => 'ClickSend API key', 'group' => 'SMS'],
        'sms_sender_name' => ['label' => 'SMS afzendernaam', 'group' => 'SMS', 'description' => 'Max 11 tekens (bijv. G-Win).'],
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

        // Enrich with labels and metadata
        foreach ($rows as &$row) {
            $key = $row['setting_key'];
            $meta = self::$settingMeta[$key] ?? [];
            $row['key'] = $key;
            $row['value'] = $row['setting_value'];
            $row['label'] = $meta['label'] ?? $key;
            $row['group'] = $meta['group'] ?? 'Overig';
            $row['description'] = $meta['description'] ?? null;
            $row['type'] = $meta['type'] ?? null;
        }

        // Sort by group then label
        usort($rows, function($a, $b) {
            $groupOrder = ['Bedrijf' => 1, 'Sociaal' => 2, 'Afspraken' => 3, 'Afspraken - Betaling' => 4, 'SMS' => 5, 'Overig' => 9];
            $ga = $groupOrder[$a['group']] ?? 9;
            $gb = $groupOrder[$b['group']] ?? 9;
            return $ga === $gb ? strcmp($a['label'], $b['label']) : $ga - $gb;
        });

        return $rows;
    }
}
