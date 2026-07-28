<?php
// TWI_088
// Cost 3 - Reprocess - [Command,Villainy]
// Text: Choose up to 4 units in your discard pile. Put them on the bottom of your deck in a random order and create that many Battle Droid tokens.

// TWI_088 Reprocess continuation — put the chosen discard units on the bottom of the deck in a
// random order and create that many Battle Droid tokens.
$customDQHandlers["TWI_088#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    $count = 0;
    $cardIDs = [];
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cardIDs[] = $o->CardID;
        $o->removed = true;
        $count++;
    }
    if ($count > 0) {
        DecisionQueueController::CleanupRemovedCards();
        _topDeckPutRemainingToBottom(intval($player), $cardIDs); // shuffles then appends to deck bottom
        SWUCreateUnitTokens(intval($player), 'TWI_T01', $count);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_088:0"] = function($player, $mzID = '') {
// Reprocess — "Choose up to 4 units in your discard pile. Put them on the
                          // bottom of your deck in a random order and create that many Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $specs = [];
            foreach (ZoneSearch('myDiscard', ['Unit', 'Token Unit']) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $specs[] = $mz;
            }
            if (empty($specs)) return; // no units → "that many" = 0, fizzle cleanly
            $max = min(4, count($specs));
            DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
                "0|{$max}|" . implode('&', $specs), 1,
                tooltip: 'Choose_up_to_4_units_to_bottom_of_deck');
            DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_088#0', 1);
            return;
};
