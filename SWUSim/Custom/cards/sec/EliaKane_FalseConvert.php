<?php
// SEC_242
// Cost 4 - Elia Kane - False Convert - [Villainy] - Power 3 - HP 6
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: Look at 3 enemy resources. You may defeat 1 of them. If you do, its controller puts the top card of their deck into play as a resource and readies it.

// SEC_242 Elia Kane — Raid 1 (auto) + When Played: look at 3 enemy resources, may defeat 1; if you do,
// its controller puts the top of their deck into play as a (ready) resource.
$whenPlayedAbilities["SEC_242:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Reveal 3 of the opponent's resources, picked for the active player's benefit (ready first,
    // active-player-owned first, then random) and logged. Excludes Credit tokens.
    $offer = SWURevealResources(intval($player), OtherPlayer(intval($player)), 3);
    if (empty($offer)) return;
    // Carry the revealed mzIDs so the handler can enforce "only a REVEALED resource may be defeated".
    SWUQueueMayChooseTarget(intval($player), $offer, "Defeat_an_enemy_resource?", "Choose_an_enemy_resource_(of_3)", "SEC_242#0|" . OtherPlayer(intval($player)) . "|" . implode(',', $offer));
};

$customDQHandlers["SEC_242#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    // You may only defeat one of the REVEALED resources (the looked-at 3) — an unrevealed resource (e.g.
    // an exhausted Plot card the Ready-first reveal protected) can't be picked.
    $revealed = array_filter(explode(',', $parts[1] ?? ''));
    if (!empty($revealed) && !in_array($lastDecision, $revealed, true)) return;
    // Defeat in the OWNER's (opponent's) frame so the resource lands in their discard (its Owner may be unset).
    $oppMz = preg_replace('/^their/', 'my', (string)$lastDecision);
    $playerID = $opp;
    SWUDefeatResource($opp, $oppMz);
    $deck = ZoneSearch("myDeck", null);
    if (!empty($deck)) SWURampResourceReady($opp, $deck[0]);   // controller replaces from deck, readied
};
