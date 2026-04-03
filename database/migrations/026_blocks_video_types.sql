-- Add video block types
ALTER TABLE blocks MODIFY COLUMN type ENUM('hero', 'feature', 'cta', 'text', 'gallery', 'youtube', 'vimeo') DEFAULT 'text';
