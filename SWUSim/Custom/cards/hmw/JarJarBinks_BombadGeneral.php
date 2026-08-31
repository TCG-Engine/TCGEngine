<?php
// HMW_005
// Cost 6 - Jar Jar Binks, Bombad General - [Vigilance][Heroism] - Leader (Ground) 4/5
// Traits: Gungan
// FRONT:  Action [1 resource, Exhaust]: If you gave a token upgrade to a unit this phase, deal 1 damage
//         to a unit and heal 1 damage from a base.
// EPIC:   Epic Action: If you control 6 or more resources, deploy this leader.
// DEPLOY: Shielded
//         On Attack: If you gave a token upgrade to a unit this phase, you may deal 1 damage to a unit
//         and heal 1 damage from a base.
//
// The Epic Action needs NO code (SWUDeployLeader's threshold IS the printed cost, 6) and the deployed
// Shielded is auto-wired from $Shielded_Cards — neither is hand-written here, both are covered by
// sections in the test file so a change to either default cannot silently alter this card.
//
// ⚠ THE TWO SIDES DIFFER IN OPTIONALITY, which is why they are two registrations over one shared
// resolver rather than one handler: the front Action is MANDATORY once its cost is paid, the deployed
// On Attack is a "you may". Flattening them would make one side wrong in a way no shared section sees.
//
// ⚠ THE CONDITION IS AN EFFECT GATE, NOT A COST. "[1 resource, Exhaust]" is the cost (in brackets);
// everything after the colon is the effect, so an unmet condition is a SOFT PASS — the leader still
// exhausts and still pays, and the ability resolves to nothing. Putting the condition in
// SWULeaderActionAffordable would make the Action VANISH instead of fizzling, which is a different
// (and wrong) game state. Guarded by Front_NoTokenGivenThisPhase_SOFTPASS against
// Front_UnaffordableResource_COMPLETENoOp — the pair separates "cost paid, effect fizzled" from
// "cost unpayable, nothing happened".
//
// "DEAL 1 DAMAGE TO A UNIT **AND** HEAL 1 DAMAGE FROM A BASE" is a plain conjunction, NOT "if you do":
// per the standard reading, the heal is not gated on the damage being possible. So an empty board still
// heals. Both halves are unqualified — no controller word on either — so the unit pool is EVERY unit on
// the board and the base pool is BOTH bases.
//
// The "you gave a token upgrade to a unit this phase" flag itself is engine-side
// (_SWUNoteTokenUpgradeGiven in GameLogic, hooked into all four giver funnels); see the comment there
// for why it cannot live on any single chokepoint.

$leaderActionResourceCosts["HMW_005"] = 1;

// True iff THIS PLAYER gave a token upgrade to a unit during the current phase. Per-seat by
// construction (AddGlobalEffects is seat-scoped), which is what makes
// Front_OPPONENTGaveTheToken_DoesNotQualify fail for a globally-stored flag.
if (!function_exists('_SWUGaveTokenUpgradeThisPhase')) {
    function _SWUGaveTokenUpgradeThisPhase(int $player): bool {
        return GlobalEffectCount($player, 'SWU_GAVE_TOKEN_UPGRADE') > 0;
    }
}

// Shared resolver for both sides. $may is the ONLY difference: the front side queues a mandatory
// choose, the deployed side an MZMAYCHOOSE whose decline abandons BOTH halves (the printed "you may"
// governs the whole sentence, not the damage alone). $close is 1 only for the front Action — combat
// owns the after-action on the deployed side, so closing there would double-close.
if (!function_exists('_SWUHmw005Resolve')) {
    function _SWUHmw005Resolve(int $player, bool $may, bool $close): void {
        global $playerID; $playerID = $player;
        $c = $close ? '1' : '0';
        $units = _SWUCollectUnitTargets($player, ['side' => 'any']);
        if (!empty($units)) {
            SWUOfferUnitTarget($player, '', [
                'continuation' => "HMW_005#0|{$c}",
                'side'         => 'any',
                'may'          => $may,
                'prompt'       => 'Deal_1_damage_to_a_unit',
                'question'     => 'Deal_1_damage_to_a_unit_and_heal_1_damage_from_a_base?',
            ]);
            return;
        }
        // No unit anywhere: the "and heal" half is NOT gated on the damage half, so go straight to the
        // base pick rather than fizzling the whole clause.
        SWUOfferBaseTarget($player, [
            'continuation' => 'HEAL_TARGET|1',
            'baseSide'     => 'any',
            'may'          => $may,
            'prompt'       => 'Heal_1_damage_from_a_base',
            'question'     => 'Heal_1_damage_from_a_base?',
        ]);
        if ($close) SWUQueueAfterAction($player);
    }
}

// Half 1 — apply the damage, then offer the base, then (front side only) close. Both halves route
// through the UNIVERSAL DEAL_TARGET/HEAL_TARGET continuations rather than reimplementing the
// unit-vs-base routing, which is seat-safe above two players (a "p{n}Base-0" mzID resolves through
// SWUMzOwner there, not GetOpponent()).
$customDQHandlers["HMW_005#0"] = function ($player, $parts, $lastDecision) {
    global $playerID, $customDQHandlers; $playerID = intval($player);
    $close = ($parts[0] ?? '0') === '1';
    // A decline here declines the whole "deal … and heal …" package — the printed "you may" governs the
    // whole sentence, not the damage alone. Deployed side only; the front choose is mandatory.
    if (SWUDecisionDeclined($lastDecision)) { if ($close) SWUQueueAfterAction(intval($player)); return; }
    $customDQHandlers['DEAL_TARGET'](intval($player), ['1'], $lastDecision);
    SWUOfferBaseTarget(intval($player), [
        'continuation' => 'HEAL_TARGET|1',
        'baseSide'     => 'any',
        'prompt'       => 'Heal_1_damage_from_a_base',
    ]);
    if ($close) SWUQueueAfterAction(intval($player));
};

// ── FRONT ────────────────────────────────────────────────────────────────────
// SWULeaderAction has already exhausted the leader and paid $leaderActionResourceCosts by the time this
// runs, so the soft-pass branch only has to close the action.
$leaderAbilities["HMW_005"] = function (int $player): void {
    global $playerID; $playerID = $player;
    if (!_SWUGaveTokenUpgradeThisPhase($player)) { SWUQueueAfterAction($player); return; }
    _SWUHmw005Resolve($player, false, true);
};

// ── DEPLOYED ─────────────────────────────────────────────────────────────────
// His own Shielded satisfies the condition as he deploys (the Shield IS a token upgrade given to a
// unit), so on the turn he deploys this side needs no other card — see
// Deployed_EntersWithAShield_WHICHITSELFSatisfiesTheCondition.
$onAttackAbilities["HMW_005:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (!_SWUGaveTokenUpgradeThisPhase(intval($player))) return;   // no offer at all, not a dead offer
    _SWURecordDamageSource(intval($player), $mzID);   // CR 9.12 — the leader UNIT deals this damage
    _SWUHmw005Resolve(intval($player), true, false);
};
