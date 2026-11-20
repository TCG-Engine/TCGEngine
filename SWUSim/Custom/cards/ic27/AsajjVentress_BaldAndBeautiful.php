<?php
// IC27_007
// Cost 6 - Asajj Ventress - Bald and Beautiful - [Cunning,Villainy] - Leader (deployed Ground 5/6)
//   Traits: Force, Separatist, Sith
// Text: Action [Exhaust]: Attack with a unit. After completing this attack, if the attacker survived, you may ready
//       a resource.
// Epic Action: If you control 6 or more resources, deploy this leader.
// DeployText: Saboteur. On Attack: You may ready a resource.
//
// "ready a resource" = one of YOUR resources — the engine-wide reading of the unqualified wording (SOR_015 Boba,
// IC27_146, …); an owner ruling on whether it may be any player's is OPEN, and this card follows the family.
// Resources are fungible, so there is nothing to pick: SWUReadyResources readies one exhausted resource. With none
// exhausted the "may" is not asked at all (the no-effect-prompt family). Saboteur needs no code (keyword data).

function Ic27007OfferReadyResource($player): void {
    global $playerID; $playerID = intval($player);
    $exhausted = 0;
    foreach (GetResources(intval($player)) as $r) {
        if (empty($r->removed) && intval($r->Status ?? 1) === 0) $exhausted++;
    }
    if ($exhausted <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Ready_a_resource?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_007#1", 1);
}

$customDQHandlers["IC27_007#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    SWUReadyResources(intval($player), 1);
};

// ── FRONT: Action [Exhaust] — attack with a unit; after it, if the attacker survived, may ready a resource ─────
// The rider is an attack-duration marker on the attacker (GameLogic marker table), captured into $combatCtx at
// combat start and raised as the IC27_007 trigger by CollectAfterAttackTriggers only when the attacker is still in
// play by UID ("if the attacker survived"). It belongs to THIS attack: the marker expires with it. Usable with no
// ready unit — the [Exhaust] cost still changes the game state — then it just closes the action.
$leaderAbilities["IC27_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (!SWUObjGone($o) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Choose_a_unit_to_attack_with", "IC27_007#0");
};

$customDQHandlers["IC27_007#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $o = ($lastDecision && str_contains((string)$lastDecision, '-')) ? GetZoneObject((string)$lastDecision) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect((string)$lastDecision, SWUMakeTurnEffect('IC27_007', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), (string)$lastDecision);   // owns SWUAfterAction once it attacks
};

// ── DEPLOYED: On Attack — you may ready a resource ─────────────────────────────
$onAttackAbilities["IC27_007:0"] = function($player, $mzID) {
    Ic27007OfferReadyResource($player);
};
