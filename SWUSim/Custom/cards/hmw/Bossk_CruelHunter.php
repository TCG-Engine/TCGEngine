<?php
// HMW_015
// Cost 5 - Bossk, Cruel Hunter - [Cunning][Villainy] - Leader (Ground) 4/5
// Traits: Underworld, Bounty Hunter - Unique
// FRONT:  Action [Exhaust]: Heal 1 damage from a damaged enemy unit and give a Weakness token to it.
// EPIC:   Epic Action: If you control 5 or more resources, deploy this leader.
// DEPLOY: On Attack: You may deal 2 damage to a unit with a token upgrade on it.
//         (Shield and Weakness tokens are token upgrades.)
//
// The set-mate HMW_003 Doctor Hemlock is the template: same two-sided leader shape, same Weakness
// token, and the same asymmetry between a tightly-filtered FRONT and a loose DEPLOYED side.
//
// The Epic Action needs NO code — SWUDeployLeader gates on the leader's printed cost, which is 5, i.e.
// exactly "5 or more resources". Guarded by the Epic_DeployAtFiveResources / Epic_BlockedAtFourResources
// boundary pair so a change to that default cannot silently alter this card.
//
// ⚠ NO $leaderActionResourceCosts entry: the front cost is "[Exhaust]" alone, with no resource. The
// exhaust is paid by SWULeaderAction before the closure runs, so the closure only offers the target.

// ── FRONT ────────────────────────────────────────────────────────────────────
// Two printed restrictions, and BOTH belong in the OFFER rather than at resolution:
//   • "ENEMY"   -> side 'their', which is already team-aware via OpponentsOf, so in Team Suns a
//                  TEAMMATE's damaged unit is friendly and correctly not offered.
//   • "DAMAGED" -> an extraFilter on Damage > 0. An undamaged unit is not a legal target at all; the
//                  heal would do nothing, and the card asks for a damaged one.
//
// Deliberately NO SWULeaderActionAffordable gate. "A damaged enemy unit" is an EFFECT GATE, not a cost:
// with no legal target the Action stays AVAILABLE, the leader still exhausts, and the ability resolves
// to nothing (a SOFT PASS). Putting the condition in the affordability check would make the whole
// action vanish instead — the TS26_02 Anakin lesson, and the same call HMW_005 Jar Jar makes in this
// set. Pinned by Front_NoDamagedEnemy_SoftPass_LeaderStillExhausts.
$leaderAbilities["HMW_015"] = function (int $player): void {
    global $playerID;
    $playerID = $player;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'HMW_015#0',
        'side'         => 'their',
        'extraFilter'  => fn($u) => intval($u->Damage ?? 0) > 0,
        'prompt'       => 'Heal_1_and_give_a_Weakness_token_to_a_damaged_enemy_unit',
    ]);
    // The continuation deliberately never closes the action, so append the closer here. It rides behind
    // the choose when there IS a target and closes the soft pass when there is not (in that case the
    // offer queues nothing at all). Same shape as HMW_003.
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWU_AFTER_ACTION", 1);
};

// ONE TARGET, TWO EFFECTS. Written in printed order (heal, then token) — but ⚠ MEASURED: the order is
// NOT observable here, and no shrink sweep is needed. I first wrote both as load-bearing and the
// mutations came back GREEN; the arithmetic says why.
//   A legal target is DAMAGED and alive, i.e. 1 <= D < H. Afterwards it is on max(0, D-1) damage with
//   H-1 HP, so it is defeated iff D-1 >= H-1, i.e. D >= H — which contradicts "alive". So the front
//   side can NEVER defeat its own target: the heal and the -1 HP cancel exactly. With no possible
//   defeat there is nothing for SWUCheckShrinkDefeats to find, and swapping the two calls changes
//   nothing (a lone sweep at the end of the handler sees the same final board either way).
// So the sweep the shared GIVE_WEAKNESS continuation runs is DELIBERATELY ABSENT here rather than
// copied in as dead code — do not "restore" it without a board that reaches it. Both facts are pinned
// by Front_CanNeverDefeatItsTarget_HealAndMinusOneHpCancel.
// The two clauses are joined by "and", not "If you do", so the token is not conditional on the heal
// having moved anything — though the "damaged" filter means it always does.
// Healing cannot defeat or remove a unit, so the chosen mzID is still valid for the second half; no
// UID re-resolution is needed here (contrast a damage-then-act card, where it is mandatory).
$customDQHandlers["HMW_015#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnHealUnit(intval($player), $lastDecision, 1);
    DoGiveTokenUpgrade(intval($player), $lastDecision, 'HMW_T02');
};

// ── DEPLOYED ─────────────────────────────────────────────────────────────────
// "You may deal 2 damage to A UNIT with a token upgrade on it."
//   • "You may"  -> MZMAYCHOOSE (the 'may' option), which is also the safe form inside an On Attack:
//                   a mandatory MZCHOOSE queued directly in an OnAttack closure auto-resolves to
//                   nothing because OnAttackTrigger restores $playerID before MZCountChoices runs.
//   • "a unit"   -> UNQUALIFIED, side 'any'. Friendly units carrying a token upgrade are legal targets
//                   too — the card lets you shoot your own board, which is printed, not an oversight.
//   • "with a token upgrade on it" -> _SWUHasTokenUpgrade, which reads the rules CATEGORY (CardType
//                   'Token Upgrade') rather than the reminder's two examples. See the helper's note in
//                   CardHelpers.php; Deployed_ExperienceIsATokenUpgradeToo is what pins it.
// Combat owns the after-action — do NOT close it here.
$onAttackAbilities["HMW_015:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE',
        'amount'       => 2,
        'side'         => 'any',
        'may'          => true,
        'extraFilter'  => fn($u) => _SWUHasTokenUpgrade($u),
        'prompt'       => 'Deal_2_damage_to_a_unit_with_a_token_upgrade',
    ]);
};
