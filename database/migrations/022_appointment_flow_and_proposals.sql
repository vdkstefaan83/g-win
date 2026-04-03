-- Flow steps table (if not created by migration 021)
CREATE TABLE IF NOT EXISTS appointment_flow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_type_id INT NOT NULL,
    step_type ENUM('date_picker','time_picker','date_proposals','details_form','send_email','payment') NOT NULL,
    label_nl VARCHAR(100) DEFAULT '',
    label_fr VARCHAR(100) DEFAULT '',
    config JSON DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed flow steps for existing types (pregnancy=1, child=2)
INSERT IGNORE INTO appointment_flow_steps (appointment_type_id, step_type, label_nl, label_fr, config, sort_order) VALUES
(1, 'date_picker', 'Datum kiezen', 'Choisir une date', '{"day_of_week": "6"}', 0),
(1, 'time_picker', 'Tijdstip kiezen', 'Choisir un créneau', '{}', 1),
(1, 'details_form', 'Uw gegevens', 'Vos coordonnées', '{}', 2),
(2, 'date_picker', 'Datum kiezen', 'Choisir une date', '{"day_of_week": "0"}', 0),
(2, 'details_form', 'Uw gegevens', 'Vos coordonnées', '{}', 1);

-- Date proposals table for "Afhalen" type
CREATE TABLE IF NOT EXISTS appointment_date_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    proposed_date DATE NOT NULL,
    proposed_time TIME NOT NULL,
    is_selected TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
