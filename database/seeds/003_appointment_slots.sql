-- Pregnancy appointment slots (Saturday = day 6)
-- Slots: 11:00, 12:15, 13:30, 14:45, 16:00, 17:15
INSERT INTO appointment_slots (day_of_week, start_time, end_time, type, max_bookings) VALUES
(6, '11:00:00', '12:15:00', 'pregnancy', 1),
(6, '12:15:00', '13:30:00', 'pregnancy', 1),
(6, '13:30:00', '14:45:00', 'pregnancy', 1),
(6, '14:45:00', '16:00:00', 'pregnancy', 1),
(6, '16:00:00', '17:15:00', 'pregnancy', 1),
(6, '17:15:00', '18:30:00', 'pregnancy', 1);

-- Child appointment slots (Sunday = day 0)
-- Full day available, admin will assign specific time
INSERT INTO appointment_slots (day_of_week, start_time, end_time, type, max_bookings) VALUES
(0, '10:00:00', '18:00:00', 'child', 1);
