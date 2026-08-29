<?php
// LOF_010
// Cost 5 - Third Sister - Seething With Ambition - [Aggression,Villainy] - Power 5 - HP 4
// Text: Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase. (It can't be attacked for this phase unless it has Sentinel.)
// DeployText: Hidden (This unit can't be attacked if she was deployed this phase.) / On Attack: The next unit you play this phase gains Hidden.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_010 Third Sister — On Attack: the next unit you play this phase gains Hidden (entry-block flag).
$onAttackAbilities["LOF_010:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_LOF010_NEXT_HIDDEN');
};

// LOF_010 Third Sister — Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase.
$leaderAbilities["LOF_010"] = fn(int $player) => SWUOfferDiscountPlay($player,
    ['discount' => 0, 'types' => ['Unit'],
     'prompt' => "Play_a_unit_from_hand_(it_gains_Hidden)", 'continuation' => 'LOF_010#0']);

$customDQHandlers["LOF_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'HIDDEN';
    SWUNestedPlay(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};
