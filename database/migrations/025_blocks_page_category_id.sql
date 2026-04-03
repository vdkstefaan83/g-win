-- Add page_category_id to blocks for category-level blocks
ALTER TABLE blocks ADD COLUMN page_category_id INT NULL AFTER page_id;
ALTER TABLE blocks ADD INDEX idx_blocks_page_category_id (page_category_id);
