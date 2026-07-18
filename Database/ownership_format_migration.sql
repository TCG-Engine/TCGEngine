-- AccountFiles/AccountDatabaseAPI.php (SaveAssetOwnership, UpdateAssetFormat) is SHARED code —
-- every TCGEngine app with its own `ownership` table calls it (SWUDeck, AzukiDeck, GrandArchiveSim,
-- SWUSim, SoulMastersDB, RBDeck, ...), each against its own database. SaveAssetOwnership's INSERT now
-- always references the `format` column, so this migration must be applied to EVERY app database that
-- has an `ownership` table, not just SWUDeck's — otherwise that app's CreateDeck.php breaks with
-- "Unknown column 'format' in field list".
--
-- Per-app default formats are NOT decided by this shared migration's column DEFAULT — each app's own
-- CreateDeck.php passes its own default explicitly to SaveAssetOwnership():
--   SWUDeck        → 'premier'      (SWUDeck/CreateDeck.php)
--   AzukiDeck       → 'standard'    (AzukiDeck/CreateDeck.php)
--   GrandArchiveSim → 'standard'    (deck builder not yet built; set explicitly when it is)
--   SoulMastersDB   → 'constructed' (SoulMastersDB/CreateDeck.php)
--   RBDeck          → 'constructed' (no database yet — "will be constructed when we get to hosting
--                                     that"; set explicitly once its CreateDeck.php exists)
-- The column's own DEFAULT ('standard' below) only matters for the rare direct-SQL-insert path that
-- bypasses SaveAssetOwnership entirely.
--
-- Applied so far (2026-07-17): swudeck (prod, by user), azukisim/grandarchivesim (prod, by user), and
-- the equivalent azukisim/grandarchivesim/swusim local dev docker databases. SoulMastersDB has no
-- local docker service to migrate here — confirm wherever it actually runs before this ships.

ALTER TABLE `ownership`
  ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'standard' AFTER `keyIndicator2`;
