<?php
// TWI_046
// Cost 3 - Captain Typho - Protecting the Senator - [Vigilance,Heroism] - Power 2 - HP 4
// Text: When Played/On Attack: Give a unit Sentinel for this phase.

// TWI_046 Captain Typho — "When Played/On Attack: Give a unit Sentinel for this phase." (MZMAYCHOOSE for
// the OnAttack window per the mandatory-MZCHOOSE-skip limitation.)
$whenPlayedAbilities["TWI_046:0"] = $onAttackAbilities["TWI_046:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Give_a_unit_Sentinel_this_phase", "Choose_a_unit", "TWI_046#0");
};

$customDQHandlers["TWI_046#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'SENTINEL');
};
