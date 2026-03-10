-- Appointment payment flow: extend payments table and appointments table

-- Make order_id nullable and add appointment support to payments
ALTER TABLE payments
    MODIFY COLUMN order_id INT NULL,
    ADD COLUMN appointment_id INT NULL AFTER order_id,
    ADD COLUMN payment_type ENUM('order','appointment') NOT NULL DEFAULT 'order' AFTER appointment_id,
    ADD FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE;

-- Add payment tracking fields to appointments
ALTER TABLE appointments
    ADD COLUMN payment_status ENUM('none','pending','paid','overdue','cancelled') DEFAULT 'none' AFTER status,
    ADD COLUMN payment_deadline DATETIME NULL AFTER payment_status,
    ADD COLUMN reminder_sent_at DATETIME NULL AFTER payment_deadline,
    ADD COLUMN reminder_deadline DATETIME NULL AFTER reminder_sent_at,
    ADD COLUMN pre_reminder_sent_at DATETIME NULL AFTER reminder_deadline,
    ADD COLUMN deposit_amount DECIMAL(10,2) NULL AFTER pre_reminder_sent_at,
    ADD COLUMN payment_token VARCHAR(64) NULL UNIQUE AFTER deposit_amount,
    ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'nl' AFTER payment_token;

-- Notification audit log
CREATE TABLE IF NOT EXISTS appointment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    type ENUM('payment_request','payment_reminder','payment_confirmed','cancellation','pre_appointment_reminder') NOT NULL,
    channel ENUM('email','sms') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent','failed') DEFAULT 'sent',
    details TEXT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
