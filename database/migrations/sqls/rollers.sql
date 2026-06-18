-- Rollers subcategory in the Credit Shop, under "Functional" (page 23).
-- Adds the functional rollers (interaction_type='roller') at 5 credits each,
-- matching the other Functional items. Idempotent. Requires an emulator restart
-- (CatalogPageEventHandler reads a startup-cached page list).

INSERT INTO catalog_pages (name, caption, layout, role_id, catalog_page_id, order_id, icon_id, enabled, visible, images_json, texts_json)
SELECT 'rollers','Rollers','default_3x3',NULL,23,5,0,1,1,'[]','[]' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM catalog_pages WHERE name='rollers' AND catalog_page_id=23);

SET @rollers_page = (SELECT id FROM catalog_pages WHERE name='rollers' AND catalog_page_id=23 LIMIT 1);

-- Idempotent re-run: clear prior roller offers on this page first.
DELETE cif FROM catalog_item_furniture_item cif
  JOIN catalog_items ci ON ci.id = cif.catalog_items_id
  WHERE ci.catalog_page_id = @rollers_page;
DELETE FROM catalog_items WHERE catalog_page_id = @rollers_page;

INSERT INTO catalog_items (name, cost_credits, cost_points, cost_points_type, requires_club_membership, meta_data, amount, stack_limit, sell_limit, catalog_page_id)
SELECT fi.asset_name, 5, 0, 0, 0, NULL, 1, 0, 0, @rollers_page
FROM furniture_items fi
WHERE fi.interaction_type = 'roller';

INSERT INTO catalog_item_furniture_item (catalog_items_id, furniture_items_id)
SELECT ci.id, fi.id FROM catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name WHERE ci.catalog_page_id = @rollers_page;

UPDATE catalog_items ci JOIN furniture_items fi ON fi.asset_name = ci.name SET ci.name = fi.name WHERE ci.catalog_page_id = @rollers_page;
