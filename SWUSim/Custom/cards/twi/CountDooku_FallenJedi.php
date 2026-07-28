<?php
// TWI_138
// Cost 8 - Count Dooku - Fallen Jedi - [Aggression,Villainy] - Power 6 - HP 6
// Text: Exploit 2 / Overwhelm / When Played: For each unit you exploited while playing this card, you may deal damage to an enemy unit equal to the power of the exploited unit.

// TWI_138 Count Dooku (unit) — Exploit 2 + Overwhelm (auto-wired). When Played: "For each unit you
// exploited while playing this card, you may deal damage to an enemy unit equal to the power of the
// exploited unit." $gLastExploitedPowers (populated by EXPLOIT_RESOLVE) holds the powers of the units
// defeated to pay Exploit; offer one optional damage instance per power.
$whenPlayedAbilities["TWI_138:0"] = function($player, $mzID) {
    global $playerID, $gLastExploitedPowers;
    $playerID = intval($player);
    $powers = is_array($gLastExploitedPowers ?? null) ? $gLastExploitedPowers : [];
    $gLastExploitedPowers = [];   // consume
    if (empty($powers)) return;
    CountDookuFallenJediOfferNext(intval($player), $powers);
};

$customDQHandlers["TWI_138#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amt  = intval($parts[0] ?? 0);
    $rest = (isset($parts[1]) && $parts[1] !== '') ? array_map('intval', explode(',', $parts[1])) : [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUDealDamageToUnit($lastDecision, $amt, intval($player));
    }
    if (!empty($rest)) CountDookuFallenJediOfferNext(intval($player), $rest);
};

// Offer the next exploited-power damage: MAY deal $amt to an enemy unit, then recurse for the rest.
function CountDookuFallenJediOfferNext(int $player, array $powers): void
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
      "TWI_138#0|{$amt}|" . implode(',', $powers)
    );
    return; // remaining powers resolved by the continuation
  }
}
