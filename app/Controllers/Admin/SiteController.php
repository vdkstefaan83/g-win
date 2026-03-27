<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Site;

class SiteController extends Controller
{
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $sites = $this->siteModel->findAll('name', 'ASC');

        // Attach domains to each site
        foreach ($sites as &$site) {
            $site['domains'] = $this->siteModel->getDomains($site['id']);
        }

        $this->render('admin/sites/index.twig', [
            'sites' => $sites,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/sites/create.twig');
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'slug' => 'required|max:50',
            'layout' => 'required|max:50',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/sites/create');
            return;
        }

        // Get primary domain from the domains list for the legacy field
        $domainNames = $_POST['domains'] ?? [];
        $domainLangs = $_POST['domain_langs'] ?? [];
        $domainPrimary = $_POST['domain_primary'] ?? '0';
        $primaryDomain = '';

        $domains = [];
        foreach ($domainNames as $i => $d) {
            $d = trim($d);
            if ($d === '') continue;
            $isPrimary = ((int)$domainPrimary === $i);
            if ($isPrimary) $primaryDomain = $d;
            $domains[] = [
                'domain' => $d,
                'default_lang' => $domainLangs[$i] ?? 'nl',
                'is_primary' => $isPrimary ? 1 : 0,
            ];
        }

        if (empty($domains)) {
            Session::flash('error', 'Voeg minstens één domein toe.');
            $this->redirect('/admin/sites/create');
            return;
        }

        // Ensure at least one primary
        $hasPrimary = false;
        foreach ($domains as $d) {
            if ($d['is_primary']) $hasPrimary = true;
        }
        if (!$hasPrimary) {
            $domains[0]['is_primary'] = 1;
            $primaryDomain = $domains[0]['domain'];
        }

        $data = $validation['data'];
        $data['domain'] = $primaryDomain; // legacy field

        $siteId = $this->siteModel->create($data);

        if ($siteId) {
            $this->siteModel->syncDomains($siteId, $domains);
        }

        Session::flash('success', 'Site aangemaakt.');
        $this->redirect('/admin/sites');
    }

    public function edit(int $id): void
    {
        $site = $this->siteModel->findById($id);
        if (!$site) {
            Session::flash('error', 'Site niet gevonden.');
            $this->redirect('/admin/sites');
            return;
        }

        $site['domains'] = $this->siteModel->getDomains($id);

        $this->render('admin/sites/edit.twig', ['site_item' => $site]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'slug' => 'required|max:50',
            'layout' => 'required|max:50',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/sites/{$id}/edit");
            return;
        }

        // Parse domains from form
        $domainNames = $_POST['domains'] ?? [];
        $domainLangs = $_POST['domain_langs'] ?? [];
        $domainPrimary = $_POST['domain_primary'] ?? '0';

        $domains = [];
        foreach ($domainNames as $i => $d) {
            $d = trim($d);
            if ($d === '') continue;
            $domains[] = [
                'domain' => $d,
                'default_lang' => $domainLangs[$i] ?? 'nl',
                'is_primary' => ((int)$domainPrimary === $i) ? 1 : 0,
            ];
        }

        // Ensure at least one primary
        if (!empty($domains)) {
            $hasPrimary = false;
            foreach ($domains as $d) {
                if ($d['is_primary']) $hasPrimary = true;
            }
            if (!$hasPrimary) {
                $domains[0]['is_primary'] = 1;
            }
        }

        $data = $validation['data'];
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        $this->siteModel->update($id, $data);
        $this->siteModel->syncDomains($id, $domains);

        Session::flash('success', 'Site bijgewerkt.');
        $this->redirect('/admin/sites');
    }

    public function destroy(int $id): void
    {
        $this->siteModel->delete($id);
        Session::flash('success', 'Site verwijderd.');
        $this->redirect('/admin/sites');
    }
}
