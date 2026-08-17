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
    $queued = SWUDiscardCards(intval($player), 1);   // the opponent discards a card of their choice
    // ⚠ "If it's a unit" has to read the card the opponent ACTUALLY discarded, so the continuation must sit
    // behind the discard — and where that is depends on which path SWUDiscardCards took (bug report #965).
    // With 2+ cards in hand the pick is queued on the OPPONENT'S queue; a continuation on the caster's
    // queue resolves first, reads whatever was already on top of their discard pile, and offers the exhaust
    // regardless. With ≤1 card the discard happens INLINE and nothing is queued for them — putting the
    // continuation on their queue there would strand it, because a lone CUSTOM on a player who is not
    // otherwise acting never drains. Hence the branch.
    // Either way the handler re-frames to the caster (who rides in the param), so the exhaust offer lands
    // on the right player's queue.
    DecisionQueueController::AddDecision($queued ? $opp : intval($player),
        'CUSTOM', "JTL_201#0|" . intval($player), 1);
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
