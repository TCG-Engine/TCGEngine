<?php
// SEC_178
// Cost 2 - Pursue the Lead - [Aggression]
// Text: Choose a player. That player discards a card from their hand. If it costs 3 or less, create a Spy token.

// SEC_178 Pursue the Lead — #0: the chosen player picks a card to discard; #1: discard it and, if it
// cost 3 or less, the caster creates a Spy token.
$customDQHandlers["SEC_178#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster    = intval($parts[0] ?? $player);
    $discarder = SWUDecodePlayerPick($lastDecision, $caster); // "You"→caster, "Opponent"/"P{n}"→that player
    $playerID  = $discarder;
    // ZoneSearch still returns cards already marked removed — when the caster discards from their own
    // hand, the in-flight Pursue the Lead has already been Removed to discard, so exclude removed cards.
    $hand = array_values(array_filter(ZoneSearch("myHand"), function($mz){
        $o = GetZoneObject($mz); return $o !== null && empty($o->removed);
    }));
    if (empty($hand)) return;
    if (count($hand) === 1) {
        // Single card → resolve synchronously (a cross-player PASSPARAMETER auto-resolve is fragile).
        $mz   = $hand[0];
        $o    = GetZoneObject($mz);
        $cost = ($o !== null) ? intval(CardCost($o->CardID ?? '')) : 99;
        DoDiscardCard($discarder, $mz);
        if ($cost <= 3) SWUCreateUnitToken($caster, 'SEC_T01');
        return;
    }
    SWUQueueChooseTarget($discarder, $hand, "Discard_a_card_from_your_hand", "SEC_178#1|{$caster}");
};

$customDQHandlers["SEC_178#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the discarder
    $caster = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cost = intval(CardCost($o->CardID ?? ''));
    DoDiscardCard(intval($player), $lastDecision);
    if ($cost <= 3) SWUCreateUnitToken($caster, 'SEC_T01');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_178:0"] = function($player, $mzID = '') {
// Pursue the Lead — "Choose a player. That player discards a card from their
                          // hand. If it costs 3 or less, create a Spy token."
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1,
                tooltip: "Choose_a_player_to_discard_a_card");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_178#0|" . intval($player), 1);
            return;
};
