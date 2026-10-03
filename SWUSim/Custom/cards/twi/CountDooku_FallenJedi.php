<?php
// TWI_138
// Cost 8 - Count Dooku - Fallen Jedi - [Aggression,Villainy] - Power 6 - HP 6
// Text: Exploit 2 / Overwhelm / When Played: For each unit you exploited while playing this card, you may deal damage to an enemy unit equal to the power of the exploited unit.

// TWI_138 Count Dooku (unit) — Exploit 2 + Overwhelm (auto-wired). When Played: "For each unit you
// exploited while playing this card, you may deal damage to an enemy unit equal to the power of the
// exploited unit." $gLastExploitedPowers (populated by EXPLOIT_RESOLVE) holds the powers of the units
// defeated to pay Exploit; offer one optional damage instance per power.
// CR 8.34.1: a "for each" ability is resolved by "determining how many times an effect will be applied, choosing how
// each instance will be applied, then resolving all effects simultaneously" — and 8.34.1.a: for damage, "all damage is
// calculated and dealt as one instance". So every pick is only RECORDED (target UID + amount); when the last one is
// chosen, the damage is summed per target and dealt once to each. Dealing each pick as it was chosen let a Shield stop
// only the first of two hits on the same unit, and changed the board between choices.
$whenPlayedAbilities["TWI_138:0"] = function($player, $mzID) {
    global $playerID, $gLastExploitedPowers;
    $playerID = intval($player);
    $powers = is_array($gLastExploitedPowers ?? null) ? $gLastExploitedPowers : [];
    $gLastExploitedPowers = [];   // consume
    if (empty($powers)) return;
    CountDookuFallenJediOfferNext(intval($player), $powers, []);
};

// $parts: [amount, remaining powers "3,2", picks so far "uid:amt,uid:amt"]
$customDQHandlers["TWI_138#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amt   = intval($parts[0] ?? 0);
    $rest  = (isset($parts[1]) && $parts[1] !== '') ? array_map('intval', explode(',', $parts[1])) : [];
    $picks = (isset($parts[2]) && $parts[2] !== '') ? explode(',', $parts[2]) : [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if (!SWUObjGone($o)) $picks[] = intval($o->UniqueID ?? 0) . ':' . $amt;
    }
    CountDookuFallenJediOfferNext(intval($player), $rest, $picks);
};

// Offer the next exploited-power instance (MAY deal it to an enemy unit). Once every instance has been chosen, deal
// the recorded damage: one instance per target, summed (CR 8.34.1.a).
function CountDookuFallenJediOfferNext(int $player, array $powers, array $picks): void
{
  global $playerID;
  $playerID = intval($player);
  while (!empty($powers)) {
    $amt = intval(array_shift($powers));
    $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if ($amt <= 0 || empty($enemies))
      continue; // 0-power or no enemy → skip this instance
    // Show the damage amount in the choose prompt (the tooltip is what the player sees).
    SWUQueueMayChooseTarget(
      $player,
      $enemies,
      "Deal_{$amt}_to_an_enemy_unit?",
      "Deal_{$amt}_damage_to_an_enemy_unit",
      "TWI_138#0|{$amt}|" . implode(',', $powers) . '|' . implode(',', $picks)
    );
    return; // remaining powers resolved by the continuation
  }
  $byTarget = [];
  foreach ($picks as $pk) {
    [$uid, $a] = array_pad(explode(':', $pk), 2, '0');
    if (intval($uid) > 0) $byTarget[intval($uid)] = ($byTarget[intval($uid)] ?? 0) + intval($a);
  }
  foreach ($byTarget as $uid => $total) {
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($uid));
    if ($mz !== null && $total > 0) SWUDealDamageToUnit($mz, $total, intval($player));
  }
}
