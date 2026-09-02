<?php
// HMW_247
// Cost 4 - Surveillance Cruiser - [Villainy] - Unit, Space - Power 4 - HP 4
// Traits: Imperial, Vehicle, Capital Ship - non-unique
// Text: When Played: If an opponent controls an Endor, Kashyyyk, Naboo, or Tatooine base, draw a card.
//
// ⚠ "AN OPPONENT CONTROLS" — a player reference, so this is a 3-4 seat card and the two-seat shortcut
// would be a live bug. `OtherPlayer($p)` answers 2 for seat 1 and 1 for EVERY other seat, so at four
// seats it would inspect one arbitrary base and miss the rest. OpponentsOf() is also team-aware, which
// is what makes a TEAMMATE's matching base correctly NOT count in Team Suns.
// Pinned by TwinSuns_AFarSeatsBaseCounts (P2 ordinary, only far-seat P3 matches — cannot pass at two
// seats) and TeamSuns_TeammatesBaseDoesNotCount.
//
// ⚠ AND THE SCOPING TRAP THIS CARD SITS ON. The shared helper is
// `_SWUControlsBaseWithTrait($player, $trait)`, which reads ONE seat's own base — the HMW base-condition
// family (HMW_142 Kashyyyk, HMW_234 Tatooine, HMW_177 Endor) all pass it the CONTROLLER, because their
// text reads "while YOU control a <X> base". This card is the mirror image: it asks about an OPPONENT's
// base, so the helper must be handed each opponent's seat, never the caster's. Passing $player here
// still satisfies every positive section in the file — OnlyMyOwnBaseMatches_NoDraw is the one that
// catches it.
//
// FOUR named traits, and they are a set rather than one condition repeated: a handler wired for a single
// trait passes whichever positive happens to use it. Each has its own section.
// "Draw A card" is singular: one card however many opponents or traits match, hence the early return.

$whenPlayedAbilities["HMW_247:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    static $BASE_TRAITS = ['Endor', 'Kashyyyk', 'Naboo', 'Tatooine'];
    foreach (OpponentsOf(intval($player)) as $opp) {
        foreach ($BASE_TRAITS as $trait) {
            if (_SWUControlsBaseWithTrait(intval($opp), $trait)) {
                DoDrawCard(intval($player), 1);
                return;
            }
        }
    }
};
