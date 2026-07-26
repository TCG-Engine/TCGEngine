<?php
// LOF_234
// Cost 5 - Darth Malak - Covetous Apprentice - [Villainy] - Power 4 - HP 7
// Text: Overwhelm / When Played: If you control a Sith leader unit, you may ready this unit.

// LOF_234 Darth Malak — Overwhelm + When Played: if you control a Sith leader unit, may ready this unit.
$whenPlayedAbilities["LOF_234:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hasSithLeader = false;
    foreach (GetLeader(intval($player)) as $l) {
        if (!empty($l->removed) || empty($l->Deployed)) continue;
        if (HasTrait($l->CardID ?? '', 'Sith')) { $hasSithLeader = true; break; }
    }
    if (!$hasSithLeader) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Ready_this_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_234#0|{$uid}", 1);
};

$customDQHandlers["LOF_234#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
