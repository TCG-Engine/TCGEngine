<?php
// SHD_091
// Cost 8 - Jabba's Rancor - Pateesa - [Command,Villainy] - Power 9 - HP 9
// Text: If you control Jabba the Hutt (as a leader or unit), this unit costs 1 resource less to play. / When Played/On Attack: Deal 3 damage to another friendly ground unit and 3 damage to an enemy ground unit.

$customDQHandlers["SHD_091#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUDealDamageToUnit($lastDecision, 3, intval($player));
    }
    $enemy = [];
    foreach (ZoneSearch('theirGroundArena', AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $enemy[] = $mz;
    }
    SWUQueueChooseTarget(intval($player), $enemy, "Deal_3_to_an_enemy_ground_unit", "DEAL_UNIT_DAMAGE|3");
};

// ─── SHD_091 Jabba's Rancor ───────────────────────────────────────────────────
// Cost -1 with Jabba (registered in GameLogic $playCostModifiers). When Played / On Attack: Deal 3 to
// another friendly ground unit AND 3 to an enemy ground unit. Friendly pick uses MZMAYCHOOSE (OnAttack-safe:
// a mandatory multi-target MZCHOOSE queued directly in an OnAttack closure auto-resolves to nothing); the
// enemy pick is queued from the #0 continuation, where a mandatory MZCHOOSE is safe.
$shd091JabbasRancor = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($mzID);
  $selfUID = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
  $friendly = [];
  foreach (ZoneSearch('myGroundArena', AnyUnitFilter) as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID)
      $friendly[] = $mz;
  }
  SWUQueueMayChooseTarget(
    intval($player),
    $friendly,
    "Deal_3_to_another_friendly_ground_unit?",
    "Choose_a_friendly_ground_unit",
    "SHD_091#0"
  );
};

$whenPlayedAbilities["SHD_091:0"] = $shd091JabbasRancor;

$onAttackAbilities["SHD_091:0"] = $shd091JabbasRancor;
