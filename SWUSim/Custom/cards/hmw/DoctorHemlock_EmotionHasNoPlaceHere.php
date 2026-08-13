<?php
// HMW_003
// Cost 6 - Doctor Hemlock, Emotion Has No Place Here - [Vigilance][Villainy] - Leader (Ground) 3/6
// Traits: Imperial, Official
// FRONT:  Action [1 resource, Exhaust]: Give a Weakness token to a unit without a Weakness token on it.
// EPIC:   Epic Action: If you control 6 or more resources, deploy this leader.
// DEPLOY: On Attack: You may give a Weakness token to a unit.
//
// The Epic Action needs NO code: SWUDeployLeader's threshold IS the leader's printed cost (6), which is
// exactly "6 or more resources". Guarded by the Epic_DeployAtSixResources / Epic_BlockedAtFiveResources
// boundary pair so a future change to that default cannot silently alter this card.
//
// Weakness (HMW_T02) is a -1/-1 Token Upgrade; its stat modifier flows through the normal upgrade stat
// loop, and the shared GIVE_WEAKNESS continuation runs SWUCheckShrinkDefeats so a host reduced to 0
// remaining HP is defeated (there is no state-based defeat for that on its own).

$leaderActionResourceCosts["HMW_003"] = 1;

// ── FRONT ────────────────────────────────────────────────────────────────────
// Mandatory ("Give …", not "You may"), and "a unit" carries no friendly/enemy qualifier, so ENEMY
// units are legal targets too (side 'any').
//
// "without a Weakness token on it" is a TARGET FILTER: it must exclude such units from the OFFER, not
// merely no-op at resolution. GiveTokenUpgrade does not forward extraFilter, so this calls
// SWUOfferUnitTarget directly with the same GIVE_WEAKNESS continuation.
//
// Deliberately NO SWULeaderActionAffordable target gate: the cost is state-changing
// ([1 resource, Exhaust]), so per CR 6.4.587.c the Action stays usable with no legal target and simply
// fizzles — matching HMW_009 Chewbacca's front side in this same set. SWULeaderAction has already
// exhausted the leader by the time this closure runs, so it only pays the resource (via
// $leaderActionResourceCosts) and offers the target.
$leaderAbilities["HMW_003"] = function (int $player): void {
    global $playerID; $playerID = $player;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'GIVE_WEAKNESS',
        'side'         => 'any',
        'extraFilter'  => fn($u) => SWUFindUpgradeIndex($u, 'HMW_T02') < 0,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit_without_one',
    ]);
    // The universal continuations deliberately never close the action, so append the closer. It rides
    // behind the choose when there IS a target, and closes the fizzle when there is not (the offer
    // queues nothing at all in that case).
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// ── DEPLOYED ─────────────────────────────────────────────────────────────────
// "You may give a Weakness token to a unit" — note there is NO Weakness-exclusion here. The asymmetry
// with the front side is printed, not an oversight, so this side may stack a second -1/-1 on an
// already-weakened unit (guarded by Deployed_OnAttack_StacksOnAlreadyWeakenedUnit).
// MZMAYCHOOSE (via 'may') is the safe pick inside OnAttack — a mandatory MZCHOOSE queued directly in an
// OnAttack closure auto-resolves to nothing. Combat owns the after-action; do not close it here.
$onAttackAbilities["HMW_003:0"] = function ($player, $mzID = '') {
    GiveTokenUpgrade(intval($player), $mzID, [
        'token'        => 'WEAKNESS',
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
};
