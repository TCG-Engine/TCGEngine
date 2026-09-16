<?php
// HMW_153 Poacher's Starfighter — Unit (Space) 3/1, cost 2, [Aggression][Villainy], Underworld/Vehicle/Fighter.
// "When Played: You may defeat this unit. If you do, create a Beast token and deal 1 damage to it."
// SEC_150 Valiant Commando's shape. "If you do" gates on the defeat actually happening (a can't-be-defeated
// effect refuses it). "it" is the Beast created now.

$whenPlayedAbilities["HMW_153:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $uid = SWUObjUID(GetZoneObject($mzID), 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Defeat_Poacher's_Starfighter_to_create_a_Beast_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_153#0|{$uid}", 1);
};

$customDQHandlers["HMW_153#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null || !SWUDefeatUnit(intval($player), $mz)) return;
    $beastUID = SWUCreateUnitToken(intval($player), 'HMW_T03');
    $beastMz = SWUFindMzByUID($beastUID);
    if ($beastMz !== null) SWUDealDamageToUnit($beastMz, 1, intval($player));
};
