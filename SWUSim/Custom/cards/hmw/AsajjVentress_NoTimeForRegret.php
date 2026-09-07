<?php
// HMW_001
// Cost 5 - Asajj Ventress, No Time For Regret - [Vigilance][Aggression] - Leader (Ground) 3/6
// Traits: Force, Night - Unique
// FRONT:  Action [Exhaust]: Attack with a unit. For this attack replace any Raid it has or gains with
//         Restore, or vice versa.
// EPIC:   Epic Action: If you control 5 or more resources, deploy this leader.
// DEPLOY: Restore 2.
//         Action: Attack with a unit. For this attack, replace any Raid it has or gains with Restore,
//         or vice versa.
//
// The Epic Action needs NO code: SWUDeployLeader's threshold IS the leader's printed cost (5), which is
// exactly "5 or more resources" (boundary pair Epic_DeployAtFiveResources / Epic_BlockedAtFourResources).
// Deployed Restore 2 is generated ($Restore_Cards['HMW_001'] => 2) — the undeployed leader is not a unit
// and never attacks, so carrying it on the CardID is harmless.
//
// ⚠ PREVIEW SET: HMW is not in card-specific-rulings.md, so every reading below is CR + analogue.
//
// ── THE SWAP ─────────────────────────────────────────────────────────────────────────────────────────
// "Replace any Raid it has or gains with Restore, OR VICE VERSA" is a bidirectional EXCHANGE of the two
// keywords for one attack, not a one-way conversion: a unit with Raid 2 and Restore 1 attacks as Raid 1
// and Restore 2.
//
// "HAS OR GAINS" is why this is modelled as a live recomputation rather than a snapshot taken when the
// attack begins. Raid and Restore are already conditional values recomputed on every read, so a Raid
// granted DURING the attack — by an aura, an upgrade, a Support lend — is swapped too, for free. A
// snapshot would silently miss exactly the case the words "or gains" exist to cover.
//
// ⚠ WHY THE DELTA LIVES IN THE *CONDITIONAL* FUNCTIONS. The public entry points
// GetKeyword_Raid_Value / GetKeyword_Restore_Value are GENERATED and must never be hand-edited, so the
// only hand-written seam is GetConditionalKeyword_{Raid,Restore}_Value, which contributes an ADDITIVE
// delta to the final value. Adding (otherKeyword - thisKeyword) there makes the final value equal the
// other keyword's, which is exactly the swap — and it lands for EVERY consumer at once (combat power,
// the Restore heal, the Support lend, the Active Effects popup) instead of needing each of the seven
// call sites patched.
//
// ⚠ RE-ENTRANCY. Computing the Raid delta needs the Restore value, whose own conditional function calls
// back into this helper. The static $busy latch makes those inner reads return the UNSWAPPED values,
// which is precisely what the delta must be computed against — without it the two functions would
// recurse forever.
//
// Suppression is handled for free: GetKeyword_*_Value returns null for a blanked unit before the
// conditional function is ever reached, so a unit that lost its abilities has neither keyword and the
// swap has nothing to exchange.

// True while this attack's swap marker is on $obj. Registered in $turnEffectRegistry (GameLogic) as a
// SWU_DUR_ATTACK MARKER — the registry literal is defined AFTER cards/_loader.php runs, so the entry
// has to live there rather than being appended from this file.
function _SWUHmw001SwapActive($obj): bool {
    if ($obj === null || !empty($obj->removed)) return false;
    foreach (SWUParsedTurnEffects($obj) as $e) {
        if (($e['base'] ?? '') === 'HMW_001') return true;
    }
    return false;
}

// The additive correction that turns $want's final value into the OTHER keyword's value.
function _SWUHmw001SwapDelta($obj, string $want): int {
    static $busy = false;
    if ($busy) return 0;                       // inner reads must see the UNSWAPPED values
    if (!_SWUHmw001SwapActive($obj)) return 0;
    $busy = true;
    $raid    = intval(GetKeyword_Raid_Value($obj));
    $restore = intval(GetKeyword_Restore_Value($obj));
    $busy = false;
    return ($want === 'RAID') ? ($restore - $raid) : ($raid - $restore);
}

// ── "ATTACK WITH A UNIT" ─────────────────────────────────────────────────────────────────────────────
// No "you may" and no "another", so: a MANDATORY choose (once the Action is taken) over every friendly
// unit that could declare an attack RIGHT NOW. _SWUUnitCanAttackNow enforces ready + printed/granted
// can't-attack restrictions + having a legal target, at SELECTION time — the same pool HMW_124 Luminara
// uses. Ventress's own deployed leader unit is eligible: the text says "a unit", not "another unit".
function _SWUHmw001EligibleAttackers(int $player): array {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $units = [];
    foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            if (_SWUUnitCanAttackNow(intval($player), $arr[$i], $arena)) $units[] = "{$zone}-{$i}";
        }
    }
    $playerID = $savedPID;
    return $units;
}

// Queues the choose. Returns false when nothing was queued, so each caller can close its own action.
function _SWUHmw001Offer(int $player): bool {
    global $playerID; $playerID = intval($player);
    $units = _SWUHmw001EligibleAttackers(intval($player));
    if (empty($units)) return false;
    SWUQueueChooseTarget(intval($player), $units,
        "Attack_with_a_unit_(Raid_and_Restore_are_swapped)", "HMW_001#0");
    return true;
}

// ── FRONT ────────────────────────────────────────────────────────────────────
// SWULeaderAction has already exhausted the leader by the time this runs. The cost is state-changing,
// so per CR 6.4.587.c the Action stays USABLE with no eligible attacker and simply fizzles — the same
// call HMW_003 Hemlock and HMW_009 Chewbacca make on their front sides in this set. When the offer
// queues nothing there is no combat to own the action-end, so close it here.
$leaderAbilities["HMW_001"] = function (int $player): void {
    if (!_SWUHmw001Offer($player)) SWUAfterAction($player);
};

// ── DEPLOYED ─────────────────────────────────────────────────────────────────
// Same effect, and per the deployed-leader convention the front's self-Exhaust is dropped (costKind
// 'none'). ⚠ That makes the deployed Action FREE, so unlike the front it must NOT be offered when it
// could do nothing — CR 6.4.587.c only protects an ability with a state-changing cost. The target gate
// lives in SWUUnitActionAffordable, exactly as HMW_009 Chewbacca's deployed side does it in this set.
$unitAbilities["HMW_001"]     = function ($player, $mzID = '') {
    if (!_SWUHmw001Offer(intval($player))) SWUAfterAction(intval($player));
};
$unitActionCostKind["HMW_001"] = 'none';

// Mark the chosen attacker for the duration of the attack, then begin it. Combat owns the after-action
// from here, so no SWUAfterAction on this branch (same shape as HMW_124 Luminara).
$customDQHandlers["HMW_001#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID; $playerID = intval($player);
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('HMW_001', [], SWU_DUR_ATTACK, 'HMW_001'));
    BeginSWUAttack(intval($player), $lastDecision);
};
