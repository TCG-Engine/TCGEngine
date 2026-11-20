<?php
// IC27_004
// Cost 6 - Mace Windu - Here With Backup - [Command,Heroism] - Power 4 - HP 7 - Force, Jedi, Republic
// Text: Action [1 resource, Exhaust]: Attack with a unit. It gains Restore 2 for this attack. (When it attacks, heal 2
// damage from your base.)
// Epic Action: If you control 6 or more resources, deploy this leader.
// DeployText: Restore 2 / When Deployed: Resource the top card of your deck.

// ── FRONT: Action [1 resource, Exhaust]: attack with a unit; it gains Restore 2 for this attack ───────────────────────
// The [1 resource] is $leaderActionResourceCosts (checked before the exhaust, paid centrally — a Credit token can pay it).
// "Attack with a unit" means a unit you CONTROL: the pool is every unit that could declare an attack right now
// (_SWUUnitCanAttackNow — ready, not "can't attack", a legal target exists), the IC27_120 Rey shape. The cost changes game
// state, so with no unit able to attack the action still resolves (to nothing) — no availability gate (CR 6.4.587.c).
// Restore 2 is an ATTACK-duration RESTORE token, the one Support lends (CombatLogic): ExecuteSWUAttack reads Restore when
// the attack starts, and SWUExpireTurnEffects(SWU_DUR_ATTACK) drops it when the attack ends.
$leaderActionResourceCosts["IC27_004"] = 1;

$leaderAbilities["IC27_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $attackers = [];
    foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            if (_SWUUnitCanAttackNow($player, $arr[$i], $arena)) $attackers[] = "{$zone}-{$i}";
        }
    }
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, "Choose_a_unit_to_attack_with_(gains_Restore_2)", "IC27_004#0");
};

$customDQHandlers["IC27_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject($lastDecision))) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('RESTORE', [2], SWU_DUR_ATTACK, 'IC27_004'));
    BeginSWUAttack(intval($player), $lastDecision);   // owns the action close
};

// ── DEPLOYED: Restore 2 (printed — $Restore_Cards) / When Deployed: resource the top card of your deck ───────────────
// "Resource" = put into play as a resource, EXHAUSTED (rulings errata 07/20/2026). SWUResourceTopOfDeck takes the first
// LIVE deck card and no-ops on an empty deck; it reports the resourcing to IC27_089 Maz Kanata's observer.
$whenPlayedAbilities["IC27_004:0"] = function($player, $mzID) {
    SWUResourceTopOfDeck(intval($player));
};
