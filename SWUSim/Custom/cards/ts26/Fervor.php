<?php
// TS26_72
// Cost 5 - Fervor - [Aggression]
// Text: Ready a unit. / Deal 3 damage to a unit.

// TS26_72 Fervor — ready the chosen unit, then deal 3 to a chosen unit.
$customDQHandlers["TS26_72#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) OnReadyCard(intval($player), $lastDecision);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3,
        'prompt' => "Deal_3_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_72:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Ready_a_unit", "TS26_72#0");
};
