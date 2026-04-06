<?php

use App\Controllers\Front\HomeController;
use App\Controllers\Front\PageController;
use App\Controllers\Front\AppointmentController;
use App\Controllers\Front\ShopController;
use App\Controllers\Front\CartController;
use App\Controllers\Front\CheckoutController;
use App\Controllers\Front\PaymentController;
use App\Controllers\Front\AuthController as FrontAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\SiteController;
use App\Controllers\Admin\PageController as AdminPageController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\BlockController;
use App\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\SettingController;
use App\Controllers\Admin\GoogleCalendarController;
use App\Controllers\Admin\PageCategoryController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\MailTemplateController;
use App\Controllers\Admin\AppointmentTypeController;

/** @var \Bramus\Router\Router $router */

// ============================================================
// Front-end routes
// ============================================================

$router->get('/', function () {
    (new HomeController())->index();
});

// Front auth
$router->get('/login', function () {
    (new FrontAuthController())->loginForm();
});
$router->post('/login', function () {
    (new FrontAuthController())->login();
});
$router->get('/register', function () {
    (new FrontAuthController())->registerForm();
});
$router->post('/register', function () {
    (new FrontAuthController())->register();
});
$router->get('/logout', function () {
    (new FrontAuthController())->logout();
});

// Appointments (NL + FR)
$router->get('/afspraken', function () {
    (new AppointmentController())->index();
});
$router->get('/rendez-vous', function () {
    (new AppointmentController())->index();
});
$router->post('/afspraken', function () {
    (new AppointmentController())->store();
});
$router->post('/rendez-vous', function () {
    (new AppointmentController())->store();
});
$router->get('/afspraken/bevestiging/{id}', function ($id) {
    (new AppointmentController())->confirm((int)$id);
});
$router->get('/rendez-vous/confirmation/{id}', function ($id) {
    (new AppointmentController())->confirm((int)$id);
});

// Appointment payment flow
$router->get('/afspraken/betalen/{token}', function ($token) {
    (new AppointmentController())->pay($token);
});
$router->get('/rendez-vous/betalen/{token}', function ($token) {
    (new AppointmentController())->pay($token);
});
$router->get('/afspraken/betaling/succes/{id}', function ($id) {
    (new AppointmentController())->paymentSuccess((int)$id);
});
$router->get('/rendez-vous/betaling/succes/{id}', function ($id) {
    (new AppointmentController())->paymentSuccess((int)$id);
});

// API - Appointment slots (AJAX)
$router->get('/api/appointment-slots', function () {
    (new AppointmentController())->getAvailableSlots();
});

// Dynamic appointment type flows (must be after specific routes)
$router->get('/afspraken/{slug}', function ($slug) {
    (new AppointmentController())->flow($slug);
});
$router->get('/rendez-vous/{slug}', function ($slug) {
    (new AppointmentController())->flow($slug);
});
$router->post('/afspraken/{slug}', function ($slug) {
    (new AppointmentController())->storeFlow($slug);
});
$router->post('/rendez-vous/{slug}', function ($slug) {
    (new AppointmentController())->storeFlow($slug);
});

// Shop (NL + FR)
$router->get('/shop', function () {
    (new ShopController())->index();
});
$router->get('/boutique', function () {
    (new ShopController())->index();
});
$router->get('/shop/categorie/{slug}', function ($slug) {
    (new ShopController())->category($slug);
});
$router->get('/boutique/categorie/{slug}', function ($slug) {
    (new ShopController())->category($slug);
});
$router->get('/shop/product/(.*)', function ($slug) {
    (new ShopController())->show(urldecode($slug));
});
$router->get('/boutique/produit/(.*)', function ($slug) {
    (new ShopController())->show(urldecode($slug));
});

// Cart (NL + FR)
$router->get('/winkelwagen', function () {
    (new CartController())->index();
});
$router->get('/panier', function () {
    (new CartController())->index();
});
$router->post('/api/cart/add', function () {
    (new CartController())->add();
});
$router->post('/api/cart/update', function () {
    (new CartController())->update();
});
$router->post('/api/cart/remove', function () {
    (new CartController())->remove();
});

// Checkout (NL + FR)
$router->get('/afrekenen', function () {
    (new CheckoutController())->index();
});
$router->get('/paiement', function () {
    (new CheckoutController())->index();
});
$router->post('/afrekenen', function () {
    (new CheckoutController())->process();
});
$router->post('/paiement', function () {
    (new CheckoutController())->process();
});
$router->get('/betaling/succes', function () {
    (new PaymentController())->returnSuccess();
});
$router->get('/paiement/succes', function () {
    (new PaymentController())->returnSuccess();
});
$router->get('/betaling/annulatie', function () {
    (new PaymentController())->cancel();
});
$router->get('/paiement/annulation', function () {
    (new PaymentController())->cancel();
});
$router->post('/webhook/mollie', function () {
    (new PaymentController())->webhook();
});

// ============================================================
// Admin routes
// ============================================================

$router->get('/admin/login', function () {
    (new AdminAuthController())->loginForm();
});
$router->post('/admin/login', function () {
    (new AdminAuthController())->login();
});
$router->get('/admin/logout', function () {
    (new AdminAuthController())->logout();
});

