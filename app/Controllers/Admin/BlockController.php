<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use Core\Helpers\FileUpload;
use App\Models\Block;
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
        $blocks = $siteId
            ? $this->blockModel->getBySite((int)$siteId)
            : $this->blockModel->findAll('sort_order', 'ASC');

        $this->render('admin/blocks/index.twig', [
            'blocks' => $blocks,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'selected_site_id' => $siteId,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/blocks/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'title' => 'required|max:255',
            'type' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/blocks/create');
        }

        $data = $validation['data'];
        $data['content'] = $this->input('content', '');
        $data['subtitle'] = $this->input('subtitle', '');
        $data['link_url'] = $this->input('link_url', '');
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        // Block-type specific options (JSON)
        if ($data['type'] === 'hero') {
            $data['options'] = json_encode([
                'show_appointment_btn' => (bool) $this->input('opt_appointment_btn'),
                'show_shop_btn' => (bool) $this->input('opt_shop_btn'),
            ]);
        }

        // Support image URL or file upload
        $imageUrl = $this->input('image_url', '');
        if (!empty($imageUrl)) {
            $data['image'] = $imageUrl;
        } elseif (!empty($_FILES['image']['name'])) {
            $filename = FileUpload::uploadImage($_FILES['image'], 'pages');
            if ($filename) {
                $data['image'] = 'pages/' . $filename;
            }
        }

        $this->blockModel->create($data);
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

        $this->render('admin/blocks/edit.twig', [
            'block' => $block,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'title' => 'required|max:255',
            'type' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/blocks/{$id}/edit");
        }

        $data = $validation['data'];
        $data['content'] = $this->input('content', '');
        $data['subtitle'] = $this->input('subtitle', '');
        $data['link_url'] = $this->input('link_url', '');
        $data['sort_order'] = (int) $this->input('sort_order', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;

        // Block-type specific options (JSON)
        if ($data['type'] === 'hero') {
            $data['options'] = json_encode([
                'show_appointment_btn' => (bool) $this->input('opt_appointment_btn'),
                'show_shop_btn' => (bool) $this->input('opt_shop_btn'),
            ]);
        }

        // Support image URL or file upload
        $imageUrl = $this->input('image_url', '');
        if (!empty($imageUrl)) {
            $data['image'] = $imageUrl;
        } elseif (!empty($_FILES['image']['name'])) {
            $block = $this->blockModel->findById($id);
            if ($block && $block['image'] && !str_starts_with($block['image'], 'http')) {
                FileUpload::delete($block['image']);
            }
            $filename = FileUpload::uploadImage($_FILES['image'], 'pages');
            if ($filename) {
                $data['image'] = 'pages/' . $filename;
            }
        }

        $this->blockModel->update($id, $data);
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
