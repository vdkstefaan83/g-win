-- Add sort_order to products
ALTER TABLE products ADD COLUMN sort_order INT DEFAULT 0 AFTER is_featured;
