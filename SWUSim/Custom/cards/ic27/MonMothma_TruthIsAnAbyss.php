<?php
// IC27_077
// Cost 4 - Mon Mothma - Truth Is An Abyss - [Command,Heroism] - Power 3 - HP 6 - Rebel, Official
// Text: When Played: You may attack with another unit, even if it's exhausted. For this attack, it gains Overwhelm and
// can't attack bases.

// IC27_077 Mon Mothma — the LAW_065 4-LOM shape ("you may attack with …, even if it's exhausted. It can't attack bases")
// plus an attack-duration Overwhelm grant (LAW_001 Saw Gerrera's token).
// The pool: every unit YOU CONTROL other than Mon Mothma (by UniqueID — she enters exhausted, so "even if it's exhausted"
// would otherwise make her eligible) that is not under a "can't attack" restriction and has an enemy UNIT to attack in its
// arena (bases are off-limits). An empty pool can only fizzle, so nothing is offered — and that matters: BeginSWUAttack
// readies an "even if it's exhausted" attacker before it looks for a target (the 4-LOM note).
// Overwhelm still damages the base through excess: SEC_103 Mon Mothma's ruling, "Units that can't attack bases can still
// damage bases through other abilities, like Overwhelm." The When Played's play owns the action close.
$whenPlayedAbilities["IC27_077:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID));
    $attackers = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || SWUObjUID($o) === $selfUID) continue;
        if (_SWUUnitHardCantAttack($o)) continue;
        if (($o->CardID ?? '') === 'LOF_063' && !LostAbilities($o) && intval($o->Damage ?? 0) <= 0) continue;   // Oggdo Bogdo
        $arena = strpos($mz, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
        if (empty(SWUGetAllValidAttackTargets(intval($player), $o, $arena, true))) continue;   // no enemy unit (no bases)
        $attackers[] = $mz;
    }
    if (empty($attackers)) return;
    SWUQueueMayChooseTarget(intval($player), $attackers, "Attack_with_another_unit_(even_if_exhausted;_Overwhelm,_can't_attack_bases)?",
        "Choose_a_unit_to_attack_with", "IC27_077#0");
};

$customDQHandlers["IC27_077#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'IC27_077'));
    BeginSWUAttack(intval($player), $lastDecision, true);   // even if it's exhausted; can't attack bases this attack
};
