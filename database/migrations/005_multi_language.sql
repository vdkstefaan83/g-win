-- Multi-language support: add lang and translation_of columns
-- Default 'nl' for all existing content

-- Pages
ALTER TABLE pages ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER site_id;
ALTER TABLE pages ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE pages ADD FOREIGN KEY fk_pages_translation (translation_of) REFERENCES pages(id) ON DELETE SET NULL;

-- Blocks
ALTER TABLE blocks ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER site_id;
ALTER TABLE blocks ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE blocks ADD FOREIGN KEY fk_blocks_translation (translation_of) REFERENCES blocks(id) ON DELETE SET NULL;

-- Page Categories
ALTER TABLE page_categories ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER site_id;
ALTER TABLE page_categories ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE page_categories ADD FOREIGN KEY fk_page_categories_translation (translation_of) REFERENCES page_categories(id) ON DELETE SET NULL;

-- Menus
ALTER TABLE menus ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER site_id;

-- Products
ALTER TABLE products ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER category_id;
ALTER TABLE products ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE products ADD FOREIGN KEY fk_products_translation (translation_of) REFERENCES products(id) ON DELETE SET NULL;
