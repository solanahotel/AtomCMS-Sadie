-- Marketplace foundation (D2): tag every furniture definition with its shop ORIGIN, a RARITY, and
-- whether it is MARKETABLE (listable on the player marketplace).
--
-- Rule (from the economy spec): anything NOT from the Regular Credit Shop is listable. Origin is
-- derived from catalog-page membership:
--   * Regular Credit Shop pages (the default furniture catalogue, pages 1-20 + 29) -> credit_shop, NOT marketable
--   * Rare Shop (page 27)        -> rare_shop,    marketable, rarity 'rare'
--   * Club Shop (pages 26, 28)   -> club,         marketable, rarity 'uncommon'
--   * Credit Furni (page 30)     -> credit_furni, marketable, rarity 'uncommon'
-- Items not sold in any catalogue page are left origin NULL / marketable 0; wheel/event drops will be
-- tagged with an explicit origin at grant time. Values are tunable; this is the starting backfill.

ALTER TABLE `furniture_items`
  ADD COLUMN `origin` VARCHAR(32) DEFAULT NULL,
  ADD COLUMN `rarity` VARCHAR(16) DEFAULT NULL,
  ADD COLUMN `marketable` TINYINT(1) NOT NULL DEFAULT 0;

-- 1) Regular Credit Shop (not marketable). Leaf pages that hold items under the Credit Shop tree.
UPDATE `furniture_items` fi
JOIN (
  SELECT DISTINCT l.furniture_items_id AS fid
  FROM `catalog_item_furniture_item` l
  JOIN `catalog_items` ci ON ci.id = l.catalog_items_id
  WHERE ci.catalog_page_id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,29)
) c ON c.fid = fi.id
SET fi.origin = 'credit_shop', fi.marketable = 0, fi.rarity = 'common';

-- 2) Club Shop (marketable). Pages 26 + 28.
UPDATE `furniture_items` fi
JOIN (
  SELECT DISTINCT l.furniture_items_id AS fid
  FROM `catalog_item_furniture_item` l
  JOIN `catalog_items` ci ON ci.id = l.catalog_items_id
  WHERE ci.catalog_page_id IN (26,28)
) c ON c.fid = fi.id
SET fi.origin = 'club', fi.marketable = 1, fi.rarity = 'uncommon';

-- 3) Credit Furni (marketable). Page 30.
UPDATE `furniture_items` fi
JOIN (
  SELECT DISTINCT l.furniture_items_id AS fid
  FROM `catalog_item_furniture_item` l
  JOIN `catalog_items` ci ON ci.id = l.catalog_items_id
  WHERE ci.catalog_page_id = 30
) c ON c.fid = fi.id
SET fi.origin = 'credit_furni', fi.marketable = 1, fi.rarity = 'uncommon';

-- 4) Rare Shop (marketable, rarity rare). Page 27 — applied last so rare wins any overlap.
UPDATE `furniture_items` fi
JOIN (
  SELECT DISTINCT l.furniture_items_id AS fid
  FROM `catalog_item_furniture_item` l
  JOIN `catalog_items` ci ON ci.id = l.catalog_items_id
  WHERE ci.catalog_page_id = 27
) c ON c.fid = fi.id
SET fi.origin = 'rare_shop', fi.marketable = 1, fi.rarity = 'rare';

-- Credit items (CF_<number> redeemable furni: gold bars, coins, money bags) are NOT listable in the
-- Item Market. They belong only in the Credit Exchange (sold for $HOTEL). Tag + exclude them.
UPDATE `furniture_items` SET `origin` = 'credit_item', `marketable` = 0 WHERE `asset_name` REGEXP '^CF_[0-9]';
