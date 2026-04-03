-- Update pregnancy flow (type_id=1) with complete steps
-- First remove old steps
DELETE FROM appointment_flow_steps WHERE appointment_type_id = 1;

-- Full pregnancy flow: date → time → details → payment → emails
INSERT INTO appointment_flow_steps (appointment_type_id, step_type, label_nl, label_fr, config, sort_order) VALUES
(1, 'date_picker', 'Datum kiezen', 'Choisir une date', '{"day_of_week": "6"}', 0),
(1, 'time_picker', 'Tijdstip kiezen', 'Choisir un créneau', '{}', 1),
(1, 'details_form', 'Uw gegevens', 'Vos coordonnées', '{}', 2),
(1, 'send_email', 'Betaalverzoek', 'Demande de paiement', '{"template_slug": "payment_request", "trigger": "on_confirm"}', 3),
(1, 'payment', 'Betaling', 'Paiement', '{"deposit_from_settings": true}', 4),
(1, 'send_email', 'Bevestiging', 'Confirmation', '{"template_slug": "payment_confirmed", "trigger": "on_payment"}', 5),
(1, 'send_email', 'Herinnering afspraak', 'Rappel rendez-vous', '{"template_slug": "pre_appointment_reminder", "trigger": "on_reminder"}', 6);

-- Update child flow (type_id=2) with complete steps
DELETE FROM appointment_flow_steps WHERE appointment_type_id = 2;

INSERT INTO appointment_flow_steps (appointment_type_id, step_type, label_nl, label_fr, config, sort_order) VALUES
(2, 'date_picker', 'Datum kiezen', 'Choisir une date', '{"day_of_week": "0"}', 0),
(2, 'details_form', 'Uw gegevens', 'Vos coordonnées', '{}', 1),
(2, 'send_email', 'Betaalverzoek', 'Demande de paiement', '{"template_slug": "payment_request", "trigger": "on_confirm"}', 2),
(2, 'payment', 'Betaling', 'Paiement', '{"deposit_from_settings": true}', 3),
(2, 'send_email', 'Bevestiging', 'Confirmation', '{"template_slug": "payment_confirmed", "trigger": "on_payment"}', 4),
(2, 'send_email', 'Herinnering afspraak', 'Rappel rendez-vous', '{"template_slug": "pre_appointment_reminder", "trigger": "on_reminder"}', 5);
