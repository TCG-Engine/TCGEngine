<?php
// TWI_086
// Cost 7 - Admiral Trench - Holding the Line - [Command,Villainy] - Power 5 - HP 5
// Text: Exploit 1 / When Played: Return up to 3 units that were defeated this phase from your discard pile to your hand.

// TWI_086 Admiral Trench — "When Played: Return up to 3 units that were defeated this phase from your
// discard pile to your hand." Eligible = discard units whose CardID is in the defeated-this-phase
// multiset (SWU_DEFEATED_CARD_{id}; CardID-keyed like SOR_091).
$whenPlayedAbilities["TWI_086:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $discard = GetDiscard(intval($player));
    $remaining = [];
    $specs = [];
    for ($i = 0; $i < count($discard); $i++) {
        $o = $discard[$i];
        if (SWUObjGone($o)) continue;
        if (strpos(CardType($o->CardID ?? '') ?? '', 'Unit') === false) continue; // units only
        $cid = $o->CardID ?? '';
        if (!isset($remaining[$cid])) $remaining[$cid] = GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . $cid);
        if ($remaining[$cid] > 0) { $specs[] = "myDiscard-{$i}"; $remaining[$cid]--; }
    }
    if (empty($specs)) return;
    $max = min(3, count($specs));
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
        "0|{$max}|" . implode('&', $specs), 1, tooltip: 'Return_up_to_3_units_defeated_this_phase_to_hand');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_086#0', 1);
};

$customDQHandlers["TWI_086#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    $any = false;
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $o->removed = true;
        AddHand(intval($player), CardID: $o->CardID);
        $any = true;
    }
    if ($any) DecisionQueueController::CleanupRemovedCards();
};
