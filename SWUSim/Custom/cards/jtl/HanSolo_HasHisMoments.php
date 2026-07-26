<?php
// JTL_203
// Cost 5 - Han Solo - Has His Moments - [Cunning,Heroism] - Power 4 - HP 5 - Upgrade Power 2 - Upgrade HP 3
// Text: Ambush / Piloting [2 resources Cunning Heroism] / When played as an upgrade: You may attack with attached unit. If it's the Millennium Falcon, it deals its combat damage before the defender.

// JTL_203 Han Solo (pilot) — When played as an upgrade: You may attack with the attached unit. If it's
// the Millennium Falcon, it deals its combat damage before the defender (SHOOT_FIRST marker).
$whenPlayedAsUpgradeAbilities["JTL_203:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host) || intval($host->Status) !== 1) return; // must be ready
    $uid = intval($host->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Attack_with_the_attached_unit?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_203#0|{$uid}", 1);
};

$customDQHandlers["JTL_203#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    if (CardTitle($obj->CardID ?? '') === 'Millennium Falcon') AddTurnEffect($mz, 'SHOOT_FIRST');
    BeginSWUAttack(intval($player), $mz);
};
