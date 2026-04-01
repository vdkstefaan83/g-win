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
        $selectedSiteId = $this->input('site_id');
        $siteId = $selectedSiteId ? (int)$selectedSiteId : null;

        $this->render('admin/settings/index.twig', [
            'settings' => $this->settingModel->getAllForSite($siteId),
            'sites' => $siteModel->findAll('name', 'ASC'),
            'selected_site_id' => $selectedSiteId,
        ]);
    }

    public function update(): void
    {
        $siteId = $this->input('site_id');
        $siteId = $siteId ? (int)$siteId : null;

        $settings = $this->input('settings');
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value, $siteId);
            }
        }

        Session::flash('success', 'Instellingen opgeslagen.');
        $this->redirect('/admin/settings' . ($siteId ? '?site_id=' . $siteId : ''));
    }
}
