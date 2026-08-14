<?php
// LOF_219
// Cost 1 - Psychometry - [Cunning]
// Text: Choose another card in your discard pile. Search the top 5 cards of your deck for a card that shares a trait with the chosen card, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LOF_219 Psychometry — the chosen discard card sets the trait filter; search the top 5 for a card that
// shares any of its traits, reveal+draw it (rest to the bottom in random order).
$customDQHandlers["LOF_219#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $want = array_values(array_filter(array_map('trim', explode(',', CardTrait($o->CardID ?? '') ?? ''))));
    // The chosen discard card can carry a GRANTED trait too (LAW_212 Malakili: owned out-of-play
    // Creatures gain Underworld) — the share works in both directions.
    if (!in_array('Underworld', $want, true) && _SWUCardHasTrait(intval($player), $o->CardID ?? '', 'Underworld')) $want[] = 'Underworld';
    if (empty($want)) return; // a traitless card can share no trait → nothing to find
    $searcher = intval($player);
    _topDeckSearchBegin($searcher, 5, function($cid) use ($want, $searcher) {
        foreach ($want as $w) { if (_SWUCardHasTrait($searcher, $cid, $w)) return true; }
        return false;
    }, "count:1", "TOPDECKSEARCH_FINALIZE");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_219:0"] = function($player, $mzID = '') {
// Psychometry — "Choose another card in your discard pile. Search the top 5 cards
                          // of your deck for a card that shares a trait with it, reveal it, and draw it."
            global $playerID; $playerID = intval($player);
            $cards = []; $skipped = false;
            $myD = GetDiscard($player);
            for ($i = 0; $i < count($myD); $i++) {
                $c = $myD[$i];
                if (SWUObjGone($c)) continue;
                if (!$skipped && ($c->CardID ?? '') === 'LOF_219') { $skipped = true; continue; } // "another card"
                $cards[] = "myDiscard-{$i}";
            }
            if (empty($cards)) return;
            SWUQueueChooseTarget(intval($player), $cards, "Choose_a_discard_card_(search_top_5_for_a_shared-trait_card)", "LOF_219#0");
            return;
};
