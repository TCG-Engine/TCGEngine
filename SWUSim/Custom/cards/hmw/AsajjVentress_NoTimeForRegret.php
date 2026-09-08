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
// "Replace any Raid it has or gains with Restore, OR VICE VERSA" is a CHOICE OF DIRECTION, not a
// simultaneous exchange (USER RULING 2026-09-08). You pick ONE keyword and replace it with the other
// for this attack.
//
// ⚠ THIS WAS ORIGINALLY BUILT AS AN EXCHANGE, AND THAT READING MAKES THE CARD A NO-OP ON EXACTLY THE
// UNIT IT IS MOST INTERESTING ON. LAW_050 Honnah is printed with BOTH Raid 2 and Restore 2, so
// exchanging them returns her to Raid 2 / Restore 2 and the Action does nothing at all. Under the
// ruling she is either Raid 4 or Restore 4 — the player chooses which.
//
// So the direction is a real decision, and it is offered only when there is one to make:
//   • BOTH keywords non-zero  -> OPTIONCHOOSE, the two directions (Honnah_BothKeywords_* sections).
//   • exactly ONE non-zero    -> auto-resolved to the only direction that does anything, NO prompt.
//                                The house rule bans a one-answer question, and the other direction
//                                would replace a keyword the unit does not have.
//   • NEITHER                  -> no marker and no prompt; the unit just attacks.
//
// ⚠ THE ONE OPEN QUESTION, flagged rather than guessed: with NEITHER keyword the words "or gains" are
// unreachable — a Raid granted mid-attack has no chosen direction to be replaced under, so it stays
// Raid. Prompting for a direction on every attack by a vanilla unit is a no-op prompt in all but a
// vanishing case, so this side of the trade was taken deliberately. If the intent is that the direction
// is always chosen, move the OPTIONCHOOSE above the keyword check in _SWUHmw001Offer's continuation.
//
// "HAS OR GAINS" is why this is modelled as a live recomputation rather than a snapshot taken when the
// attack begins. Raid and Restore are already conditional values recomputed on every read, so a Raid
// granted DURING the attack — by an aura, an upgrade, a Support lend — is swapped too, for free. A
// snapshot would silently miss exactly the case the words "or gains" exist to cover.
//
// ⚠ WHY THE DELTA LIVES IN THE *CONDITIONAL* FUNCTIONS. The public entry points
// GetKeyword_Raid_Value / GetKeyword_Restore_Value are GENERATED and must never be hand-edited, so the
// only hand-written seam is GetConditionalKeyword_{Raid,Restore}_Value, which contributes an ADDITIVE
// delta to the final value — and it lands for EVERY consumer at once (combat power, the Restore heal,
// the Support lend, the Active Effects popup) instead of needing each of the seven call sites patched.
// Replacing Raid with Restore is "subtract the whole Raid, add that much Restore", and vice versa.
//
// ⚠ RE-ENTRANCY. Computing the Raid delta needs the Restore value, whose own conditional function calls
// back into this helper. The static $busy latch makes those inner reads return the UNSWAPPED values,
// which is precisely what the delta must be computed against — without it the two functions would
// recurse forever.
//
// Suppression is handled for free: GetKeyword_*_Value returns null for a blanked unit before the
// conditional function is ever reached, so a unit that lost its abilities has neither keyword and the
// swap has nothing to exchange.

// The direction chosen for this attack: 'R2S' (Raid becomes Restore), 'S2R' (Restore becomes Raid), or
// '' when no marker is present. Registered in $turnEffectRegistry (GameLogic) as a SWU_DUR_ATTACK
// MARKER — the registry literal is defined AFTER cards/_loader.php runs, so the entry has to live there
// rather than being appended from this file. The direction rides as a token PARAM ("HMW_001-R2S"), so
// it survives the request boundary between choosing it and combat reading it.
function _SWUHmw001SwapDirection($obj): string {
    if ($obj === null || !empty($obj->removed)) return '';
    foreach (SWUParsedTurnEffects($obj) as $e) {
        if (($e['base'] ?? '') !== 'HMW_001') continue;
        return strval(($e['params'] ?? [])[0] ?? '');
    }
    return '';
}

// The additive correction that applies the chosen replacement to $want's final value.
function _SWUHmw001SwapDelta($obj, string $want): int {
    static $busy = false;
    if ($busy) return 0;                       // inner reads must see the UNREPLACED values
    $dir = _SWUHmw001SwapDirection($obj);
    if ($dir === '') return 0;
    $busy = true;
    $raid    = intval(GetKeyword_Raid_Value($obj));
    $restore = intval(GetKeyword_Restore_Value($obj));
    $busy = false;
    if ($dir === 'R2S') return ($want === 'RAID') ? -$raid : $raid;   // all Raid becomes Restore
    return ($want === 'RAID') ? $restore : -$restore;                 // 'S2R' — all Restore becomes Raid
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
        "Attack_with_a_unit", "HMW_001#0");
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

// Mark the chosen attacker with the direction for the duration of the attack, then begin it. Combat
// owns the after-action from here, so no SWUAfterAction on this branch (same shape as HMW_124 Luminara).
function _SWUHmw001BeginSwappedAttack(int $player, string $mzID, string $dir): void {
    global $playerID; $playerID = $player;
    if (SWUObjGone(GetZoneObject($mzID))) return;
    if ($dir !== '') AddTurnEffect($mzID, SWUMakeTurnEffect('HMW_001', [$dir], SWU_DUR_ATTACK));
    BeginSWUAttack($player, $mzID);
}

// The attacker has been chosen. Read BOTH keywords off it — unreplaced, since no marker exists yet —
// and only raise the direction prompt when both are live.
$customDQHandlers["HMW_001#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;

    $raid    = intval(GetKeyword_Raid_Value($obj));
    $restore = intval(GetKeyword_Restore_Value($obj));

    if ($raid > 0 && $restore > 0) {
        // Carry the attacker by UniqueID, not by mzID: the direction is answered in a LATER request and
        // the arena can reindex under a positional id in between.
        DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE",
            "Replace_Raid_With_Restore&Replace_Restore_With_Raid", 1,
            tooltip: "Replace_which_keyword_for_this_attack?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM",
            "HMW_001#1|" . intval($obj->UniqueID ?? 0), 1);
        return;
    }
    // Exactly one keyword (or neither): no decision to make, so no prompt.
    _SWUHmw001BeginSwappedAttack(intval($player), $lastDecision,
        $raid > 0 ? 'R2S' : ($restore > 0 ? 'S2R' : ''));
};

// The direction was chosen. $parts[0] is the attacker's UniqueID (a continuation's payload starts at
// index 0 — the handler name is already stripped).
$customDQHandlers["HMW_001#1"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mzID = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mzID === null || $mzID === '') return;
    _SWUHmw001BeginSwappedAttack(intval($player), $mzID,
        $lastDecision === 'Replace_Restore_With_Raid' ? 'S2R' : 'R2S');
};
