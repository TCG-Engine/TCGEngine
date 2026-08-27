<?php
// SHD_153
// Cost 5 - Poe Dameron - Quick to Improvise - [Aggression,Heroism] - Power 6 - HP 6
// Text: On Attack: Discard up to 3 cards from your hand. For each card discarded this way, choose a different option: / <bullet>Deal 2 damage to a unit or base. / Defeat an upgrade. / An opponent discards a card from their hand.</bullet>

// ─── SHD_153 Poe Dameron ───────────────────────────────────────────────────────
// On Attack: Discard up to 3 cards from your hand. For EACH card discarded, choose a DIFFERENT option
// (deal 2 to a unit/base · defeat an upgrade · an opponent discards a card). The discard count K drives
// the modal: SWUQueueModalChoose offers K picks of the 3 distinct options (MODAL_CHOOSE drops each chosen
// label + re-offers). MZMULTICHOOSE is safe in an OnAttack closure (JTL_018); the modal runs from CUSTOMs.
$onAttackAbilities["SHD_153:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $hand = ZoneSearch("myHand", null);
    if (empty($hand)) return;                          // empty hand → nothing to discard, no options
    $max = min(3, count($hand));
    SWUQueueMultiChoose(intval($player), 0, $max, $hand,
        "Discard_up_to_3_cards_from_your_hand", "SHD_153#0");
};

$customDQHandlers["SHD_153#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picked = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && $lastDecision !== '')
        ? explode('&', $lastDecision) : [];
    $objs = [];
    foreach ($picked as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $objs[] = $o; }
    $k = 0;
    foreach ($objs as $o) { $o->removed = true; SWUAddToDiscard(intval($player), $o->CardID ?? '', 'HAND'); $k++; }
    if ($k > 0) {
        DecisionQueueController::CleanupRemovedCards();
        SWUQueueModalChoose(intval($player), 'SHD_153', ['Deal2', 'DefeatUpgrade', 'OppDiscard'], min($k, 3), 1);
    }
};
