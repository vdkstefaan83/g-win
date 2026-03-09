-- Add options JSON column to blocks table
ALTER TABLE blocks ADD COLUMN options JSON DEFAULT NULL AFTER link_url;

-- Set default options for existing hero blocks
UPDATE blocks SET options = '{"show_appointment_btn": true, "show_shop_btn": true}' WHERE type = 'hero';
