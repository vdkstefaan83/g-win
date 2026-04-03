-- Add 'services' to the location ENUM
ALTER TABLE menus MODIFY COLUMN location ENUM('header', 'footer', 'services') NOT NULL;