// Dashboard
$router->get('/admin', function () {
    (new DashboardController())->index();
});

// Sites
$router->get('/admin/sites', function () {
    (new SiteController())->index();
});
$router->get('/admin/sites/create', function () {
    (new SiteController())->create();
});
$router->post('/admin/sites/store', function () {
    (new SiteController())->store();
});
$router->get('/admin/sites/{id}/edit', function ($id) {
    (new SiteController())->edit((int)$id);
});
$router->post('/admin/sites/{id}/update', function ($id) {
    (new SiteController())->update((int)$id);
});
$router->post('/admin/sites/{id}/delete', function ($id) {
    (new SiteController())->destroy((int)$id);
});

// Page Categories
$router->get('/admin/page-categories', function () {
    (new PageCategoryController())->index();
});
$router->get('/admin/page-categories/create', function () {
    (new PageCategoryController())->create();
});
$router->post('/admin/page-categories/store', function () {
    (new PageCategoryController())->store();
});
$router->get('/admin/page-categories/{id}/edit', function ($id) {
    (new PageCategoryController())->edit((int)$id);
});
$router->post('/admin/page-categories/{id}/update', function ($id) {
    (new PageCategoryController())->update((int)$id);
});
$router->post('/admin/page-categories/{id}/delete', function ($id) {
    (new PageCategoryController())->destroy((int)$id);
});

// Pages
$router->get('/admin/pages', function () {
    (new AdminPageController())->index();
});
$router->get('/admin/pages/create', function () {
    (new AdminPageController())->create();
});
$router->post('/admin/pages/store', function () {
    (new AdminPageController())->store();
});
$router->post('/admin/pages/images/delete', function () {
    (new AdminPageController())->deleteImage();
});
$router->post('/admin/pages/images/reorder', function () {
    (new AdminPageController())->reorderImages();
});
$router->post('/admin/pages/reorder', function () {
    (new AdminPageController())->reorder();
});
$router->get('/admin/pages/{id}/edit', function ($id) {
    (new AdminPageController())->edit((int)$id);
});
$router->post('/admin/pages/{id}/update', function ($id) {
    (new AdminPageController())->update((int)$id);
});
$router->post('/admin/pages/{id}/delete', function ($id) {
    (new AdminPageController())->destroy((int)$id);
});

// Menus
$router->get('/admin/menus', function () {
    (new MenuController())->index();
});
$router->get('/admin/menus/create', function () {
    (new MenuController())->create();
});
$router->post('/admin/menus/store', function () {
    (new MenuController())->store();
});
$router->get('/admin/menus/{id}/edit', function ($id) {
    (new MenuController())->edit((int)$id);
});
$router->post('/admin/menus/{id}/update', function ($id) {
    (new MenuController())->update((int)$id);
});
$router->post('/admin/menus/{id}/delete', function ($id) {
    (new MenuController())->destroy((int)$id);
});
$router->post('/admin/menus/items/reorder', function () {
    (new MenuController())->reorder();
});

// Blocks
$router->get('/admin/blocks', function () {
    (new BlockController())->index();
});
$router->get('/admin/blocks/create', function () {
    (new BlockController())->create();
});
$router->post('/admin/blocks/store', function () {
    (new BlockController())->store();
});
$router->post('/admin/blocks/reorder', function () {
    (new BlockController())->reorder();
});
$router->get('/admin/blocks/{id}/edit', function ($id) {
    (new BlockController())->edit((int)$id);
});
$router->post('/admin/blocks/{id}/update', function ($id) {
    (new BlockController())->update((int)$id);
});
$router->post('/admin/blocks/{id}/delete', function ($id) {
    (new BlockController())->destroy((int)$id);
});

// Appointment Types
$router->get('/admin/appointment-types', function () {
    (new AppointmentTypeController())->index();
});
$router->get('/admin/appointment-types/create', function () {
    (new AppointmentTypeController())->create();
});
$router->post('/admin/appointment-types/store', function () {
    (new AppointmentTypeController())->store();
});
$router->get('/admin/appointment-types/{id}/edit', function ($id) {
    (new AppointmentTypeController())->edit((int)$id);
});
$router->post('/admin/appointment-types/{id}/update', function ($id) {
    (new AppointmentTypeController())->update((int)$id);
});
$router->post('/admin/appointment-types/{id}/delete', function ($id) {
    (new AppointmentTypeController())->destroy((int)$id);
});

// Mail Templates
$router->get('/admin/mail-templates', function () {
    (new MailTemplateController())->index();
});
$router->get('/admin/mail-templates/create', function () {
    (new MailTemplateController())->create();
});
$router->post('/admin/mail-templates/store', function () {
    (new MailTemplateController())->store();
});
$router->get('/admin/mail-templates/{id}/edit', function ($id) {
    (new MailTemplateController())->edit((int)$id);
});
$router->post('/admin/mail-templates/{id}/update', function ($id) {
    (new MailTemplateController())->update((int)$id);
});
$router->post('/admin/mail-templates/{id}/delete', function ($id) {
    (new MailTemplateController())->destroy((int)$id);
});

