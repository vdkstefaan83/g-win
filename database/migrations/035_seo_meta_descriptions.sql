-- =====================================================
-- SEO Meta Descriptions voor alle pagina's en settings
-- =====================================================

-- SETTINGS: Tagline en OG image
INSERT INTO settings (setting_key, setting_value, site_id) VALUES
('site_tagline', '3D Scanning & Sculpting — Unieke 3D-beelden van uw dierbaarste momenten', NULL)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO settings (setting_key, setting_value, site_id) VALUES
('site_og_image', 'https://gwin.vanderkerken.com/assets/images/gwin_liggend.png', NULL)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- =====================================================
-- NL PAGINA'S — meta_description
-- =====================================================

-- Page 1: 3D Scannen (cat: 3D Scan)
UPDATE pages SET meta_description = 'G‑Win digitaliseert kunstwerken, erfgoedobjecten en industriële onderdelen met professionele 3D-scantechnologie. Perfecte basis voor verdere verwerking, analyse of creatie.' WHERE id = 1 AND (meta_description IS NULL OR meta_description = '');

-- Page 2: Ontwerp gepersonaliseerde kunst (cat: 3D Art)
UPDATE pages SET meta_description = 'Exclusieve 3D-beelden, design awards en gepersonaliseerde kunstwerken door G‑Win. Moderne 3D-technologie gecombineerd met verfijnd ambachtswerk.' WHERE id = 2 AND (meta_description IS NULL OR meta_description = '');

-- Page 3: Zwangerschapsbeeldjes (cat: Zwangerschap)
UPDATE pages SET meta_description = 'Een zwangerschapsbeeldje van G‑Win: de tastbare herinnering aan het mooiste begin. Geavanceerde 3D-technologie met zorg en gevoel. Maak een afspraak.' WHERE id = 3 AND (meta_description IS NULL OR meta_description = '');

-- Page 4: Contact
UPDATE pages SET meta_description = 'Neem contact op met G‑Win voor vragen over 3D-scanning, zwangerschapsbeeldjes, design awards of bestellingen. Duivenstuk 4, 8531 Bavikhove.' WHERE id = 4 AND (meta_description IS NULL OR meta_description = '');

-- Page 5: Over ons
UPDATE pages SET meta_description = 'Het verhaal van G‑Win: pionier in 3D-technologie sinds 2014. Vakmanschap, innovatie en passie voor het vastleggen van waardevolle momenten.' WHERE id = 5 AND (meta_description IS NULL OR meta_description = '');

-- Page 6: Erfgoed & Kunst (cat: 3D Scan)
UPDATE pages SET meta_description = 'G‑Win digitaliseert kunstwerken en erfgoedobjecten met 3D-scantechnologie. Van museumsculpturen tot historische artefacten — precieze digitale replica''s.' WHERE id = 6 AND (meta_description IS NULL OR meta_description = '');

-- Page 7: Industriële Scanoplossingen (cat: 3D Scan)
UPDATE pages SET meta_description = 'Industriële 3D-scan- en meetoplossingen door G‑Win. Prototyping, reverse engineering en kwaliteitscontrole in studio of op locatie.' WHERE id = 7 AND (meta_description IS NULL OR meta_description = '');

-- Page 8: Product Marketing (cat: 3D Scan)
UPDATE pages SET meta_description = '360° productvisualisatie met 3D-modellen voor websites, virtuele beurzen en digitale productpresentaties door G‑Win.' WHERE id = 8 AND (meta_description IS NULL OR meta_description = '');

-- Page 9: Awards & Trofeeën (cat: 3D Art)
UPDATE pages SET meta_description = 'Unieke 3D-awards en trofeeën op maat door G‑Win. Van sportprestaties tot bedrijfsprijzen — elk stuk individueel ontworpen en handmatig afgewerkt.' WHERE id = 9 AND (meta_description IS NULL OR meta_description = '');

-- Page 10: Logo's in 3D (cat: 3D Art) — if exists
UPDATE pages SET meta_description = 'Uw bedrijfslogo als driedimensionaal object door G‑Win. Perfect als bedrijfscadeau, bureaudecoratie of blikvanger voor uw stand.' WHERE id = 10 AND (meta_description IS NULL OR meta_description = '');

-- Page 21: Sterrenkindje (cat: Sterrenkindje)
UPDATE pages SET meta_description = 'G‑Win creëert met zorg en delicatesse 3D-herinneringen van uw baby. Een tastbaar aandenken dat troost biedt en voor altijd gekoesterd wordt.' WHERE id = 21 AND (meta_description IS NULL OR meta_description = '');

-- Page 23: Hoe werkt het (cat: Zwangerschap)
UPDATE pages SET meta_description = 'Hoe verloopt een 3D-scan bij G‑Win? Van afspraak tot ontvangst van uw zwangerschapsbeeldje — alle stappen uitgelegd.' WHERE id = 23 AND (meta_description IS NULL OR meta_description = '');

-- Page 24: Newborn (cat: Zwangerschap)
UPDATE pages SET meta_description = 'Newborn beeldjes door G‑Win: leg de eerste kostbare momenten van uw baby vast in een tijdloos 3D-kunstwerk. Handmatig afgewerkt met zorg.' WHERE id = 24 AND (meta_description IS NULL OR meta_description = '');

-- Page 26: Placenta art (cat: Zwangerschap)
UPDATE pages SET meta_description = 'Placenta art door G‑Win: het wonder van nieuw leven vereeuwigd als kunstwerk. Een uniek aandenken aan de band tussen moeder en kind.' WHERE id = 26 AND (meta_description IS NULL OR meta_description = '');

