-- Adds the SWUDeck-specific 2nd-leader thumbnail slot for Twin Suns decks. Unlike
-- ownership_format_migration.sql, SetAssetKeyIdentifier's keyIndicator=3 branch is opt-in per call
-- (an UPDATE, not referenced by every INSERT), so this only needs to run against the `swudeck`
-- database — azukisim/grandarchivesim/swusim/soulmastersdb never call it with keyIndicator=3.

ALTER TABLE `ownership`
  ADD COLUMN `keyIndicator3` varchar(64) DEFAULT NULL AFTER `keyIndicator2`;
