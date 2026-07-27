<?php
// LAW_039
// Cost 3 - Latts Razzi - Deadly Whipmaster - [Vigilance,Command] - Power 2 - HP 1
// Text: When Played: Give a Shield token or an Experience token to this unit. Then, she deals damage equal to her power to an enemy ground unit.

// LAW_039 Latts Razzi — When Played: give a Shield OR Experience token to this unit, then she deals
// damage equal to her power to an enemy ground unit.
$whenPlayedAbilities["LAW_039:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Shield&Experience", 1, "Give_a_Shield_or_Experience_token_to_this_unit");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_039#0|{$uid}", 1);
};

$customDQHandlers["LAW_039#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $mz  = SWUFindMzByUID($uid);
    if ($mz === null) return;
    if ($lastDecision === 'Shield') DoGiveShieldToken(intval($player), $mz);
    else                            DoGiveExperienceToken(intval($player), $mz);
    $mz = SWUFindMzByUID($uid);                       // re-resolve (Exp adds a subcard)
    $power = intval(ObjectCurrentPower(GetZoneObject($mz)));
    if ($power <= 0) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $power, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_{$power}_to_an_enemy_ground_unit",
    ]);
};
