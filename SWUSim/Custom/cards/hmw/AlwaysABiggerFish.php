<?php
// HMW_099
// Cost 2 - Always a Bigger Fish - [Vigilance] - Event - Trait: Innate
// Text: Defeat a friendly Creature unit. If you do, play a Creature unit that costs up to 3 more than the
//       defeated unit from your hand for free.

// The IBH_095 You Have Failed Me / SHD_108 Enforced Loyalty shape ("Defeat a friendly unit. If you do, …"):
//   • "friendly" = the TEAM (SWUFriendlyUnits) — USER RULING 2026-08-25 on IBH_095: a teammate's unit may be
//     defeated to satisfy "If you do". The trait is read off the LIVE object (TraitContains), so a granted
//     Creature trait counts and a lost one does not.
//   • The defeat has no "may": a mandatory choose (a lone Creature auto-resolves), and it is NOT gated on a
//     playable Creature existing — only the play hangs off "If you do".
$whenPlayedAbilities["HMW_099:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $pool = [];
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o) && TraitContains($o, 'Creature')) $pool[] = $mz;
    }
    if (empty($pool)) return;
    SWUQueueChooseTarget(intval($player), $pool, 'Defeat_a_friendly_Creature_unit', 'HMW_099#0');
};

// Defeat it; if it really was defeated, offer the play. The cap is the defeated unit's PRINTED cost + 3
// (CR 2.6 — a token is 0, a discount on the new card is irrelevant), captured BEFORE the defeat.
// "If you do" is met only by an actual defeat (SWUDefeatUnit's return). ⚠ Measured GREEN under mutation:
// no board today makes YOUR OWN defeat of a friendly Creature fail — the "can't be defeated by ENEMY card
// abilities" protections never apply to a friend (SWUIsEnemySeat, which is also what lets a TEAMMATE's
// TWI_220-protected Creature be defeated here). Kept because the text requires it, not as a tested guard.
// The play is from the HAND, a hidden zone → always a MAY-choose (user ruling 2026-08-15), even with one
// legal card. The pool is not re-checked in the continuation: the server already refuses an answer that
// is not in the offered pool (SWUValidateDecisionAnswer) — a re-check here was measured unreachable.
//
// ★ SAME-WINDOW TRIGGERS ARE ORDERABLE (USER RULING 2026-09-15). The defeated Creature's When Defeated and
// the played Creature's When Played are both set off by this one event, wait for it to finish (CR 7.6.8),
// and then resolve in the order the player chooses (CR 7.6.9). The engine flushes a When Defeated to the
// flat RESOLVE_TRIGGER queue and a When Played to the EffectStack, so left alone they never meet and the
// When Defeated is forced first. So the defeat's triggers are PARKED (SWU_DEFER_WD — the same bag Collateral
// Damage uses), carried to the play serialized in the continuation's param (they must survive the request
// boundary at the hand pick), and handed to ActivateCard's Exploit replay seam ($gExploitDeferredBag), which
// flushes them in ONE batch with the played unit's entry triggers — one ordering prompt. With no play (none
// affordable, declined, refused) they are flushed on their own, exactly as before.
$customDQHandlers["HMW_099#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cap = intval(CardCost($o->CardID ?? '') ?? 0) + 3;
    SWUBeginDeferWhenDefeated();
    $defeated = SWUDefeatUnit(intval($player), $lastDecision);
    $bag = json_decode(GetSWUVar('SWU_DEFER_WD_BAG', '[]'), true);
    SetSWUVar('SWU_DEFER_WD', 'false');
    SetSWUVar('SWU_DEFER_WD_BAG', '[]');
    if (!is_array($bag)) $bag = [];
    $playerID = intval($player);
    if (!$defeated) { _SWUHmw099FlushParked(intval($player), $bag); return; }
    DecisionQueueController::CleanupRemovedCards();   // the event still lingers in hand as a removed entry
    $hand = _SWUHmw099Playables(intval($player), $cap);
    if (empty($hand)) { _SWUHmw099FlushParked(intval($player), $bag); return; }
    $tip = "Play_a_Creature_unit_that_costs_{$cap}_or_less_from_your_hand_for_free";
    DecisionQueueController::AddDecision(intval($player), 'MZMAYCHOOSE', implode('&', $hand), 1, tooltip: $tip);
    // dontSkipOnPass: a decline by the Pass button leaves a sticky PASS, and a skipped continuation would
    // strand the parked When Defeated forever. The handler treats PASS / '-' as a decline.
    $b64 = rtrim(strtr(base64_encode(json_encode($bag)), '+/', '-_'), '=');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_099#1|{$b64}", 1, dontSkipOnPass: 1);
};

