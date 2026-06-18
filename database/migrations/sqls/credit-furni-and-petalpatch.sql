-- New top-level "Credit Furni" shop (the exchangeable credit coins/bars) + Petal
-- Patch added to the Rare Shop. Idempotent. Requires an emulator restart (catalog
-- page list is startup-cached).

-- 1) Credit Furni tab
INSERT INTO catalog_pages (name, caption, layout, role_id, catalog_page_id, order_id, icon_id, enabled, visible, images_json, texts_json)
SELECT 'credit_furni','Credit Furni','default_3x3',NULL,NULL,5,0,1,1,'[]','[]' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM catalog_pages WHERE name='credit_furni');
SET @cf_page = (SELECT id FROM catalog_pages WHERE name='credit_furni' LIMIT 1);

DELETE cif FROM catalog_item_furniture_item cif JOIN catalog_items ci ON ci.id=cif.catalog_items_id WHERE ci.catalog_page_id=@cf_page;
DELETE FROM catalog_items WHERE catalog_page_id=@cf_page;

INSERT INTO catalog_items (name, cost_credits, cost_points, cost_points_type, requires_club_membership, meta_data, amount, stack_limit, sell_limit, catalog_page_id)
SELECT fi.asset_name,
  CASE fi.asset_name
    WHEN 'CF_1_coin_bronze' THEN 1
    WHEN 'CF_5_coin_silver' THEN 5
    WHEN 'CF_10_coin_gold'  THEN 10
    WHEN 'CF_20_moneybag'   THEN 20
    WHEN 'CF_50_goldbar'    THEN 50
  END, 0, 0, 0, NULL, 1, 0, 0, @cf_page
FROM furniture_items fi
WHERE fi.asset_name IN ('CF_1_coin_bronze','CF_5_coin_silver','CF_10_coin_gold','CF_20_moneybag','CF_50_goldbar');

INSERT INTO catalog_item_furniture_item (catalog_items_id, furniture_items_id)
SELECT ci.id, fi.id FROM catalog_items ci JOIN furniture_items fi ON fi.asset_name=ci.name WHERE ci.catalog_page_id=@cf_page;
UPDATE catalog_items ci JOIN furniture_items fi ON fi.asset_name=ci.name SET ci.name=fi.name WHERE ci.catalog_page_id=@cf_page;

-- 2) Petal Patch -> Rare Shop (rare_daffodil_rug), 100 credits, not already present
SET @rare_page = (SELECT id FROM catalog_pages WHERE name='rare_shop' LIMIT 1);
INSERT INTO catalog_items (name, cost_credits, cost_points, cost_points_type, requires_club_membership, meta_data, amount, stack_limit, sell_limit, catalog_page_id)
SELECT fi.asset_name, 100, 0, 0, 0, NULL, 1, 0, 0, @rare_page
FROM furniture_items fi
WHERE fi.asset_name = 'rare_daffodil_rug'
  AND NOT EXISTS (SELECT 1 FROM catalog_items ci2 JOIN catalog_item_furniture_item cif2 ON cif2.catalog_items_id=ci2.id WHERE ci2.catalog_page_id=@rare_page AND cif2.furniture_items_id=fi.id);
INSERT INTO catalog_item_furniture_item (catalog_items_id, furniture_items_id)
SELECT ci.id, fi.id FROM catalog_items ci JOIN furniture_items fi ON fi.asset_name=ci.name WHERE ci.catalog_page_id=@rare_page AND ci.name='rare_daffodil_rug';
UPDATE catalog_items ci JOIN furniture_items fi ON fi.asset_name=ci.name SET ci.name=fi.name WHERE ci.catalog_page_id=@rare_page AND ci.name='rare_daffodil_rug';
