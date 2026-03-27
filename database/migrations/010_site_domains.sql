-- site_domains: multiple domains per site with default language
CREATE TABLE IF NOT EXISTS site_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    default_lang CHAR(2) NOT NULL DEFAULT 'nl',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrate existing domain data from sites table to site_domains
INSERT IGNORE INTO site_domains (site_id, domain, default_lang, is_primary)
SELECT id, domain, 'nl', 1 FROM sites WHERE domain IS NOT NULL AND domain != '';
