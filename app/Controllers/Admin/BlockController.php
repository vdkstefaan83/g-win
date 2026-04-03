<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use Core\Helpers\FileUpload;
use App\Models\Block;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\Site;

class BlockController extends Controller
{
    private Block $blockModel;
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->blockModel = new Block();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $siteId = $this->input('site_id');
        $pageFilter = $this->input('page_filter');

        $blocks = $siteId
            ? $this->blockModel->getBySite((int)$siteId)
            : $this->blockModel->getAllWithSite();

        // Filter by page
        if ($pageFilter === 'homepage') {
            $blocks = array_filter($blocks, fn($b) => empty($b['page_id']));
        } elseif ($pageFilter && is_numeric($pageFilter)) {
            $blocks = array_filter($blocks, fn($b) => (int)($b['page_id'] ?? 0) === (int)$pageFilter);
        }
        $blocks = array_values($blocks);

        $pageModel = new Page();

        $this->render('admin/blocks/index.twig', [
            'blocks' => $blocks,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'pages' => $pageModel->getAllWithSite(),
            'selected_site_id' => $siteId,
            'selected_page_filter' => $pageFilter,
        ]);
    }

    public function reorder(): void
    {
        $items = json_decode(file_get_contents('php://input'), true);
        if (is_array($items)) {
            foreach ($items as $index => $blockId) {
                $this->blockModel->update((int)$blockId, ['sort_order' => $index]);
            }
        }
        $this->json(['success' => true]);
    }

    public function create(): void
    {
        $this->render('admin/blocks/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect('/admin/blocks/create');
        }

        $validation = $this->validate([
            'title' => 'required|max:255',
            'type' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/blocks/create');
        }

        $data = $validation['data'];
        $data['site_id'] = (int) $siteIds[0];
        $data['lang'] = $this->input('lang', 'nl');
        $data['content'] = $this->input('content', '');
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        $data['subtitle'] = $this->input('subtitle', '');
        $data['link_url'] = $this->input('link_url', '');

        if ($data['type'] === 'hero') {
            $data['options'] = json_encode([
                'show_appointment_btn' => (bool) $this->input('opt_appointment_btn'),
                'show_shop_btn' => (bool) $this->input('opt_shop_btn'),
            ]);
        }

        // Support image URL or file upload — upload takes priority
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $filename = FileUpload::uploadImage($_FILES['image'], 'pages');
            if ($filename) {
                $data['image'] = 'pages/' . $filename;
            } else {
                Session::flash('error', 'Afbeelding uploaden mislukt. Controleer bestandstype (jpg/png/gif/webp) en grootte (max 5MB).');
            }
        } elseif (!empty($this->input('image_url', ''))) {
            $data['image'] = $this->input('image_url', '');
        }

        $blockId = $this->blockModel->create($data);
        if ($blockId) {
            $this->blockModel->syncSites($blockId, $siteIds);
        }
        Session::flash('success', 'Blok aangemaakt.');
        $this->redirect('/admin/blocks');
    }

    public function edit(int $id): void
    {
        $block = $this->blockModel->findById($id);
        if (!$block) {
            Session::flash('error', 'Blok niet gevonden.');
            $this->redirect('/admin/blocks');
        }

        // Decode JSON options for the template
        if (!empty($block['options']) && is_string($block['options'])) {
            $block['options'] = json_decode($block['options'], true) ?: [];
        }

        $block['site_ids'] = $this->blockModel->getSiteIds($id);

        // Find linked translation
        $translation = $this->blockModel->findLinkedTranslation($id);

        if (($block['lang'] ?? 'nl') === 'nl') {
            $nl = $block;
            $fr = $translation ?: [];
        } else {
            $fr = $block;
            $nl = $translation ?: [];
        }

        $pageModel = new Page();
        $catModel = new PageCategory();

        $this->render('admin/blocks/edit.twig', [
            'block' => $block,
            'nl' => $nl,
            'fr' => $fr,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'pages' => $pageModel->getAllWithSite(),
            'page_categories' => $catModel->getAllWithSite(),
        ]);
    }

    public function update(int $id): void
    {
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect("/admin/blocks/{$id}/edit");
        }

        $nlTitle = trim($this->input('nl_title', ''));
        $type = $this->input('type', '');
        if (empty($nlTitle) || empty($type)) {
            Session::flash('error', 'NL titel en type zijn verplicht.');
            $this->redirect("/admin/blocks/{$id}/edit");
        }

        // Parse page target (page:123 or cat:456 or empty=homepage)
        $pageTarget = $this->input('page_target', '');
        $pageId = null;
        $pageCategoryId = null;
        if (str_starts_with($pageTarget, 'page:')) {
            $pageId = (int) substr($pageTarget, 5);
        } elseif (str_starts_with($pageTarget, 'cat:')) {
            $pageCategoryId = (int) substr($pageTarget, 4);
        }

        // Shared fields
        $shared = [
            'site_id' => (int) $siteIds[0],
            'type' => $type,
            'page_id' => $pageId,
            'page_category_id' => $pageCategoryId,
            'sort_order' => (int) $this->input('sort_order', 0),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'link_url' => $this->input('link_url', ''),
        ];

        if ($type === 'hero') {
            $shared['options'] = json_encode([
                'show_appointment_btn' => (bool) $this->input('opt_appointment_btn'),
                'show_shop_btn' => (bool) $this->input('opt_shop_btn'),
            ]);
        } elseif (in_array($type, ['youtube', 'vimeo'])) {
            $shared['options'] = json_encode([
                'autoplay' => (bool) $this->input('opt_autoplay'),
                'muted' => (bool) $this->input('opt_muted'),
                'loop' => (bool) $this->input('opt_loop'),
            ]);
        }

        // Handle image (shared) — file upload takes priority over URL
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $existingBlock = $this->blockModel->findById($id);
            if ($existingBlock && $existingBlock['image'] && !str_starts_with($existingBlock['image'], 'http')) {
                FileUpload::delete($existingBlock['image']);
            }
            $filename = FileUpload::uploadImage($_FILES['image'], 'pages');
            if ($filename) {
                $shared['image'] = 'pages/' . $filename;
            } else {
                Session::flash('error', 'Afbeelding uploaden mislukt. Controleer bestandstype (jpg/png/gif/webp) en grootte (max 5MB).');
            }
        } elseif (!empty($this->input('image_url', ''))) {
            $shared['image'] = $this->input('image_url', '');
        }

        // Determine NL/FR records
        $block = $this->blockModel->findById($id);
        $translation = $this->blockModel->findLinkedTranslation($id);

        if (($block['lang'] ?? 'nl') === 'nl') {
            $nlId = $block['id'];
            $frId = $translation ? $translation['id'] : null;
        } else {
            $frId = $block['id'];
            $nlId = $translation ? $translation['id'] : null;
        }

        // For video types, use plain text content field
        $nlContent = in_array($type, ['youtube', 'vimeo'])
            ? $this->input('nl_content_plain', '')
            : $this->input('nl_content', '');

        // Update NL
        $nlData = array_merge($shared, [
            'title' => $nlTitle,
            'subtitle' => $this->input('nl_subtitle', ''),
            'content' => $nlContent,
            'lang' => 'nl',
            'translation_of' => null,
        ]);

        if ($nlId) {
            $this->blockModel->update($nlId, $nlData);
            $this->blockModel->syncSites($nlId, $siteIds);
        } else {
            $nlId = $this->blockModel->create($nlData);
            $this->blockModel->syncSites($nlId, $siteIds);
        }

        // Update FR (only if title filled)
        $frTitle = trim($this->input('fr_title', ''));
        if (!empty($frTitle)) {
            $frContent = in_array($type, ['youtube', 'vimeo'])
                ? $this->input('fr_content_plain', '')
                : $this->input('fr_content', '');

            $frData = array_merge($shared, [
                'title' => $frTitle,
                'subtitle' => $this->input('fr_subtitle', ''),
                'content' => $frContent,
                'lang' => 'fr',
                'translation_of' => $nlId,
            ]);

            if ($frId) {
                $this->blockModel->update($frId, $frData);
                $this->blockModel->syncSites($frId, $siteIds);
            } else {
                $frId = $this->blockModel->create($frData);
                $this->blockModel->syncSites($frId, $siteIds);
            }
        }

        Session::flash('success', 'Blok bijgewerkt.');
        $this->redirect('/admin/blocks');
    }

    public function destroy(int $id): void
    {
        $block = $this->blockModel->findById($id);
        if ($block && $block['image']) {
            FileUpload::delete($block['image']);
        }
        $this->blockModel->delete($id);
        Session::flash('success', 'Blok verwijderd.');
        $this->redirect('/admin/blocks');
    }
}
