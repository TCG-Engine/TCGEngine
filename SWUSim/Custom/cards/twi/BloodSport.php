<?php
// TWI_173
// Blood Sport
// Text: Deal 2 damage to each ground unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_173:0"] = function($player, $mzID = '') {
// Blood Sport — "Deal 2 damage to each ground unit." (AoE, UID-snapshot.)
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
            // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
            // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
            // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
            foreach (['teamGroundArena', 'theirGroundArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player)); }
            return;
};
