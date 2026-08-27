<?php
// JTL_041
// Cost 11 - Annihilator - Tagge's Flagship - [Vigilance,Villainy] - Power 12 - HP 12
// Text: When Played/When Defeated: You may defeat an enemy unit. If you do, search its controller's deck and hand for each card with that unit's name and discard them. (They shuffle their deck.)

// ── JTL_041 Annihilator — When Played/When Defeated: may defeat an enemy unit, then search its
// controller's deck AND hand for every card with that unit's name and discard them (they shuffle). ──
$whenPlayedAbilities["JTL_041:0"] = $whenDefeatedAbilities["JTL_041:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_defeat_an_enemy_unit_(name-hunt_their_deck_and_hand)", "Defeat_an_enemy_unit", "JTL_041#0");
};

$customDQHandlers["JTL_041#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $controller = intval($o->Controller ?? 0);
    if ($controller <= 0) $controller = SWUMzOwner((string)$lastDecision, intval($player));   // never OtherPlayer/GetOpponent: both name one seat
    $name = SWUObjectTitle($o);
    // "If you do, search…" — gate the name-hunt on a defeat EVENT actually firing. SWUDefeatUnit returns
    // false ONLY when the target AVOIDS defeat (immune to enemy-ability defeat, e.g. SHD_187 Lurking TIE
    // Phantom) → no name-hunt, no peek. A defeat that is REPLACED (e.g. L3-37 attaches as an upgrade instead
    // of dying) still returns true — a defeat event fired and the unit leaves the arena as a unit — so the
    // name-hunt DOES run (keys off OnCardDefeated, not residual play-state).
    $defeated = SWUDefeatUnit(intval($player), $lastDecision);
    if (!$defeated) return;
    // "Search its controller's deck and hand …" — reveal both searched zones to the active player as
    // information-only OK popups. Queue them NOW (pre-discard/pre-shuffle) so each snapshot shows the
    // full zone that was searched, including the copies about to be discarded. Only reached when a unit
    // was actually defeated; declining the "may" returns above, so no peek without a defeat.
    AddGameLogEntry('REVEAL', "P" . intval($player) . " searched P{$controller}'s hand and deck for " . $name . " (Annihilator)", 'ALL');
    // ⚠ BOTH zones, and BOTH seat-aware. Two defects here, found 2026-08-27 by asking why the deck
    // counterpart SWUQueueShowOpponentDeck() existed with no caller:
    //   • the DECK reveal was never wired at all, so "search its controller's deck and hand" showed only
    //     the hand — the comment above already promised both.
    //   • the hand reveal omitted $opp, i.e. the legacy two-seat default. $controller is the DETERMINED
    //     seat (the defeated unit's controller); at 3+ seats the bare call revealed a bystander's hand.
    SWUQueueShowOpponentHand(intval($player), $controller);
    SWUQueueShowOpponentDeck(intval($player), $controller);
    // Name-hunt the controller's HAND — unconditional (reveal the hand and discard ALL
    // matches, no choice).
    $hand = &GetHand($controller);
    $handDiscarded = false;
    foreach ($hand as $h) {
        if (!empty($h->removed)) continue;
        if (SWUObjectTitle($h) === $name) { $h->Remove(); SWUAddToDiscard($controller, $h->CardID, 'HAND'); $handDiscarded = true; }
    }
    // SEC_016 Padmé — fire ONCE (collective) if the controller lost 1+ cards from their hand this way.
    if ($handDiscarded && function_exists('_SWUSec016React')) _SWUSec016React($controller);
    // Name-hunt the controller's DECK — per-card OPTIONAL: the searcher chooses WHICH name-matches to
    // discard (may keep some). Splice the matches out and present them for a 0..all multi-select; the
    // JTL_041_DECK_FINALIZE handler discards the chosen, returns the kept ones, and reshuffles the deck.
    $deck = &GetDeck($controller);
    $matchIdx = [];
    foreach ($deck as $i => $c) { if (empty($c->removed) && SWUObjectTitle($c) === $name) $matchIdx[] = $i; }
    if (empty($matchIdx)) { DecisionQueueController::CleanupRemovedCards(); $d0 = &GetDeck($controller); EngineShuffle($d0, true); return; }
    rsort($matchIdx); // splice high→low so earlier indices stay valid
    $matchIDs = [];
    foreach ($matchIdx as $i) { $matchIDs[] = $deck[$i]->CardID; array_splice($deck, $i, 1); }
    $matchIDs = array_reverse($matchIDs);
    foreach ($deck as $i => $card) { $card->mzIndex = $i; }
    DecisionQueueController::CleanupRemovedCards();
    $allIDs  = implode(',', $matchIDs);
    $costMap = implode(',', array_map(fn($cid) => $cid . ':' . intval(CardCost($cid)), $matchIDs));
    $param   = $allIDs . '|' . $allIDs . '|' . 'count:' . count($matchIDs) . '|' . $costMap; // all matches selectable, up to all (0 = take nothing)
    DecisionQueueController::AddDecision(intval($player), "TOPDECKSEARCH", $param, 1, tooltip: "Choose_which_named_cards_to_discard_from_the_opponents_deck");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "JTL_041#1|{$controller}|" . $allIDs, 1);
};

// JTL_041 deck-hunt finalize: the searcher's chosen name-matches are discarded from the DECK OWNER's deck;
// the kept ones go back and the whole deck is reshuffled. $parts[0]=deck owner, $parts[1]=all spliced match
// IDs; $lastDecision = the chosen subset (empty / "PASS" = take nothing).
$customDQHandlers["JTL_041#1"] = function ($player, $parts, $lastDecision) {
  $deckOwner = intval($parts[0] ?? 0);
  if ($deckOwner <= 0)
    return;
  $allIDs = array_values(array_filter(explode(',', $parts[1] ?? '')));
  $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
  foreach ($resolved['drawn'] as $cardID)
    SWUAddToDiscard($deckOwner, $cardID, 'DECK'); // chosen → discard
  _topDeckPutRemainingToBottom($deckOwner, $resolved['remaining']);                      // kept matches returned (shuffled)
  $deck = &GetDeck($deckOwner);
  EngineShuffle($deck, true);                                                            // reshuffle the whole deck
};
