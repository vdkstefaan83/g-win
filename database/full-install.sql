-- ============================================================
-- G-WIN Full Install
-- Drop alles, maak tabellen aan en seed alle data
-- Gebruik: importeer dit bestand in phpMyAdmin of via CLI
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DROP ALL TABLES
-- ============================================================
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS appointment_slots;
DROP TABLE IF EXISTS page_sites;
DROP TABLE IF EXISTS block_sites;
DROP TABLE IF EXISTS menu_sites;
DROP TABLE IF EXISTS blocks;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menus;
DROP TABLE IF EXISTS page_images;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS page_categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS sites;

-- ============================================================
-- CREATE TABLES
-- ============================================================

CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    domain VARCHAR(255) NOT NULL,
    layout VARCHAR(50) NOT NULL DEFAULT 'gwin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'superadmin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(10),
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE page_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    lang CHAR(2) NOT NULL DEFAULT 'nl',
    translation_of INT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_slug (site_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    lang CHAR(2) NOT NULL DEFAULT 'nl',
    translation_of INT,
    page_category_id INT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT,
    intro_text TEXT,
    intro_image VARCHAR(255),
    meta_title VARCHAR(255),
    meta_description TEXT,
    is_published TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (page_category_id) REFERENCES page_categories(id) ON DELETE SET NULL,
    UNIQUE KEY unique_site_slug (site_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE page_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    lang CHAR(2) NOT NULL DEFAULT 'nl',
    name VARCHAR(100) NOT NULL,
    location ENUM('header', 'footer') DEFAULT 'header',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    page_id INT,
    parent_id INT,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    lang CHAR(2) NOT NULL DEFAULT 'nl',
    translation_of INT,
    title VARCHAR(255),
    subtitle VARCHAR(255),
    content TEXT,
    image VARCHAR(255),
    link_url VARCHAR(255),
    options JSON DEFAULT NULL,
    type ENUM('hero', 'feature', 'cta', 'text', 'gallery') DEFAULT 'text',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    type ENUM('pregnancy', 'child') NOT NULL,
    max_bookings INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    slot_id INT NOT NULL DEFAULT 0,
    type ENUM('pregnancy', 'child') NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    google_event_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    lang CHAR(2) NOT NULL DEFAULT 'nl',
    translation_of INT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description LONGTEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (translation_of) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    billing_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    mollie_id VARCHAR(255),
    method ENUM('bancontact', 'paypal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'pending', 'paid', 'failed', 'cancelled', 'refunded') DEFAULT 'open',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_key (site_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pivot tables for multi-site support
CREATE TABLE page_sites (
    page_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (page_id, site_id),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE block_sites (
    block_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (block_id, site_id),
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_sites (
    menu_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (menu_id, site_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED: Site
-- ============================================================
INSERT INTO sites (name, slug, domain, layout) VALUES
('G-Win', 'gwin', 'g-win.be', 'gwin');

-- ============================================================
-- SEED: Admin user (wachtwoord: admin123 - WIJZIG DIT!)
-- ============================================================
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@g-win.be', '$2y$12$iuuk5zOaDl7kkZlXhjLQd.ScIbC7tZm4jWhCAgNw1qgA.x3CPlFMy', 'superadmin');

-- ============================================================
-- SEED: Afspraak slots
-- ============================================================
INSERT INTO appointment_slots (day_of_week, start_time, end_time, type, max_bookings) VALUES
(6, '11:00:00', '12:15:00', 'pregnancy', 1),
(6, '12:15:00', '13:30:00', 'pregnancy', 1),
(6, '13:30:00', '14:45:00', 'pregnancy', 1),
(6, '14:45:00', '16:00:00', 'pregnancy', 1),
(6, '16:00:00', '17:15:00', 'pregnancy', 1),
(6, '17:15:00', '18:30:00', 'pregnancy', 1),
(0, '10:00:00', '18:00:00', 'child', 1);

-- ============================================================
-- SEED: Settings
-- ============================================================
INSERT INTO settings (site_id, setting_key, setting_value)
SELECT s.id, k, v FROM sites s
CROSS JOIN (
    SELECT 'company_name' AS k, 'G-WIN' AS v UNION ALL
    SELECT 'company_tagline', '3D Scanning & Sculpting | 3D.Beelden/Design.Awards' UNION ALL
    SELECT 'company_owner', 'Gwin Steenhoudt' UNION ALL
    SELECT 'company_address', 'Duivenstuk 4' UNION ALL
    SELECT 'company_city', '8531 Bavikhove' UNION ALL
    SELECT 'company_phone', '+32 (0)56 499 284' UNION ALL
    SELECT 'company_mobile', '+32 (0)479 94 80 20' UNION ALL
    SELECT 'company_email', 'info@gwin.be' UNION ALL
    SELECT 'company_vat', 'BE0837.145.236' UNION ALL
    SELECT 'social_facebook', 'https://www.facebook.com/gwin.be/' UNION ALL
    SELECT 'social_linkedin', 'https://www.linkedin.com/company/6596642' UNION ALL
    SELECT 'social_sketchfab', 'https://sketchfab.com/g-win' UNION ALL
    SELECT 'appointment_max_months', '24'
) AS seed
WHERE s.slug = 'gwin';

-- ============================================================
-- SEED: Pagina's
-- ============================================================

-- 3D Scannen
INSERT INTO pages (site_id, title, slug, content, meta_title, meta_description, is_published, sort_order)
SELECT s.id, '3D Scannen', '3d-scannen',
'<div class="space-y-12">
  <div class="text-center">
    <h1 class="text-4xl font-bold text-krijgers-800 mb-4">3D Scanning & Sculpting</h1>
    <p class="text-xl text-gray-600 max-w-3xl mx-auto">Dankzij een combinatie van moderne 3D-technologie (scanning | sculpting | printing) en verfijnd handwerk heeft G-Win zich ontwikkeld tot een veelzijdig en uniek productiehuis.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="bg-white rounded-xl shadow-lg p-6 text-center">
      <h3 class="text-xl font-bold text-krijgers-800 mb-3">Erfgoed | Kunst | Emotie Souvenirs</h3>
      <p class="text-gray-600">G-Win digitaliseert kunstwerken, erfgoedobjecten en emotioneel waardevolle objecten met 3D-scantechnologie voor digitale toepassingen.</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 text-center">
      <h3 class="text-xl font-bold text-krijgers-800 mb-3">Product Marketing | Virtuele Beurzen</h3>
      <p class="text-gray-600">360 productvisualisatie met 3D-modellen voor websites, virtuele beurzen en digitale productpresentaties die de aandacht trekken.</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 text-center">
      <h3 class="text-xl font-bold text-krijgers-800 mb-3">Industriele Scan- en Meetoplossingen</h3>
      <p class="text-gray-600">Industriele 3D-scan- en meetoplossingen in studio of op locatie voor prototyping, reverse engineering en kwaliteitscontrole.</p>
    </div>
  </div>
</div>',
'3D Scannen | G-WIN',
'3D-scanning voor talrijke toepassingen: producten, prototyping, reverse engineering, trofeeen, artwork en personen.',
1, 1
FROM sites s WHERE s.slug = 'gwin';

-- 3D Beelden & Design Awards
INSERT INTO pages (site_id, title, slug, content, meta_title, meta_description, is_published, sort_order)
SELECT s.id, '3D Beelden & Design Awards', '3d-beelden-design-awards',
'<div class="space-y-12">
  <div class="text-center">
    <h1 class="text-4xl font-bold text-krijgers-800 mb-4">3D Beelden & Design Awards</h1>
    <p class="text-xl text-gray-600 max-w-3xl mx-auto">Exclusieve Design Awards, Logo''s en Gepersonaliseerde 3D-Beeldjes. Moderne 3D-technologie gecombineerd met verfijnd handwerk leidt tot uitzonderlijke resultaten.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
      <div class="p-6">
        <h3 class="text-lg font-bold text-krijgers-800 mb-2">Dochter-Moeder</h3>
        <p class="text-gray-600 text-sm">Een dochter, net oma geworden, houdt de hand van haar bejaarde mama. Hoogte: 15 cm.</p>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
      <div class="p-6">
        <h3 class="text-lg font-bold text-krijgers-800 mb-2">Gouden Schoen Tessa</h3>
        <p class="text-gray-600 text-sm">Eretrofee voor de 4e Gouden Schoen van Tessa Wullaert - een unieke sportprestatie vereeuwigd in 3D.</p>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
      <div class="p-6">
        <h3 class="text-lg font-bold text-krijgers-800 mb-2">Babyhandje</h3>
        <p class="text-gray-600 text-sm">Levensecht 3D-sculptuur van een pasgeboren babyhandje op ware grootte.</p>
      </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
      <div class="p-6">
        <h3 class="text-lg font-bold text-krijgers-800 mb-2">Sterrenkindje</h3>
        <p class="text-gray-600 text-sm">Herdenkingssculptuur voor stilgeboren kinderen - een tastbare herinnering vol liefde.</p>
      </div>
    </div>
  </div>
  <div class="bg-krijgers-50 rounded-xl p-8 text-center">
    <h3 class="text-2xl font-bold text-krijgers-800 mb-4">Categorieen</h3>
    <div class="flex flex-wrap justify-center gap-3">
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">3D-beelden</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Awards in 3D</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Logo in 3D</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Kerst in 3D</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Rouwen in 3D</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Erotiek in 3D</span>
      <span class="bg-krijgers-800 text-white px-4 py-2 rounded-full text-sm">Zwanger in 3D</span>
    </div>
  </div>
</div>',
'3D Beelden & Design Awards | G-WIN',
'Exclusieve Design Awards, Logo''s en Gepersonaliseerde 3D-Beeldjes door G-WIN.',
1, 2
FROM sites s WHERE s.slug = 'gwin';

-- Zwangerschapsbeeldjes
INSERT INTO pages (site_id, title, slug, content, meta_title, meta_description, is_published, sort_order)
SELECT s.id, 'Zwangerschapsbeeldjes', 'zwangerschapsbeeldjes',
'<div class="space-y-12">
  <div class="text-center">
    <h1 class="text-4xl font-bold text-krijgers-800 mb-4">Zwangerschapsbeeldjes</h1>
    <p class="text-xl text-gray-600 max-w-3xl mx-auto">Stijlvolle zwangerschapsbeeldjes: een tastbare 3D-herinnering aan een unieke periode. Beschikbaar van 10 tot 25 cm in diverse materialen en afwerkingen.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">Kimono-versie</h3>
      <p class="text-gray-600 text-sm italic">"Wat ben ik bijzonder betoverd over het resultaat!! Het is fenomenaal!"</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">I.feel.your.little.body</h3>
      <p class="text-gray-600 text-sm">Gespecialiseerde uitvoering met nadruk op de zwangere buik.</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">Bronzen beeldje</h3>
      <p class="text-gray-600 text-sm italic">"Het bewaart de mooie zwangerschapsherinneringen voor altijd."</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">#3Dbelly</h3>
      <p class="text-gray-600 text-sm italic">"Het is toegekomen! Zoooo mooi!!"</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">#30weken</h3>
      <p class="text-gray-600 text-sm">Pocket-sized 3D-herinnering in Californisch koper.</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">#32weken</h3>
      <p class="text-gray-600 text-sm italic">"Wat een prachtig beeldje is het geworden!!"</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">Newborn baby</h3>
      <p class="text-gray-600 text-sm">3D-scan op 10 dagen oud met ondersteunende moederhand.</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-krijgers-800 mb-2">Voronoi Belly</h3>
      <p class="text-gray-600 text-sm">Artistieke geometrische patroonversie van het zwangerschapsbeeldje.</p>
    </div>
  </div>
  <div class="bg-krijgers-50 rounded-xl p-8 text-center">
    <h3 class="text-2xl font-bold text-krijgers-800 mb-4">Materialen & Afwerking</h3>
    <p class="text-gray-600 max-w-2xl mx-auto">Beschikbaar in luxe kunststoffen, keramische afwerkingen, brons en zilver. Elk beeldje wordt gepersonaliseerd qua hoogte, materiaal en kleur.</p>
    <div class="mt-6">
      <a href="/afspraken" class="inline-block bg-krijgers-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-krijgers-700 transition">Maak een afspraak</a>
    </div>
  </div>
</div>',
'Zwangerschapsbeeldjes | G-WIN',
'Stijlvolle 3D-zwangerschapsbeeldjes: een tastbare herinnering aan een unieke periode. Van 10 tot 25 cm.',
1, 3
FROM sites s WHERE s.slug = 'gwin';

-- Contact
INSERT INTO pages (site_id, title, slug, content, meta_title, meta_description, is_published, sort_order)
SELECT s.id, 'Contact', 'contact',
'<div class="max-w-4xl mx-auto">
  <h1 class="text-4xl font-bold text-krijgers-800 mb-8 text-center">Contact</h1>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
    <div>
      <h2 class="text-2xl font-bold text-krijgers-800 mb-6">Contactgegevens</h2>
      <div class="space-y-4">
        <div>
          <h3 class="font-semibold text-krijgers-800">Adres</h3>
          <p class="text-gray-600">Duivenstuk 4<br>8531 Bavikhove</p>
        </div>
        <div>
          <h3 class="font-semibold text-krijgers-800">Telefoon</h3>
          <p class="text-gray-600">Tel: +32 (0)56 499 284<br>GSM: +32 (0)479 94 80 20</p>
        </div>
        <div>
          <h3 class="font-semibold text-krijgers-800">E-mail</h3>
          <p class="text-gray-600"><a href="mailto:info@gwin.be" class="text-krijgers-gold-600 hover:underline">info@gwin.be</a></p>
        </div>
        <div>
          <h3 class="font-semibold text-krijgers-800">BTW</h3>
          <p class="text-gray-600">BE0837.145.236</p>
        </div>
      </div>
      <h2 class="text-2xl font-bold text-krijgers-800 mt-8 mb-4">Volg ons</h2>
      <div class="flex space-x-4">
        <a href="https://www.facebook.com/gwin.be/" target="_blank" class="text-krijgers-800 hover:text-krijgers-gold-600 transition">Facebook</a>
        <a href="https://www.linkedin.com/company/6596642" target="_blank" class="text-krijgers-800 hover:text-krijgers-gold-600 transition">LinkedIn</a>
        <a href="https://sketchfab.com/g-win" target="_blank" class="text-krijgers-800 hover:text-krijgers-gold-600 transition">Sketchfab</a>
      </div>
    </div>
    <div>
      <h2 class="text-2xl font-bold text-krijgers-800 mb-6">Locatie</h2>
      <div class="bg-gray-200 rounded-xl overflow-hidden" style="height: 400px;">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2520.8!2d3.35!3d50.87!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sDuivenstuk+4+Bavikhove!5e0!3m2!1snl!2sbe!4v1" width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
      </div>
    </div>
  </div>
</div>',
'Contact | G-WIN',
'Neem contact op met G-WIN voor 3D scanning, sculpting en beelden. Duivenstuk 4, 8531 Bavikhove.',
1, 4
FROM sites s WHERE s.slug = 'gwin';

-- Over ons
INSERT INTO pages (site_id, title, slug, content, meta_title, meta_description, is_published, sort_order)
SELECT s.id, 'Over ons', 'over-ons',
'<div class="max-w-4xl mx-auto space-y-12">
  <div class="text-center">
    <h1 class="text-4xl font-bold text-krijgers-800 mb-4">Over G-WIN</h1>
    <p class="text-xl text-gray-600">Moderne 3D-technologie gecombineerd met verfijnd handwerk</p>
  </div>
  <div class="prose prose-lg max-w-none">
    <p>Dankzij een combinatie van moderne 3D-technologie (scanning | sculpting | printing) en verfijnd handwerk heeft G-Win zich ontwikkeld tot een veelzijdig en uniek productiehuis.</p>
    <p>Onder leiding van <strong>Gwin Steenhoudt</strong> bedienen we klanten in kunst, erfgoed, PR, marketing, entertainment en industrie met een breed gamma aan diensten:</p>
    <ul>
      <li><strong>Artwork-on-Demand</strong> - Unieke kunstwerken op maat</li>
      <li><strong>Design Awards & Trofeeen</strong> - Exclusieve awards en gepersonaliseerde geschenken</li>
      <li><strong>Gelaatssculpturen</strong> - Fotorealistische 3D-figuren</li>
      <li><strong>Bronzen standbeelden</strong> - Monumentale en herdenkingsstukken</li>
      <li><strong>Zilveren juwelen</strong> - Miniatuur hangers en sieraden</li>
      <li><strong>3D-zwangerschapsbeeldjes</strong> - Tastbare herinneringen aan een unieke periode</li>
    </ul>
    <p>G-WIN draagt het erkend ambachtslabel - een bewijs van vakmanschap en kwaliteit.</p>
  </div>
</div>',
'Over ons | G-WIN',
'G-WIN: moderne 3D-technologie gecombineerd met verfijnd handwerk voor unieke 3D-beelden en sculpturen.',
1, 5
FROM sites s WHERE s.slug = 'gwin';

-- ============================================================
-- SEED: Paginacategorieen
-- ============================================================
INSERT INTO page_categories (site_id, name, slug, description, sort_order, is_active)
SELECT s.id, '3D Scannen', '3d-scannen', 'Ontdek onze 3D-scanning diensten voor erfgoed, kunst, industrie en marketing.', 1, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO page_categories (site_id, name, slug, description, sort_order, is_active)
SELECT s.id, '3D Beelden & Design Awards', '3d-beelden', 'Exclusieve Design Awards, Logo''s en Gepersonaliseerde 3D-Beeldjes.', 2, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO page_categories (site_id, name, slug, description, sort_order, is_active)
SELECT s.id, 'Zwangerschapsbeeldjes', 'zwangerschapsbeeldjes', 'Stijlvolle 3D-zwangerschapsbeeldjes: een tastbare herinnering aan een unieke periode.', 3, 1
FROM sites s WHERE s.slug = 'gwin';

-- Pagina's koppelen aan categorieen + intro teksten toevoegen
UPDATE pages SET
    page_category_id = (SELECT id FROM page_categories WHERE slug = '3d-scannen' LIMIT 1),
    intro_text = 'Dankzij moderne 3D-scantechnologie digitaliseert G-Win kunstwerken, erfgoedobjecten en industriele onderdelen met uitzonderlijke precisie.'
WHERE slug = '3d-scannen';

UPDATE pages SET
    page_category_id = (SELECT id FROM page_categories WHERE slug = '3d-beelden' LIMIT 1),
    intro_text = 'Exclusieve Design Awards, Logo''s en Gepersonaliseerde 3D-Beeldjes. Moderne technologie gecombineerd met verfijnd handwerk.'
WHERE slug = '3d-beelden-design-awards';

UPDATE pages SET
    page_category_id = (SELECT id FROM page_categories WHERE slug = 'zwangerschapsbeeldjes' LIMIT 1),
    intro_text = 'Stijlvolle 3D-zwangerschapsbeeldjes van 10 tot 25 cm in diverse materialen en afwerkingen.'
WHERE slug = 'zwangerschapsbeeldjes';

-- Extra voorbeeldpagina's binnen categorieen
INSERT INTO pages (site_id, page_category_id, title, slug, content, intro_text, is_published, sort_order)
SELECT s.id,
    (SELECT id FROM page_categories WHERE slug = '3d-scannen' LIMIT 1),
    'Erfgoed & Kunst', 'erfgoed-kunst',
    '<p>G-Win digitaliseert kunstwerken en erfgoedobjecten met 3D-scantechnologie. Van museumsculpturen tot historische artefacten - wij maken nauwkeurige digitale replica''s voor bewaring, restauratie en virtuele presentatie.</p>',
    'Digitalisering van kunstwerken en erfgoedobjecten met nauwkeurige 3D-scantechnologie.',
    1, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO pages (site_id, page_category_id, title, slug, content, intro_text, is_published, sort_order)
SELECT s.id,
    (SELECT id FROM page_categories WHERE slug = '3d-scannen' LIMIT 1),
    'Industriele Scanoplossingen', 'industriele-scanoplossingen',
    '<p>Industriele 3D-scan- en meetoplossingen in studio of op locatie. Ideaal voor prototyping, reverse engineering, kwaliteitscontrole en productieoptimalisatie.</p>',
    'Professionele 3D-scanoplossingen voor industrie: prototyping, reverse engineering en kwaliteitscontrole.',
    1, 2
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO pages (site_id, page_category_id, title, slug, content, intro_text, is_published, sort_order)
SELECT s.id,
    (SELECT id FROM page_categories WHERE slug = '3d-scannen' LIMIT 1),
    'Product Marketing & Virtuele Beurzen', 'product-marketing',
    '<p>360 productvisualisatie met 3D-modellen voor websites, virtuele beurzen en digitale productpresentaties. Laat uw producten tot leven komen met interactieve 3D-weergave.</p>',
    '360 productvisualisatie en interactieve 3D-modellen voor websites en virtuele beurzen.',
    1, 3
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO pages (site_id, page_category_id, title, slug, content, intro_text, is_published, sort_order)
SELECT s.id,
    (SELECT id FROM page_categories WHERE slug = '3d-beelden' LIMIT 1),
    'Awards & Trofeeen', 'awards-trofeeen',
    '<p>Unieke design awards en trofeeen volledig op maat. Van sportprijzen tot bedrijfsawards - elk stuk wordt individueel ontworpen en met de hand afgewerkt.</p>',
    'Op maat gemaakte design awards en trofeeen voor bedrijfsevenementen en sportprestaties.',
    1, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO pages (site_id, page_category_id, title, slug, content, intro_text, is_published, sort_order)
SELECT s.id,
    (SELECT id FROM page_categories WHERE slug = '3d-beelden' LIMIT 1),
    'Logo''s in 3D', 'logos-in-3d',
    '<p>Uw bedrijfslogo als driedimensionaal object. Perfect als relatiegeschenk, bureaudecoratie of opvallend element op uw beurs- of winkelinrichting.</p>',
    'Uw bedrijfslogo als uniek driedimensionaal object - perfect als relatiegeschenk of decoratie.',
    1, 2
FROM sites s WHERE s.slug = 'gwin';

-- ============================================================
-- SEED: Blokken (Homepage)
-- ============================================================

-- Hero
INSERT INTO blocks (site_id, title, subtitle, content, image, options, type, sort_order, is_active)
SELECT s.id,
'Uw momenten in 3D vereeuwigd',
'3D Scanning & Sculpting',
'Moderne technologie gecombineerd met verfijnd handwerk voor uitzonderlijke 3D beelden en sculpturen.',
'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&q=80',
'{"show_appointment_btn": true, "show_shop_btn": true}',
'hero', 1, 1
FROM sites s WHERE s.slug = 'gwin';

-- Features
INSERT INTO blocks (site_id, title, content, image, link_url, type, sort_order, is_active)
SELECT s.id,
'3D Scanning & Sculpting',
'3D-scanning voor talrijke toepassingen: producten, prototyping, reverse engineering, trofeeen, artwork en personen.',
'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=600&q=80',
'/3d-scannen',
'feature', 2, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO blocks (site_id, title, content, image, link_url, type, sort_order, is_active)
SELECT s.id,
'Zwangerschapsbeeldjes',
'Stijlvolle 3D-herinnering aan een unieke periode. Van 10 tot 25 cm in diverse materialen.',
'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=600&q=80',
'/zwangerschapsbeeldjes',
'feature', 3, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO blocks (site_id, title, content, image, link_url, type, sort_order, is_active)
SELECT s.id,
'Design Awards & 3D Art',
'Exclusieve Design Awards, Logo''s en Gepersonaliseerde 3D-Beeldjes voor elke gelegenheid.',
'https://images.unsplash.com/photo-1569163139599-0f4517e36f51?w=600&q=80',
'/3d-beelden-design-awards',
'feature', 4, 1
FROM sites s WHERE s.slug = 'gwin';

-- Gallery
INSERT INTO blocks (site_id, title, subtitle, image, type, sort_order, is_active)
SELECT s.id, 'Dochter-Moeder', 'Generatieverbinding, 15 cm',
'https://images.unsplash.com/photo-1561839561-b13bcfe95249?w=400&q=80',
'gallery', 10, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO blocks (site_id, title, subtitle, image, type, sort_order, is_active)
SELECT s.id, 'Gouden Schoen Tessa', 'Eretrofee sportprestatie',
'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&q=80',
'gallery', 11, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO blocks (site_id, title, subtitle, image, type, sort_order, is_active)
SELECT s.id, 'Babyhandje', 'Levensgroot sculptuur',
'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?w=400&q=80',
'gallery', 12, 1
FROM sites s WHERE s.slug = 'gwin';

INSERT INTO blocks (site_id, title, subtitle, image, type, sort_order, is_active)
SELECT s.id, 'Sterrenkindje', 'Herdenkingssculptuur',
'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&q=80',
'gallery', 13, 1
FROM sites s WHERE s.slug = 'gwin';

-- Text (About)
INSERT INTO blocks (site_id, title, subtitle, content, image, link_url, type, sort_order, is_active)
SELECT s.id,
'Vakmanschap ontmoet technologie',
'Over G-WIN',
'Dankzij een combinatie van moderne 3D-technologie en verfijnd handwerk heeft G-Win zich ontwikkeld tot een veelzijdig en uniek productiehuis. Onder leiding van Gwin Steenhoudt bedienen we klanten in kunst, erfgoed, marketing en industrie.',
'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=800&q=80',
'/over-ons',
'text', 20, 1
FROM sites s WHERE s.slug = 'gwin';

-- CTA
INSERT INTO blocks (site_id, title, content, image, type, sort_order, is_active)
SELECT s.id,
'Klaar om uw moment te vereeuwigen?',
'Neem contact met ons op voor een persoonlijk gesprek of maak direct een afspraak voor een 3D-scan sessie.',
'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=1920&q=80',
'cta', 30, 1
FROM sites s WHERE s.slug = 'gwin';

-- ============================================================
-- SEED: Categorieen
-- ============================================================
INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES
('3D Scannen', '3d-scannen', '3D scanning diensten voor diverse toepassingen', 1, 1),
('Design Awards', 'design-awards', 'Exclusieve design awards en trofeeen in 3D', 2, 1),
('Zwangerschapsbeeldjes', 'zwangerschapsbeeldjes', 'Tastbare 3D-herinneringen aan zwangerschap', 3, 1),
('Bronzen Beelden', 'bronzen-beelden', 'Bronzen standbeelden en herdenkingsstukken', 4, 1),
('Zilveren Juwelen', 'zilveren-juwelen', 'Zilveren hangers en miniatuur juwelen', 5, 1),
('Artwork-on-Demand', 'artwork-on-demand', 'Unieke kunstwerken op maat', 6, 1);

-- ============================================================
-- SEED: Producten
-- ============================================================
INSERT INTO products (category_id, name, slug, description, price, stock, is_active, is_featured) VALUES
((SELECT id FROM categories WHERE slug = 'zwangerschapsbeeldjes'), 'Zwangerschapsbeeldje Classic', 'zwangerschapsbeeldje-classic',
'<p>Een stijlvol 3D-zwangerschapsbeeldje in klassieke afwerking. Hoogte: 15 cm.</p>
<ul>
  <li>Hoogte: 15 cm</li>
  <li>Materiaal: Premium kunststof</li>
  <li>Afwerking: Wit mat</li>
  <li>Inclusief persoonlijke 3D-scan sessie</li>
</ul>', 249.00, 99, 1, 1),

((SELECT id FROM categories WHERE slug = 'zwangerschapsbeeldjes'), 'Zwangerschapsbeeldje Bronze', 'zwangerschapsbeeldje-bronze',
'<p>Luxe 3D-zwangerschapsbeeldje met bronzen afwerking. Hoogte: 15 cm.</p>
<ul>
  <li>Hoogte: 15 cm</li>
  <li>Materiaal: Kunststof met bronzen coating</li>
  <li>Afwerking: Brons metallic</li>
  <li>Inclusief persoonlijke 3D-scan sessie</li>
</ul>', 349.00, 99, 1, 1),

((SELECT id FROM categories WHERE slug = 'zwangerschapsbeeldjes'), 'Zwangerschapsbeeldje Groot', 'zwangerschapsbeeldje-groot',
'<p>Groter formaat 3D-zwangerschapsbeeldje voor een nog indrukwekkender resultaat. Hoogte: 25 cm.</p>
<ul>
  <li>Hoogte: 25 cm</li>
  <li>Materiaal: Premium kunststof</li>
  <li>Afwerking: Naar keuze</li>
  <li>Inclusief persoonlijke 3D-scan sessie</li>
</ul>', 399.00, 99, 1, 1),

((SELECT id FROM categories WHERE slug = 'zwangerschapsbeeldjes'), 'Zwangerschapsbeeldje Californisch Koper', 'zwangerschapsbeeldje-koper',
'<p>Pocket-sized 3D-herinnering in Californisch koper. Hoogte: 10 cm.</p>
<ul>
  <li>Hoogte: 10 cm</li>
  <li>Materiaal: Kunststof met koper afwerking</li>
  <li>Afwerking: Californisch koper</li>
  <li>Inclusief persoonlijke 3D-scan sessie</li>
</ul>', 199.00, 99, 1, 0),

((SELECT id FROM categories WHERE slug = 'design-awards'), 'Custom Design Award', 'custom-design-award',
'<p>Exclusieve design award volledig op maat. Ideaal voor bedrijfsevenementen, sportprestaties of jubilea.</p>
<ul>
  <li>Volledig gepersonaliseerd ontwerp</li>
  <li>3D-geprint en handmatig afgewerkt</li>
  <li>Diverse materialen en afwerkingen mogelijk</li>
  <li>Prijs op aanvraag - neem contact op voor een offerte</li>
</ul>', 0.00, 0, 1, 1),

((SELECT id FROM categories WHERE slug = 'bronzen-beelden'), 'Bronzen Herdenkingsbeeld', 'bronzen-herdenkingsbeeld',
'<p>Bronzen herdenkingsbeeld op maat. Geschikt voor memoriale stukken, sterrenkindje-beeldjes of bijzondere gelegenheden.</p>
<ul>
  <li>Echt brons</li>
  <li>Op maat gemaakt</li>
  <li>Handmatig afgewerkt</li>
  <li>Prijs op aanvraag</li>
</ul>', 0.00, 0, 1, 0),

((SELECT id FROM categories WHERE slug = 'zilveren-juwelen'), 'Zilveren Hanger Miniportret', 'zilveren-hanger-miniportret',
'<p>Uniek zilveren hangertje met 3D-miniportret. Een persoonlijk juweel om altijd bij je te dragen.</p>
<ul>
  <li>Materiaal: 925 sterling zilver</li>
  <li>Inclusief 3D-scan en ontwerp</li>
  <li>Handmatig afgewerkt</li>
</ul>', 179.00, 99, 1, 0);

-- ============================================================
-- SEED: Menu's
-- ============================================================

-- Header menu
INSERT INTO menus (site_id, name, location)
SELECT s.id, 'Hoofdnavigatie', 'header'
FROM sites s WHERE s.slug = 'gwin';

SET @header_menu_id = LAST_INSERT_ID();

INSERT INTO menu_items (menu_id, label, url, page_id, sort_order) VALUES
(@header_menu_id, 'Home', '/', NULL, 0),
(@header_menu_id, '3D Scannen', NULL, (SELECT id FROM pages WHERE slug = '3d-scannen' LIMIT 1), 1),
(@header_menu_id, '3D Beelden & Awards', NULL, (SELECT id FROM pages WHERE slug = '3d-beelden-design-awards' LIMIT 1), 2),
(@header_menu_id, 'Zwangerschapsbeeldjes', NULL, (SELECT id FROM pages WHERE slug = 'zwangerschapsbeeldjes' LIMIT 1), 3),
(@header_menu_id, 'Shop', '/shop', NULL, 4),
(@header_menu_id, 'Afspraken', '/afspraken', NULL, 5),
(@header_menu_id, 'Contact', NULL, (SELECT id FROM pages WHERE slug = 'contact' LIMIT 1), 6);

-- Footer menu
INSERT INTO menus (site_id, name, location)
SELECT s.id, 'Footernavigatie', 'footer'
FROM sites s WHERE s.slug = 'gwin';

SET @footer_menu_id = LAST_INSERT_ID();

INSERT INTO menu_items (menu_id, label, url, page_id, sort_order) VALUES
(@footer_menu_id, 'Home', '/', NULL, 0),
(@footer_menu_id, '3D Scannen', NULL, (SELECT id FROM pages WHERE slug = '3d-scannen' LIMIT 1), 1),
(@footer_menu_id, '3D Beelden & Awards', NULL, (SELECT id FROM pages WHERE slug = '3d-beelden-design-awards' LIMIT 1), 2),
(@footer_menu_id, 'Zwangerschapsbeeldjes', NULL, (SELECT id FROM pages WHERE slug = 'zwangerschapsbeeldjes' LIMIT 1), 3),
(@footer_menu_id, 'Contact', NULL, (SELECT id FROM pages WHERE slug = 'contact' LIMIT 1), 4),
(@footer_menu_id, 'Over ons', NULL, (SELECT id FROM pages WHERE slug = 'over-ons' LIMIT 1), 5);

-- ============================================================
-- SEED: Multi-site pivot data (koppel alles aan de gwin site)
-- ============================================================
INSERT INTO page_sites (page_id, site_id) SELECT id, site_id FROM pages WHERE site_id IS NOT NULL;
INSERT INTO block_sites (block_id, site_id) SELECT id, site_id FROM blocks WHERE site_id IS NOT NULL;
INSERT INTO menu_sites (menu_id, site_id) SELECT id, site_id FROM menus WHERE site_id IS NOT NULL;
