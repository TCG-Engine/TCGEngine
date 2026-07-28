<?php
// SOR_147
// Cost 6 - Black One - Scourge of Starkiller Base - [Aggression,Heroism] - Power 4 - HP 4
// Text: When Played/When Defeated: You may discard your hand. If you do, draw 3 cards.

// SOR_147 Black One — When Played/When Defeated: You may discard your hand; if you do, draw 3.
$whenPlayedAbilities["SOR_147:0"] =
$whenDefeatedAbilities["SOR_147:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Discard_your_hand_to_draw_3?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SOR_147#0', 1);
};

$customDQHandlers["SOR_147#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES' && $lastDecision !== '1') return;
    global $playerID;
    $playerID = intval($player);
    $discarded = false;
    foreach (GetHand(intval($player)) as $h) {
        if (!empty($h->removed)) continue;
        $cid = $h->CardID;
        $h->Remove();
        SWUAddToDiscard(intval($player), $cid, 'HAND');
        $discarded = true;
    }
    // SEC_016 Padmé "when you discard 1+ cards from your hand" — fire ONCE for the whole-hand discard
    // (collective), then draw. (This bulk-discard path bypasses DoDiscardCard's inline reaction.)
    if ($discarded && function_exists('_SWUSec016React')) _SWUSec016React(intval($player));
    DoDrawCard(intval($player), 3);
};
