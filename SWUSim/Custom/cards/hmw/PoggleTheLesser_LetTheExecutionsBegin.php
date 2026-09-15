<?php
// HMW_012
// Cost 5 - Poggle the Lesser - Let the Executions Begin - [Aggression,Villainy] - Leader (deployed 1/6 Ground)
// Traits: Separatist, Official - Unique
// Text:       Action [1 resource, Exhaust]: Ready a friendly Creature unit and deal 1 damage to it.
//             Epic Action: If you control 5 or more resources, deploy this leader.
// DeployText: When Deployed: Create a Beast token.
//             On Attack: You may ready a friendly Creature unit and deal 1 damage to it.
//
// Epic deploy needs no code: SWUDeployLeader gates on the printed cost (5).
//
// ★ "READY … AND DEAL 1 DAMAGE TO IT" — two halves joined by "and", NOT "If you do". So:
//   • the 1 damage lands whether or not the ready happened — a Creature under SHD_193 Frozen in Carbonite
//     ("can't ready") still takes it;
//   • per CR 1.e a READY Creature is still a legal choice for a readying effect (it simply isn't
//     "readied"), so the pool is every friendly Creature, ready or exhausted, and a ready one takes the 1.
//
// THE POOL is shared by both sides: FRIENDLY units with the Creature trait — team-wide in Team Suns
// ("friendly" is the team), trait read off the live object (TraitContains, via _SWUCollectUnitTargets) so a
// trait gained or lost in play counts. Poggle himself (Separatist, Official) is never in it.
//
// FRONT: mandatory target (no "may"), so SWUQueueChooseTarget — a lone Creature auto-resolves. The cost
// ([1 resource, Exhaust]) is state-changing, so with no friendly Creature the Action is still legal and
// resolves to nothing (the soft pass); the resource half is gated by $leaderActionResourceCosts.
// DEPLOYED On Attack: "You may" → SWUQueueMayChooseTarget (also the documented safe choose for an On Attack
// closure). A pool that is empty raises no prompt. Combat owns the action's close there.

function _SWUHmw012CreaturePool(int $player): array {
    return _SWUCollectUnitTargets(intval($player), ['side' => 'friendly', 'traits' => 'Creature']);
}

// ── FRONT: Action [1 resource, Exhaust] ─────────────────────────────────────────────────────────────
$leaderActionResourceCosts["HMW_012"] = 1;
$leaderAbilities["HMW_012"] = function (int $player): void {
    global $playerID; $playerID = $player;
    $pool = _SWUHmw012CreaturePool($player);
    if (empty($pool)) { SWUAfterAction($player); return; }   // soft pass — the cost is already paid
    SWUQueueChooseTarget($player, $pool,
        "Ready_a_friendly_Creature_unit_and_deal_1_damage_to_it", 'HMW_012#0|1');
};

// Both sides land here. $parts[0] = 1 when the FRONT Action owns the close; 0 for the On Attack (combat
// owns it). The chosen Creature is carried across the two halves by UniqueID: readying can trigger a
// "when this unit readies" tax and the damage can defeat it, and either can re-index the arena.
$customDQHandlers["HMW_012#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $close = intval($parts[0] ?? 0) === 1;
    $o = SWUDecisionDeclined($lastDecision) ? null : GetZoneObject((string)$lastDecision);
    if (!SWUObjGone($o)) {
        $uid = intval($o->UniqueID ?? 0);
        OnReadyCard(intval($player), (string)$lastDecision);           // honours can't-ready (CR 1.e: may no-op)
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player)); // "and" — unconditional
    }
    if ($close) SWUAfterAction(intval($player));
};

// ── DEPLOYED: When Deployed — "Create a Beast token." (HMW_T03, 3/3 Creature; enters exhausted) ─────
$whenPlayedAbilities["HMW_012:0"] = function ($player, $mzID = '') {
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};

// ── DEPLOYED: On Attack — "You may ready a friendly Creature unit and deal 1 damage to it." ─────────
$onAttackAbilities["HMW_012:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $pool = _SWUHmw012CreaturePool(intval($player));
    if (empty($pool)) return;   // a "may" that could only fizzle is never offered
    SWUQueueMayChooseTarget(intval($player), $pool,
        "Ready_a_friendly_Creature_unit_and_deal_1_damage_to_it?",
        "Ready_a_friendly_Creature_unit_and_deal_1_damage_to_it", 'HMW_012#0|0');
};
