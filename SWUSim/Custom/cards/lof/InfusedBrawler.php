<?php
// LOF_156
// Cost 2 - Infused Brawler - [Aggression] - Power 2 - HP 2
// Text: When Played: You may use the Force (lose your Force token). If you do, give 2 Experience tokens to this unit. / When this unit completes an attack: Defeat an Experience token on it.

// LOF_156 Infused Brawler — When Played: may use the Force → give 2 Experience to this unit. AND
// "When this unit completes an attack: defeat an Experience token on it" (self-attrition).
$whenPlayedAbilities["LOF_156:0"] = function($player, $mzID) {
    $o = GetZoneObject($mzID);
    $uid = SWUObjUID($o, 0);
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_give_this_unit_2_Experience?", "LOF_156#0|{$uid}");
};

$customDQHandlers["LOF_156#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    DoGiveExperienceToken(intval($player), $mz);
    DoGiveExperienceToken(intval($player), $mz);
};

$onAttackEndAbilities["LOF_156:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDefeatExperienceToken($mzID); // defeat one Experience token on itself
};
