<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Page;
use App\Models\Site;

class PageController extends Controller
{
    private Page $pageModel;
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->pageModel = new Page();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $siteId = $this->input('site_id');
        $sites = $this->siteModel->findAll('name', 'ASC');

        $pages = $siteId
            ? $this->pageModel->getBySite((int)$siteId)
            : $this->pageModel->findAll('sort_order', 'ASC');

        $this->render('admin/pages/index.twig', [
            'pages' => $pages,
            'sites' => $sites,
            'selected_site_id' => $siteId,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/pages/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/pages/create');
        }

        $data = $validation['data'];
        $data['content'] = $this->input('content', '');
        $data['meta_title'] = $this->input('meta_title', '');
        $data['meta_description'] = $this->input('meta_description', '');
        $data['is_published'] = $this->input('is_published') ? 1 : 0;
        $data['sort_order'] = (int) $this->input('sort_order', 0);

        $this->pageModel->create($data);
        Session::flash('success', 'Pagina aangemaakt.');
        $this->redirect('/admin/pages');
    }

    public function edit(int $id): void
    {
        $page = $this->pageModel->findById($id);
        if (!$page) {
            Session::flash('error', 'Pagina niet gevonden.');
            $this->redirect('/admin/pages');
        }

        $this->render('admin/pages/edit.twig', [
            'page' => $page,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/pages/{$id}/edit");
        }

        $data = $validation['data'];
        $data['content'] = $this->input('content', '');
        $data['meta_title'] = $this->input('meta_title', '');
        $data['meta_description'] = $this->input('meta_description', '');
        $data['is_published'] = $this->input('is_published') ? 1 : 0;
        $data['sort_order'] = (int) $this->input('sort_order', 0);

        $this->pageModel->update($id, $data);
        Session::flash('success', 'Pagina bijgewerkt.');
        $this->redirect('/admin/pages');
    }

    public function destroy(int $id): void
    {
        $this->pageModel->delete($id);
        Session::flash('success', 'Pagina verwijderd.');
        $this->redirect('/admin/pages');
    }
}
