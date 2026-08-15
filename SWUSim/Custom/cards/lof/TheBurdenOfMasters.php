<?php
// LOF_125
// Cost 1 - The Burden of Masters - [Command]
// Text: Put a Force unit from your discard pile on the bottom of your deck. If you do, play a unit from your hand and give 2 Experience tokens to it.

// LOF_125 The Burden of Masters — put the chosen Force unit on the bottom of the deck, then play a unit
// from hand granting it 2 Experience ($gPlayGrantExp entry hook).
$customDQHandlers["LOF_125#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck(intval($player));
    $obj = new Deck($cardID, 'Deck', intval($player));
    $obj->mzIndex = count($deck);
    array_push($deck, $obj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    // "If you do" → play a unit from hand and give it 2 Experience tokens.
    $hand = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0);
    $targets = [];
    foreach ($hand as $mz) { $h = GetZoneObject($mz); if ($h !== null && empty($h->removed)) $targets[] = $mz; }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_from_your_hand_(gets_2_Experience)", "LOF_125#1", may: true);
};

$customDQHandlers["LOF_125#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantExp;
    $playerID  = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantExp = 2;
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantExp = null;
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_125:0"] = function($player, $mzID = '') {
// The Burden of Masters — "Put a Force unit from your discard pile on the bottom
                          // of your deck. If you do, play a unit from your hand and give 2 Experience to it."
            global $playerID; $playerID = intval($player);
            $force = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $force[] = $mz;
            }
            if (empty($force)) return;
            SWUQueueChooseTarget(intval($player), $force, "Put_a_Force_unit_from_discard_on_the_bottom_of_your_deck", "LOF_125#0");
            return;
};
