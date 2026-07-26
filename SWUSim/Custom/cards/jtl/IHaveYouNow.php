<?php
// JTL_193
// Cost 1 - I Have You Now - [Cunning,Villainy]
// Text: Attack with a Vehicle unit. Prevent all damage that would be dealt to it during this attack.

// ── JTL_193 I Have You Now — chosen Vehicle attacks; all damage to it is prevented this attack (JTL_193).
$customDQHandlers["JTL_193#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    AddTurnEffect($lastDecision, 'JTL_193');
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent. Shared play-effect lives in TrenchRun.php.
$whenPlayedAbilities["JTL_193:0"] = function($player, $mzID = '') { TrenchRunAttackPlay(intval($player), 'JTL_193'); };
