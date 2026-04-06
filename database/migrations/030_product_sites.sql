-- Product-site pivot table
CREATE TABLE IF NOT EXISTS product_sites (
    product_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (product_id, site_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link all existing products to site 1 (G-Win)
INSERT IGNORE INTO product_sites (product_id, site_id)
SELECT id, 1 FROM products WHERE translation_of IS NULL;
