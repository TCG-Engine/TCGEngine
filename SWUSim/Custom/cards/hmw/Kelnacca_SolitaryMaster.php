<?php
// HMW_036
// Cost 4 - Kelnacca, Solitary Master - [Command][Vigilance] - Unit (Ground) 4/5 - unique
// Traits: Force, Jedi, Wookiee
// Text: Restore 2
//       When Played: You may pay any number of resources. For every 3 resources paid this way, deal
//       damage equal to this unit's power to an enemy unit.
//
// Restore 2 needs no code — $Restore_Cards['HMW_036'] => 2 is generator-derived, and the keyword has
// generic coverage under Tests/Cases/keywords/.
//
// ── "PAY ANY NUMBER OF RESOURCES" is the SEC_040 Emergency Powers shape ──────────────────────────────
// ("Choose a non-leader unit and pay any number of resources. For each resource paid this way, give an
// Experience token to the chosen unit.") That card is the house pattern for this sentence and it is
// followed here exactly:
//   • ONE NUMBERCHOOSE over the full range 0..(ready resources), NOT an iterative pay-1-at-a-time loop
//     and NOT a range clipped to useful multiples. "Any number" is literal: with 7 ready a player may
//     pay all 7 and take the two instances that 6 of them buy, burning the 7th. That is their call —
//     over-paying can be deliberate (a card that counts EXHAUSTED resources, e.g. HMW_117 Chewbacca).
//   • ⚠ SCALED-EFFECT COST — resources ONLY, never Credit tokens / SEC_122 Droids. The magnitude keys
//     off "resources paid this way", and a Credit is NOT a resource (CR 3.13): it is a separate token
//     created in the resource zone, and defeating one pays 1 less rather than becoming a resource paid.
//     So a Credit can pay this CARD's own play cost on the normal play path, but must never scale this
//     effect. SWUExhaustResources (which skips Credits) is correct and SWUPayInlineAbilityCost is not —
//     the same deliberate exception SEC_040#1 and LOF_255#0 carry. Do not "fix" it back.
//     Shared coverage: Tests/Cases/core/CreditsDoNotScaleResourcePaidEffects.md.
// The only difference from Emergency Powers is the divisor: the effect fires once per THREE paid, so
// the prompt is suppressed below 3 (where it could only fizzle) and intdiv() converts payment to
// instances.
//
// Each instance re-reads Kelnacca's CURRENT power and picks its own enemy target, and the next instance
// is a QUEUED CUSTOM rather than an inline call — so "is Kelnacca still here / is there still an enemy
// unit" are evaluated at DRAIN time, after the previous instance's damage has resolved. Computing them
// inline would read the pre-damage board and could aim an instance at a unit that had just died (the
// HMW_035 recompute-before-every-pick lesson). Kelnacca rides through as a UniqueID: the arena
// reindexes whenever a target is defeated, and the answers arrive in later requests.

$whenPlayedAbilities["HMW_036:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    $maxX = SWUResourceCount(intval($player), readyOnly: true);   // resources only — see the header note
    if ($maxX < 3) return;                                        // cannot reach one instance → no prompt
    if (intval(ObjectCurrentPower($o)) <= 0) return;              // 0 damage is a fizzle too
    if (empty(_SWUCollectUnitTargets(intval($player), ['side' => 'their']))) return;  // no enemy unit
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|" . $maxX, 1,
        tooltip: "Pay_any_number_of_resources_(1_hit_per_3)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM",
        "HMW_036#0|" . intval($o->UniqueID ?? 0) . "|{$maxX}", 1);
};

// Pay what was chosen, then convert it to instances. Paying 1 or 2 — or 4, or 7 — is legal; the
// remainder simply buys nothing, which is why the payment happens in full before the intdiv.
// ⚠ The offered maximum is carried in the param and RE-ENFORCED here. The client cap is UX only: the
// schema harness (and a crafted request) hand an answer straight to the handler without consulting the
// decision's range, so a resolver that trusts its input can pass green while the offer was wrong.
// Clamping here is what makes "answer N and assert the outcome" actually guard the range.
$customDQHandlers["HMW_036#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $x    = intval($lastDecision);
    $maxX = intval($parts[1] ?? 0);
    if ($maxX > 0 && $x > $maxX) $x = $maxX;
    // ⚠ REDUNDANT WITH the intdiv guard below, and deliberately kept: it mirrors SEC_040#1 and stops a
    // negative answer ever reaching SWUExhaustResources. Because either one alone suppresses a zero
    // payment, neither reds Decline_NothingPaidNothingDealt on its own — only mutating BOTH does.
    if ($x <= 0) return;
    // ⚠ SCALED-EFFECT COST — resources ONLY, never Credits / Droids. See the header note.
    if (!SWUExhaustResources(intval($player), $x)) return;
    $instances = intdiv($x, 3);
    if ($instances <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "CUSTOM",
        "HMW_036#1|" . intval($parts[0] ?? 0) . "|{$instances}", 1);
};

// One instance: re-read the power, offer an enemy target, then queue the next instance behind it.
$customDQHandlers["HMW_036#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid  = intval($parts[0] ?? 0);
    $left = intval($parts[1] ?? 0);
    if ($left <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null || $mz === '') return;          // Kelnacca has left play — nothing to measure
    $self = GetZoneObject($mz);
    if (SWUObjGone($self)) return;
    $pow = intval(ObjectCurrentPower($self));        // CURRENT power, re-read per instance
    if ($pow > 0) {
        SWUOfferUnitTarget(intval($player), $mz, [
            'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $pow, 'side' => 'their',
            'prompt' => "Deal_{$pow}_damage_to_an_enemy_unit",
        ]);
    }
    if ($left > 1) {
        DecisionQueueController::AddDecision(intval($player), "CUSTOM",
            "HMW_036#1|{$uid}|" . ($left - 1), 1);
    }
};
