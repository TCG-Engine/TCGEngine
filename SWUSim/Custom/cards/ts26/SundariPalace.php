<?php
// TS26_12
// Sundari Palace - [Cunning] - HP 27
// Text: 
// Epic Action: For each friendly leader unit, you may resource a card from your hand and ready it. If you do, defeat that many friendly resources at the start of the regroup phase.

$baseAbilities["TS26_12"] = function($player) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    SundariPalaceOffer(intval($player), $n);
};

$customDQHandlers["TS26_12#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) {
        SWUAfterAction(intval($player)); return;   // declined → stop the loop
    }
    SWURampResourceReady(intval($player), $lastDecision);              // hand card → resource, READY
    AddGlobalEffects(intval($player), 'SWU_SUNDARI_DEFEAT');           // defeat 1 friendly resource at regroup
    SundariPalaceOffer(intval($player), $remaining - 1);
};

// TS26_12 Sundari Palace — Epic Action: for each friendly leader unit, you may resource a card from your
// hand and ready it; if you do, defeat that many friendly resources at the start of the regroup phase.
function SundariPalaceOffer(int $player, int $remaining): void {
    global $playerID; $playerID = intval($player);
    if ($remaining <= 0) { SWUAfterAction($player); return; }
    $hand = ZoneSearch("myHand");
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget($player, $hand, "Resource_a_card_from_hand_and_ready_it?", "Choose_a_card", "TS26_12#0|{$remaining}");
}
