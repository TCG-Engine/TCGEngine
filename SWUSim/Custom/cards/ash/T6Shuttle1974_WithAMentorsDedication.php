<?php
// ASH_109
// Cost 4 - T-6 Shuttle 1974 - With a Mentor's Dedication - [Command,Heroism] - Power 2 - HP 6
// Text: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / Action [Exhaust]: Give another unit +2/+2 for this phase. You may attack with that unit.

// ASH_109 T-6 Shuttle 1974 — Action [Exhaust]: give another unit +2/+2 for this phase. You may attack
// with that unit.
$unitAbilities["ASH_109"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $uid = SWUObjUID($self, 0);
    $tg = [];
    foreach (SWUAllUnits() as $mz) { $o = GetZoneObject($mz); if ($o && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $tg[] = $mz; }
    if (empty($tg)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $tg, "Give_another_unit_+2/+2_this_phase", "ASH_109#0");
};

$customDQHandlers["ASH_109#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction($player); return; }
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'ASH_109');
    $o = GetZoneObject($lastDecision);
    $tuid = SWUObjUID($o, 0);
    // "You may attack with that unit" — only if it's ready (a unit that's exhausted can't attack).
    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Attack_with_that_unit?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "ASH_109#1|{$tuid}", 1);
    } else {
        SWUAfterAction($player);
    }
};

$customDQHandlers["ASH_109#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') { SWUAfterAction($player); return; }
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) { SWUAfterAction($player); return; }
    BeginSWUAttack($player, $mz);   // combat owns the after-action
};
