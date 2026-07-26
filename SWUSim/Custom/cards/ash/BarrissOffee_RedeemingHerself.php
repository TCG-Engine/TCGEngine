<?php
// ASH_044
// Cost 3 - Barriss Offee - Redeeming Herself - [Cunning,Vigilance] - Power 3 - HP 4
// Text: When Played: Heal up to 2 damage from a unit. Give an Advantage token to it for each damage healed this way.

// ASH_044 Barriss Offee — When Played: heal up to 2 damage from a unit; give an Advantage token to it
// for each damage healed this way.
$whenPlayedAbilities["ASH_044:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Heal_up_to_2_from_a_unit_(Advantage_per_heal)?", "Choose_a_unit", "ASH_044#0");
};

$customDQHandlers["ASH_044#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $healed = min(2, intval($o->Damage ?? 0));
    if ($healed <= 0) return;
    OnHealUnit(intval($player), $lastDecision, $healed);
    for ($i = 0; $i < $healed; $i++) DoGiveAdvantageToken(intval($player), $lastDecision);
};
