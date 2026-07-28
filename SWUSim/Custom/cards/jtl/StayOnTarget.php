<?php
// JTL_177
// Cost 2 - Stay on Target - [Aggression]
// Text: Attack with a Vehicle unit. For this attack, it gets +2/+0 and gains: "When this unit deals damage to a base: Draw a card."

// ── JTL_177 Stay on Target — chosen Vehicle gets +2/+0 + a granted base-damage→draw (JTL_177), attacks.
$customDQHandlers["JTL_177#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    AddTurnEffect($lastDecision, 'JTL_177');
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent. Shared play-effect lives in TrenchRun.php.
$whenPlayedAbilities["JTL_177:0"] = function($player, $mzID = '') { TrenchRunAttackPlay(intval($player), 'JTL_177'); };
