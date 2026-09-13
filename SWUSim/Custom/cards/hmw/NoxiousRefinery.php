<?php
// HMW_160
// Cost 4 - Noxious Refinery - [Aggression][Villainy] - Upgrade - Trait: Fortification - NON-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "When the regroup phase starts: Reveal the top card of your deck.
//                             If it's Aggression, deal 1 damage to an enemy unit."
//
// FORTIFY needs no code (HMW_160 is in $Fortify_Cards; SWUGetUpgradeValidTargets routes it to
// ['myBase-0']). Direct sibling: HMW_070 Dark Sanctum in this same set — the base-hosted regroup
// trigger, the per-copy loop and the RegroupPhaseStart call site all mirror it.
//
// ⚠ REVEAL ≠ LOOK ≠ DRAW. "Reveal the top card of your deck" shows it to everyone and LEAVES IT THERE:
// the deck count must not change, and the card must still be on top afterwards. It is not milled, not
// drawn, and not bottomed. That is the whole reason RevealDoesNotMoveOrLoseTheCard exists — every other
// section would pass against an implementation that consumed the card.
//
// ⚠ "IF IT'S AGGRESSION" reads the revealed card's ASPECT, and a card can carry several: CardAspect
// returns a comma-joined string, so a dual-aspect [Aggression][Villainy] card DOES satisfy it. Matching
// the whole string (=== 'Aggression') instead of a substring would silently exclude every dual card,
// which is most of them.
//
// The damage is MANDATORY (no "may") and "an ENEMY unit" is scoped to the base's controller, so it is a
// plain MZCHOOSE over that seat's opponents' units. With no enemy unit in play it simply fizzles — the
// reveal still happened.
//
// Fires once PER ATTACHED COPY (non-unique, each copy grants its own instance), exactly like HMW_070.
// ⚠ Each copy reveals AGAIN — and the second reveal sees the SAME top card, because a reveal does not
// move it. So two copies on an Aggression top card deal 1 twice, not 1.

// ONE copy's trigger for seat $p — the regroup-start trigger window resolves each copy as its own item
// (GameLogic _SWURegroupStartResolve 'HMW160'), so copies can be ordered against other regroup triggers.
function _SWUHmw160ResolveOne(int $p): void {
    global $playerID;
    $saved = $playerID;
    $playerID = $p;
    $deck = GetDeck($p);
    $top  = null;
    foreach ($deck as $d) { if (empty($d->removed)) { $top = $d; break; } }
    if ($top === null) { $playerID = $saved; return; }        // empty deck — nothing to reveal
    $topID = $top->CardID ?? '';
    // ⚠ Was written with visibility 0 — which matches NO seat tag and is not 'ALL', so nobody (not
    // even the revealing player) ever saw it. A reveal is public.
    AddGameLogEntry('REVEAL', 'P' . $p . ' revealed ' . GameLogCardRef($topID)
        . ' from the top of their deck (' . GameLogCardRef('HMW_160') . ')', 'ALL');
    if (strpos(CardAspect($topID) ?? '', 'Aggression') !== false) {
        // "an ENEMY unit" — relative to the base's controller. Mandatory, so a plain choose; it
        // fizzles cleanly when that seat's opponents control nothing.
        // Queued with HMW_160 as the log source, so the damage line names it when the choice resolves
        // (the queued-source stamp — see GameOnDecisionAdded).
        SWULogWithSource($p, 'HMW_160', fn() => SWUOfferUnitTarget($p, '', [
            'continuation' => 'DEAL_UNIT_DAMAGE',
            'amount'       => 1,
            'side'         => 'their',
            'prompt'       => 'Deal_1_damage_to_an_enemy_unit_(Noxious_Refinery)',
        ]));
    }
    $playerID = $saved;
}
