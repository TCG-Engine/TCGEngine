-- completedgame.Format: retype int -> varchar, backfill history to 'premier', lock to NOT NULL.
--
-- Applies to the SWUStats stats database(s) ONLY (local docker DB: `swudeck`; prod: the SWUStats DB).
-- `completedgame` is not a shared cross-app table (unlike `ownership`), so this does not need to run
-- against every app DB.
--
-- All existing rows are premier: the completedgame INSERT has always been gated to premier games, so
-- backfilling every historical row to 'premier' is correct. The DEFAULT 'premier' also keeps any
-- inserter that omits the column (e.g. the dead logCompletedGameStats()) valid, so no NULLs appear.

ALTER TABLE `completedgame` MODIFY COLUMN `Format` varchar(16) NULL DEFAULT 'premier';   -- widen type; DEFAULT here so games inserted mid-migration default to premier (closes the backfill->lockdown race)
UPDATE `completedgame` SET `Format` = 'premier' WHERE `Format` IS NULL OR `Format` = '';
ALTER TABLE `completedgame` MODIFY COLUMN `Format` varchar(16) NOT NULL DEFAULT 'premier';
