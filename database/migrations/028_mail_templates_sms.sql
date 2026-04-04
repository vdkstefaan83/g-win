-- Add SMS text fields to mail_templates
ALTER TABLE mail_templates ADD COLUMN sms_nl TEXT AFTER body_fr;
ALTER TABLE mail_templates ADD COLUMN sms_fr TEXT AFTER sms_nl;

-- Seed SMS texts for existing templates
UPDATE mail_templates SET
    sms_nl = 'G-Win: Betaal uw voorschot van €{bedrag} vóór {deadline} om uw afspraak te bevestigen. {betaallink}',
    sms_fr = 'G-Win: Payez votre acompte de €{bedrag} avant le {deadline} pour confirmer votre rendez-vous. {betaallink}'
WHERE slug = 'payment_request';

UPDATE mail_templates SET
    sms_nl = 'G-Win: Herinnering! Betaal vóór {deadline} of uw afspraak wordt geannuleerd. {betaallink}',
    sms_fr = 'G-Win: Rappel! Payez avant le {deadline} ou votre rendez-vous sera annulé. {betaallink}'
WHERE slug = 'payment_reminder';

UPDATE mail_templates SET
    sms_nl = 'G-Win: Uw afspraak voor {type} op {datum} is bevestigd. Adres: Duivenstuk 4, Bavikhove.',
    sms_fr = 'G-Win: Votre rendez-vous pour {type} le {datum} est confirmé. Adresse: Duivenstuk 4, Bavikhove.'
WHERE slug = 'payment_confirmed';

UPDATE mail_templates SET
    sms_nl = 'G-Win: Uw afspraak van {datum} is geannuleerd (betaling niet ontvangen).',
    sms_fr = 'G-Win: Votre rendez-vous du {datum} a été annulé (paiement non reçu).'
WHERE slug = 'cancellation';

UPDATE mail_templates SET
    sms_nl = 'G-Win: Herinnering! Uw afspraak is op {datum}{tijdstip_zin}. Adres: Duivenstuk 4, Bavikhove.',
    sms_fr = 'G-Win: Rappel! Votre rendez-vous est prévu le {datum}{tijdstip_zin}. Adresse: Duivenstuk 4, Bavikhove.'
WHERE slug = 'pre_appointment_reminder';
