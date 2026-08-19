<?php
// SEC_136
// Cost 4 - Arihnda Pryce - On the Road to Power - [Aggression,Villainy] - Power 4 - HP 4
// Text: When Defeated: You may defeat another friendly unit. If you do, deal 4 damage to each enemy base.

// SEC_136 Arihnda Pryce — When Defeated: you may defeat another friendly unit; if you do, deal 4 to
// each enemy base.
$whenDefeatedAbilities["SEC_136:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $friendly = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    // The question named the COST and not the payoff, so "defeat another friendly unit?" read as pure
    // downside. The 4 damage to each enemy base is the whole reason to say yes.
    SWUQueueMayChooseTarget(intval($player), $friendly,
        "Defeat_another_friendly_unit_to_deal_4_damage_to_each_enemy_base?",
        "Choose_a_friendly_unit_to_defeat", "SEC_136#0");
};

$customDQHandlers["SEC_136#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    SWUDealDamageToBase(4, OtherPlayer(intval($player)));
};
