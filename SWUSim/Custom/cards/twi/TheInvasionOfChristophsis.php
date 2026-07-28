<?php
// TWI_078
// Cost 15 - The Invasion of Christophsis - [Vigilance]
// Text: Exploit 4 / Choose an opponent. Defeat each unit that player controls.

// TWI_078 The Invasion of Christophsis — defeat each unit the CHOSEN opponent controls.
$customDQHandlers["TWI_078#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $uids = [];
    foreach (["p{$opp}GroundArena", "p{$opp}SpaceArena"] as $z) {
        $zone = GetZone($z);
        for ($i = 0; $i < count($zone); $i++) {
            $o = $zone[$i];
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? -1);
        }
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_078:0"] = function($player, $mzID = '') {
// The Invasion of Christophsis — "Exploit 4. Choose an opponent. Defeat each
                          // unit that player controls."
            global $playerID;
            $playerID = intval($player);
            if (SeatCountForGame() <= 2) {
                // 2-player: the single opponent — defeat all their units (ZoneSearch "their" = the one opp).
                $uids = [];
                foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                    foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
                        $o = GetZoneObject($mz);
                        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? -1);
                    }
                }
                foreach ($uids as $uid) {
                    $mz = SWUFindMzByUID($uid);
                    if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
                }
                return;
            }
            // Twin Suns: choose ONE opponent, then defeat only that player's units (a bare ZoneSearch
            // "their" would union ALL opponents — wrong for a "choose an opponent" card).
            SWUQueueChooseOpponent(intval($player), "TWI_078#0", "Defeat_all_units_of_which_opponent?");
            return;
};
