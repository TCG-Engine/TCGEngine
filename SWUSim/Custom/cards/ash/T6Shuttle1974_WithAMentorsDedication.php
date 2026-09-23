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
    // "You may attack with that unit" — only a ready FRIENDLY unit.
    //
    // The BUFF half is unqualified ("another unit") and may legally land on an enemy; the ATTACK half
    // may not. "You can only attack with a ready friendly unit" is the general rule, printed as
    // reminder text on IBH_021/023/030/036/064/092 (owner ruling 2026-09-24) — and the Support keyword
    // already enforces it, SWUGetValidSupportAttackers() scanning myGroundArena/mySpaceArena only.
    //
    // ⚠ Without the controller check this card made an ENEMY unit attack ITS OWN SIDE. BeginSWUAttack()
    // computes the target pool in the frame of the player passed to it — the ABILITY's controller — so
    // an enemy attacker was offered its own allies AND ITSELF as targets. Bug Report game 1157583:
    // "P2's The Mandalorian attacked P1's The Mandalorian". Controller, not the "my"/"their" mz prefix,
    // so a unit taken with a control-change effect is judged by who actually controls it now.
    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1
        && intval($o->Controller ?? 0) === intval($player)) {
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
