<?php
// IC27_038
// Cost 5 - Admiral Holdo - We Are The Spark - [Vigilance,Heroism] - Unit (Ground) 3/7
// Traits: Resistance, Official - unique
// Text: Draw 1 more card during the regroup phase.
//
// No registrations — DrawPhase() (GameLogic.php) asks _SWURegroupExtraDraws($p) for every live seat and
// folds the answer into that seat's single regroup draw.
//   • PREVIEW ASSUMPTION (no official ruling for a preview card): the extra card is part of the SAME draw
//     instruction — DoDrawCard($p, 2 + N), not a second DoDrawCard($p, N). Under the 2026-09-07 deck-out
//     ruling that makes a deck-out ONE event of 3 × undrawn (an empty deck is one 9, which ASH_070 caps
//     to 4 — a separate instruction would be 6→4 plus 3).
//   • "Draw" is the CONTROLLER's draw: a stolen Holdo gives the thief the card, the owner nothing.
//   • Read at the draw step, not snapshotted. A blanked Holdo (Imprisoned, Galen naming her) gives nothing
//     — _SWUCountActiveUnitsWithCardID skips LostAbilities — while a "for this phase" blank has already
//     expired by the regroup draw.
//   • Counted, not boolean: she is unique, but a TWI_116 Clone copying her is not, and each instance of
//     "draw 1 more card" adds its own card.
// Tests: SWUSim/Tests/Cases/ic27/AdmiralHoldo_WeAreTheSpark.md

function _SWURegroupExtraDraws(int $player): int {
    return _SWUCountActiveUnitsWithCardID($player, 'IC27_038');
}
