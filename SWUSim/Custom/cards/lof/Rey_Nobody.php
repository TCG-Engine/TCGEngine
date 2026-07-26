<?php
// LOF_012
// Cost 7 - Rey - Nobody - [Aggression,Heroism] - Power 6 - HP 8
// Text: Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit.
// DeployText: When Deployed: You may discard your hand. If you do, draw 2 cards.
// Epic Action: If you control 7 or more resources, deploy this leader.

// LOF_012 Rey — Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit.
$leaderAbilities["LOF_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_PLAYED_NONUNIT_FORCE') <= 0) { SWUAfterAction($player); return; }
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                           ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_damage_to_a_unit", "LOF_012#0");
};

$customDQHandlers["LOF_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUAfterAction(intval($player));
};

// LOF_012 Rey (deployed) — When Deployed: You may discard your hand. If you do, draw 2 cards.
// Same shape as SOR_147 Black One; the deploy action owns the After Action (entry trigger).
$whenPlayedAbilities["LOF_012:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Discard_your_hand_to_draw_2?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LOF_012#1', 1);
};

$customDQHandlers["LOF_012#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID; $playerID = intval($player);
    foreach (GetHand(intval($player)) as $h) {
        if (!empty($h->removed)) continue;
        $cid = $h->CardID;
        $h->Remove();
        SWUAddToDiscard(intval($player), $cid, 'HAND');
    }
    DoDrawCard(intval($player), 2);
};