// Appointments
$router->get('/admin/appointments', function () {
    (new AdminAppointmentController())->index();
});
$router->get('/admin/appointments/{id}', function ($id) {
    (new AdminAppointmentController())->show((int)$id);
});
$router->get('/admin/appointments/{id}/edit', function ($id) {
    (new AdminAppointmentController())->edit((int)$id);
});
$router->post('/admin/appointments/{id}/update', function ($id) {
    (new AdminAppointmentController())->update((int)$id);
});
$router->post('/admin/appointments/{id}/confirm', function ($id) {
    (new AdminAppointmentController())->confirm((int)$id);
});
$router->post('/admin/appointments/{id}/cancel', function ($id) {
    (new AdminAppointmentController())->cancelAppointment((int)$id);
});
$router->post('/admin/appointments/block-date', function () {
    (new AdminAppointmentController())->blockDate();
});
$router->post('/admin/appointments/unblock-date', function () {
    (new AdminAppointmentController())->unblockDate();
});

// API - Admin appointments (AJAX filtering)
$router->get('/api/admin/appointments', function () {
    (new AdminAppointmentController())->filter();
});

// Customers
$router->get('/admin/customers', function () {
    (new CustomerController())->index();
});
$router->get('/admin/customers/{id}', function ($id) {
    (new CustomerController())->show((int)$id);
});
$router->get('/admin/customers/{id}/edit', function ($id) {
    (new CustomerController())->edit((int)$id);
});
$router->post('/admin/customers/{id}/update', function ($id) {
    (new CustomerController())->update((int)$id);
});

// API - Admin customers (AJAX filtering)
$router->get('/api/admin/customers', function () {
    (new CustomerController())->filter();
});

// Products
$router->get('/admin/products', function () {
    (new ProductController())->index();
});
$router->get('/admin/products/create', function () {
    (new ProductController())->create();
});
$router->post('/admin/products/store', function () {
    (new ProductController())->store();
});
$router->get('/admin/products/{id}/edit', function ($id) {
    (new ProductController())->edit((int)$id);
});
$router->post('/admin/products/{id}/update', function ($id) {
    (new ProductController())->update((int)$id);
});
$router->post('/admin/products/{id}/delete', function ($id) {
    (new ProductController())->destroy((int)$id);
});
$router->post('/admin/products/images/delete', function () {
    (new ProductController())->deleteImage();
});
$router->post('/admin/products/images/reorder', function () {
    (new ProductController())->reorderImages();
});

// Categories
$router->get('/admin/categories', function () {
    (new CategoryController())->index();
});
$router->get('/admin/categories/create', function () {
    (new CategoryController())->create();
});
$router->post('/admin/categories/store', function () {
    (new CategoryController())->store();
});
$router->get('/admin/categories/{id}/edit', function ($id) {
    (new CategoryController())->edit((int)$id);
});
$router->post('/admin/categories/{id}/update', function ($id) {
    (new CategoryController())->update((int)$id);
});
$router->post('/admin/categories/{id}/delete', function ($id) {
    (new CategoryController())->destroy((int)$id);
});

// Orders
$router->get('/admin/orders', function () {
    (new OrderController())->index();
});
$router->get('/admin/orders/{id}', function ($id) {
    (new OrderController())->show((int)$id);
});
$router->post('/admin/orders/{id}/update-status', function ($id) {
    (new OrderController())->updateStatus((int)$id);
});

// API - Admin orders (AJAX filtering)
$router->get('/api/admin/orders', function () {
    (new OrderController())->filter();
});

// Users
$router->get('/admin/users', function () {
    (new UserController())->index();
});
$router->get('/admin/users/create', function () {
    (new UserController())->create();
});
$router->post('/admin/users/store', function () {
    (new UserController())->store();
});
$router->get('/admin/users/{id}/edit', function ($id) {
    (new UserController())->edit((int)$id);
});
$router->post('/admin/users/{id}/update', function ($id) {
    (new UserController())->update((int)$id);
});
$router->post('/admin/users/{id}/delete', function ($id) {
    (new UserController())->destroy((int)$id);
});

// Settings
$router->get('/admin/settings', function () {
    (new SettingController())->index();
});
$router->post('/admin/settings', function () {
    (new SettingController())->update();
});

// Google Calendar
$router->get('/admin/google-calendar', function () {
    (new GoogleCalendarController())->index();
});
$router->get('/admin/google-calendar/authorize', function () {
    (new GoogleCalendarController())->authorize();
});
$router->get('/admin/google-calendar/callback', function () {
    (new GoogleCalendarController())->callback();
});
$router->post('/admin/google-calendar/disconnect', function () {
    (new GoogleCalendarController())->disconnect();
});

// ============================================================
// CMS page catch-all (MUST be last)
// ============================================================
$router->get('/{category}/{page}', function ($category, $page) {
    (new PageController())->showCategoryPage($category, $page);
});
$router->get('/{slug}', function ($slug) {
    (new PageController())->showOrCategory($slug);
});
