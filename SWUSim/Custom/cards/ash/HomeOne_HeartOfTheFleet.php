<?php
// ASH_065
// Cost 8 - Home One - Heart of the Fleet - [Vigilance,Heroism] - Power 7 - HP 10
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / When Played: Heal all damage from each friendly unit.

// ASH_065 Home One — Sentinel (auto) + When Played: heal all damage from each friendly unit.
$whenPlayedAbilities["ASH_065:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->Damage ?? 0) > 0) {
            $mz = SWUFindMzByUID(intval($u->UniqueID ?? 0));
            if ($mz !== null) OnHealUnit(intval($player), $mz, intval($u->Damage));
        }
    }
};
