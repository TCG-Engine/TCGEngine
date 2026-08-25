<?php
// LAW_096
// Cost 7 - Rhydonium Detonation - [Cunning,Vigilance]
// Text: Each player may return a non-leader unit to its owner's hand. Then, defeat all non-leader units.

// "EACH PLAYER may return a non-leader unit" — one optional bounce per LIVE seat, offered in player
// order (caster first), and only then the mass defeat. Was a hard-coded two-step chain (caster, then
// OtherPlayer), so at four seats seats 3 and 4 were never offered their save.
//
// Same QUEUED-WALK shape as LAW_099 Governor's Shuttle — each pick is interactive, so the remaining
// seats ride the continuation Param rather than a loop variable or an in-memory global.
// ⚠ Unlike LAW_099, each bounce is applied IMMEDIATELY, before the next seat is asked: the card reads
//   "…may return a unit. THEN, defeat all", and a later seat must see the board as it now stands (pinned
//   by SavePool_P2SeesTheBoardAfterP1sReturn). So the pool is recomputed per seat, never precomputed.
// ⚠ The pool is EVERY non-leader unit on the table, not just that seat's own — "a non-leader unit" names
//   no controller (pinned by P1SavesAnOpponentUnit).

if (!function_exists('_SWULaw096Targets')) {
    function _SWULaw096Targets(int $seat): array {
        global $playerID; $playerID = $seat;
        return SWUAllUnits(null, null, NonLeaderUnitFilter);
    }
}

if (!function_exists('_SWULaw096Ask')) {
    function _SWULaw096Ask(int $caster, array $remaining): void {
        while (!empty($remaining)) {
            $seat = intval(array_shift($remaining));
            $targets = _SWULaw096Targets($seat);      // recomputed: earlier bounces have already applied
            if (empty($targets)) continue;            // nothing left on the table to save
            SWUQueueMayChooseTarget($seat, $targets, "Return_a_non-leader_unit_to_hand?",
                "Choose_a_non-leader_unit_to_return", "LAW_096#PICK|{$caster}|" . implode(',', $remaining));
            return;                                   // resumes in LAW_096#PICK once this seat answers
        }
        RhydoniumDetonationDefeatAllNonLeader($caster);
    }
}

$customDQHandlers["LAW_096#PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster    = intval($parts[0] ?? $player);
    $remaining = array_values(array_filter(explode(',', (string)($parts[1] ?? '')), fn($v) => $v !== ''));
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $playerID = intval($player);                  // the mzID was minted in THIS seat's frame
        SWUBounceUnit(intval($player), $lastDecision);
    }
    _SWULaw096Ask($caster, $remaining);
};

// LAW_096 helper — defeat all non-leader units across all four arenas (UID snapshot, index-shift safe).
function RhydoniumDetonationDefeatAllNonLeader(int $caster): void
{
  global $playerID;
  $playerID = $caster;
  $uids = [];
  foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
    foreach (ZoneSearch($zone, NonLeaderUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $uids[] = intval($o->UniqueID);
    }
  }
  foreach ($uids as $uid) {
    $playerID = $caster;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null)
      SWUDefeatUnit($caster, $mz);
  }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_096:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (empty(_SWULaw096Targets(intval($player)))) return;   // nothing to bounce or defeat
    _SWULaw096Ask(intval($player), SWUSeatsInPlayerOrder(intval($player)));
};