// "For free" bypasses EVERY modifier to the cost, the aspect penalty included, and the cost-reducing
// pickers with it (CR 6.2 step 3.d) — so this is the ignoreCost play (SWUNestedPlay), NOT SWUNestedPlayUnit
// with a large discount: that path would still raise HMW_049 Greater Sarlacc's resource-defeat picker and
// TWI_118's Exploit picker, both of which are cost modifiers. An additional NON-resource cost would still
// have to be paid; no Creature has one today (HMW_048 Vernestra's is the only such cost in HMW, and she is
// not a Creature) — revisit if one is printed.
$customDQHandlers["HMW_099#1"] = function ($player, $parts, $lastDecision) {
    global $playerID, $gExploitDeferredBag; $playerID = intval($player);
    $raw = strval($parts[0] ?? '');
    $bag = ($raw === '') ? [] : json_decode(base64_decode(strtr($raw, '-_', '+/')), true);
    if (!is_array($bag)) $bag = [];
    if (SWUDecisionDeclined($lastDecision) || strpos(strval($lastDecision), '-') === false) {
        _SWUHmw099FlushParked(intval($player), $bag);
        return;
    }
    // Into the Exploit replay seam: ActivateCard's unit branch replays this bag into $gPendingTriggers just
    // before the played unit's FlushEntryTriggerBag, so both windows land in one ordering prompt.
    if (!is_array($gExploitDeferredBag)) $gExploitDeferredBag = [];
    foreach ($bag as $entry) $gExploitDeferredBag[] = $entry;
    SWUNestedPlay(intval($player), $lastDecision, true, 0);
    // The play never reached the replay (refused mid-way): the parked triggers must still resolve.
    if (!empty($gExploitDeferredBag)) {
        $left = $gExploitDeferredBag;
        $gExploitDeferredBag = [];
        _SWUHmw099FlushParked(intval($player), $left);
    }
};

// Flush parked When-Defeated / leave-play / bounty entries ON THEIR OWN — the no-play path. Mirrors
// SWUFlushDeferredWhenDefeated: triggers active-player-first, bounty offered after them.
function _SWUHmw099FlushParked(int $player, array $bag): void {
    if (empty($bag)) return;
    $bounty = [];
    foreach ($bag as $entry) {
        if (($entry['__kind__'] ?? 'trigger') === 'bounty') { $bounty[] = $entry; continue; }
        AddTrigger($entry['player'], $entry['triggerType'], $entry['cardID'], $entry['mzID'], $entry['extraParams'] ?? '');
    }
    FlushTriggerBag($player);
    foreach ($bounty as $entry) {
        DecisionQueueController::AddDecision($entry['activePlayer'], "YESNO", "-", 1, tooltip:"Collect_bounty?");
        DecisionQueueController::AddDecision($entry['activePlayer'], "CUSTOM", "SWUCollectBounty|{$entry['cardID']}", 1);
    }
}

// The play pool: Creature UNITS in the player's hand whose printed cost is at most $cap. Out of play, so the trait is read through _SWUCardHasTrait (honours HMW_108's strip).
function _SWUHmw099Playables(int $player, int $cap): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $h = GetZoneObject($mz);
        if (SWUObjGone($h)) continue;
        $cid = $h->CardID ?? '';
        if (CardType($cid) !== 'Unit') continue;
        if (!_SWUCardHasTrait($player, $cid, 'Creature')) continue;
        if (intval(CardCost($cid) ?? 0) > $cap) continue;
        $out[] = $mz;
    }
    return $out;
}
