<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Setting;
use App\Models\Site;

class SettingController extends Controller
{
    private Setting $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new Setting();
    }

    public function index(): void
    {
        $siteModel = new Site();

        $this->render('admin/settings/index.twig', [
            'settings' => $this->settingModel->getAllForSite(),
            'sites' => $siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(): void
    {
        $settings = $this->input('settings');
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
            }
        }

        Session::flash('success', 'Instellingen opgeslagen.');
        $this->redirect('/admin/settings');
    }
}
