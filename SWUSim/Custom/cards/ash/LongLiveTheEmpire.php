<?php
// ASH_103
// Cost 2 - Long Live the Empire - [Command,Villainy]
// Text: Defeat a friendly Imperial unit. If you do, resource the top card of your deck.

// ASH_103 Long Live the Empire — defeat the chosen friendly Imperial unit; if defeated, resource the top
// card of the deck (enters the resource zone exhausted).
$customDQHandlers["ASH_103#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDefeatUnit(intval($player), $lastDecision);
    $deck = GetDeck(intval($player));
    if (!empty($deck) && !empty($deck[0]) && empty($deck[0]->removed)) SWURampResourceExhausted(intval($player), 'myDeck-0');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_103:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Imperial')) $tg[] = $mz;
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Defeat_a_friendly_Imperial_unit", "ASH_103#0|" . intval($player));
};
