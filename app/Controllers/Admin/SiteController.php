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
        $this->render('admin/sites/index.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
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
            'domain' => 'required|max:255',
            'layout' => 'required|max:50',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/sites/create');
        }

        $this->siteModel->create($validation['data']);
        Session::flash('success', 'Site aangemaakt.');
        $this->redirect('/admin/sites');
    }

    public function edit(int $id): void
    {
        $site = $this->siteModel->findById($id);
        if (!$site) {
            Session::flash('error', 'Site niet gevonden.');
            $this->redirect('/admin/sites');
        }

        $this->render('admin/sites/edit.twig', ['site_item' => $site]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'slug' => 'required|max:50',
            'domain' => 'required|max:255',
            'layout' => 'required|max:50',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/sites/{$id}/edit");
        }

        $data = $validation['data'];
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        $this->siteModel->update($id, $data);
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
