-- Drawn games (CR 6.3: both bases at 0 remaining HP at the same time) — docs/superpowers/specs/2026-10-30-swusim-draws-design.md.
-- Adds a `draws` count beside wins/losses on the saved-deck stats and the rated-queue ratings. A draw is never a win or
-- a loss for anyone, so before this column existed it had nowhere to go.
--
-- Apply to the SWUSim app database only (local docker: swusim; prod: petranaki.net). Idempotent: each ALTER runs only
-- when its table exists and the column does not. The code checks DBColumnExists() first, so a box that has not run
-- this keeps working and simply does not count draws.

SET @s = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'favoritedeck') = 1
        AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'favoritedeck' AND column_name = 'draws') = 0,
    'ALTER TABLE `favoritedeck` ADD COLUMN `draws` int(11) NOT NULL DEFAULT 0 AFTER `losses`', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'favoritedeckmatchup') = 1
        AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'favoritedeckmatchup' AND column_name = 'draws') = 0,
    'ALTER TABLE `favoritedeckmatchup` ADD COLUMN `draws` int(11) NOT NULL DEFAULT 0 AFTER `losses`', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'glicko_ratings') = 1
        AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'glicko_ratings' AND column_name = 'draws') = 0,
    'ALTER TABLE `glicko_ratings` ADD COLUMN `draws` int(11) NOT NULL DEFAULT 0 AFTER `losses`', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
