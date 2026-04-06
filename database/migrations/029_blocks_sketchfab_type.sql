-- Add sketchfab to block types
ALTER TABLE blocks MODIFY COLUMN type ENUM('hero', 'feature', 'cta', 'text', 'gallery', 'youtube', 'vimeo', 'sketchfab') DEFAULT 'text';
