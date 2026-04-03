-- Mail Templates table
CREATE TABLE IF NOT EXISTS mail_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    subject_nl VARCHAR(255) NOT NULL,
    subject_fr VARCHAR(255) DEFAULT '',
    body_nl LONGTEXT NOT NULL,
    body_fr LONGTEXT DEFAULT '',
    available_variables TEXT COMMENT 'Comma-separated placeholders for admin reference',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed existing hardcoded email templates
INSERT INTO mail_templates (name, slug, subject_nl, subject_fr, body_nl, body_fr, available_variables) VALUES
(
    'Betaalverzoek',
    'payment_request',
    'Betaalverzoek - Afspraak G-Win',
    'Demande de paiement - Rendez-vous G-Win',
    '<h2>Betaalverzoek</h2><p>Beste {voornaam},</p><p>Bedankt voor uw afspraak voor <strong>{type}</strong> op <strong>{datum}</strong>{tijdstip_zin}.</p><p>Om uw afspraak te bevestigen, gelieve een voorschot van <strong>€{bedrag}</strong> te betalen vóór <strong>{deadline}</strong>.</p><p style=\"margin:20px 0;\"><a href=\"{betaallink}\" style=\"display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;\">Nu betalen</a></p><p>Indien de betaling niet op tijd gebeurt, wordt uw afspraak geannuleerd.</p><p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Demande de paiement</h2><p>Cher(e) {voornaam},</p><p>Merci pour votre rendez-vous pour <strong>{type}</strong> le <strong>{datum}</strong>{tijdstip_zin}.</p><p>Pour confirmer votre rendez-vous, veuillez payer un acompte de <strong>€{bedrag}</strong> avant le <strong>{deadline}</strong>.</p><p style=\"margin:20px 0;\"><a href=\"{betaallink}\" style=\"display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;\">Payer maintenant</a></p><p>Si le paiement n''est pas effectué à temps, votre rendez-vous sera annulé.</p><p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,datum,tijdstip,tijdstip_zin,type,bedrag,deadline,betaallink'
),
(
    'Herinnering betaling',
    'payment_reminder',
    'Herinnering betaling - Afspraak G-Win',
    'Rappel de paiement - Rendez-vous G-Win',
    '<h2>Herinnering betaling</h2><p>Beste {voornaam},</p><p>We hebben uw betaling voor uw afspraak bij G-Win nog niet ontvangen.</p><p>Gelieve de betaling uit te voeren vóór <strong>{deadline}</strong>, anders wordt uw afspraak automatisch geannuleerd.</p><p style=\"margin:20px 0;\"><a href=\"{betaallink}\" style=\"display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;\">Nu betalen</a></p><p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Rappel de paiement</h2><p>Cher(e) {voornaam},</p><p>Nous n''avons pas encore reçu votre paiement pour votre rendez-vous chez G-Win.</p><p>Veuillez effectuer le paiement avant le <strong>{deadline}</strong>, sinon votre rendez-vous sera automatiquement annulé.</p><p style=\"margin:20px 0;\"><a href=\"{betaallink}\" style=\"display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;\">Payer maintenant</a></p><p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,deadline,betaallink'
),
(
    'Betaling bevestigd',
    'payment_confirmed',
    'Afspraak bevestigd - G-Win',
    'Rendez-vous confirmé - G-Win',
    '<h2>Afspraak bevestigd</h2><p>Beste {voornaam},</p><p>Uw betaling is ontvangen. Uw afspraak voor <strong>{type}</strong> op <strong>{datum}</strong>{tijdstip_zin} is bevestigd.</p><div style=\"background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;\"><strong>Praktische info:</strong><br>• Elke afspraak duurt ongeveer 60-90 minuten<br>• Kom 10 minuten voor uw afspraak<br>• Adres: Duivenstuk 4, 8531 Bavikhove</div><p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Rendez-vous confirmé</h2><p>Cher(e) {voornaam},</p><p>Votre paiement a été reçu. Votre rendez-vous pour <strong>{type}</strong> le <strong>{datum}</strong>{tijdstip_zin} est confirmé.</p><div style=\"background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;\"><strong>Informations pratiques:</strong><br>• Chaque rendez-vous dure environ 60-90 minutes<br>• Arrivez 10 minutes avant votre rendez-vous<br>• Adresse: Duivenstuk 4, 8531 Bavikhove</div><p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,datum,tijdstip,tijdstip_zin,type'
),
(
    'Annulering',
    'cancellation',
    'Afspraak geannuleerd - G-Win',
    'Rendez-vous annulé - G-Win',
    '<h2>Afspraak geannuleerd</h2><p>Beste {voornaam},</p><p>Uw afspraak op <strong>{datum}</strong> is geannuleerd omdat de betaling niet op tijd is ontvangen.</p><p>Wilt u een nieuwe afspraak maken? Bezoek dan onze website.</p><p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Rendez-vous annulé</h2><p>Cher(e) {voornaam},</p><p>Votre rendez-vous du <strong>{datum}</strong> a été annulé car le paiement n''a pas été effectué à temps.</p><p>Si vous souhaitez prendre un nouveau rendez-vous, visitez notre site web.</p><p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,datum'
),
(
    'Herinnering afspraak',
    'pre_appointment_reminder',
    'Herinnering afspraak - G-Win',
    'Rappel rendez-vous - G-Win',
    '<h2>Herinnering aan uw afspraak</h2><p>Beste {voornaam},</p><p>We herinneren u aan uw afspraak voor <strong>{type}</strong> op <strong>{datum}</strong>{tijdstip_zin}.</p><div style=\"background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;\"><strong>Praktische info:</strong><br>• Kom 10 minuten voor uw afspraak<br>• Adres: Duivenstuk 4, 8531 Bavikhove<br>• Telefoon: +32 (0)56 499 284</div><p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Rappel de votre rendez-vous</h2><p>Cher(e) {voornaam},</p><p>Nous vous rappelons votre rendez-vous pour <strong>{type}</strong> le <strong>{datum}</strong>{tijdstip_zin}.</p><div style=\"background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;\"><strong>Informations pratiques:</strong><br>• Arrivez 10 minutes avant votre rendez-vous<br>• Adresse: Duivenstuk 4, 8531 Bavikhove<br>• Téléphone: +32 (0)56 499 284</div><p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,datum,tijdstip,tijdstip_zin,type'
),
(
    'Bestelbevestiging',
    'order_confirmation',
    'Bestelbevestiging #{bestelnummer} - G-Win',
    'Confirmation de commande #{bestelnummer} - G-Win',
    '<h2>Bestelbevestiging</h2><p>Beste {voornaam},</p><p>Bedankt voor uw bestelling <strong>#{bestelnummer}</strong>.</p>{bestel_items}<p>Met vriendelijke groet,<br>G-Win</p>',
    '<h2>Confirmation de commande</h2><p>Cher(e) {voornaam},</p><p>Merci pour votre commande <strong>#{bestelnummer}</strong>.</p>{bestel_items}<p>Cordialement,<br>G-Win</p>',
    'voornaam,achternaam,bestelnummer,totaal,bestel_items,adres'
);
