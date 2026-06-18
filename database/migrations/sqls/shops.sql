-- Shops: Credit Shop / Club Shop / Rare Shop
-- ----------------------------------------------------------------------------
-- - Renames the stock "Furni" top tab to "Credit Shop".
-- - Adds two top-level catalog tabs: "Club Shop" (visible to all, but items
--   require Habbo Club to purchase — enforced by CatalogPurchaseEventHandler via
--   catalog_items.requires_club_membership) and "Rare Shop" (rares; credit cost
--   for now, Solana later).
-- - Rare Shop = all Eggs, Dragons, Thrones (furniture only, excludes wearables).
-- - Club Shop = green club lounges (club_sofa) + all dice.
-- - Seeds the HABBO_CLUB subscription (internal key kept as HABBO_CLUB because the
--   emulator checks that name; the visible name is changed to "Solana Club" in
--   ExternalTexts.json) and grants 'dev' membership so the Club Shop is testable.
-- Idempotent. Catalog is read from DB per request, so no emulator restart needed;
-- relogin once for the new subscription to load.
-- ----------------------------------------------------------------------------

-- 1) Rename Furni -> Credit Shop
UPDATE catalog_pages SET caption = 'Credit Shop' WHERE id = 25 AND name = 'furni';

-- 2) Club subscription + grant dev membership (so the Club Shop is buyable/testable)
INSERT INTO subscriptions (name)
SELECT 'HABBO_CLUB' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM subscriptions WHERE name = 'HABBO_CLUB');

INSERT INTO player_subscriptions (player_id, subscription_id, created_at, expires_at)
SELECT p.id, s.id, NOW(6), DATE_ADD(NOW(6), INTERVAL 3650 DAY)
FROM players p CROSS JOIN subscriptions s
WHERE p.username = 'dev' AND s.name = 'HABBO_CLUB'
  AND NOT EXISTS (SELECT 1 FROM player_subscriptions ps WHERE ps.player_id = p.id AND ps.subscription_id = s.id);

-- 3) Top-level shop pages (tabs)
INSERT INTO catalog_pages (name, caption, layout, role_id, catalog_page_id, order_id, icon_id, enabled, visible, images_json, texts_json)
SELECT 'club_shop','Club Shop','default_3x3',NULL,NULL,2,0,1,1,'[]','[]' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM catalog_pages WHERE name = 'club_shop');

INSERT INTO catalog_pages (name, caption, layout, role_id, catalog_page_id, order_id, icon_id, enabled, visible, images_json, texts_json)
SELECT 'rare_shop','Rare Shop','default_3x3',NULL,NULL,3,0,1,1,'[]','[]' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM catalog_pages WHERE name = 'rare_shop');

SET @club_page = (SELECT id FROM catalog_pages WHERE name = 'club_shop' LIMIT 1);
SET @rare_page = (SELECT id FROM catalog_pages WHERE name = 'rare_shop' LIMIT 1);

-- 4) Clear any prior items on these pages (idempotent re-run)
DELETE cif FROM catalog_item_furniture_item cif
  JOIN catalog_items ci ON ci.id = cif.catalog_items_id
  WHERE ci.catalog_page_id IN (@club_page, @rare_page);
DELETE FROM catalog_items WHERE catalog_page_id IN (@club_page, @rare_page);

-- 5) Rare Shop items: all Eggs, Dragons, Thrones (furniture only). 100 credits each.
INSERT INTO catalog_items (name, cost_credits, cost_points, cost_points_type, requires_club_membership, meta_data, amount, stack_limit, sell_limit, catalog_page_id)
SELECT fi.asset_name, 100, 0, 0, 0, NULL, 1, 0, 0, @rare_page
FROM furniture_items fi
WHERE fi.asset_name NOT LIKE 'clothing%' AND (
     (fi.name LIKE '%Egg%'    AND fi.name NOT LIKE '%Egg Chair%' AND fi.name NOT LIKE '%Leggy%' AND fi.name NOT LIKE '%Earring%' AND fi.name NOT LIKE '%Keyring%' AND fi.name NOT LIKE '%Crown%' AND fi.name NOT LIKE '%Leggings%' AND fi.name NOT LIKE '%Eggnog%' AND fi.name NOT LIKE '%Eggshell%')
  OR (fi.name LIKE '%Dragon%' AND fi.name NOT LIKE '%Dragonfly%' AND fi.name NOT LIKE '%Wings%' AND fi.name NOT LIKE '%Mask%' AND fi.name NOT LIKE '%Crown%' AND fi.name NOT LIKE '%Outfit%' AND fi.name NOT LIKE '%Cap%' AND fi.name NOT LIKE '%Print%' AND fi.name NOT LIKE '%Familiar%' AND fi.name NOT LIKE '%Baby%')
  OR (fi.name LIKE '%Throne%')
);
INSERT INTO catalog_item_furniture_item (catalog_items_id, furniture_items_id)
SELECT ci.id, fi.id FROM catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name WHERE ci.catalog_page_id = @rare_page;
UPDATE catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name SET ci.name = fi.name WHERE ci.catalog_page_id = @rare_page;

-- 6) Club Shop items: green club lounges + all dice. 50 credits, club-only.
INSERT INTO catalog_items (name, cost_credits, cost_points, cost_points_type, requires_club_membership, meta_data, amount, stack_limit, sell_limit, catalog_page_id)
SELECT fi.asset_name, 50, 0, 0, 1, NULL, 1, 0, 0, @club_page
FROM furniture_items fi
WHERE fi.asset_name IN (
   'club_sofa','nft_a0club_sofa',
   'edice','edicehc','CF_350_d20dice',
   'hobbies_c26_d4','hobbies_c26_d6','hobbies_c26_d8','hobbies_c26_d10','hobbies_c26_d12','hobbies_c26_d20',
   'hobbies_c26_ornated4','hobbies_c26_ornated6','hobbies_c26_ornated8','hobbies_c26_ornated10','hobbies_c26_ornated12','hobbies_c26_ornated20',
   'hobbies_c26_dicetray');
INSERT INTO catalog_item_furniture_item (catalog_items_id, furniture_items_id)
SELECT ci.id, fi.id FROM catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name WHERE ci.catalog_page_id = @club_page;
UPDATE catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name SET ci.name = fi.name WHERE ci.catalog_page_id = @club_page;
