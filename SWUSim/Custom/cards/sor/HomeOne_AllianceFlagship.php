<?php
// SOR_102
// Cost 8 - Home One - Alliance Flagship - [Command,Heroism] - Power 7 - HP 7
// Text: Restore 2 / Each other friendly unit gains Restore 1. / When Played: Play a [Heroism] unit from your discard pile. It costs [3 resources] less.

// SOR_102 Home One — When Played: "Play a [Heroism] unit from your discard pile. It costs 3 less."
// (Restore 2 + the Restore-1 grant are keyword-wired.) Choose a Heroism unit in own discard → play it
// at a 3-cost discount via SWUPlayDiscardUnitDiscounted.
$whenPlayedAbilities["SOR_102:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // Available ready resources AFTER Home One itself was paid for — the discount play must still
    // be affordable, so only offer Heroism units whose discounted (-3) cost can actually be paid.
    // Without this, the UI lets the player pick an unaffordable unit that then fizzles on payment.
    $ready = 0;
    foreach (GetResources(intval($player)) as $r) {
        if (empty($r->removed) && intval($r->Status) === 1) $ready++;
    }
    $targets = [];
    foreach (ZoneSearch('myDiscard', NonLeaderUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (strpos(CardAspect($o->CardID) ?? '', 'Heroism') === false) continue;
        $cost = max(0, intval(CardCost($o->CardID)) + SWUAspectPenalty(intval($player), $o->CardID) - 3);
        if ($cost > $ready) continue; // can't afford after the -3 discount → not a legal target
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_Heroism_unit_from_your_discard_(costs_3_less)", "SOR_102#0");
};

$customDQHandlers["SOR_102#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    // EXPERIMENT: play the chosen discard card via the canonical ActivateCard path
    // (full cost pipeline) instead of SWUPlayDiscardUnitDiscounted (reduced cost).
    SWUNestedPlay(intval($player), $lastDecision, false, 3);   // nested: the When Played flush owns the after-action
};
