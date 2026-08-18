<?php
// ASH_053
// Cost 8 - Pre Vizsla - Strong-Willed Ruler - [Vigilance,Villainy] - Power 6 - HP 6
// Text: When Played: Defeat any number of non-leader units with a total of 6 or less remaining HP. Create a Mandalorian token for each unit defeated this way.

// One weighted multi-select (SWUQueueBudgetMultiChoose): the player sees every non-leader unit that fits
// the 6-HP budget, picks freely with a live "N of 6 HP left" counter, and confirms once. Units that no
// longer fit the remaining budget grey out after each click. This replaced a re-offered one-at-a-time
// MZMAYCHOOSE loop — see the helper's comment for why.

// Every non-leader unit's REMAINING HP (current HP minus damage), keyed by mzID. "Non-leader units" names
// no controller, so both sides are in scope — including Pre Vizsla himself, who is a legal target of his
// own When Played at exactly 6.
function _SWUAsh053Weights(int $player): array
{
  global $playerID;
  $playerID = $player;
  $out = [];
  foreach (SWUAllUnits() as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o) || IsLeaderUnit($o))
      continue;
    $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
    if ($rem >= 0)
      $out[$mz] = $rem;
  }
  return $out;
}

$whenPlayedAbilities["ASH_053:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueBudgetMultiChoose(
      intval($player),
      _SWUAsh053Weights(intval($player)),
      6,
      'HP',
      "Defeat_any_number_of_non-leader_units_with_6_or_less_combined_remaining_HP",
      "ASH_053#0|6"
    );
};

$customDQHandlers["ASH_053#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $budget = intval($parts[0] ?? 6);
    // Re-measure and re-cap server-side: the client's greying-out is UX, and a scripted answer skips it
    // entirely. Picks that no longer exist or no longer fit are dropped rather than resolved.
    $picked = SWUFilterBudgetAnswer($lastDecision, _SWUAsh053Weights(intval($player)), $budget);
    if (empty($picked)) return;
    // Resolve to UniqueIDs BEFORE defeating anything — every defeat compacts its arena, so the second
    // mzID in the batch would otherwise address whichever unit slid into that slot.
    $uids = [];
    foreach ($picked as $mz) {
      $o = GetZoneObject($mz);
      if (!SWUObjGone($o)) $uids[] = intval($o->UniqueID ?? 0);
    }
    $count = 0;
    foreach ($uids as $uid) {
      if ($uid <= 0) continue;
      $mz = SWUFindMzByUID($uid);
      if ($mz === null) continue;
      // Count only what is ACTUALLY defeated: a unit that "can't be defeated by enemy card abilities"
      // (SHD_187 Lurking TIE Phantom) survives being chosen, and earns no token — the tokens are "for
      // each unit defeated this way", not for each unit selected.
      if (SWUDefeatUnit(intval($player), $mz)) $count++;
    }
    _SWUAsh053Finish(intval($player), $count);
};
