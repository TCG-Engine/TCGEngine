<?php
// LAW_065
// Cost 5 - 4-LOM - Devious - [Command,Cunning,Villainy] - Power 4 - HP 5
// Text: When Played: You may attack with a friendly Bounty Hunter unit, even if it's exhausted. It can't attack bases for this attack.

// LAW_065 4-LOM — When Played: you may attack with a friendly Bounty Hunter unit, even if it's
// exhausted. It can't attack bases for this attack (noBases).
$whenPlayedAbilities["LAW_065:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Only offer an attacker that could actually attack something. "It can't attack bases for this attack"
    // means an enemy UNIT in that attacker's arena is required, so on an empty enemy board the ability can
    // only fizzle and is auto-declined rather than prompted (USER RULING 2026-08-17, option (a)).
    // ⚠ This is not cosmetic: choosing an attacker with no legal target used to READY it. BeginSWUAttack
    // readies the attacker for the "even if it's exhausted" clause and then aborts for want of a target
    // without restoring that, so the prompt handed out a free ready.
    // Guarded by NoEnemyUnitToAttack_TheChosenAttackerMustNotBeREADIED.
    $bh = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (!HasTrait($o->CardID ?? '', 'Bounty Hunter')) continue;
        if (empty(SWUGetAllValidAttackTargets(intval($player), $o, $o->Location ?? '', true))) continue; // noBases
        $bh[] = $mz;
    }
    if (empty($bh)) return;
    SWUQueueMayChooseTarget(intval($player), $bh, "Attack_with_a_friendly_Bounty_Hunter_(even_if_exhausted)?", "Choose_a_Bounty_Hunter", "LAW_065#0");
};

$customDQHandlers["LAW_065#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    BeginSWUAttack(intval($player), $lastDecision, true);   // can't attack bases this attack
};
