<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Category;

class CategoryController extends Controller
{
    private Category $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new Category();
    }

    public function index(): void
    {
        $this->render('admin/categories/index.twig', [
            'categories' => $this->categoryModel->findAll('sort_order', 'ASC'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/categories/create.twig', [
            'parent_categories' => $this->categoryModel->getParentCategories(),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'slug' => 'required|max:100',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/categories/create');
        }

        $data = $validation['data'];
        $data['description'] = $this->input('description', '');
        $data['parent_id'] = $this->input('parent_id') ?: null;
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        $this->categoryModel->create($data);
        Session::flash('success', 'Categorie aangemaakt.');
        $this->redirect('/admin/categories');
    }

    public function edit(int $id): void
    {
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            Session::flash('error', 'Categorie niet gevonden.');
            $this->redirect('/admin/categories');
        }

        $this->render('admin/categories/edit.twig', [
            'category' => $category,
            'parent_categories' => $this->categoryModel->getParentCategories(),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'slug' => 'required|max:100',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/categories/{$id}/edit");
        }

        $data = $validation['data'];
        $data['description'] = $this->input('description', '');
        $data['parent_id'] = $this->input('parent_id') ?: null;
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        $this->categoryModel->update($id, $data);
        Session::flash('success', 'Categorie bijgewerkt.');
        $this->redirect('/admin/categories');
    }

    public function destroy(int $id): void
    {
        $this->categoryModel->delete($id);
        Session::flash('success', 'Categorie verwijderd.');
        $this->redirect('/admin/categories');
    }
}
