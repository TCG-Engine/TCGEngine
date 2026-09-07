<?php
// HMW_041
// Cost 6 - Keeper of Skara Nal - Awoken - [Aggression][Vigilance] - Unit (Ground) 5/8
// Traits: Droid, Vehicle, Walker - Unique
// Text: Restore 2
//       On Attack: You may discard 2 cards named Keeper of Skara Nal from your hand. If you do, this
//       unit gets +15/+0 and gains Overwhelm for this attack.
//
// Restore 2 is generated (GeneratedKeywordCode carries 'HMW_041' => 2) — nothing to write for it.
//
// ⚠ PREVIEW SET: HMW is not in card-specific-rulings.md, so the readings below are CR + released
// analogue, pinned by tests rather than by a ruling.
//
// THE COST — "you may discard 2 cards NAMED Keeper of Skara Nal from your hand".
//   • Matched by TITLE, not CardID — the convention IC27_078 Anakin uses for "a card named Darth
//     Vader", so any future printing of this character qualifies. HMW_041 is UNIQUE, so the copy doing
//     the attacking is the only one in play and the two being spent are the deck's other copies
//     sitting in hand; that is the card's whole design, not an edge case.
//   • ALL OR NOTHING. With fewer than two matching cards there is no offer at all, rather than an
//     offer that discards one and fizzles — "discard 2" is a cost, and a cost you cannot pay is not
//     presented (LAW_257's "you may pay 1" bug is the shape being avoided). Three separate gates reach
//     that state and each has its own section: one copy, an empty hand, and a full hand of non-Keepers.
//   • EXACTLY two, never "all matching" — a third copy stays in hand.
//
// THE RIDERS — "if you do, this unit gets +15/+0 AND gains Overwhelm for this attack". Joined by
// "if you do", so BOTH are gated on the discard actually happening; a decline leaves a plain 5/8 whose
// excess damage is lost rather than spilling.
//
// Format triage: the text names no player and uses neither "friendly" nor "enemy" — "your hand" and
// "this unit" are self-scoped — so Premier, Twin Suns and Team Suns share this one code path and 2P
// coverage is complete for this card.

// Hand mzIDs of every card whose TITLE matches this one, in zone order.
function _SWUHmw041CopiesInHand(int $player): array {
    $out = [];
    foreach (ZoneSearch("myHand", null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (CardTitle($o->CardID ?? '') === 'Keeper of Skara Nal') $out[] = $mz;
    }
    return $out;
}

$onAttackAbilities["HMW_041:0"] = function ($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (count(_SWUHmw041CopiesInHand(intval($player))) < 2) return;   // cost unpayable → no offer
    // The YESNO is queued directly in the OnAttack closure, which is safe: only a TARGET choose queued
    // here would auto-resolve to nothing (OnAttackTrigger restores $playerID before MZCountChoices
    // runs). Same shape as ASH_172 Razor Crest, the direct analogue for this whole card.
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Discard_2_Keeper_of_Skara_Nal_for_+15/+0_and_Overwhelm?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_041#0|{$mzID}", 1);
};

$customDQHandlers["HMW_041#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined — no discard, no buff, no Overwhelm
    $attackerMz = $parts[0] ?? '';
    if ($attackerMz === '' || !str_contains($attackerMz, '-')) return;
    // Re-read the hand HERE rather than trusting a list captured before the YESNO: the offer and the
    // answer are separated by a request boundary, and nothing may hold game objects across one.
    $copies = _SWUHmw041CopiesInHand(intval($player));
    if (count($copies) < 2) return;   // cost became unpayable between the offer and the answer
    // ⚠ MEASURED, not assumed: discarding in plain zone order is SAFE here, and the descending-order
    // guard this was first written with is genuinely redundant rather than merely untested. MZMove
    // only FLAGS the object (`$removed->Remove()`); the zone is not compacted until
    // CleanupRemovedCards runs, so myHand-1 is still myHand-1 after myHand-0 leaves, and re-passing an
    // already-removed mzID no-ops (GetZoneObject returns null on `removed`). Reversing the order was
    // mutated in and came back GREEN on all 9 sections, so it is gone rather than kept with a
    // justification the next card would copy. Do not "restore" it — the ordering rule that DOES bite
    // is arena mzIDs across a DEFEAT, which compacts.
    $pay = array_slice($copies, 0, 2);
    foreach ($pay as $mz) DoDiscardCard(intval($player), $mz);
    SWUAddAttackPowerBonus($attackerMz, 15);                                        // +15/+0
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'HMW_041'));
};
