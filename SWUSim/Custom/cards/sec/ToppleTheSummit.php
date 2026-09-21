<?php
// SEC_183
// Topple the Summit
// Text: Deal 3 damage to each damaged unit. Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_183:0"] = function($player, $mzID = '') {
// Topple the Summit — "Deal 3 to each damaged unit." (Plot auto.)
            global $playerID; $playerID = intval($player);
            $uids = [];
            // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
            // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
            // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
            foreach (["teamGroundArena", "teamSpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $uids[] = intval($o->UniqueID);
                }
            }
            foreach ($uids as $uid) { $playerID = intval($player); $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 3, intval($player)); }
            return;
};
