<?php
// LOF_104
// Cost 6 - Luminous Beings - [Command,Heroism]
// Text: Put up to 3 Force units from your discard pile on the bottom of your deck in a random order. Give that many units +4/+4 for this phase.

// LOF_104 Luminous Beings — chosen Force units leave the discard for the bottom of the deck (random
// order); then give that many units +4/+4 for this phase (player picks exactly that many units).
$customDQHandlers["LOF_104#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $chosen = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS')
        ? array_values(array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS')) : [];
    if (empty($chosen)) return;
    $cardIDs = [];
    foreach ($chosen as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cardIDs[] = $o->CardID;
        $o->removed = true;
    }
    DecisionQueueController::CleanupRemovedCards();
    shuffle($cardIDs);
    $deck = &GetDeck(intval($player));
    foreach ($cardIDs as $cid) { $obj = new Deck($cid, 'Deck', intval($player)); $obj->mzIndex = count($deck); array_push($deck, $obj); }
    foreach ($deck as $i => $card) { $card->mzIndex = $i; }
    $n = count($cardIDs);
    if ($n === 0) return;
    $units = SWUAllUnits();
    if (empty($units)) return;
    $pick = min($n, count($units));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "{$pick}|{$pick}|" . implode('&', $units), 1,
        tooltip: "Give_+4/+4_to_{$pick}_unit(s)_this_phase");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_104#1", 1);
};

$customDQHandlers["LOF_104#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        SWUApplyPhaseBuff($mz, 4, 4, 'LOF_104');
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_104:0"] = function($player, $mzID = '') {
// Luminous Beings — "Put up to 3 Force units from your discard pile on the bottom
                          // of your deck in a random order. Give that many units +4/+4 for this phase."
            global $playerID; $playerID = intval($player);
            $force = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $force[] = $mz;
            }
            if (empty($force)) return;
            $max = min(3, count($force));
            DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $force), 1,
                tooltip: "Choose_up_to_3_Force_units_from_your_discard");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_104#0", 1);
            return;
};
