-- Add language support to categories (product categories) table
ALTER TABLE categories ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER parent_id;
ALTER TABLE categories ADD COLUMN translation_of INT NULL AFTER lang;
ALTER TABLE categories ADD FOREIGN KEY fk_categories_translation (translation_of) REFERENCES categories(id) ON DELETE SET NULL;
