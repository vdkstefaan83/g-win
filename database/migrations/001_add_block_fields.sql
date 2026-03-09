-- Add link_url and subtitle to blocks for richer homepage sections
ALTER TABLE blocks ADD COLUMN link_url VARCHAR(255) DEFAULT NULL AFTER image;
ALTER TABLE blocks ADD COLUMN subtitle VARCHAR(255) DEFAULT NULL AFTER title;
