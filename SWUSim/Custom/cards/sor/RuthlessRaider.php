<?php
// SOR_134
// Cost 6 - Ruthless Raider - [Aggression,Villainy] - Power 4 - HP 6
// Text: When Played/When Defeated: Deal 2 damage to an enemy base and 2 damage to an enemy unit.

// SOR_134 Ruthless Raider — When Played / When Defeated: deal 2 to an enemy base AND 2 to an enemy unit.
function _SWUSor134Resolve(int $player, int $opp): void {
  global $playerID;
  $playerID = $player;
  if ($opp > 0) SWUDealDamageToBase(2, $opp);
  SWUOfferUnitTarget($player, '', [
    'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'side' => 'their',
    'prompt' => "Deal_2_to_an_enemy_unit",
  ]);
}

$sor134RuthlessRaider = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  // "AN enemy base" names no seat — above two seats the controller picks which. Two seats stay inline:
  // the When Defeated half resolves for a seat that is usually NOT acting, and a lone CUSTOM queued on
  // an idle seat is not guaranteed to drain.
  if (SeatCountForGame() > 2) {
    SWUQueueChooseOpponent(intval($player), 'SOR_134#BASE', "Deal_2_to_which_opponent's_base?");
    return;
  }
  _SWUSor134Resolve(intval($player), intval(OpponentsOf(intval($player))[0] ?? 0));
};

$whenPlayedAbilities["SOR_134:0"] = $sor134RuthlessRaider;

$whenDefeatedAbilities["SOR_134:0"] = $sor134RuthlessRaider;

// The unit half is queued from here, so it still follows the base damage (and the game-over check).
$customDQHandlers["SOR_134#BASE"] = function ($player, $parts, $lastDecision) {
  _SWUSor134Resolve(intval($player), SWUPickedOpponent($lastDecision));
};
