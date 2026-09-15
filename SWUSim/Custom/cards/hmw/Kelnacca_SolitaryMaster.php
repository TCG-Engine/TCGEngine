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
// The strikes are assigned in ONE divided-damage prompt and resolved SIMULTANEOUSLY — see HMW_036#1.
// Kelnacca rides through the payment as a UniqueID: the answers arrive in later requests.

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

// ── The strikes: ONE assignment, then ALL of it at once (CR 34.1 / 34.1.a) ────────────────────────────
// "For every 3 resources paid …" is a FOR-EACH: every instance is determined first and then they resolve
// SIMULTANEOUSLY, and damage from it "is calculated and dealt as one instance". So N strikes aimed at one
// unit are ONE hit of N × power — a single Shield prevents all of it. This used to offer and deal one
// strike at a time, so a Shield ate the first and the rest landed.
// USER DECISION 2026-09-14 — the UI is the divided-damage MZSPLITASSIGN with a STEP of Kelnacca's power:
// each −/+ moves one strike, the pool is strikes × power, and a unit may take several strikes. A lone enemy
// unit is not a choice, so it takes the whole pool without a prompt.
// Power is read ONCE, here, when the strikes are determined.
$customDQHandlers["HMW_036#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid       = intval($parts[0] ?? 0);
    $instances = intval($parts[1] ?? 0);
    if ($instances <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null || $mz === '') return;          // Kelnacca has left play — nothing to measure
    $self = GetZoneObject($mz);
    if (SWUObjGone($self)) return;
    $pow = intval(ObjectCurrentPower($self));
    if ($pow <= 0) return;
    $enemies = _SWUCollectUnitTargets(intval($player), ['side' => 'their']);
    if (empty($enemies)) return;
    $total  = $instances * $pow;
    $srcTok = _SWUEncodeDamageSource($mz);          // Kelnacca deals it (CR 9.12)
    if (count($enemies) === 1) {
        SWUDealSplitDamage(intval($player), $enemies[0] . ':' . $total, $srcTok);
        return;
    }
    // "total|targets|ALL|step": ALL = every strike must be placed; step = one strike's damage.
    DecisionQueueController::AddDecision(intval($player), "MZSPLITASSIGN",
        "{$total}|" . implode('&', $enemies) . "|ALL|{$pow}", 1,
        tooltip: "Assign_{$instances}_strike" . ($instances === 1 ? "" : "s") . "_of_{$pow}_damage_among_enemy_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_036#2|{$pow}|{$total}|{$srcTok}", 1);
};

// Deal the assignment — each unit's strikes as ONE instance, all units simultaneously (SWUDealSplitDamage).
// ⚠ Re-validated here as well as in SWUValidateDecisionAnswer: an amount that is not a whole number of
// strikes, or a total beyond the pool, is dropped rather than trusted.
$customDQHandlers["HMW_036#2"] = function($player, $parts, $lastDecision) {
    $pow    = intval($parts[0] ?? 0);
    $total  = intval($parts[1] ?? 0);
    $srcTok = (string)($parts[2] ?? '');
    if ($pow <= 0 || SWUDecisionDeclined($lastDecision)) return;
    $kept = []; $sum = 0;
    foreach (explode(',', (string)$lastDecision) as $pair) {
        $bits = explode(':', trim($pair));
        if (count($bits) < 2) continue;
        $amt = intval($bits[1]);
        if ($amt <= 0 || $amt % $pow !== 0 || $sum + $amt > $total) continue;
        $sum   += $amt;
        $kept[] = trim($bits[0]) . ':' . $amt;
    }
    if (!empty($kept)) SWUDealSplitDamage(intval($player), implode(',', $kept), $srcTok);
};
