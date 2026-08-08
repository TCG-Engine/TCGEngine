<?php
// JTL_046
// Cost 2 - Paige Tico - Dropping the Hammer - [Vigilance,Heroism] - Power 3 - HP 2 - Upgrade Power 2 - Upgrade HP 2
// Text: / Piloting [2 resources Vigilance Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / Attached unit gains: "On Attack: Give an Experience token to this unit, then deal 1 damage to it."

// JTL_046 Paige Tico (pilot) — granted "On Attack: Give an Experience token to this unit, then deal 1 to it."
$onAttackAbilities["JTL_046:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    DoGiveExperienceToken(intval($player), $mzID);
    // Source = the HOST: this is an ability GRANTED to the attached unit, so the unit is dealing the
    // damage to itself. Matters for SEC_050 Vigil, which only increases damage dealt "by another card".
    SWUDealDamageToUnit($mzID, 1, intval($player), $mzID);
};
