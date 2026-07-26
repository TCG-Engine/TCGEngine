<?php
// ASH_053
// Cost 8 - Pre Vizsla - Strong-Willed Ruler - [Vigilance,Villainy] - Power 6 - HP 6
// Text: When Played: Defeat any number of non-leader units with a total of 6 or less remaining HP. Create a Mandalorian token for each unit defeated this way.

$customDQHandlers["ASH_053#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $budget = intval($parts[0] ?? 0); $count = intval($parts[1] ?? 0);
    if (SWUDecisionDeclined($lastDecision)) { _SWUAsh053Finish(intval($player), $count); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { _SWUAsh053Finish(intval($player), $count); return; }
    $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
    SWUDefeatUnit(intval($player), $lastDecision);
    $budget -= max(0, $rem); $count++;
    if ($budget <= 0) { _SWUAsh053Finish(intval($player), $count); return; }
    PreVizslaStrongWilledRulerOffer(intval($player), $budget, $count);
};

$whenPlayedAbilities["ASH_053:0"] = function($player, $mzID) {
    PreVizslaStrongWilledRulerOffer(intval($player), 6, 0);
};

// ASH_053 Pre Vizsla — When Played: defeat any number of non-leader units with a COMBINED 6-or-less
// remaining HP; create a Mandalorian token for each unit defeated this way. Iterative budget-defeat
// loop (mirrors _SWUCombinedBudgetOffer); the running count rides the handler param.
function PreVizslaStrongWilledRulerOffer(int $player, int $budget, int $count): void
{
  global $playerID;
  $playerID = $player;
  $targets = [];
  foreach (SWUAllUnits() as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o) || IsLeaderUnit($o))
      continue;   // non-leader units
    $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
    if ($rem >= 0 && $rem <= $budget)
      $targets[] = $mz;
  }
  if (empty($targets)) {
    _SWUAsh053Finish($player, $count);
    return;
  }
  SWUQueueMayChooseTarget(
    $player,
    $targets,
    "Defeat_a_non-leader_unit_(remaining_combined_HP_budget_{$budget})?",
    "Choose_a_unit_to_defeat",
    "ASH_053#0|{$budget}|{$count}"
  );
}
