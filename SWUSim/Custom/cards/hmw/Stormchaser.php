<?php
// HMW_180
// Cost 2 - Stormchaser - [Aggression] - Power 3 - HP 2 - Tusken
// Text: When Played: You may reveal a Disaster card from your hand. If you do or if there's a Disaster
//       card in your discard pile, draw a card.
//
// ⚠ The draw is an **OR**, not the far more common "If you do," rider: declining the reveal STILL
// draws whenever a Disaster already sits in the discard pile. Both limbs are checked independently,
// and satisfying both still draws exactly one card.

// "a Disaster card in your discard pile" — the discard is an out-of-play zone, so the bare-CardID
// HasTrait is the correct read here (TraitContains is for IN-PLAY objects, whose traits can be
// granted or suppressed; a card in the discard has only its printed traits).
function _SWUHmw180DisasterInDiscard(int $player): bool {
    global $playerID; $playerID = $player;
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (HasTrait($o->CardID ?? '', 'Disaster')) return true;
    }
    return false;
}

$whenPlayedAbilities["HMW_180:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // NOTE: no CleanupRemovedCards() here, deliberately. The neighbouring hand-offer cards (ASH_132
    // Queen Soruna, LOF_150 Cin Drallig) call it first on the stated grounds that the just-played card
    // still lingers in the hand array as a removed entry, which would push every offered myHand-N off
    // by one. That is NOT true on this path: a UNIT's When Played dispatches from the entry-trigger
    // flush, after ActivateCard has already compacted the hand. Measured 2026-08-24 by dumping the
    // pending offer with and without the call, on BOTH dispatch paths (played from hand, and played by
    // HMW_018 The Warrior's leader action): identical pools, `myHand-1` either way. Adding the call
    // back would be a harmless no-op carrying a false explanation — the kind of comment that gets
    // copied onto the next card and then believed.
    $disasters = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!HasTrait($o->CardID ?? '', 'Disaster')) continue;
        $disasters[] = $mz;
    }

    if (empty($disasters)) {
        // Nothing to reveal — but the SECOND limb of the OR stands on its own, so the discard pile
        // still gets its say. No prompt is raised for a choice that does not exist.
        if (_SWUHmw180DisasterInDiscard(intval($player))) DoDrawCard(intval($player), 1);
        return;
    }

    // Always offered, even when a Disaster in the discard has already guaranteed the draw. Revealing
    // is NOT strictly worse than declining: SEC_016 Padmé Amidala pays the controller for revealing
    // ("When you reveal or discard 1 or more cards from your hand: ... deal 1 damage to a unit"), so
    // auto-declining the "redundant" reveal would silently cost a real player real value.
    SWUQueueMayChooseTarget(intval($player), $disasters,
        "Reveal_a_Disaster_card_from_your_hand?", "Choose_a_Disaster_card_to_reveal", "HMW_180#0");
};

$customDQHandlers["HMW_180#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);

    if (SWUDecisionDeclined($lastDecision)) {
        // Declining loses only the FIRST limb. The discard pile is still checked.
        if (_SWUHmw180DisasterInDiscard(intval($player))) DoDrawCard(intval($player), 1);
        return;
    }

    $revealed = GetZoneObject($lastDecision);
    if (SWUObjGone($revealed)) {
        // The pick evaporated — fall back to the second limb rather than dropping the ability.
        if (_SWUHmw180DisasterInDiscard(intval($player))) DoDrawCard(intval($player), 1);
        return;
    }

    DoRevealCard(intval($player), $lastDecision);
    // A reveal is PUBLIC information, and DoRevealCard only sets a client flash message for the
    // current request — nothing durable the opponent can scroll back to. Log it like every other
    // public reveal (SHD RicketyQuadjumper / SEC CikatroVizago).
    AddGameLogEntry('REVEAL',
        'P' . intval($player) . ' revealed ' . GameLogCardRef($revealed->CardID ?? ''), 'ALL');

    // Both limbs can be true at once; the card draws "a card", so this is one draw either way.
    DoDrawCard(intval($player), 1);

    // Stormchaser's own ability is now fully resolved; observers of the reveal trigger after it
    // (CR 7.6). SEC_016 Padmé queues her decision, so she resolves after this draw regardless.
    if (function_exists('_SWUSec016React')) _SWUSec016React(intval($player));
};
