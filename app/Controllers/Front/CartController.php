<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Site;
use App\Models\Menu;

class CartController extends Controller
{
    private Cart $cartModel;
    private CartItem $cartItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->cartItemModel = new CartItem();
    }

    public function index(): void
    {
        $cart = $this->cartModel->getOrCreate(session_id());
        $items = $this->cartModel->getItems($cart['id']);
        $total = $this->cartModel->getTotal($cart['id']);

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        $this->render('front/cart/index.twig', [
            'cart_items' => $items,
            'cart_total' => $total,
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    public function add(): void
    {
        $productId = (int) $this->input('product_id');
        $quantity = max(1, (int) $this->input('quantity', 1));

        $productModel = new Product();
        $product = $productModel->findById($productId);

        if (!$product || !$product['is_active']) {
            $this->json(['error' => 'Product niet gevonden.'], 404);
            return;
        }

        $cart = $this->cartModel->getOrCreate(session_id());
        $existingItem = $this->cartItemModel->findByCartAndProduct($cart['id'], $productId);

        if ($existingItem) {
            $this->cartItemModel->updateQuantity($existingItem['id'], $existingItem['quantity'] + $quantity);
        } else {
            $this->cartItemModel->create([
                'cart_id' => $cart['id'],
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['price'],
            ]);
        }

        $this->json([
            'success' => true,
            'cart_count' => $this->cartModel->getItemCount($cart['id']),
            'cart_total' => $this->cartModel->getTotal($cart['id']),
        ]);
    }

    public function update(): void
    {
        $itemId = (int) $this->input('item_id');
        $quantity = max(0, (int) $this->input('quantity', 1));

        if ($quantity === 0) {
            $this->cartItemModel->delete($itemId);
        } else {
            $this->cartItemModel->updateQuantity($itemId, $quantity);
        }

        $cart = $this->cartModel->getOrCreate(session_id());

        $this->json([
            'success' => true,
            'cart_count' => $this->cartModel->getItemCount($cart['id']),
            'cart_total' => $this->cartModel->getTotal($cart['id']),
        ]);
    }

    public function remove(): void
    {
        $itemId = (int) $this->input('item_id');
        $this->cartItemModel->delete($itemId);

        $cart = $this->cartModel->getOrCreate(session_id());

        $this->json([
            'success' => true,
            'cart_count' => $this->cartModel->getItemCount($cart['id']),
            'cart_total' => $this->cartModel->getTotal($cart['id']),
        ]);
    }
}
