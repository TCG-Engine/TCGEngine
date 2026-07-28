<?php
// SEC_220
// Cost 3 - Hired Slicer - [Cunning] - Power 3 - HP 4
// Text: On Attack: Reveal the top 2 cards of a deck. If you do, you may exhaust a unit that shares a Trait with one of those cards. Put those cards on the bottom of that deck in a random order.

// SEC_220 Hired Slicer — On Attack: Reveal the top 2 cards of a deck. If you do, you may exhaust a unit
// that shares a Trait with one of those cards. Put those cards on the bottom of that deck in random order.
// First decision (deck choice) is an OPTIONCHOOSE — safe in OnAttack (fixed labels, not mz-counted); the
// reveal + trait-filtered MZMAYCHOOSE run in continuations (safe from the OnAttack $playerID-restore skip).
$onAttackAbilities["SEC_220:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1,
        tooltip:"Reveal_the_top_2_cards_of_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_220#0", 1);
};

// Deck chosen → reveal (remove) top 2, find trait-sharing units, offer the may-exhaust (else just bottom).
$customDQHandlers["SEC_220#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $deckPlayer = SWUDecodePlayerPick($lastDecision, intval($player)); // "You"→caster, "Opponent"/"P{n}"→that player
    $deck = &GetDeck($deckPlayer);
    $revealed = [];
    for ($k = 0; $k < 2 && count($deck) > 0; $k++) {
        $card = array_shift($deck);
        $revealed[] = $card->CardID;
        AddGameLogEntry('REVEAL', "P{$deckPlayer} revealed " . GameLogCardRef($card->CardID));
    }
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    if (empty($revealed)) return;                                   // empty deck → nothing to reveal
    // Union of the revealed cards' traits.
    $traits = [];
    foreach ($revealed as $cid) {
        $raw = CardTrait($cid);
        if ($raw === null || $raw === '') continue;
        foreach (explode(',', $raw) as $t) { $t = trim($t); if ($t !== '') $traits[$t] = true; }
    }
    $eligible = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (array_keys($traits) as $t) { if (TraitContains($o, $t)) { $eligible[] = $mz; break; } }
    }
    $revealedStr = implode(",", $revealed);
    if (empty($eligible)) { _topDeckPutRemainingToBottom($deckPlayer, $revealed); return; } // no match → bottom
    SWUQueueMayChooseTarget(intval($player), $eligible, "Exhaust_a_trait-sharing_unit?",
        "Choose_a_unit_to_exhaust", "SEC_220#1|{$deckPlayer}|{$revealedStr}");
};

// Exhaust the chosen unit (if any), then bottom the revealed cards in random order.
$customDQHandlers["SEC_220#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $deckPlayer = intval($parts[0] ?? 0);
    $revealed   = ($parts[1] ?? '') === '' ? [] : explode(",", $parts[1]);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') OnExhaustCard(intval($player), $lastDecision);
    if (!empty($revealed)) _topDeckPutRemainingToBottom($deckPlayer, $revealed);
};
