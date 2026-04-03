-- Appointment Types table
CREATE TABLE IF NOT EXISTS appointment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name_nl VARCHAR(100) NOT NULL,
    name_fr VARCHAR(100) DEFAULT '',
    description_nl TEXT,
    description_fr TEXT,
    icon VARCHAR(50) DEFAULT NULL COMMENT 'Icon identifier for front-end',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed existing hardcoded types
INSERT INTO appointment_types (slug, name_nl, name_fr, description_nl, description_fr, sort_order) VALUES
('pregnancy', 'Zwangerschapsbeeldje', 'Sculpture de grossesse', 'Zaterdagen, vaste tijdsloten van 60-90 min', 'Samedis, créneaux fixes de 60-90 min', 1),
('child', 'Beeldje met kind', 'Sculpture avec enfant', 'Zondagen, tijdstip in overleg', 'Dimanches, horaire à convenir', 2);

-- Change type columns from ENUM to VARCHAR for flexibility
ALTER TABLE appointments MODIFY COLUMN type VARCHAR(50) NOT NULL;
ALTER TABLE appointment_slots MODIFY COLUMN type VARCHAR(50) NOT NULL;

-- Add appointment_type_id foreign key columns
ALTER TABLE appointments ADD COLUMN appointment_type_id INT NULL AFTER type;
ALTER TABLE appointments ADD INDEX idx_appointment_type_id (appointment_type_id);

ALTER TABLE appointment_slots ADD COLUMN appointment_type_id INT NULL AFTER type;
ALTER TABLE appointment_slots ADD INDEX idx_slot_type_id (appointment_type_id);

-- Backfill appointment_type_id from type slug
UPDATE appointments a
  JOIN appointment_types at ON at.slug = a.type
  SET a.appointment_type_id = at.id;

UPDATE appointment_slots s
  JOIN appointment_types at ON at.slug = s.type
  SET s.appointment_type_id = at.id;
