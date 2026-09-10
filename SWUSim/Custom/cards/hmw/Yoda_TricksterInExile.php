<?php
// HMW_056
// Cost 3 - Yoda, Trickster In Exile - [Cunning][Vigilance][Heroism] - Unit (Ground) 4/4
// Traits: Force, Fringe, Jedi - unique
// Text: Hidden
//       When Defeated: You may put this card from your discard pile on top of your deck. If you do, heal 2
//       damage from your base.
//
// Hidden needs no code ($Hidden_Cards; enforced by _SWUHiddenBlocksAttack).
//
// When Defeated: a YESNO (the optional half names no target), then HMW_056#0 moves the card and heals.
//   • "THIS card from YOUR discard pile": a defeated unit goes to its OWNER's discard, but its CONTROLLER
//     resolves the When Defeated. For a stolen Yoda those differ — the card is not in the resolver's pile,
//     so nothing happens. The resolver's discard may still hold an OLDER Yoda of their own, which is not
//     "this card"; the gate that tells them apart is the OWNER-keyed SWU_DEFEATED_CARD_ multiset (stamped
//     on every defeat path, cleared at regroup): if the resolver owned no Yoda defeated this phase, this
//     card cannot be in their pile. A TWI_116 Clone copy is stamped under the copied CardID but reverts to
//     TWI_116 in the discard, so _SWUFindSelfInDiscardMzID (newest first, Clone-aware) finds the card.
//   • "IF YOU DO": the heal is gated on the card actually having moved, re-found at answer time.
//   • Always offered when the card is there, even on an undamaged base — recurring Yoda is the point;
//     the heal is a rider, clamped at 0 by OnHealBase.
$whenDefeatedAbilities["HMW_056:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_HMW_056') <= 0) return;   // not the owner
    if (_SWUFindSelfInDiscardMzID(intval($player), 'HMW_056') === null) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Put_Yoda_on_top_of_your_deck?_If_you_do,_heal_2_damage_from_your_base.");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_056#0", 1);
};

$customDQHandlers["HMW_056#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') return;
    $mz = _SWUFindSelfInDiscardMzID(intval($player), 'HMW_056');
    $o = $mz !== null ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) return;                         // left the discard before the answer — no heal
    $cid = (string)($o->CardID ?? '');
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    // TOP of the deck is index 0 (the engine-wide convention); reindex after the unshift.
    $deck = &GetDeck(intval($player));
    array_unshift($deck, new Deck($cid, 'Deck', intval($player)));
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    OnHealBase(intval($player), intval($player), 2);
};
