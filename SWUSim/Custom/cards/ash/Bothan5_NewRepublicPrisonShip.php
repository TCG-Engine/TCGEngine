<?php
// ASH_128
// Cost 5 - Bothan-5 - New Republic Prison Ship - [Command] - Power 4 - HP 5
// Text: When another friendly non-Vehicle unit is defeated: You may have this unit capture that unit from your discard pile. Use this ability only once each round.

$customDQHandlers["ASH_128#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined
    $captorUID = intval($parts[0] ?? 0);
    $cardID    = strval($parts[1] ?? '');
    $captorMz  = SWUFindMzByUID($captorUID);
    if ($captorMz === null) return;
    $captor = GetZoneObject($captorMz);
    if (SWUObjGone($captor)) return;
    // find the just-defeated card in the controller's discard (most recent copy)
    $disc = &GetDiscard(intval($player));
    $idx = -1;
    for ($i = count($disc) - 1; $i >= 0; $i--) {
        if (!empty($disc[$i]->removed)) continue;
        if (($disc[$i]->CardID ?? '') === $cardID) { $idx = $i; break; }
    }
    if ($idx < 0) return;
    $owner = intval($disc[$idx]->Owner ?? $player);
    $disc[$idx]->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    if (!is_array($captor->Subcards)) $captor->Subcards = [];
    $captor->Subcards[] = (object)[
        'CardID' => $cardID, 'Owner' => $owner, 'Controller' => intval($player),
        'TurnEffects' => [], 'IsPilot' => false, 'IsCaptive' => true,
    ];
    AddGlobalEffects(intval($player), 'SWU_ASH128_USED');   // once each round
    AddGameLogEntry('CAPTURE', 'P' . intval($player) . ' captured ' . GameLogCardRef($cardID) . ' from discard with Bothan-5');
};
