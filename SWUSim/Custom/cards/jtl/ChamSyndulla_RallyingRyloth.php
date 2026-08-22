<?php
// JTL_164
// Cost 4 - Cham Syndulla - Rallying Ryloth - [Aggression] - Power 5 - HP 4
// Text: When Played: If an opponent controls more resources than you, you may put the top card of your deck into play as a resource.

// ── JTL_164 Cham Syndulla — When Played: If an opponent controls more resources than you, you may put
// the top card of your deck into play as a resource. (YES branch reuses the JTL_119 ramp.) ─────────────
$whenPlayedAbilities["JTL_164:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $me  = intval($player);
    // "AN opponent" in a CONDITION is EXISTENTIAL — true if ANY live opponent qualifies — not a target
    // to be chosen. This card must therefore NEVER prompt for a seat; adding a picker would be its own
    // I1 violation (a prompt Premier must never see). OtherPlayer() interrogated exactly one seat, so
    // above two seats two of the three opponents were invisible to the test, and from seats 2/3/4 only
    // seat 1 was ever compared. OpponentsOf() also filters to LIVE seats, so an eliminated seat's
    // abandoned board cannot satisfy the condition.
    $myRes = SWUResourceCount($me);
    $anyRicher = false;
    foreach (OpponentsOf($me) as $o) { if (SWUResourceCount($o) > $myRes) { $anyRicher = true; break; } }
    if (!$anyRicher) return;                                   // some opponent must control MORE
    $hasCard = false;
    foreach (GetDeck($me) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    DecisionQueueController::AddDecision($me, 'YESNO', '-', 1, tooltip: "Put_top_of_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision($me, 'CUSTOM', 'JTL_119#0', 1);
};
