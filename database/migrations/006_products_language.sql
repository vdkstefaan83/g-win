-- Add language support to products table
ALTER TABLE products ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER category_id;
ALTER TABLE products ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE products ADD FOREIGN KEY fk_products_translation (translation_of) REFERENCES products(id) ON DELETE SET NULL;
