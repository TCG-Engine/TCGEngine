<?php
// SEC_244
// Cost 7 - Darth Nihilus - Lord of Hunger - [Villainy] - Power 6 - HP 6
// Text: When Played/On Attack: Deal 3 damage to the unit with the least remaining HP among other units. (If multiple units are tied, choose one.). If it's a non-Vehicle unit, give an Experience token to this unit.

$customDQHandlers["SEC_244#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $min = PHP_INT_MAX; $cands = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $selfUID) continue;
        $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
        if ($rem < $min) { $min = $rem; $cands = [$mz]; }
        elseif ($rem === $min) { $cands[] = $mz; }
    }
    if (empty($cands)) return;
    SWUQueueChooseTarget(intval($player), $cands, "Deal_3_to_the_lowest-HP_other_unit", "SEC_244#0|{$selfUID}");
};

$customDQHandlers["SEC_244#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $isNonVehicle = !TraitContains($o, 'Vehicle');
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    if ($isNonVehicle) {
        $smz = SWUFindMzByUID($selfUID);
        if ($smz !== null) DoGiveExperienceToken(intval($player), $smz);
    }
};

// SEC_244 Darth Nihilus — When Played / On Attack: deal 3 to the OTHER unit with the least remaining HP
// (tie → choose); if it's a non-Vehicle unit, give an Experience token to this unit. The pick is routed
// through a continuation so a tie-MZCHOOSE is safe in the OnAttack window.
$sec244 = function ($player, $mzID) {
  $self = GetZoneObject($mzID);
  $selfUID = SWUObjUID($self, 0);
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_244#1|{$selfUID}", 1);
};

$whenPlayedAbilities["SEC_244:0"] = $sec244;

$onAttackAbilities["SEC_244:0"] = $sec244;
