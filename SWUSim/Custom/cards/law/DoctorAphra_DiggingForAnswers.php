<?php
// LAW_194
// Cost 4 - Doctor Aphra - Digging For Answers - [Aggression] - Power 4 - HP 5
// Text: On Attack: Discard 3 cards from your deck. You may return an Underworld card discarded this way to your hand.

// LAW_194 Doctor Aphra — On Attack: discard 3 from your deck. You may return an Underworld card
// discarded this way to your hand.
$onAttackAbilities["LAW_194:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $milled = [];
    for ($i = 0; $i < 3; $i++) { $cid = SWUMillTopCard(intval($player)); if ($cid !== null) $milled[] = $cid; }
    $targets = []; $usedIdx = [];
    $discard = GetDiscard(intval($player));
    foreach ($milled as $cid) {
        if (!_SWUCardHasTrait(intval($player), $cid, 'Underworld')) continue; // LAW_212 Malakili: owned Creatures count
        for ($j = 0; $j < count($discard); $j++) {
            if (in_array($j, $usedIdx, true) || !empty($discard[$j]->removed)) continue;
            if (($discard[$j]->CardID ?? '') === $cid) { $targets[] = "myDiscard-{$j}"; $usedIdx[] = $j; break; }
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_an_Underworld_card_to_hand?", "Choose_a_card", "LAW_203#0");  // reuse LAW_203#0 (return-from-discard)
};
