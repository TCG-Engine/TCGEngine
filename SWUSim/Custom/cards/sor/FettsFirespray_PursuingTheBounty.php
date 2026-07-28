<?php
// SOR_184
// Cost 6 - Fett's Firespray - Pursuing the Bounty - [Cunning,Villainy] - Power 5 - HP 6
// Text: When Played: If you control Boba Fett or Jango Fett (as a leader or unit), ready this unit. / Action [2 resources]: Exhaust a non-unique unit.

// SOR_184 Fett's Firespray — "Action [2 resources]:" with NO exhaust: the unit isn't tapped and
// needn't be ready, so the action is repeatable while resources last.
$unitActionCostKind["SOR_184"] = 'none';

$unitActionResourceCosts["SOR_184"] = 2;

$unitAbilities["SOR_184"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = _SWUNonUniqueUnitTargets(intval($player));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_non-unique_unit", "EXHAUST_UNIT");
    SWUQueueAfterAction($player);
};

// SOR_184 Fett's Firespray — When Played: if you control Boba Fett or Jango Fett (leader, unit, or
// upgrade), ready this unit (so it can act the turn it's played).
$whenPlayedAbilities["SOR_184:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Boba Fett', 'Jango Fett'])) return;
    OnReadyCard(intval($player), $mzID);
};
