<?php
// TS26_29
// Cost 4 - Ziton Moj - Sowing Chaos - [Aggression,Cunning,Villainy] - Power 4 - HP 4
// Text: Ambush (When you play this unit, he may attack an enemy unit.) / On Attack: For each player, deal 1 damage to a unit that player controls.

// TS26_29 Ziton Moj — Ambush. On Attack: for each player, deal 1 damage to a unit that player controls.
// (Queued via an intermediate CUSTOM so the mid-combat picks resolve under the caster — the OnAttack
// closure-level MZCHOOSE-skip only affects a decision queued directly in the closure.)
$onAttackAbilities["TS26_29:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_29#0", 1);
};

// "For EACH PLAYER, deal 1 damage to a unit that player controls." One MZMULTICHOOSE over every unit in
// play, then all the damage at once.
//
// ⚠ REWRITTEN 2026-08-24. The old shape was a two-step sequential pick (a friendly unit, then an enemy
// unit) and was wrong twice over:
//   • TWO SEATS ONLY — "each player" reached the caster and one opponent; seats 3/4 were never damaged.
//   • SEQUENTIAL DAMAGE — OFFICIAL RULING (06/04/2026): "All damage dealt by Ziton's On Attack ability is
//     dealt simultaneously." Applying it one pick at a time lets an early death re-index the arenas and
//     change what the later picks resolve to (the multi-unit-loop defeat-shift family).
//
// USER RULINGS 2026-08-24:
//   • "Each player" INCLUDES Ziton's own controller — the attacker must damage one of their own units.
//   • The pick is MANDATORY for every player who controls a unit; a player with none is simply skipped
//     and the required pick count drops. It is not gated on everyone having a unit.
//   • Ziton MAY KILL HIMSELF this way (he is a legal pick, and the only one when he is alone on your
//     board). Everyone else still takes their 1 — but his COMBAT damage then does not resolve, which
//     falls out for free because On Attack resolves before the damage step and the attacker is gone.
$customDQHandlers["TS26_29#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // Units grouped by CONTROLLING seat, in the caster's frame (SWUAllUnits() is the canonical 4-arena
    // merge; SWUMzOwner decodes the seat from each mzID in every format).
    $bySeat = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $bySeat[SWUMzOwner($mz, intval($player))][] = $mz;
    }
    if (empty($bySeat)) return;                       // no unit anywhere → nothing to do
    $all  = [];
    foreach ($bySeat as $mzs) foreach ($mzs as $mz) $all[] = $mz;
    $need = count($bySeat);                            // exactly one per seat THAT HAS a unit
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
        "{$need}|{$need}|" . implode('&', $all), 1, "Deal_1_to_one_unit_each_player_controls");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_29#1", 1);
};

$customDQHandlers["TS26_29#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    // ⚠ RE-VALIDATE ONE-PER-SEAT SERVER-SIDE. MZMULTICHOOSE's min/max only constrains the COUNT — it
    // cannot express "one per player", so a crafted answer could put all picks on one seat's board.
    // The client cap is UX; this is the rule.
    $seen = [];
    $uids = [];
    foreach (explode('&', (string)$lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $seat = SWUMzOwner($mz, intval($player));
        if (isset($seen[$seat])) continue;             // second pick on the same seat → ignore it
        $seen[$seat] = true;
        $uids[] = intval($o->UniqueID ?? 0);           // carry UIDs: indices shift as units die
    }
    if (empty($uids)) return;
    // Simultaneous: open the batch window so "when a unit is defeated" observers see the whole batch as
    // one event, and resolve each target by UID so an earlier death cannot re-point a later mzID.
    SWUSimulDefeatBegin();
    foreach ($uids as $uid) {
        if ($uid <= 0) continue;
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player));
    }
    SWUSimulDefeatEnd();
};
