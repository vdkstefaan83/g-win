<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use Core\Helpers\FileUpload;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;

class ProductController extends Controller
{
    private Product $productModel;
    private ProductImage $imageModel;
    private Category $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->imageModel = new ProductImage();
        $this->categoryModel = new Category();
    }

    public function index(): void
    {
        $this->render('admin/products/index.twig', [
            'products' => $this->productModel->findAll('name', 'ASC'),
            'categories' => $this->categoryModel->findAll('name', 'ASC'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/products/create.twig', [
            'categories' => $this->categoryModel->findAll('name', 'ASC'),
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|max:255',
            'price' => 'required|numeric',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/products/create');
        }

        $data = $validation['data'];
        $data['category_id'] = $this->input('category_id') ?: null;
        $data['description'] = $this->input('description', '');
        $data['stock'] = (int) $this->input('stock', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;
        $data['is_featured'] = $this->input('is_featured') ? 1 : 0;

        $productId = $this->productModel->create($data);

        // Handle image uploads
        if ($productId && !empty($_FILES['images']['name'][0])) {
            $this->handleImageUploads($productId);
        }

        Session::flash('success', 'Product aangemaakt.');
        $this->redirect('/admin/products');
    }

    public function edit(int $id): void
    {
        $product = $this->productModel->getWithImages($id);
        if (!$product) {
            Session::flash('error', 'Product niet gevonden.');
            $this->redirect('/admin/products');
        }

        $this->render('admin/products/edit.twig', [
            'product' => $product,
            'categories' => $this->categoryModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|max:255',
            'price' => 'required|numeric',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/products/{$id}/edit");
        }

        $data = $validation['data'];
        $data['category_id'] = $this->input('category_id') ?: null;
        $data['description'] = $this->input('description', '');
        $data['stock'] = (int) $this->input('stock', 0);
        $data['is_active'] = $this->input('is_active') ? 1 : 0;
        $data['is_featured'] = $this->input('is_featured') ? 1 : 0;

        $this->productModel->update($id, $data);

        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $this->handleImageUploads($id);
        }

        Session::flash('success', 'Product bijgewerkt.');
        $this->redirect("/admin/products/{$id}/edit");
    }

    public function destroy(int $id): void
    {
        // Delete images
        $images = $this->imageModel->getByProduct($id);
        foreach ($images as $image) {
            FileUpload::delete('products/' . $image['filename']);
        }

        $this->productModel->delete($id);
        Session::flash('success', 'Product verwijderd.');
        $this->redirect('/admin/products');
    }

    public function deleteImage(): void
    {
        $imageId = (int) $this->input('image_id');
        $image = $this->imageModel->findById($imageId);

        if ($image) {
            FileUpload::delete('products/' . $image['filename']);
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

    private function handleImageUploads(int $productId): void
    {
        $existingCount = count($this->imageModel->getByProduct($productId));

        foreach ($_FILES['images']['name'] as $key => $name) {
            if (empty($name)) continue;

            $file = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key],
            ];

            $filename = FileUpload::uploadImage($file, 'products');
            if ($filename) {
                $this->imageModel->create([
                    'product_id' => $productId,
                    'filename' => $filename,
                    'sort_order' => $existingCount + $key,
                    'is_primary' => ($existingCount === 0 && $key === 0) ? 1 : 0,
                ]);
            }
        }
    }
}
