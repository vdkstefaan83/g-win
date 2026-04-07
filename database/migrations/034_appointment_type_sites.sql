-- Appointment type - site pivot table
CREATE TABLE IF NOT EXISTS appointment_type_sites (
    appointment_type_id INT NOT NULL,
    site_id INT NOT NULL,
    PRIMARY KEY (appointment_type_id, site_id),
    FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link all existing types to all sites
INSERT IGNORE INTO appointment_type_sites (appointment_type_id, site_id)
SELECT at.id, s.id FROM appointment_types at CROSS JOIN sites s;
