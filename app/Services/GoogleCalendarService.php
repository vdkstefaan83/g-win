<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use App\Models\Setting;

class GoogleCalendarService
{
    private Client $client;
    private Setting $settingModel;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
        $this->client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
        $this->client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->settingModel = new Setting();
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): bool
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) return false;

            $this->settingModel->set('google_access_token', json_encode($token));
            if (isset($token['refresh_token'])) {
                $this->settingModel->set('google_refresh_token', $token['refresh_token']);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Google Calendar auth failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getAuthorizedClient(): ?Client
    {
        $accessToken = $this->settingModel->get('google_access_token');
        if (!$accessToken) return null;

        $this->client->setAccessToken(json_decode($accessToken, true));

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->settingModel->get('google_refresh_token');
            if (!$refreshToken) return null;

            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            $this->settingModel->set('google_access_token', json_encode($this->client->getAccessToken()));
        }

        return $this->client;
    }

    public function createEvent(array $appointment, array $customer): ?string
    {
        $client = $this->getAuthorizedClient();
        if (!$client) return null;

        try {
            $calendar = new Calendar($client);
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';

            $event = new Event([
                'summary' => "{$typeLabel} - {$customer['first_name']} {$customer['last_name']}",
                'description' => "Klant: {$customer['first_name']} {$customer['last_name']}\nEmail: {$customer['email']}\nTel: {$customer['phone']}\n\n{$appointment['notes']}",
                'start' => [
                    'dateTime' => $appointment['date'] . 'T' . $appointment['start_time'],
                    'timeZone' => 'Europe/Brussels',
                ],
                'end' => [
                    'dateTime' => $appointment['date'] . 'T' . $appointment['end_time'],
                    'timeZone' => 'Europe/Brussels',
                ],
            ]);

            $createdEvent = $calendar->events->insert('primary', $event);
            return $createdEvent->getId();
        } catch (\Exception $e) {
            error_log('Google Calendar create event failed: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteEvent(string $eventId): bool
    {
        $client = $this->getAuthorizedClient();
        if (!$client) return false;

        try {
            $calendar = new Calendar($client);
            $calendar->events->delete('primary', $eventId);
            return true;
        } catch (\Exception $e) {
            error_log('Google Calendar delete event failed: ' . $e->getMessage());
            return false;
        }
    }
}