-- Page 30: Sterrenkindje verloop (cat: Sterrenkindje)
UPDATE pages SET meta_description = 'Praktisch verloop en begeleiding bij G‑Win voor sterrenkindje-beeldjes. Met respect, warmte en alle tijd die nodig is.' WHERE id = 30 AND (meta_description IS NULL OR meta_description = '');

-- Page 32: Sterrenkindje urnes (cat: Sterrenkindje)
-- Already has meta_description, skip

-- =====================================================
-- FR PAGINA'S — meta_description
-- =====================================================

-- Page 11: Scan 3D (FR of page 1)
UPDATE pages SET meta_description = 'G‑Win numérise œuvres d''art, objets patrimoniaux et pièces industrielles avec une technologie de scan 3D professionnelle.' WHERE id = 11 AND (meta_description IS NULL OR meta_description = '');

-- Page 12: Sculptures 3D (FR of page 2)
UPDATE pages SET meta_description = 'Sculptures 3D exclusives, design awards et œuvres d''art personnalisées par G‑Win. Technologie 3D moderne combinée à un savoir-faire artisanal.' WHERE id = 12 AND (meta_description IS NULL OR meta_description = '');

-- Page 13: Sculptures de grossesse (FR of page 3)
UPDATE pages SET meta_description = 'Une figurine de grossesse G‑Win : le souvenir tangible du plus beau commencement. Technologie 3D avancée avec soin et émotion. Prenez rendez-vous.' WHERE id = 13 AND (meta_description IS NULL OR meta_description = '');

-- Page 14: Contact FR
UPDATE pages SET meta_description = 'Contactez G‑Win pour toute question sur la numérisation 3D, figurines de grossesse, design awards ou commandes. Duivenstuk 4, 8531 Bavikhove.' WHERE id = 14 AND (meta_description IS NULL OR meta_description = '');

-- Page 15: Over ons FR (if exists)
UPDATE pages SET meta_description = 'L''histoire de G‑Win : pionnier en technologie 3D depuis 2014. Savoir-faire, innovation et passion pour immortaliser les moments précieux.' WHERE id = 15 AND (meta_description IS NULL OR meta_description = '');

-- Page 16-18: FR subcategories of 3D Scan
UPDATE pages SET meta_description = 'G‑Win numérise des œuvres d''art et objets patrimoniaux grâce à une technologie de scan 3D de haute précision.' WHERE id = 16 AND (meta_description IS NULL OR meta_description = '');
UPDATE pages SET meta_description = 'Solutions de scan et mesure 3D industrielles par G‑Win : prototypage, rétro-ingénierie et contrôle qualité.' WHERE id = 17 AND (meta_description IS NULL OR meta_description = '');
UPDATE pages SET meta_description = 'Visualisation produit à 360° avec modèles 3D pour sites web et salons virtuels par G‑Win.' WHERE id = 18 AND (meta_description IS NULL OR meta_description = '');

-- Page 19-20: FR subcategories of 3D Art
UPDATE pages SET meta_description = 'Awards et trophées 3D uniques sur mesure par G‑Win. Chaque pièce conçue individuellement et finie à la main.' WHERE id = 19 AND (meta_description IS NULL OR meta_description = '');
UPDATE pages SET meta_description = 'Votre logo d''entreprise en objet tridimensionnel par G‑Win. Parfait comme cadeau d''affaires ou décoration.' WHERE id = 20 AND (meta_description IS NULL OR meta_description = '');

-- Page 22: Sterrenkindje FR (if exists)
UPDATE pages SET meta_description = 'G‑Win crée avec soin et délicatesse des souvenirs 3D de votre bébé. Un souvenir tangible qui réconforte et se chérit pour toujours.' WHERE id = 22 AND (meta_description IS NULL OR meta_description = '');

-- Page 25: Nouveau né FR
UPDATE pages SET meta_description = 'Figurines nouveau-né par G‑Win : immortalisez les premiers instants précieux de votre bébé dans une œuvre 3D intemporelle.' WHERE id = 25 AND (meta_description IS NULL OR meta_description = '');

-- Page 27: Placenta art FR (if exists)
UPDATE pages SET meta_description = 'Placenta art par G‑Win : le miracle de la vie immortalisé en œuvre d''art. Un souvenir unique du lien entre mère et enfant.' WHERE id = 27 AND (meta_description IS NULL OR meta_description = '');

-- Page 28: Comment ça marche FR
UPDATE pages SET meta_description = 'Comment se déroule un scan 3D chez G‑Win ? De la prise de rendez-vous à la réception de votre figurine — toutes les étapes expliquées.' WHERE id = 28 AND (meta_description IS NULL OR meta_description = '');

-- Page 31: Processus Sterrenkindje FR
UPDATE pages SET meta_description = 'Processus pratique et accompagnement chez G‑Win pour les figurines enfant étoile. Avec respect, chaleur et tout le temps nécessaire.' WHERE id = 31 AND (meta_description IS NULL OR meta_description = '');

-- Page 33: Urnes FR — already has meta_description, skip

-- =====================================================
-- PAGINACATEGORIEËN — update beschrijvingen voor SEO
-- =====================================================

-- Cat 7: Sterrenkindje NL — verbeteren
UPDATE page_categories SET description = 'Herdenkingsbeeldjes voor stilgeboren kinderen. G‑Win creëert met zorg en respect een tastbaar aandenken dat troost biedt.' WHERE id = 7 AND description = 'Always close by, even when you are not there.';

-- Cat 8: Enfant d'étoile FR — verbeteren
UPDATE page_categories SET description = 'Figurines commémoratives pour enfants mort-nés. G‑Win crée avec soin et respect un souvenir tangible qui apporte du réconfort.' WHERE id = 8 AND description = 'Always close by, even when you are not there.';
