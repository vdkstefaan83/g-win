<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use Core\Helpers\FileUpload;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageImage;
use App\Models\Site;

class PageController extends Controller
{
    private Page $pageModel;
    private PageCategory $catModel;
    private PageImage $imageModel;
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->pageModel = new Page();
        $this->catModel = new PageCategory();
        $this->imageModel = new PageImage();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $siteId = $this->input('site_id');

        $pages = $siteId
            ? $this->pageModel->getBySite((int)$siteId)
            : $this->pageModel->getAllWithSite();

        $this->render('admin/pages/index.twig', [
            'pages' => $pages,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'selected_site_id' => $siteId,
        ]);
    }

    public function reorder(): void
    {
        $items = json_decode(file_get_contents('php://input'), true);
        if (is_array($items)) {
            foreach ($items as $index => $pageId) {
                $this->pageModel->update((int)$pageId, ['sort_order' => $index]);
            }
        }
        $this->json(['success' => true]);
    }

    public function create(): void
    {
        $this->render('admin/pages/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'page_categories' => $this->catModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect('/admin/pages/create');
        }

        $validation = $this->validate([
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/pages/create');
        }

        $data = $validation['data'];
        $data['site_id'] = (int) $siteIds[0];
        $data['lang'] = $this->input('lang', 'nl');
        $data['translation_of'] = $this->input('translation_of') ?: null;
        $data['content'] = $this->input('content', '');
        $data['intro_text'] = $this->input('intro_text', '');
        $data['meta_title'] = $this->input('meta_title', '');
        $data['meta_description'] = $this->input('meta_description', '');
        $data['is_published'] = $this->input('is_published') ? 1 : 0;
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['page_category_id'] = $this->input('page_category_id') ?: null;

        // Intro image
        if (!empty($_FILES['intro_image']['name'])) {
            $filename = FileUpload::uploadImage($_FILES['intro_image'], 'pages');
            if ($filename) {
                $data['intro_image'] = 'pages/' . $filename;
            }
        }

        $pageId = $this->pageModel->create($data);

        // Sync sites pivot
        if ($pageId) {
            $this->pageModel->syncSites($pageId, $siteIds);
        }

        // Multi-image upload
        if ($pageId && !empty($_FILES['images']['name'][0])) {
            $this->handleImageUploads($pageId);
        }

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

        $page['images'] = $this->imageModel->getByPage($id);
        $page['site_ids'] = $this->pageModel->getSiteIds($id);

        // Find linked translation for NL/FR tabs
        $translation = $this->pageModel->findLinkedTranslation($id);

        if (($page['lang'] ?? 'nl') === 'nl') {
            $nl = $page;
            $fr = $translation ?: [];
        } else {
            $fr = $page;
            $nl = $translation ?: [];
        }

        $this->render('admin/pages/edit.twig', [
            'page' => $page,
            'nl' => $nl,
            'fr' => $fr,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'page_categories' => $this->catModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect("/admin/pages/{$id}/edit");
        }

        // NL fields are required
        $nlTitle = trim($this->input('nl_title', ''));
        $nlSlug = trim($this->input('nl_slug', ''));
        if (empty($nlTitle) || empty($nlSlug)) {
            Session::flash('error', 'NL titel en slug zijn verplicht.');
            $this->redirect("/admin/pages/{$id}/edit");
        }

        // Shared fields
        $shared = [
            'site_id' => (int) $siteIds[0],
            'page_category_id' => $this->input('page_category_id') ?: null,
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_published' => $this->input('is_published') ? 1 : 0,
        ];

        // Determine which record is NL and which is FR
        $page = $this->pageModel->findById($id);
        $translation = $this->pageModel->findLinkedTranslation($id);

        if (($page['lang'] ?? 'nl') === 'nl') {
            $nlId = $page['id'];
            $frId = $translation ? $translation['id'] : null;
        } else {
            $frId = $page['id'];
            $nlId = $translation ? $translation['id'] : null;
        }

        // Update NL record
        $nlData = array_merge($shared, [
            'title' => $nlTitle,
            'slug' => $nlSlug,
            'content' => $this->input('nl_content', ''),
            'intro_text' => $this->input('nl_intro_text', ''),
            'meta_description' => $this->input('nl_meta_description', ''),
            'lang' => 'nl',
            'translation_of' => null,
        ]);

        if ($nlId) {
            $this->pageModel->update($nlId, $nlData);
            $this->pageModel->syncSites($nlId, $siteIds);
        } else {
            $nlId = $this->pageModel->create($nlData);
            $this->pageModel->syncSites($nlId, $siteIds);
        }

        // Handle FR (only if title is filled in)
        $frTitle = trim($this->input('fr_title', ''));
        if (!empty($frTitle)) {
            $frData = array_merge($shared, [
                'title' => $frTitle,
                'slug' => trim($this->input('fr_slug', '')),
                'content' => $this->input('fr_content', ''),
                'intro_text' => $this->input('fr_intro_text', ''),
                'meta_description' => $this->input('fr_meta_description', ''),
                'lang' => 'fr',
                'translation_of' => $nlId,
            ]);

            if ($frId) {
                $this->pageModel->update($frId, $frData);
                $this->pageModel->syncSites($frId, $siteIds);
            } else {
                $frId = $this->pageModel->create($frData);
                $this->pageModel->syncSites($frId, $siteIds);
            }
        }

        // Intro image (applies to the main record being edited)
        if ($this->input('remove_intro_image')) {
            if ($page['intro_image']) {
                FileUpload::delete($page['intro_image']);
            }
            $this->pageModel->update($id, ['intro_image' => null]);
        } elseif (!empty($_FILES['intro_image']['name'])) {
            if ($page['intro_image']) {
                FileUpload::delete($page['intro_image']);
            }
            $filename = FileUpload::uploadImage($_FILES['intro_image'], 'pages');
            if ($filename) {
                $this->pageModel->update($id, ['intro_image' => 'pages/' . $filename]);
            }
        }

        // Multi-image upload
        if (!empty($_FILES['images']['name'][0])) {
            $this->handleImageUploads($id);
        }

        Session::flash('success', 'Pagina bijgewerkt.');
        $this->redirect("/admin/pages/{$id}/edit");
    }

    public function destroy(int $id): void
    {
        $page = $this->pageModel->findById($id);
        if ($page && $page['intro_image']) {
            FileUpload::delete($page['intro_image']);
        }

        $images = $this->imageModel->getByPage($id);
        foreach ($images as $image) {
            FileUpload::delete('pages/' . $image['filename']);
        }

        $this->pageModel->delete($id);
        Session::flash('success', 'Pagina verwijderd.');
        $this->redirect('/admin/pages');
    }

    public function deleteImage(): void
    {
        $imageId = (int) $this->input('image_id');
        $image = $this->imageModel->findById($imageId);

        if ($image) {
            FileUpload::delete('pages/' . $image['filename']);
            $this->imageModel->delete($imageId);
        }

        $this->json(['success' => true]);
    }

    public function reorderImages(): void
    {
        $items = json_decode(file_get_contents('php://input'), true);
        if (is_array($items)) {
            foreach ($items as $index => $imageId) {
                $this->imageModel->update((int)$imageId, ['sort_order' => $index]);
            }
        }
        $this->json(['success' => true]);
    }

    private function handleImageUploads(int $pageId): void
    {
        $existingCount = count($this->imageModel->getByPage($pageId));

        foreach ($_FILES['images']['name'] as $key => $name) {
            if (empty($name)) continue;

            $file = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key],
            ];

            $filename = FileUpload::uploadImage($file, 'pages');
            if ($filename) {
                $this->imageModel->create([
                    'page_id' => $pageId,
                    'filename' => $filename,
                    'sort_order' => $existingCount + $key,
                    'is_primary' => ($existingCount === 0 && $key === 0) ? 1 : 0,
                ]);
            }
        }
    }
}
