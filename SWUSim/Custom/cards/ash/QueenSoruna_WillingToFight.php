<?php
// ASH_132
// Cost 6 - Queen Soruna - Willing to Fight - [Command] - Power 5 - HP 7
// Text: When Played/On Attack: You may reveal a unit from your hand. If you do, deal 3 damage to a unit with the same cost as the revealed unit.

// ASH_132 Queen Soruna — When Played/On Attack: you may reveal a unit from your hand. If you do, deal 3
// damage to a unit with the same cost as the revealed unit.
$whenPlayedAbilities["ASH_132:0"] = $onAttackAbilities["ASH_132:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();   // the just-played Soruna still lingers in hand
    $handUnits = [];
    foreach (ZoneSearch("myHand", AnyUnitFilter) as $mz) $handUnits[] = $mz;
    if (empty($handUnits)) return;
    SWUQueueMayChooseTarget(intval($player), $handUnits, "Reveal_a_unit_from_your_hand?", "Choose_a_unit_to_reveal", "ASH_132#0");
};

$customDQHandlers["ASH_132#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $revealed = GetZoneObject($lastDecision);
    if (SWUObjGone($revealed)) return;
    $cost = intval(CardCost($revealed->CardID ?? ''));
    DoRevealCard(intval($player), $lastDecision);
    // SEC_016 Padmé "When you reveal … 1 or more cards from your hand" — a non-disclose hand reveal
    // must fire her react too (no-op when no Padmé is in play).
    if (function_exists('_SWUSec016React')) _SWUSec016React(intval($player));
    $tg = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) === $cost) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_3_to_a_unit_with_cost_{$cost}", "DEAL_UNIT_DAMAGE|3");
};
