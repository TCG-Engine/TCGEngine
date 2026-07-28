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
            foreach (['myGroundArena', 'theirGroundArena'] as $z) {
                foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
                }
            }
            foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player)); }
            return;
};
