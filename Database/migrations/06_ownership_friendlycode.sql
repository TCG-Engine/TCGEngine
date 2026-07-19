-- Friendly share codes: a 12-char [a-zA-Z] code per deck, resolved to assetIdentifier.
-- Expand-first: nullable column + unique index. NULLs are allowed (InnoDB permits multiple NULLs
-- under a UNIQUE index), so non-deck rows and not-yet-backfilled decks stay NULL and old code that
-- omits the column keeps working. Adding the unique index scans the table -- run in a low-traffic
-- window; if `ERROR 1206` (lock table full) appears, bump innodb_buffer_pool_size first.
ALTER TABLE `ownership` ADD COLUMN `friendlyCode` varchar(12) DEFAULT NULL;
ALTER TABLE `ownership` ADD UNIQUE INDEX `idx_ownership_friendlyCode` (`friendlyCode`);
