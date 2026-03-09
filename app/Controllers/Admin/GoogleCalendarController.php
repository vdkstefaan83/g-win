<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Services\GoogleCalendarService;
use App\Models\Setting;

class GoogleCalendarController extends Controller
{
    public function index(): void
    {
        $settingModel = new Setting();
        $isConnected = !empty($settingModel->get('google_refresh_token'));

        $this->render('admin/google-calendar/index.twig', [
            'is_connected' => $isConnected,
        ]);
    }

    public function authorize(): void
    {
        $service = new GoogleCalendarService();
        $authUrl = $service->getAuthUrl();
        $this->redirect($authUrl);
    }

    public function callback(): void
    {
        $code = $this->input('code');
        if (!$code) {
            Session::flash('error', 'Autorisatie mislukt.');
            $this->redirect('/admin/google-calendar');
        }

        $service = new GoogleCalendarService();
        if ($service->handleCallback($code)) {
            Session::flash('success', 'Google Calendar gekoppeld.');
        } else {
            Session::flash('error', 'Kon Google Calendar niet koppelen.');
        }

        $this->redirect('/admin/google-calendar');
    }

    public function disconnect(): void
    {
        $settingModel = new Setting();
        $settingModel->set('google_refresh_token', '');
        $settingModel->set('google_access_token', '');

        Session::flash('success', 'Google Calendar ontkoppeld.');
        $this->redirect('/admin/google-calendar');
    }
}
