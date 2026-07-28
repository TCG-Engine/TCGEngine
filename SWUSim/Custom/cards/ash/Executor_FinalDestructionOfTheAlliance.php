<?php
// ASH_197
// Cost 8 - Executor - Final Destruction of the Alliance - [Cunning,Villainy] - Power 5 - HP 12
// Text: This unit gets +1/+0 for each upgrade on other friendly units. / When Played: Give an Advantage token to each other friendly unit.

// ASH_197 Executor — When Played: give an Advantage token to each OTHER friendly unit. (The "+1/+0 for
// each upgrade on other friendly units" passive lives in ObjectCurrentPower.)
$whenPlayedAbilities["ASH_197:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $uid) {
            $mz = SWUFindMzByUID(intval($u->UniqueID ?? 0));
            if ($mz !== null) DoGiveAdvantageToken(intval($player), $mz);
        }
    }
};
