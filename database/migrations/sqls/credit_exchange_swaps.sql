-- On-chain Credit Exchange swaps (Phase 5). Each row is a buy: intent -> on-chain pay -> verify.
-- The platform never custodies seller proceeds; only the fee reaches treasury (later burned).
-- tx_signature is the verifiable proof on Solana explorer.
CREATE TABLE IF NOT EXISTS `credit_exchange_swaps` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `listing_id` INT NOT NULL,
  `buyer_id` BIGINT NOT NULL,
  `seller_id` BIGINT NOT NULL,
  `buyer_wallet` VARCHAR(64) DEFAULT NULL,
  `seller_wallet` VARCHAR(64) DEFAULT NULL,
  `amount_hotel` DECIMAL(20,9) NOT NULL,
  `fee_hotel` DECIMAL(20,9) NOT NULL,
  `tx_signature` VARCHAR(128) DEFAULT NULL,
  `status` ENUM('intent','confirmed','failed','expired') NOT NULL DEFAULT 'intent',
  `created_at` DATETIME NOT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ces_tx` (`tx_signature`),
  KEY `ix_ces_listing` (`listing_id`),
  KEY `ix_ces_status` (`status`)
) ENGINE=InnoDB;

ALTER TABLE `credit_exchange_swaps` ADD COLUMN `reference` VARCHAR(64) DEFAULT NULL AFTER `seller_wallet`, ADD KEY `ix_ces_ref` (`reference`);
