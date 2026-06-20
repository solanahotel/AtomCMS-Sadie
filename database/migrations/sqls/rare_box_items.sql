-- Configurable Rare Box pool (managed in Housekeeping). The daily wheel's Rare Box opens into one of
-- these items, picked uniformly (each item = 100/N %). Keep ~5 for the intended 20% each.
CREATE TABLE IF NOT EXISTS `rare_box_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `furniture_item_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rare_box_furni` (`furniture_item_id`)
) ENGINE=InnoDB;
