<?php
// ASH_004
// Cost 8 - Grand Admiral Thrawn - Victory is Mine - [Vigilance,Villainy] - Power 5 - HP 8
// Text: Action [Exhaust]: Attack with a unit. It gains Restore 2 for this attack if you control the same number of units as the defending player.
// DeployText: Restore 2 / On Attack: If you control more units than the defending player, you may defeat a non-leader unit they control.
// Epic Action: If you control 8 or more resources, deploy this leader.

// ASH_004 Grand Admiral Thrawn — if you control more units than the defending player,
// may defeat a non-leader unit they control.
$onAttackAbilities["ASH_004:0"] = function($player, $mzID) {
    // "THE DEFENDING PLAYER" — named by the board, never chosen, so this must not prompt. Two defects
    // above two seats, pointing in OPPOSITE directions:
    //  • the COMPARISON was against OtherPlayer() — a single seat, and for a far-seat attacker always
    //    seat 1, i.e. a player who need not be in the combat at all;
    //  • the POOL was 'side' => 'their', which fans out across EVERY opponent, so Thrawn could defeat a
    //    bystander's unit. The pool GREW rather than shrank, which is why nothing looked broken.
    $opp = SWUCurrentDefendingSeat(intval($player));
    if (count(GetUnitsInPlay(intval($player))) <= count(GetUnitsInPlay($opp))) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'side' => 'their', 'ofSeat' => $opp,
        'nonLeader' => true, 'may' => true,
        'question' => "Defeat_an_enemy_unit?",
        'prompt'   => "Defeat_a_non-leader_unit_the_defending_player_controls",
    ]);
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
    // "Restore 2 for this attack if you control the same number of units as THE DEFENDING PLAYER."
    // ⚠ STILL OWED above two seats (card work, deliberately NOT half-fixed here): this runs BEFORE
    // BeginSWUAttack, so no attack is in flight yet and the defender has not been declared —
    // SWU_CURRENT_DEFENDING_SEAT is not set and SWUCurrentDefendingSeat() would return the fallback.
    // The condition genuinely cannot be evaluated at this point; the grant has to move to after target
    // declaration (a conditional turn effect on the attacker), which is a real restructure rather than
    // a helper swap. At ≤2 seats OtherPlayer() IS the defending player, so Premier is correct today.
    if (count(GetUnitsInPlay(intval($player))) === count(GetUnitsInPlay(OtherPlayer(intval($player))))) {
        OnHealBase(intval($player), intval($player), 2);
    }
    BeginSWUAttack(intval($player), $attackerMz);   // owns the after-action
};
