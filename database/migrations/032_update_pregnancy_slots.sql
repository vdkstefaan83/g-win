-- Update pregnancy time slots to new schedule
DELETE FROM appointment_slots WHERE type = 'pregnancy';

INSERT INTO appointment_slots (day_of_week, start_time, end_time, type, appointment_type_id, max_bookings, is_active) VALUES
(6, '11:00:00', '12:00:00', 'pregnancy', 1, 1, 1),
(6, '13:30:00', '14:30:00', 'pregnancy', 1, 1, 1),
(6, '14:45:00', '15:45:00', 'pregnancy', 1, 1, 1),
(6, '16:00:00', '17:00:00', 'pregnancy', 1, 1, 1),
(6, '17:15:00', '18:15:00', 'pregnancy', 1, 1, 1);

-- Set slot info text
INSERT INTO settings (setting_key, setting_value, site_id) VALUES
('appointment_slot_info_nl', 'Wij nemen 60 minuten de tijd om samen iets bijzonders te creëren. Kom stipt op tijd, zodat jullie deze kostbare momenten volledig kunnen benutten. Uit respect voor alle families kunnen we helaas verloren tijd niet verschuiven, zodat iedereen dezelfde zorg en aandacht krijgt.', NULL)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO settings (setting_key, setting_value, site_id) VALUES
('appointment_slot_info_fr', 'Nous prenons 60 minutes pour créer ensemble quelque chose de spécial. Arrivez à l''heure pour profiter pleinement de ces moments précieux. Par respect pour toutes les familles, nous ne pouvons malheureusement pas décaler le temps perdu, afin que chacun reçoive les mêmes soins et la même attention.', NULL)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
