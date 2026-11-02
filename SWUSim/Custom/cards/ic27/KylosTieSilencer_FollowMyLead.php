<?php
// IC27_063
// Cost 4 - Kylo's TIE Silencer - Follow My Lead - [Command,Villainy] - Unit (Space) 4/4 (unique)
//   Traits: First Order, Vehicle, Fighter
// Text: Restore 1. When this unit is discarded from your hand: For this phase, you may play it from your discard
//       pile (paying its cost).
//
// Restore 1 is printed (auto-wired). SHD_135's mechanism restricted to the HAND: OnCardDiscarded runs this
// synchronously on every discard path (forced SWUAddToDiscard and self-chosen DoDiscardCard alike), and only an
// entry that came From HAND is stamped TPP — at cost, this phase (SWUClearDiscardModifiers clears it at regroup).
// A better permission already on the entry (a free TPF, a discounted TPP) is never downgraded.
// Tests: SWUSim/Tests/Cases/ic27/KylosTieSilencer_FollowMyLead.md
$cardDiscardedHandlers['IC27_063:0'] = function(int $player, object $entry): void {
    if (($entry->From ?? '') !== 'HAND') return;
    if (SWUParseDiscardModifier((string)($entry->Modifier ?? ''))['kind'] !== '') return;
    $entry->Modifier = 'TPP';
};
