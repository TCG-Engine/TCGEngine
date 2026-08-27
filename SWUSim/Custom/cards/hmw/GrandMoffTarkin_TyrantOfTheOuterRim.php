<?php
// HMW_004
// Cost 9 - Grand Moff Tarkin - Tyrant of the Outer Rim - [Vigilance,Villainy] - Power 2 - HP 12 - Space
//   Traits: Imperial, Official — deployed side: The Death Star (Imperial, Vehicle, Capital Ship, Space)
// Text: Ignore the aspect penalties on upgrades with Fortify you play.
// DeployText: Ignore the aspect penalties on upgrades with Fortify you play. /
//             When the regroup phase starts: You may defeat a base with 10 or less remaining HP.
// Epic Action: If you control 9 or more resources, deploy this leader.
//
// The aspect waiver is printed on BOTH faces, so it lives at the single cost chokepoint SWUAspectPenalty
// (keyed on _SWUControlsTarkinHmw004, which does not care whether he is deployed) — that one edit covers
// every play path: hand, discard, resources, and the affordability glow.
//
// The regroup clause is DEPLOYED-only and is collected in _SWUHmw004RegroupBaseDefeat (called from
// RegroupPhaseStart); only its resolution lives here. Defeating a base is not a separate board state in
// SWU — a base with damage >= its HP IS defeated and its owner immediately loses the game (SWU CR, base
// section), so SWUDefeatBase fills the damage in and lets the existing state-based sweep declare the result.

$customDQHandlers["HMW_004#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;          // "You may" — declining changes nothing
    // ⚠ ANY base can be offered now (see _SWUHmw004RegroupBaseDefeat), so the seat must be read out of
    // the chosen mzID. The old my/their string match collapsed every non-"my" pick onto seat 2 — which,
    // for an ability whose whole effect is "that player loses the game", is the worst possible place to
    // guess. Choosing your own base is still legal; it just loses you the game.
    $seat = SWUMzOwner((string)$lastDecision, intval($player));
    if ($seat > 0) SWUDefeatBase($seat);
};
