<?php
// SEC_090
// Cost 9 - Director Krennic - I Lose Nothing But Time - [Command,Villainy] - Power 8 - HP 10
// Text: Sentinel / When this unit is attacked: Discard a card from your deck. If it's a unit, you may return it to your hand.

// SEC_090 Director Krennic — Sentinel + On Defense (when this unit is attacked): discard a card from
// your deck; if it's a unit, you may return it to your hand. Mill is immediate; the "may return" YESNO
// holds the combat-pause (set by OnDefenseTrigger) so it resolves before combat damage.
$onDefenseAbilities["SEC_090:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;                                   // empty deck → nothing milled
    if (strpos(CardType($milled), 'Unit') === false) return;        // not a unit → no return option
    $title = str_replace(' ', '_', CardTitle($milled));
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip: "Return_{$title}_to_your_hand?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SEC_090#0|{$milled}", 1);
};

$customDQHandlers["SEC_090#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    if ($cardID === '') return;
    $dmz = _SWUFindDiscardMzID(intval($player), $cardID);           // the just-milled copy in discard
    if ($dmz === null) return;
    SWUReturnFromDiscardToHand(intval($player), $dmz);
};
