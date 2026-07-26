<?php
// JTL_238
// Cost 3 - Sith Trooper - [Villainy] - Power 3 - HP 3
// Text: On Attack: This unit gets +1/+0 for this attack for each damaged unit the defending player controls.

// JTL_238 Sith Trooper — On Attack: +1/+0 for this attack for each damaged unit the defending player
// (the opponent, in 2-player) controls.
$onAttackAbilities["JTL_238:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $count = 0;
    foreach (array_merge(GetGroundArena($opp), GetSpaceArena($opp)) as $u) {
        if (empty($u->removed) && intval($u->Damage) > 0) $count++;
    }
    if ($count > 0) SWUAddAttackPowerBonus($mzID, $count);
};
