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
    $opp = OtherPlayer($me);
    if (SWUResourceCount($opp) <= SWUResourceCount($me)) return; // opp must control MORE
    $hasCard = false;
    foreach (GetDeck($me) as $c) { if (empty($c->removed)) { $hasCard = true; break; } }
    if (!$hasCard) return;
    DecisionQueueController::AddDecision($me, 'YESNO', '-', 1, tooltip: "Put_top_of_deck_into_play_as_a_resource?");
    DecisionQueueController::AddDecision($me, 'CUSTOM', 'JTL_119#0', 1);
};
