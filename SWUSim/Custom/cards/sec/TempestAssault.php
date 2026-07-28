<?php
// SEC_144
// Tempest Assault
// Text: If you've dealt damage to an enemy base this phase, deal 2 damage to each enemy space unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_144:0"] = function($player, $mzID = '') {
// Tempest Assault — "If you've dealt damage to an enemy base this phase, deal 2
                          // to each enemy space unit."
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $opp) <= 0) return;
            $uids = [];
            foreach (ZoneSearch("theirSpaceArena", AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
            }
            return;
};
