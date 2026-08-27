<?php
// SHD_092
// Cost 11 - Finalizer - Might of the First Order - [Command,Villainy] - Power 11 - HP 11
// Text: Overwhelm / When Played: Choose any number of friendly units. Each of those units captures an enemy non-leader unit in the same arena.

// ─── SHD_092 Finalizer (When Played) — "Choose any number of friendly units. Each of those units captures
// an enemy non-leader unit in the same arena." Multi-select the captors (MZMULTICHOOSE), then chain a
// per-captor enemy pick + DoCaptureUnit. UID-based (each capture reindexes the arenas). ───
$whenPlayedAbilities["SHD_092:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $captors = [];
    foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZ => $theirZ) {
        $hasEnemy = false;
        foreach (ZoneSearch($theirZ, NonLeaderUnitFilter) as $emz) {
            $o = GetZoneObject($emz); if ($o !== null && empty($o->removed)) { $hasEnemy = true; break; }
        }
        if (!$hasEnemy) continue;                       // no capturable enemy in this arena → its units can't capture
        foreach (ZoneSearch($myZ, AnyUnitFilter) as $fmz) {
            $o = GetZoneObject($fmz); if ($o !== null && empty($o->removed)) $captors[] = $fmz;
        }
    }
    if (empty($captors)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
        "0|" . count($captors) . "|" . implode('&', $captors), 1, tooltip: "Choose_any_number_of_friendly_units_to_capture_with");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_092#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["SHD_092#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = strval(intval($o->UniqueID ?? 0));
    }
    FinalizerMightoftheFirstOrderNext(intval($player), implode(',', $uids));
};

$customDQHandlers["SHD_092#cap"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $captorUID = intval($parts[0] ?? 0);
    $remaining = $parts[1] ?? '';
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $captorMz = SWUFindMzByUID($captorUID);
        if ($captorMz !== null) DoCaptureUnit(intval($player), $captorMz, $lastDecision);
    }
    FinalizerMightoftheFirstOrderNext(intval($player), $remaining);
};

function FinalizerMightoftheFirstOrderNext(int $player, string $uidCsv): void
{
  global $playerID;
  $playerID = intval($player);
  $uids = array_values(array_filter(explode(',', $uidCsv), fn($x) => $x !== ''));
  while (!empty($uids)) {
    $captorUID = intval(array_shift($uids));
    $captorMz = SWUFindMzByUID($captorUID);
    if ($captorMz === null)
      continue;
    $captor = GetZoneObject($captorMz);
    if (SWUObjGone($captor))
      continue;
    $theirZ = ($captor->Location ?? '') === 'SpaceArena' ? 'theirSpaceArena' : 'theirGroundArena';
    $enemies = [];
    foreach (ZoneSearch($theirZ, NonLeaderUnitFilter) as $emz) {
      $o = GetZoneObject($emz);
      if ($o !== null && empty($o->removed))
        $enemies[] = $emz;
    }
    if (empty($enemies))
      continue;                  // arena emptied of enemies → this captor gets nothing
    SWUQueueChooseTarget(
      intval($player),
      $enemies,
      "Capture_an_enemy_non-leader_unit_in_the_same_arena",
      "SHD_092#cap|{$captorUID}|" . implode(',', $uids)
    );
    return;                                         // resume the loop after this capture resolves
  }
}
