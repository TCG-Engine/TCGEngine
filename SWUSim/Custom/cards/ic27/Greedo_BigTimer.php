<?php
// IC27_142
// Cost 3 - Greedo - Big-Timer - [Cunning,Villainy] - Unit (Ground) 5/3 (unique)
//   Traits: Underworld, Bounty Hunter
// Text: Ambush
//       While attacking or defending, this unit deals combat damage after the other attacking or defending unit.
//
// Ambush is printed (auto-wired). The ordering lives in SWUCombatDamage (CombatLogic.php, the IC27_142 block after
// JTL_185 Hound's Tooth): attacking he takes the LAW_086 defender-first path, defending he puts the attacker on the
// Shoot First attacker-first path. When an attacking Greedo also has a deal-first source, ExecuteSWUAttack asks his
// controller which to use (CR 6.3.5.c) and this handler records a YES as the attack-scoped IC27_142_FIRST marker.
// Tests: SWUSim/Tests/Cases/ic27/Greedo_BigTimer.md
$customDQHandlers["IC27_142#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // the mzID was minted in the attacker's frame
    if ($lastDecision !== 'YES') return;
    $mzID = $parts[0] ?? '';
    if ($mzID !== '' && !SWUObjGone(GetZoneObject($mzID))) AddTurnEffect($mzID, 'IC27_142_FIRST');
};
