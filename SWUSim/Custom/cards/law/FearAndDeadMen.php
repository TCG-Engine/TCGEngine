<?php
// LAW_179
// Fear and Dead Men
// Text: This card costs 1 resource less to play for each card discarded from your hand this phase. Deal 4 damage to each enemy ground unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_179:0"] = function($player, $mzID = '') {
// Fear and Dead Men — cost reduction handled by $playCostModifiers["LAW_179"].
                          // Effect: "Deal 4 damage to each enemy ground unit." (UID snapshot, AOE.)
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (ZoneSearch("theirGroundArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDealDamageToUnit($mz, 4, intval($player));
            }
            return;
};
