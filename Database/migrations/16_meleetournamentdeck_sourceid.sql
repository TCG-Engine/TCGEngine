-- meleetournamentdeck.sourceID — the player's melee.gg DECKLIST GUID (e.g. 2162c700-3b5a-424d-95cb-b4920028faa5).
--
-- Stats/MeleeTournamentParser.php has INSERTed this column for a long time and APIs/GetMeleeTournament.php
-- returns it as `meleeId`, and prod has it (3rd column, after tournamentID) — but no tracked schema file ever
-- created it, so a DB built from Database/database.sql could not import a single tournament:
-- "Unknown column 'sourceID' in 'field list'" on the first deck (found 2026-10-01, local docker).
--
-- IDEMPOTENT and engine-neutral: MySQL has no ADD COLUMN IF NOT EXISTS (MariaDB, which prod runs, does), so
-- the column is added only when information_schema says it is missing. A no-op on prod.
-- 64 chars covers melee's 36-char GUID with room to spare.

SET @swu_has_sourceid := (SELECT COUNT(*) FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'meleetournamentdeck'
                            AND COLUMN_NAME = 'sourceID');
SET @swu_sql := IF(@swu_has_sourceid = 0,
  'ALTER TABLE `meleetournamentdeck` ADD COLUMN `sourceID` varchar(64) DEFAULT NULL AFTER `tournamentID`',
  'SELECT ''meleetournamentdeck.sourceID already present'' AS note');
PREPARE swu_stmt FROM @swu_sql;
EXECUTE swu_stmt;
DEALLOCATE PREPARE swu_stmt;
