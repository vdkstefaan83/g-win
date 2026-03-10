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
            'categories' => $this->categoryModel->getAllForAdmin(),
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

        $translation = $this->categoryModel->findLinkedTranslation($id);

        if (($category['lang'] ?? 'nl') === 'nl') {
            $nl = $category;
            $fr = $translation ?: [];
        } else {
            $fr = $category;
            $nl = $translation ?: [];
        }

        $this->render('admin/categories/edit.twig', [
            'category' => $category,
            'nl' => $nl,
            'fr' => $fr,
            'parent_categories' => $this->categoryModel->getParentCategories(),
        ]);
    }

    public function update(int $id): void
    {
        $nlName = trim($this->input('nl_name', ''));
        $nlSlug = trim($this->input('nl_slug', ''));

        if (empty($nlName) || empty($nlSlug)) {
            Session::flash('error', 'NL naam en slug zijn verplicht.');
            $this->redirect("/admin/categories/{$id}/edit");
        }

        // Shared fields
        $shared = [
            'parent_id' => $this->input('parent_id') ?: null,
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];

        // Determine NL/FR records
        $category = $this->categoryModel->findById($id);
        $translation = $this->categoryModel->findLinkedTranslation($id);

        if (($category['lang'] ?? 'nl') === 'nl') {
            $nlId = $category['id'];
            $frId = $translation ? $translation['id'] : null;
        } else {
            $frId = $category['id'];
            $nlId = $translation ? $translation['id'] : null;
        }

        // Update NL
        $nlData = array_merge($shared, [
            'name' => $nlName,
            'slug' => $nlSlug,
            'description' => $this->input('nl_description', ''),
            'lang' => 'nl',
            'translation_of' => null,
        ]);

        if ($nlId) {
            $this->categoryModel->update($nlId, $nlData);
        } else {
            $nlId = $this->categoryModel->create($nlData);
        }

        // Update FR (only if name filled)
        $frName = trim($this->input('fr_name', ''));
        if (!empty($frName)) {
            $frData = array_merge($shared, [
                'name' => $frName,
                'slug' => trim($this->input('fr_slug', '')),
                'description' => $this->input('fr_description', ''),
                'lang' => 'fr',
                'translation_of' => $nlId,
            ]);

            if ($frId) {
                $this->categoryModel->update($frId, $frData);
            } else {
                $this->categoryModel->create($frData);
            }
        }

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
