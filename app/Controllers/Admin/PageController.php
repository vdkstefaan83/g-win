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

        // Get all pages in the other language for linking translations
        $otherLang = ($page['lang'] ?? 'nl') === 'nl' ? 'fr' : 'nl';
        $translatablePages = $this->pageModel->query(
            "SELECT id, title, lang FROM pages WHERE lang = :lang ORDER BY title ASC",
            ['lang' => $otherLang]
        )->fetchAll();

        $this->render('admin/pages/edit.twig', [
            'page' => $page,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'page_categories' => $this->catModel->findAll('name', 'ASC'),
            'translatable_pages' => $translatablePages,
        ]);
    }

    public function update(int $id): void
    {
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect("/admin/pages/{$id}/edit");
        }

        $validation = $this->validate([
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/pages/{$id}/edit");
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
        if ($this->input('remove_intro_image')) {
            $page = $this->pageModel->findById($id);
            if ($page && $page['intro_image']) {
                FileUpload::delete($page['intro_image']);
            }
            $data['intro_image'] = null;
        } elseif (!empty($_FILES['intro_image']['name'])) {
            $page = $this->pageModel->findById($id);
            if ($page && $page['intro_image']) {
                FileUpload::delete($page['intro_image']);
            }
            $filename = FileUpload::uploadImage($_FILES['intro_image'], 'pages');
            if ($filename) {
                $data['intro_image'] = 'pages/' . $filename;
            }
        }

        $this->pageModel->update($id, $data);
        $this->pageModel->syncSites($id, $siteIds);

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
