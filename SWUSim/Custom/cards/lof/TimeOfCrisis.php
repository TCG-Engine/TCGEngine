<?php
// LOF_177
// Cost 4 - Time of Crisis - [Aggression]
// Text: Each player chooses a unit they control. Deal 3 damage to each unit not chosen this way.

// LOF_177 Echoes of the Force — both players spare a unit they control, then 3 damage hits every other
// unit. The opponent's pick is queued via an intermediate CUSTOM (survives the $playerID restore).
$customDQHandlers["LOF_177#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $caster = intval($parts[0] ?? $player);
  $myUID = -1;
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    $o = GetZoneObject($lastDecision);
    if ($o !== null)
      $myUID = intval($o->UniqueID ?? -1);
  }
  // ⚠ "EACH PLAYER chooses a unit they control" — every seat at the table, not the caster plus one
  // opponent. The old chain asked exactly OtherPlayer($caster), so at four seats two players never got
  // to spare anything and their whole board took the 3 damage. Chain a pick per remaining seat instead.
  $rest = [];
  foreach (GetLiveSeatsArray() as $seat) if ($seat !== $caster) $rest[] = $seat;
  _SWULof177Next($caster, [$myUID], $rest);
};

// Ask the next seat in $rest to spare one of its units, then recurse. Resolves once $rest is empty.
function _SWULof177Next(int $caster, array $spared, array $rest): void
{
  global $playerID;
  while (!empty($rest)) {
    $seat = intval(array_shift($rest));
    $playerID = $seat;                       // that seat's own frame — its picks are "my…" relative mzIDs
    $theirs = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($theirs)) continue;            // nothing to spare → next seat
    if (count($theirs) === 1) {              // lone unit auto-spares, no prompt
      $o = GetZoneObject($theirs[0]);
      $spared[] = ($o !== null) ? intval($o->UniqueID ?? -1) : -1;
      continue;
    }
    DecisionQueueController::AddDecision($seat, "MZCHOOSE", implode('&', $theirs), 1,
      tooltip: "Choose_a_unit_you_control_(spared_from_the_3_damage)");
    DecisionQueueController::AddDecision($seat, "CUSTOM",
      "LOF_177#PICK|{$caster}|" . implode(',', $spared) . "|" . implode(',', $rest), 1);
    return;                                  // the rest of the chain resumes in #PICK
  }
  TimeofCrisisResolve($caster, $spared);
}

// Entry point when the caster had no unit of their own to spare.
$customDQHandlers["LOF_177#START"] = function ($player, $parts, $lastDecision) {
  $caster = intval($parts[0] ?? $player);
  $rest = [];
  foreach (GetLiveSeatsArray() as $seat) if ($seat !== $caster) $rest[] = $seat;
  _SWULof177Next($caster, [-1], $rest);
};

$customDQHandlers["LOF_177#PICK"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $caster = intval($parts[0] ?? $player);
  $spared = ($parts[1] ?? '') !== '' ? array_map('intval', explode(',', $parts[1])) : [];
  $rest   = ($parts[2] ?? '') !== '' ? array_map('intval', explode(',', $parts[2])) : [];
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    $playerID = intval($player);             // this seat's frame, to resolve its relative mzID
    $o = GetZoneObject($lastDecision);
    if ($o !== null) $spared[] = intval($o->UniqueID ?? -1);
  }
  _SWULof177Next($caster, $spared, $rest);
};

// Deal 3 to every unit at the table that nobody spared.
function TimeofCrisisResolve(int $caster, array $spared): void
{
  global $playerID;
  $playerID = $caster;
  $keep = array_flip(array_map('intval', $spared));
  $uids = [];
  foreach (GetLiveSeatsArray() as $pl) {
    foreach (GetUnitsInPlay($pl) as $u) {
      if (empty($u->removed)) {
        $uid = intval($u->UniqueID ?? -1);
        if (!isset($keep[$uid]))
          $uids[] = $uid;
      }
    }
  }
  foreach ($uids as $uid) {
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null && $mz !== '')
      SWUDealDamageToUnit($mz, 3, $caster);
  }
}


// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_177:0"] = function($player, $mzID = '') {
// Echoes of the Force — "Each player chooses a unit they control. Deal 3 damage to
                          // each unit not chosen this way." Caster picks first, then EVERY other seat in turn.
            global $playerID; $playerID = intval($player);
            $mine = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($mine)) {
                // Caster controls nothing to spare — skip straight to the other seats' picks.
                DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_177#START|" . intval($player), 1);
                return;
            }
            SWUQueueChooseTarget(intval($player), $mine, "Choose_a_unit_you_control_(spared_from_the_3_damage)", "LOF_177#0|" . intval($player));
            return;
};
