<?php
// ASH_004
// Cost 8 - Grand Admiral Thrawn - Victory is Mine - [Vigilance,Villainy] - Power 5 - HP 8
// Text: Action [Exhaust]: Attack with a unit. It gains Restore 2 for this attack if you control the same number of units as the defending player.
// DeployText: Restore 2 / On Attack: If you control more units than the defending player, you may defeat a non-leader unit they control.
// Epic Action: If you control 8 or more resources, deploy this leader.

// ASH_004 Grand Admiral Thrawn — if you control more units than the defending player,
// may defeat a non-leader unit they control.
$onAttackAbilities["ASH_004:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    if (count(GetUnitsInPlay(intval($player))) <= count(GetUnitsInPlay($opp))) return;
    $targets = array_merge(ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
                           ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_an_enemy_unit?",
        "Defeat_a_non-leader_unit_the_defending_player_controls", "DEFEAT_UNIT");
};

// ASH_004 Grand Admiral Thrawn — Action [Exhaust]: attack with a unit. It gains Restore 2 for this attack
// if you control the same number of units as the defending player. (Restore heals on attack, so the grant
// resolves as "heal 2 from your base when this unit attacks" — applied as the attack begins.)
$leaderAbilities["ASH_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        $arr = GetZone($z);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1) $ready[] = "{$z}-{$i}";
        }
    }
    if (empty($ready)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $ready, "Choose_a_unit_to_attack_with", "ASH_004#0");
};

$customDQHandlers["ASH_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $attackerMz = $lastDecision ?? '';
    $attacker = (!empty($attackerMz) && str_contains($attackerMz, '-')) ? GetZoneObject($attackerMz) : null;
    if (SWUObjGone($attacker)) { SWUAfterAction($player); return; }
    // "Restore 2 for this attack if you control the same number of units as the defending player."
    if (count(GetUnitsInPlay(intval($player))) === count(GetUnitsInPlay(OtherPlayer(intval($player))))) {
        OnHealBase(intval($player), intval($player), 2);
    }
    BeginSWUAttack(intval($player), $attackerMz);   // owns the after-action
};
