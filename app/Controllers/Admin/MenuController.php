<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Site;

class MenuController extends Controller
{
    private Menu $menuModel;
    private MenuItem $menuItemModel;
    private Site $siteModel;

    public function __construct()
    {
        parent::__construct();
        $this->menuModel = new Menu();
        $this->menuItemModel = new MenuItem();
        $this->siteModel = new Site();
    }

    public function index(): void
    {
        $siteId = $this->input('site_id');
        $menus = $siteId
            ? $this->menuModel->getBySite((int)$siteId)
            : $this->menuModel->findAll('name', 'ASC');

        $this->render('admin/menus/index.twig', [
            'menus' => $menus,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
            'selected_site_id' => $siteId,
        ]);
    }

    public function create(): void
    {
        $this->render('admin/menus/create.twig', [
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'site_id' => 'required|numeric',
            'name' => 'required|max:100',
            'location' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/menus/create');
        }

        $this->menuModel->create($validation['data']);
        Session::flash('success', 'Menu aangemaakt.');
        $this->redirect('/admin/menus');
    }

    public function edit(int $id): void
    {
        $menu = $this->menuModel->getWithItems($id);
        if (!$menu) {
            Session::flash('error', 'Menu niet gevonden.');
            $this->redirect('/admin/menus');
        }

        $pageModel = new Page();
        $pages = $pageModel->getBySite($menu['site_id']);

        $this->render('admin/menus/edit.twig', [
            'menu' => $menu,
            'pages' => $pages,
            'sites' => $this->siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'location' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/menus/{$id}/edit");
        }

        $this->menuModel->update($id, $validation['data']);

        // Update menu items
        $items = json_decode($this->input('items', '[]'), true);
        if (is_array($items)) {
            // Delete removed items
            $existingItems = $this->menuItemModel->getByMenu($id);
            $newItemIds = array_column($items, 'id');
            foreach ($existingItems as $existing) {
                if (!in_array($existing['id'], $newItemIds)) {
                    $this->menuItemModel->delete($existing['id']);
                }
            }

            // Update or create items
            foreach ($items as $index => $item) {
                $itemData = [
                    'menu_id' => $id,
                    'label' => $item['label'],
                    'url' => $item['url'] ?? null,
                    'page_id' => !empty($item['page_id']) ? (int)$item['page_id'] : null,
                    'parent_id' => !empty($item['parent_id']) ? (int)$item['parent_id'] : null,
                    'sort_order' => $index,
                ];

                if (!empty($item['id']) && is_numeric($item['id'])) {
                    $this->menuItemModel->update((int)$item['id'], $itemData);
                } else {
                    $this->menuItemModel->create($itemData);
                }
            }
        }

        Session::flash('success', 'Menu bijgewerkt.');
        $this->redirect("/admin/menus/{$id}/edit");
    }

    public function destroy(int $id): void
    {
        $this->menuModel->delete($id);
        Session::flash('success', 'Menu verwijderd.');
        $this->redirect('/admin/menus');
    }

    public function reorder(): void
    {
        $items = json_decode(file_get_contents('php://input'), true);
        if (is_array($items)) {
            foreach ($items as $item) {
                $this->menuItemModel->updateSortOrder(
                    (int) $item['id'],
                    (int) $item['sort_order'],
                    isset($item['parent_id']) ? (int)$item['parent_id'] : null
                );
            }
        }
        $this->json(['success' => true]);
    }
}
