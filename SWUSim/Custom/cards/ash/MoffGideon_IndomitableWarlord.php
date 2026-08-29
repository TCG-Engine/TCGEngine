<?php
// ASH_008
// Cost 7 - Moff Gideon - Indomitable Warlord - [Command,Villainy] - Power 5 - HP 8
// Text: Action [Exhaust]: If a friendly Imperial unit was defeated this phase, play a unit from your hand. It costs 1 resource less.
// DeployText: This unit gains each of the following keywords if it is on an Imperial unit in your discard pile: Ambush, Grit, Hidden, Overwhelm, Saboteur, Sentinel, Shielded, Support.
// Epic Action: If you control 7 or more resources, deploy this leader.

// ASH_008 Moff Gideon — Action [Exhaust]: if a friendly Imperial unit was defeated this phase, play a unit
// from your hand. It costs 1 resource less. (SWU_IMPERIAL_DEFEATED gate; ActivateCard with discount 1.)
$leaderAbilities["ASH_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_IMPERIAL_DEFEATED') <= 0) { SWUAfterAction($player); return; }
    $handUnits = [];
    foreach (ZoneSearch("myHand", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $handUnits[] = $mz;
    }
    if (empty($handUnits)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $handUnits, "Play_a_unit_from_your_hand_(costs_1_less)", "ASH_008#0");
};

$customDQHandlers["ASH_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    SWUNestedPlay(intval($player), $lastDecision, false, 1);   // play paying cost − 1
    SWUAfterAction($player);
};
