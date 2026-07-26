<?php
// LOF_218
// Cost 1 - Impossible Escape - [Cunning]
// Text: You may either exhaust a friendly unit or use the Force (lose your Force token). If you do either, exhaust an enemy unit and draw a card.

// LOF_218 Impossible Escape — pay a cost (exhaust a friendly OR use the Force), then exhaust an enemy
// unit and draw a card.
$customDQHandlers["LOF_218#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'UseForce') {
        if (!PlayerHasTheForce(intval($player))) return;
        UseTheForce(intval($player));
        ImpossibleEscapeBenefit(intval($player));
        return;
    }
    if ($lastDecision === 'ExhaustFriendly') {
        $friendly = [];
        foreach (SWUAllUnits('my') as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $friendly[] = $mz;
        }
        if (empty($friendly)) return; // can't pay the cost → no benefit
        SWUQueueChooseTarget(intval($player), $friendly, "Exhaust_a_friendly_unit_(cost)", "LOF_218#1");
        return;
    }
    // 'Neither' → no-op
};

$customDQHandlers["LOF_218#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    OnExhaustCard(intval($player), $lastDecision); // pay the cost
    ImpossibleEscapeBenefit(intval($player));
};

// LOF_218 shared benefit (after the cost is paid): exhaust an enemy unit and draw a card.
function ImpossibleEscapeBenefit(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  DoDrawCard($player, 1);
  $enemies = SWUAllUnits('their');
  if (empty($enemies))
    return;
  SWUQueueChooseTarget($player, $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_218:0"] = function($player, $mzID = '') {
// Impossible Escape — "You may either exhaust a friendly unit OR use the Force.
                          // If you do either, exhaust an enemy unit and draw a card."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "ExhaustFriendly&UseForce&Neither", 1,
                tooltip: "Pay_a_cost_to_exhaust_an_enemy_unit_and_draw");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_218#0", 1);
            return;
};
