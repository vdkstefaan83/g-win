<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use Core\Helpers\FileUpload;
use App\Models\PageCategory;
use App\Models\Site;

class PageCategoryController extends Controller
{
    private PageCategory $catModel;
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->catModel = new PageCategory();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $this->render('admin/page-categories/index.twig', [
            'categories' => $this->catModel->getAllWithSite(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/page-categories/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'name' => 'required|max:100',
            'slug' => 'required|max:100',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/page-categories/create');
        }

        $data = $validation['data'];
        $data['lang'] = $this->input('lang', 'nl');
        $data['description'] = $this->input('description', '');
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        if (!empty($_FILES['image']['name'])) {
            $filename = FileUpload::uploadImage($_FILES['image'], 'page-categories');
            if ($filename) {
                $data['image'] = 'page-categories/' . $filename;
            }
        }

        $this->catModel->create($data);
        Session::flash('success', 'Paginacategorie aangemaakt.');
        $this->redirect('/admin/page-categories');
    }

    public function edit(int $id): void
    {
        $category = $this->catModel->findById($id);
        if (!$category) {
            Session::flash('error', 'Categorie niet gevonden.');
            $this->redirect('/admin/page-categories');
        }

        $this->render('admin/page-categories/edit.twig', [
            'category' => $category,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'name' => 'required|max:100',
            'slug' => 'required|max:100',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/page-categories/{$id}/edit");
        }

        $data = $validation['data'];
        $data['lang'] = $this->input('lang', 'nl');
        $data['description'] = $this->input('description', '');
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        if (!empty($_FILES['image']['name'])) {
            $old = $this->catModel->findById($id);
            if ($old && $old['image']) {
                FileUpload::delete($old['image']);
            }
            $filename = FileUpload::uploadImage($_FILES['image'], 'page-categories');
            if ($filename) {
                $data['image'] = 'page-categories/' . $filename;
            }
        }

        $this->catModel->update($id, $data);
        Session::flash('success', 'Paginacategorie bijgewerkt.');
        $this->redirect('/admin/page-categories');
    }

    public function destroy(int $id): void
    {
        $category = $this->catModel->findById($id);
        if ($category && $category['image']) {
            FileUpload::delete($category['image']);
        }
        $this->catModel->delete($id);
        Session::flash('success', 'Paginacategorie verwijderd.');
        $this->redirect('/admin/page-categories');
    }
}
