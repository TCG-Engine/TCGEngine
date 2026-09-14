-- 14_grand_archive_hypothermia_rested_target_fix.sql
-- Fixes Hypothermia (card_id cyfrzrplyw, GrandArchiveSim) so its "Target rested ally gets -4
-- [LIFE] until end of turn." action actually targets a RESTED ally instead of an AWAKE one.
--
-- The ability's macro source builds its target list by filtering allies on both fields with
-- `$obj->Status == 2`. Per GameLogic.php's own convention comment ("if($field[$i]->Status == 2)
-- { // Awake (Status 2 = ready)", GrandArchiveSim/Custom/GameLogic.php:10156) Status 2 means
-- AWAKE, not rested -- so Hypothermia's stored ability_code has the check backwards: it can only
-- ever target an awake ally, the exact opposite of its printed text, and if only rested allies
-- exist on the field the target list is empty and the ability silently no-ops (early return, no
-- error surfaced to the player). Drown in Aether (card_id gnfbp3g8iw), a sibling card with the
-- same "target rested ally" wording, correctly filters `if($o->Status == 1) $rested[] = $t;` --
-- confirming Status 1 is the rested state and cyfrzrplyw's `== 2` is the bug.
--
-- Fix: change the filter from `$obj->Status == 2` to `$obj->Status == 1` so Hypothermia targets
-- rested allies as printed -- see Tests/Integration/GrandArchiveSim/hypothermia-target-rested-ally-life/.
--
-- Applies only to the GrandArchiveSim application database (table card_abilities). Idempotent: the
-- LIKE guard only matches the pre-fix `Status == 2` check, which no longer matches once the
-- REPLACE has changed it to `Status == 1`; a second run is a no-op.
UPDATE card_abilities
SET ability_code = REPLACE(
    ability_code,
    'if($obj !== null && isset($obj->Status) && $obj->Status == 2) {',
    'if($obj !== null && isset($obj->Status) && $obj->Status == 1) {'
)
WHERE root_name = 'GrandArchiveSim'
  AND card_id = 'cyfrzrplyw'
  AND ability_code LIKE '%if($obj !== null && isset($obj->Status) && $obj->Status == 2) {%';
