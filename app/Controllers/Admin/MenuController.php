<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageCategory;
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
            : $this->menuModel->getAllWithSite();

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
        $siteIds = $_POST['site_ids'] ?? [];
        if (empty($siteIds)) {
            Session::flash('error', 'Selecteer minstens één site.');
            $this->redirect('/admin/menus/create');
        }

        $validation = $this->validate([
            'name' => 'required|max:100',
            'location' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/menus/create');
        }

        $data = $validation['data'];
        $data['site_id'] = (int) $siteIds[0];
        $data['lang'] = $this->input('lang', 'nl');
        $menuId = $this->menuModel->create($data);
        if ($menuId) {
            $this->menuModel->syncSites($menuId, $siteIds);
        }
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

        // If this is a FR menu, redirect to the NL master
        if ($menu['lang'] === 'fr') {
            $nlMenu = $this->menuModel->findLinkedTranslation($id);
            if ($nlMenu) {
                $this->redirect("/admin/menus/{$nlMenu['id']}/edit");
                return;
            }
        }

        $menu['site_ids'] = $this->menuModel->getSiteIds($id);

        // Find linked FR menu
        $frMenu = $this->menuModel->findLinkedTranslation($id);
        $frItems = [];
        if ($frMenu) {
            $frWithItems = $this->menuModel->getWithItems($frMenu['id']);
            $frItems = $frWithItems ? $frWithItems['items'] : [];
        }

        $pageModel = new Page();
        $pages = $pageModel->getAllWithSite();

        $categoryModel = new PageCategory();
        $categories = $categoryModel->getAllWithSite();

        $this->render('admin/menus/edit.twig', [
            'menu' => $menu,
            'fr_menu' => $frMenu,
            'fr_items' => $frItems,
            'pages' => $pages,
            'categories' => $categories,
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

        $siteIds = $_POST['site_ids'] ?? [];
        $data = $validation['data'];
        $data['lang'] = 'nl';
        if (!empty($siteIds)) {
            $data['site_id'] = (int) $siteIds[0];
        }
        $this->menuModel->update($id, $data);
        if (!empty($siteIds)) {
            $this->menuModel->syncSites($id, $siteIds);
        }

        // Update NL menu items
        $this->syncMenuItems($id, json_decode($this->input('items', '[]'), true));

        // Handle FR menu items
        $frItems = json_decode($this->input('fr_items', '[]'), true);
        $hasFrContent = false;
        if (is_array($frItems)) {
            foreach ($frItems as $fi) {
                if (!empty(trim($fi['label'] ?? ''))) {
                    $hasFrContent = true;
                    break;
                }
            }
        }

        if ($hasFrContent) {
            // Find or create FR menu
            $frMenu = $this->menuModel->findLinkedTranslation($id);
            if (!$frMenu) {
                $frMenuId = $this->menuModel->create([
                    'name' => $data['name'] . ' (FR)',
                    'location' => $data['location'],
                    'lang' => 'fr',
                    'site_id' => $data['site_id'] ?? null,
                ]);
                if (!empty($siteIds)) {
                    $this->menuModel->syncSites($frMenuId, $siteIds);
                }
            } else {
                $frMenuId = $frMenu['id'];
                $this->menuModel->update($frMenuId, [
                    'name' => $data['name'] . ' (FR)',
                    'location' => $data['location'],
                ]);
                if (!empty($siteIds)) {
                    $this->menuModel->syncSites($frMenuId, $siteIds);
                }
            }
            $this->syncMenuItems($frMenuId, $frItems);
        }

        Session::flash('success', 'Menu bijgewerkt.');
        $this->redirect("/admin/menus/{$id}/edit");
    }

    private function syncMenuItems(int $menuId, ?array $items): void
    {
        if (!is_array($items)) return;

        // Delete removed items
        $existingItems = $this->menuItemModel->getByMenu($menuId);
        $newItemIds = array_column($items, 'id');
        foreach ($existingItems as $existing) {
            if (!in_array($existing['id'], $newItemIds)) {
                $this->menuItemModel->delete($existing['id']);
            }
        }

        // Update or create items
        foreach ($items as $index => $item) {
            $pageId = $item['page_id'] ?? '';
            $url = $item['url'] ?? null;
            if (is_string($pageId) && str_starts_with($pageId, 'cat:')) {
                $url = '/' . substr($pageId, 4);
                $pageId = null;
            }

            $itemData = [
                'menu_id' => $menuId,
                'label' => $item['label'],
                'url' => $url,
                'page_id' => !empty($pageId) ? (int)$pageId : null,
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
