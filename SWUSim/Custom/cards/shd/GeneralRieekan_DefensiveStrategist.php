<?php
// SHD_103
// Cost 6 - General Rieekan - Defensive Strategist - [Command,Heroism] - Power 5 - HP 7
// Text: When Played/On Attack: Choose a friendly unit. If it has Sentinel, give an Experience token to it. Otherwise, it gains Sentinel for this phase.

$customDQHandlers["SHD_103#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (HasKeyword_Sentinel($o)) DoGiveExperienceToken(intval($player), $lastDecision);
    else AddTurnEffect($lastDecision, 'SHD_103');            // grant Sentinel this phase (registry row)
};

// ─── SHD_103 General Rieekan ──────────────────────────────────────────────────
// When Played / On Attack: Choose a friendly unit. If it has Sentinel, give an Experience token to it.
// Otherwise, it gains Sentinel for this phase. (MZMAYCHOOSE for OnAttack-safety.)
$shd103GeneralRieekan = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $targets = [];
  foreach (['myGroundArena', 'mySpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $targets[] = $mz;
    }
  }
  SWUQueueMayChooseTarget(
    intval($player),
    $targets,
    "Choose_a_friendly_unit_(Sentinel:Exp_/_else_gains_Sentinel)?",
    "Choose_a_friendly_unit",
    "SHD_103#0"
  );
};

$whenPlayedAbilities["SHD_103:0"] = $shd103GeneralRieekan;

$onAttackAbilities["SHD_103:0"] = $shd103GeneralRieekan;
