-- Multi-site support: pages, blocks and menus can belong to multiple sites

CREATE TABLE IF NOT EXISTS page_sites (
    page_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (page_id, site_id),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS block_sites (
    block_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (block_id, site_id),
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_sites (
    menu_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (menu_id, site_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrate existing data from site_id columns to pivot tables
INSERT IGNORE INTO page_sites (page_id, site_id) SELECT id, site_id FROM pages WHERE site_id IS NOT NULL;
INSERT IGNORE INTO block_sites (block_id, site_id) SELECT id, site_id FROM blocks WHERE site_id IS NOT NULL;
INSERT IGNORE INTO menu_sites (menu_id, site_id) SELECT id, site_id FROM menus WHERE site_id IS NOT NULL;
