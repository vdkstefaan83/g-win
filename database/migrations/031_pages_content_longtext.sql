-- Increase content column size for pages and blocks to support inline images
ALTER TABLE pages MODIFY COLUMN content LONGTEXT;
ALTER TABLE blocks MODIFY COLUMN content LONGTEXT;
