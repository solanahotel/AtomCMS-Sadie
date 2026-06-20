-- Three dedicated "sold items" logs, one per marketplace section. Append-only records of completed
-- sales (the master marketplace_logs keeps the full event trail; these are the clean per-section sale
-- ledgers for review). No FKs so sale history survives player/item deletion. Charset inherits the DB.

CREATE TABLE IF NOT EXISTS `item_market_sales` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `listing_id` INT NOT NULL,
  `furniture_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `seller_id` BIGINT NOT NULL,
  `buyer_id` BIGINT NOT NULL,
  `price_credits` INT NOT NULL,
  `fee_credits` INT NOT NULL,
  `sold_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ims_seller` (`seller_id`),
  KEY `ix_ims_buyer` (`buyer_id`),
  KEY `ix_ims_sold` (`sold_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `auction_sales` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `listing_id` INT NOT NULL,
  `furniture_item_id` INT NOT NULL,
  `seller_id` BIGINT NOT NULL,
  `winner_id` BIGINT NOT NULL,
  `final_bid` INT NOT NULL,
  `fee_credits` INT NOT NULL,
  `sold_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_as_seller` (`seller_id`),
  KEY `ix_as_winner` (`winner_id`),
  KEY `ix_as_sold` (`sold_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `credit_exchange_sales` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `listing_id` INT NOT NULL,
  `furniture_item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_credit_value` INT NOT NULL,
  `seller_id` BIGINT NOT NULL,
  `buyer_id` BIGINT DEFAULT NULL,
  `price_hotel` DECIMAL(20,9) NOT NULL,
  `fee_hotel` DECIMAL(20,9) DEFAULT NULL,
  `tx_signature` VARCHAR(128) DEFAULT NULL,
  `sold_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ces_seller` (`seller_id`),
  KEY `ix_ces_buyer` (`buyer_id`),
  KEY `ix_ces_sold` (`sold_at`)
) ENGINE=InnoDB;
