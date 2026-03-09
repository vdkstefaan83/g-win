-- Page categories table
CREATE TABLE IF NOT EXISTS page_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_slug (site_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add new columns to pages table
ALTER TABLE pages ADD COLUMN page_category_id INT NULL AFTER site_id;
ALTER TABLE pages ADD COLUMN intro_text TEXT NULL AFTER content;
ALTER TABLE pages ADD COLUMN intro_image VARCHAR(255) NULL AFTER intro_text;
ALTER TABLE pages ADD FOREIGN KEY (page_category_id) REFERENCES page_categories(id) ON DELETE SET NULL;

-- Page images table
CREATE TABLE IF NOT EXISTS page_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
