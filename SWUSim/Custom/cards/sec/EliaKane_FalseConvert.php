<?php
// SEC_242
// Cost 4 - Elia Kane - False Convert - [Villainy] - Power 3 - HP 6
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: Look at 3 enemy resources. You may defeat 1 of them. If you do, its controller puts the top card of their deck into play as a resource and readies it.

// SEC_242 Elia Kane — Raid 1 (auto) + When Played: look at 3 enemy resources, may defeat 1; if you do,
// its controller puts the top of their deck into play as a (ready) resource.
// ⚠ "3 ENEMY resources" names no seat. This resolved OtherPlayer($player) — literally seat 2 — so above
// two seats Elia Kane always looked at seat 2's resources whoever the caster meant. The caster now picks
// WHOSE, following SHD_184 Bazine Netal (the canonical "look at an opponent's X" analogue) and matching
// its twin SHD_114 Scanning Officer. Auto-resolves invisibly at one opponent ⇒ Premier byte-identical.
// ⚠ FILTER to opponents holding a resource: with none there is nothing to look at and nothing to defeat.
$whenPlayedAbilities["SEC_242:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $eligible = [];
    foreach (OpponentsOf(intval($player)) as $o) {
        foreach (GetResources($o) as $r) { if (empty($r->removed)) { $eligible[] = $o; break; } }
    }
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'SEC_242#PICK|' . intval($player),
        "Choose_an_opponent_whose_resources_to_look_at", $eligible);
};

// The chosen seat is only known here, so the reveal (and the offer built from it) must happen now —
// SWURevealResources both logs the look and decides WHICH 3 are shown, so it cannot run before the pick.
$customDQHandlers["SEC_242#PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $playerID = $caster;
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $offer = SWURevealResources($caster, $opp, 3);
    if (empty($offer)) return;
    // Carry the revealed mzIDs so the handler can enforce "only a REVEALED resource may be defeated".
    SWUQueueMayChooseTarget($caster, $offer, "Defeat_an_enemy_resource?", "Choose_an_enemy_resource_(of_3)",
        "SEC_242#0|" . $opp . "|" . implode(',', $offer));
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
