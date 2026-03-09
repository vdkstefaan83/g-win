-- ============================================================
-- G-WIN Content Rollback - Removes all content from 010_gwin_content.sql
-- Run this to undo the content seed
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- MENU ITEMS & MENUS (for gwin site)
-- ============================================================
DELETE mi FROM menu_items mi
INNER JOIN menus m ON mi.menu_id = m.id
INNER JOIN sites s ON m.site_id = s.id
WHERE s.slug = 'gwin';

DELETE m FROM menus m
INNER JOIN sites s ON m.site_id = s.id
WHERE s.slug = 'gwin';

-- ============================================================
-- PRODUCTS
-- ============================================================
DELETE FROM products WHERE slug IN (
    'zwangerschapsbeeldje-classic',
    'zwangerschapsbeeldje-bronze',
    'zwangerschapsbeeldje-groot',
    'zwangerschapsbeeldje-koper',
    'custom-design-award',
    'bronzen-herdenkingsbeeld',
    'zilveren-hanger-miniportret'
);

-- ============================================================
-- CATEGORIES (only the ones we added)
-- ============================================================
DELETE FROM categories WHERE slug IN (
    '3d-scannen',
    'design-awards',
    'zwangerschapsbeeldjes',
    'bronzen-beelden',
    'zilveren-juwelen',
    'artwork-on-demand'
);

-- ============================================================
-- BLOCKS (for gwin site)
-- ============================================================
DELETE b FROM blocks b
INNER JOIN sites s ON b.site_id = s.id
WHERE s.slug = 'gwin';

-- ============================================================
-- PAGES (for gwin site)
-- ============================================================
DELETE p FROM pages p
INNER JOIN sites s ON p.site_id = s.id
WHERE s.slug = 'gwin';

-- ============================================================
-- SETTINGS (for gwin site)
-- ============================================================
DELETE st FROM settings st
INNER JOIN sites s ON st.site_id = s.id
WHERE s.slug = 'gwin'
AND st.setting_key IN (
    'company_name', 'company_tagline', 'company_owner',
    'company_address', 'company_city', 'company_phone',
    'company_mobile', 'company_email', 'company_vat',
    'social_facebook', 'social_linkedin', 'social_sketchfab'
);

SET FOREIGN_KEY_CHECKS = 1;
