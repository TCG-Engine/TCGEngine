<?php
// ASH_162
// Cost 2 - Rash Action - [Aggression,Heroism]
// Text: Attack with a unit. For this attack, it gets +1/+0 and gains: "When Attack Ends: If this unit dealt combat damage to an opponent's base, that opponent discards a card."

// ASH_162 Rash Action (event) — the chosen unit attacks with +1/+0 and the ASH_162 marker (opponent
// discards a card if it hits their base). Marker captured into combatCtx; consumed in CollectAfterAttackTriggers.
$customDQHandlers["ASH_162#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($attackerMzID, 1);
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('ASH_162', [], SWU_DUR_ATTACK));
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_162:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $readyUnits = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
        }
    }
    if (empty($readyUnits)) return;
    SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_162#0");
};
