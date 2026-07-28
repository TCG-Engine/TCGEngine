<?php
// JTL_201
// Cost 4 - Ahsoka Tano - Chasing Whispers - [Cunning,Heroism] - Power 3 - HP 5
// Text: When Played: An opponent discards a card from their hand. If it's a unit, you may exhaust a unit.

// ── JTL_201 Ahsoka Tano — When Played: An opponent discards a card from their hand. If it's a unit, you
// may exhaust a unit. ──────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_201:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $hasCard = false;
    foreach (GetHand($opp) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    SWUDiscardCards(intval($player), 1);   // the opponent discards a card of their choice
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_201#0|" . intval($player), 1);
};

$customDQHandlers["JTL_201#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0]);
    $playerID = $caster;
    $opp = OtherPlayer($caster);
    $disc = GetDiscard($opp);
    $last = null;
    for ($i = count($disc) - 1; $i >= 0; $i--) { if (empty($disc[$i]->removed)) { $last = $disc[$i]; break; } }
    if ($last === null) return;
    if (stripos(CardType($last->CardID ?? '') ?? '', 'unit') === false) return;   // discarded card wasn't a unit
    $units = array_values(array_merge(
        ZoneSearch('myGroundArena',    AnyUnitFilter), ZoneSearch('mySpaceArena',    AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)
    ));
    if (empty($units)) return;
    SWUQueueMayChooseTarget($caster, $units, "Exhaust_a_unit", "Choose_a_unit_to_exhaust", "EXHAUST_UNIT");
};
