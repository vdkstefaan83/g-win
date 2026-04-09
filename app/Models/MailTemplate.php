<?php

namespace App\Models;

use Core\Model;

class MailTemplate extends Model
{
    protected string $table = 'mail_templates';

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function getAllActive(): array
    {
        return $this->query(
            "SELECT * FROM mail_templates WHERE is_active = 1 ORDER BY name ASC"
        )->fetchAll();
    }

    /**
     * Render a mail template by slug with variable replacement.
     * Returns ['subject' => '...', 'body' => '...'] or false if not found.
     */
    public static function renderTemplate(string $slug, string $lang, array $variables = []): array|false
    {
        $model = new self();
        $template = $model->findBySlug($slug);
        if (!$template) return false;

        $subject = $lang === 'fr' && !empty($template['subject_fr'])
            ? $template['subject_fr']
            : $template['subject_nl'];

        $body = $lang === 'fr' && !empty($template['body_fr'])
            ? $template['body_fr']
            : $template['body_nl'];

        // Replace all {variable} placeholders
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', (string)$value, $subject);
            $body = str_replace('{' . $key . '}', (string)$value, $body);
        }

        // Clean up any unreplaced variables
        $subject = preg_replace('/\{[a-z_]+\}/', '', $subject);
        $body = preg_replace('/\{[a-z_]+\}/', '', $body);

        // SMS text
        $sms = $lang === 'fr' && !empty($template['sms_fr'])
            ? $template['sms_fr']
            : ($template['sms_nl'] ?? '');

        foreach ($variables as $key => $value) {
            $sms = str_replace('{' . $key . '}', (string)$value, $sms);
        }
        $sms = preg_replace('/\{[a-z_]+\}/', '', $sms);

        // Strip HTML tags from SMS (variables like {tijdstip_zin} may contain
        // <strong> for the e-mail body — SMS is plain text).
        $sms = html_entity_decode(strip_tags($sms), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Collapse whitespace introduced by stripped tags.
        $sms = trim(preg_replace('/[ \t]+/', ' ', $sms));

        return ['subject' => $subject, 'body' => $body, 'sms' => $sms];
    }
}
