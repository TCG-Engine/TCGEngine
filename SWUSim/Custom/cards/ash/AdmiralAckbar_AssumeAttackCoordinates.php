<?php
// ASH_110
// Cost 5 - Admiral Ackbar - Assume Attack Coordinates - [Command,Heroism] - Power 6 - HP 6
// Text: When Played: You may defeat this unit. If you do, search the top 10 cards of your deck for any number of space units with combined cost 5 or less and play each of them for free.

// ASH_110 Admiral Ackbar — When Played: you may defeat this unit. If you do, search the top 10 cards of
// your deck for any number of space units with combined cost 5 or less and play each of them for free.
$whenPlayedAbilities["ASH_110:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Defeat_Admiral_Ackbar_to_search_for_free_space_units?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_110#0|{$uid}", 1);
};

$customDQHandlers["ASH_110#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
    DoTopDeckPlay(intval($player), 10, fn($cid) => CardTargetArena($cid) === 'SpaceArena' && strpos(CardType($cid) ?? '', 'Unit') !== false, 5);
};
