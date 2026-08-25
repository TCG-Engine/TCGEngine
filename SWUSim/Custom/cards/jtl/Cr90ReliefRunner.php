<?php
// JTL_071
// Cost 6 - CR90 Relief Runner - [Vigilance] - Power 4 - HP 6
// Text: Restore 2 (When this unit attacks, heal 2 damage from your base.) / When Defeated: Heal up to 3 damage from a unit or base.

// Private helper: heal $amt from a unit or base mzID (JTL_071-only).
function CR90ReliefRunnerHeal(int $player, string $mz, int $amt): void
{
  global $playerID;
  $playerID = intval($player);
  if ($amt <= 0)
    return;
  if (strpos($mz, 'Base') !== false) {
    $tp = (strpos($mz, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
    OnHealBase(intval($player), $tp, $amt);
  } else {
    OnHealUnit(intval($player), $mz, $amt);
  }
}

// ── JTL_071 CR90 Relief Runner — Restore 2 (auto) + When Defeated: Heal UP TO 3 from a unit or base. ──
// Reference: distributeHealingAmong(amount 3, maxTargets 1, controller Any, canChooseNoTargets). Modelled
// as a MAY-choose over any unit/base (either side, damaged or not) → then a heal-AMOUNT pick so the player
// may heal fewer than 3. Declining heals nothing; a chosen undamaged target heals 0 (no amount prompt).
$whenDefeatedAbilities["JTL_071:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        SWUAllUnits(),   // "heal … from A UNIT" is unqualified -> every unit on the table
        SWUAllBaseMzIDs(intval($player), 'any')
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Heal_up_to_3_damage_from_a_unit_or_base?", "Heal_up_to_3_damage_from_a_unit_or_base", "JTL_071#1");
};

// Chosen target → cap = min(3, its current damage). cap<=1 heals it directly; cap>=2 offers a 1..cap
// amount pick so the controller may heal LESS than the maximum.
$customDQHandlers["JTL_071#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision))
    return;
  $mz = trim((string) $lastDecision);
  $dmg = 0;
  if (strpos($mz, 'Base') !== false) {
    $tp = (strpos($mz, 'my') === 0) ? intval($player) : GetOpponent(intval($player));
    $base = GetBase($tp);
    $dmg = (count($base) > 0 && empty($base[0]->removed)) ? intval($base[0]->Damage ?? 0) : 0;
  } else {
    $o = GetZoneObject($mz);
    $dmg = ($o !== null && empty($o->removed)) ? intval($o->Damage ?? 0) : 0;
  }
  $cap = min(3, $dmg);
  if ($cap <= 1) {
    CR90ReliefRunnerHeal(intval($player), $mz, $cap);
    return;
  }
  $opts = [];
  for ($a = 1; $a <= $cap; $a++)
    $opts[] = (string) $a;
  DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", implode('&', $opts), 1, tooltip: "Heal_how_many_damage?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_071#0|{$mz}", 1);
};

$customDQHandlers["JTL_071#0"] = function ($player, $parts, $lastDecision) {
  $mz = $parts[0] ?? '';
  $amt = intval($lastDecision);
  if ($mz !== '' && $amt > 0)
    CR90ReliefRunnerHeal(intval($player), $mz, $amt);
};
