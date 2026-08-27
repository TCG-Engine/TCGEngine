<?php
// HMW_169
// Cost 5 - Crosshair, I've Changed - [Aggression,Heroism] - Power 5 - HP 6 - Clone
// Text: When this unit is dealt damage and survives: Each player draws a card.
//       When an opponent draws 1 or more cards during the action phase: Deal 2 damage to their base.

// ─── HMW_169 Crosshair, I've Changed ─────────────────────────────────────────
// TWO clauses that FEED EACH OTHER, and that is the card: clause 1 makes the opponent draw, which is
// exactly what clause 2 punishes. They are wired into two different shared hooks and are otherwise
// independent — each has its own negative.
//
// CLAUSE 1 — the same self observer as HMW_211 Tech: _SWUOnUnitDamaged, hooked BELOW its $survived gate.
// "Dealt damage", not "dealt COMBAT damage", so all three damage funnels count (official ruling on the
// identical wording — Jabba the Hutt, Wonderful Human Being, 10/31/2025). Queued as an intermediate
// CUSTOM rather than run inline: it fires mid-combat, and what it does (draw for every seat) can itself
// raise decisions on other seats' queues, which belongs after the combat cleanup.
// "EACH PLAYER draws" is a LOOP over every LIVE seat — the caster included, and in a team game the
// teammate too ("each player" is not team-scoped).
//
// CLAUSE 2 — a field observer on _SWUOnPlayerDrew, which already carries the "during the action phase"
// gate every reaction below it shares. Three things it is NOT:
//   • not per CARD — "draws 1 OR MORE cards" is per DRAW EVENT, so a two-card draw is one trigger;
//   • not "a player" — "an OPPONENT", so the controller's own draw never punishes them (contrast
//     SEC_159 Chairman Papanoida in this same hook, which really is any player);
//   • not a teammate — OpponentsOf() excludes one, so in Team Suns a partner's draw is free.
// "THEIR base" is DETERMINED by who drew, so there is no prompt anywhere in this card.
//
// ⚠ Clause 2 is deliberately NOT written like its neighbour JTL_111 Seasoned Fleet Admiral, which reads
// `$reactor = OtherPlayer($drawingPlayer)` — that is the documented two-seat hardcode (OtherPlayer
// answers 2 for seat 1 and 1 for everyone else), so at four seats JTL_111 only ever considers ONE of the
// drawing player's opponents. Loop OpponentsOf() instead.

// Clause 1's queued reaction: every live seat draws one card, in player order from the controller.
$customDQHandlers["HMW_169#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $ctrl = intval($player);
    if ($ctrl <= 0) return;
    foreach (SWUSeatsInPlayerOrder($ctrl) as $seat) {
        $playerID = intval($seat);
        DoDrawCard(intval($seat), 1);        // fires _SWUOnPlayerDrew per seat → clause 2 can answer it
    }
    $playerID = $ctrl;
};

// Clause 1's observer. Called from _SWUOnUnitDamaged BELOW its $survived gate, so the "and survives"
// half is that gate and there is deliberately no survival check here.
function _SWUHmw169CheckObserve($obj, int $amount): void
{
    if ($obj === null || $amount <= 0)
        return;
    if (($obj->CardID ?? '') !== 'HMW_169')
        return;
    if (LostAbilities($obj))
        return;                              // both clauses are Crosshair's OWN abilities
    $ctrl = intval($obj->Controller ?? 0);   // "each player draws" is resolved by the CONTROLLER
    if ($ctrl <= 0)
        return;
    DecisionQueueController::AddDecision($ctrl, "CUSTOM", "HMW_169#0", 1);
}

// Clause 2's observer. Called from _SWUOnPlayerDrew AFTER its action-phase gate, once per draw EVENT.
// $drawingPlayer just drew; every OPPONENT of theirs controlling an active Crosshair deals 2 to the
// drawing player's own base.
function _SWUHmw169CheckDraw(int $drawingPlayer): void
{
    if ($drawingPlayer <= 0)
        return;
    global $playerID;
    $saved = $playerID;
    foreach (OpponentsOf($drawingPlayer) as $opp) {
        $playerID = intval($opp);
        foreach (GetUnitsInPlay(intval($opp)) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'HMW_169')
                continue;
            if (LostAbilities($u))
                continue;
            SWUDealDamageToBase(2, $drawingPlayer, intval($opp));
        }
    }
    $playerID = $saved;
}
