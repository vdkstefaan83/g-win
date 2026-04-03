-- Add page_id to blocks so blocks can be assigned to specific pages
-- NULL = homepage (current behavior), page_id = show on that page
ALTER TABLE blocks ADD COLUMN page_id INT NULL AFTER site_id;
ALTER TABLE blocks ADD INDEX idx_blocks_page_id (page_id);
