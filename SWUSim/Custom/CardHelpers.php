<?php
// Small shared predicates/helpers for card ability + DQ handler code. Included by
// GameLogic.php before the card registrations so both the monoliths and the
// per-card files (cards/<set>/*.php) can call them.

// True when a decision-queue answer represents "no choice / declined / passed".
//
// The pass token differs by decision TYPE: MZMAYCHOOSE passes as '-', some flows as '' / 'PASS', and a
// YESNO's No button submits the literal string **'NO'** (Core/UILibraries*.js ShowYesNoDecisionPopup →
// onSubmit('NO')). 'NO' was missing here, so a handler that queued a YESNO but declined via this helper
// treated a real "No" click as an ACCEPT — it fell straight through and resolved the effect.
//
// That shipped in three HMW cards (HMW_171 Trap Field, HMW_168 Ezra Bridger, HMW_060 Rampart's
// RAMPART_SAVE): declining Trap Field still defeated the upgrade AND dealt its 3 damage, so a player
// saying "no" watched it kill their own just-played unit. Their decline tests all answered '-' — the
// MZMAYCHOOSE token, which the client never sends for a YESNO — so every one of them passed.
//
// Safe to treat 'NO' as a decline everywhere: it is not a valid mzID, and no OPTIONCHOOSE in the repo
// offers a literal 'NO' label, so it can only ever arrive from a YESNO's No button.
// (The other convention — `if ($lastDecision !== 'YES') return;` — remains correct for YESNO handlers.)
if (!function_exists('SWUDecisionDeclined')) {
    function SWUDecisionDeclined($d): bool {
        return !$d || $d === '-' || $d === '' || $d === 'PASS' || $d === 'NO';
    }
}

// A zone object's UniqueID, or $default when the object is null/gone (defeated,
// removed). Mirrors the historical inline idiom exactly:
//   $obj ? intval($obj->UniqueID ?? $default) : $default
// No real unit ever has UniqueID <= 0 (the counter starts at 1), so the -1 and 0
// sentinels are interchangeable as "not a real unit"; callers pass whichever they
// used historically.
if (!function_exists('SWUObjUID')) {
    function SWUObjUID($obj, int $default = -1): int {
        return $obj ? intval($obj->UniqueID ?? $default) : $default;
    }
}

// True when a zone object is absent or has been removed from play — the ubiquitous
// "resolved target no longer valid" guard. Mirrors the inline idiom exactly:
//   $o === null || !empty($o->removed)
if (!function_exists('SWUObjGone')) {
    function SWUObjGone($o): bool {
        return $o === null || !empty($o->removed);
    }
}

// All in-play unit mzIDs for a side/arena, relative to the current $playerID.
//   $of    = 'my' | 'their' | null (both sides)
//   $arena = 'Ground' | 'Space'    | null (both arenas)
// Iteration is side-outer, arena-inner, so the returned order is exactly the
// historical inline merges — with 'team' where the pool is UNQUALIFIED (see the note in the body):
// SWUAllUnits() == array_merge(teamGround, teamSpace, theirGround, theirSpace);
// SWUAllUnits('my') == array_merge(myGround, mySpace);   // an explicit side is untouched
// SWUAllUnits(null,'Ground') == array_merge(teamGround, theirGround); etc.
// Outside a team game 'team' degrades to the caller's own zone, so all of these are byte-identical
// to the pre-Team-Suns behaviour at two seats.
// ─── "does this unit have a TOKEN UPGRADE on it?" ────────────────────────────
// The rules CATEGORY, read off the card data (CardType 'Token Upgrade'), NOT a hand-kept CardID list.
// That is the whole point: HMW_015 Bossk's reminder text says "(Shield and Weakness tokens are token
// upgrades.)", which restates the category with two examples rather than narrowing it — Experience
// (SOR_T01), Advantage (ASH_T02) and every reprint of them are token upgrades too, and a
// Shield-and-Weakness-only list would silently make those units untargetable.
// Captives and removed subcards are already excluded by GetUpgradesOnUnit.
if (!function_exists('_SWUHasTokenUpgrade')) {
    function _SWUHasTokenUpgrade($obj): bool {
        if ($obj === null) return false;
        foreach (GetUpgradesOnUnit($obj) as $sub) {
            if (CardType($sub->CardID ?? '') === 'Token Upgrade') return true;
        }
        return false;
    }
}

if (!function_exists('SWUAllUnits')) {
    function SWUAllUnits(?string $of = null, ?string $arena = null, $filter = null): array {
        // $filter: a ZoneSearch card-type filter (AnyUnitFilter default, NonLeaderUnitFilter, …).
        // Threaded through so callers that need a narrower pool still route through the helpers
        // instead of hand-rolling a ZoneSearch pair.
        if ($filter === null) $filter = AnyUnitFilter;
        // null = EVERY unit on the table. In a team game that is team + their; outside one 'team'
        // degenerates to 'my', so this stays byte-identical to the historical ['my','their'].
        // ⚠ THIS LINE IS THE FIX for the Phase 2 targeting hole: once 'their' excludes a teammate,
        // 'my' + 'their' no longer covers the table, so an unqualified pool must start from 'team'.
        $sides  = $of    !== null ? [$of]    : ['team', 'their'];
        $arenas = $arena !== null ? [$arena] : [GroundArena, SpaceArena]; // "Ground","Space"
        $out = [];
        foreach ($sides as $s) {
            foreach ($arenas as $a) {
                $out = array_merge($out, ZoneSearch($s . $a . 'Arena', $filter));
            }
        }
        return $out;
    }
}

// ─── "this phase" unit flags, read against the unit's CONTROLLER ─────────────
// SWU_PLAYED_UNIT_{uid} and SWU_UNIT_ATTACKED_{uid} are stored on the CONTROLLER's GlobalEffects
// (see ActivateCard / ExecuteSWUAttack). Reading them against the ACTING player is only correct while
// the pool is the actor's own board — the moment a pool spans other seats (Team Suns, or any
// unqualified "a unit" effect) that read silently returns false for every foreign unit.
// Use these instead of hand-rolling GlobalEffectCount($player, …).
if (!function_exists('SWUUnitPlayedThisPhase')) {
    function SWUUnitPlayedThisPhase($obj): bool {
        if ($obj === null) return false;
        return GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_PLAYED_UNIT_' . intval($obj->UniqueID ?? 0)) > 0;
    }
}
// ⚠ "ENTERED PLAY" IS NOT "PLAYED", and the two need different flags (bug #1025/#1026).
// A leader that DEPLOYS enters play but is not played (CR 6.x: "considered deployed, not played"), and a
// created token enters play without being played either. SWU_PLAYED_UNIT_ is set in exactly ONE place —
// ActivateCard's unit-entry branch — so it answers "was PLAYED" and nothing else. SWU_ENTERED_PHASE_ is
// set by CollectEntryTriggers (every entry, deploys included) and _SWUCreateOneToken; both clear in
// RegroupPhaseStart, i.e. once per round, so this answers "this phase" and "this round" alike.
//
// Pick by the CARD TEXT:
//     "entered play this phase" / "didn't enter play this round"  -> SWUUnitEnteredPlayThisPhase
//     "you played this phase"   / "was played this phase"         -> SWUUnitPlayedThisPhase
// Getting this wrong is silent: Premier fixtures almost only ever PLAY units, so a card reading the
// wrong flag looks correct until a leader deploys or a token is created.
if (!function_exists('SWUUnitEnteredPlayThisPhase')) {
    function SWUUnitEnteredPlayThisPhase($obj): bool {
        if ($obj === null) return false;
        return GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_ENTERED_PHASE_' . intval($obj->UniqueID ?? 0)) > 0;
    }
}
if (!function_exists('SWUUnitAttackedThisPhase')) {
    function SWUUnitAttackedThisPhase($obj): bool {
        if ($obj === null) return false;
        return GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_UNIT_ATTACKED_' . intval($obj->UniqueID ?? 0)) > 0;
    }
}

// ─── THE friendly / controlled split (Team Suns) ─────────────────────────────
// These two are the API. Card code should call one of them rather than naming a zone spec, so that
// when the team rules change there is exactly ONE function to edit per meaning.
//
//   SWUFriendlyUnits()   — "a friendly unit", "another friendly unit", auras over friendly units.
//                          Spans YOU AND YOUR TEAMMATE in a Team Suns game.
//   SWUControlledUnits() — "a unit you control", Coordinate counts, Exploit fodder, cost payment,
//                          action legality. ALWAYS self-only, in every format.
//
// ⚠ The distinction is real and load-bearing (spec §2): a teammate's unit is FRIENDLY but you do NOT
// CONTROL it. Outside a team game both return the same set, which is why Twin Suns / Premier are safe.
// $arena: 'Ground' | 'Space' | null (both).
if (!function_exists('SWUFriendlyUnits')) {
    function SWUFriendlyUnits(?string $arena = null, $filter = null): array { return SWUAllUnits('team', $arena, $filter); }
}
if (!function_exists('SWUControlledUnits')) {
    function SWUControlledUnits(?string $arena = null, $filter = null): array { return SWUAllUnits('my', $arena, $filter); }
}

// OBJECT-returning sibling of GetUnitsInPlay for text that says "FRIENDLY" — spans the TEAM.
//
// ⚠ Why this exists. The Phase 3 friendly sweep converted everything that flowed through ZoneSearch
// (192 raw sites down to 2), but GetUnitsInPlay is a PER-SEAT ACCESSOR, not a ZoneSearch — so a card
// counting "each friendly X" that way was structurally out of that sweep's reach and stayed self-only.
// Seventeen cards were in that state; audited and converted 2026-08-26.
//
// Returns objects in the same shape as GetUnitsInPlay so a call site converts by swapping the function
// name and nothing else. SWUTeammatesOf returns [] outside a team game, so Premier and Twin Suns get
// literally GetUnitsInPlay's own array back — byte-identical.
//
// ⚠ Use this ONLY where the printed word is "friendly". Text that says "YOU CONTROL" (Coordinate,
// Exploit, "if you control N units", HMW_014 Wicket's deployed side) must stay on GetUnitsInPlay:
// a teammate's unit is friendly but you do NOT control it (spec §2).
if (!function_exists('SWUFriendlyUnitObjects')) {
    function SWUFriendlyUnitObjects(int $player): array {
        $out = GetUnitsInPlay($player);
        foreach (SWUTeammatesOf($player) as $mate) {
            foreach (GetUnitsInPlay($mate) as $u) $out[] = $u;
        }
        return $out;
    }
}

// Shared "play a card at a discount" offer, used by the discount-play family
// (Alliance Dispatcher, Strategic Acumen, Home One, …). Gathers affordable
// candidates in $zone of the given $types (optionally passing an extra $filter),
// lets the player choose, and plays the chosen at $discount. Cards with follow-ups
// pass a custom 'continuation'; gated/conditional cards compute state then call this.
//
//   $opts:
//     'discount'     int      (default 1; use a large value like 999 for a free play)
//     'zone'         'myHand' | 'myDiscard'                (default 'myHand')
//     'types'        ['Unit'|'Event'|'Upgrade'] | null     (null = any)
//     'filter'       callable(string $cardID): bool        (optional extra gate)
//     'prompt'       string                                (choose-target prompt)
//     'may'          bool     (default false — a mandatory ChooseTarget; true = optional
//                              MayChooseTarget the player can decline)
//     'question'     string   (the may-prompt yes/no question; defaults to 'prompt')
//     'continuation' string   (default the shared, zone-agnostic DISCOUNT_PLAY_FROM_HAND
//                              handler, which plays the chosen mzID from ANY zone via
//                              ActivateCard; follow-up cards override)
//     'afterAction'  bool     (default true — call SWUAfterAction when no targets;
//                              set false for When-Played contexts that don't)
if (!function_exists('SWUOfferDiscountPlay')) {
    function SWUOfferDiscountPlay(int $player, array $opts): void {
        global $playerID;
        $playerID    = intval($player);
        $discount    = intval($opts['discount'] ?? 1);
        $zone        = $opts['zone'] ?? 'myHand';
        $types       = $opts['types'] ?? null;
        $filter      = $opts['filter'] ?? null;
        $prompt      = $opts['prompt'] ?? 'Play_a_card_at_a_discount';
        $may         = $opts['may'] ?? false;
        $question    = $opts['question'] ?? $prompt;
        $afterAction = $opts['afterAction'] ?? true;
        // DISCOUNT_PLAY_FROM_HAND is zone-agnostic (it plays $lastDecision's mzID via
        // ActivateCard), so it serves discard plays too.
        $cont = $opts['continuation'] ?? ('DISCOUNT_PLAY_FROM_HAND|' . $discount);

        $targets = SWUPlayablesAtDiscount($player, $zone, $types, $discount, $filter);
        if (empty($targets)) { if ($afterAction) SWUAfterAction($player); return; }
        // 'may' = the printed effect is optional ("you may", "up to N", "any number"), so the offer is
        // MZMAYCHOOSE and is presented even when only one target is legal — otherwise a lone target
        // auto-resolves and the player can never decline. Anything else stays a mandatory MZCHOOSE.
        if ($may) SWUQueueMayChooseTarget(intval($player), $targets, $question, $prompt, $cont);
        else      SWUQueueChooseTarget(intval($player), $targets, $prompt, $cont);
    }
}

// Candidate mzIDs in $zone playable after $discount, filtered by $types and an
// optional $filter(cardID). Generalizes SWUHandPlayablesAtDiscount to any zone.
if (!function_exists('SWUPlayablesAtDiscount')) {
    function SWUPlayablesAtDiscount(int $player, string $zone, ?array $types, int $discount, ?callable $filter = null): array {
        $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a play cost (CR 3.13)
        $out = [];
        foreach (ZoneSearch($zone, $types) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if ($filter !== null && !$filter($o->CardID ?? '')) continue;
            $cost = max(0, SWUComputePlayCost($player, $o) - $discount);
            if ($cost <= $ready) $out[] = $mz;
        }
        return $out;
    }
}

// Unit mzIDs eligible to receive a token, applying the standard "give a token to a {friendly} {trait}
// unit" filters. Object-aware: trait matching routes through TraitContains, so a unit that GAINED the
// trait via an upgrade (e.g. SHD_069 Foundling → Mandalorian) is included and one that LOST it is
// excluded. Iteration order matches SWUAllUnits (myGround, mySpace, theirGround, theirSpace), so
// single-target auto-resolve / first-target picks are unchanged from the historical inline loops.
//   'friendlyOnly' bool          default true  ('my' side only; false = all units, both players)
//   'traits'       string|array  OR-list; '' or [] = any unit
//   'notTraits'    string|array  exclude a unit matching ANY of these; default []
//   'excludeUID'   int|null      skip this UniqueID (the "another …" clause); default null
if (!function_exists('_SWUCollectTokenTargets')) {
    function _SWUCollectTokenTargets(int $player, array $opts = []): array {
        $friendlyOnly = array_key_exists('friendlyOnly', $opts) ? !empty($opts['friendlyOnly']) : true;
        return _SWUCollectUnitTargets($player, [
            'side'       => $friendlyOnly ? 'my' : 'any',
            'traits'     => $opts['traits']     ?? [],
            'notTraits'  => $opts['notTraits']  ?? [],
            'excludeUID' => $opts['excludeUID'] ?? null,
        ]);
    }
}

// Choose one filtered unit and grant it N token(s): the canonical "give an Experience/Shield/Advantage
// token to a {friendly} {trait} unit" When-Played / On-Attack / When-Defeated helper. Collects via
// _SWUCollectTokenTargets, no-ops when none exist, then queues a mandatory (SWUQueueChooseTarget) or
// optional (may — SWUQueueMayChooseTarget) pick whose continuation grants the token(s).
//   'token'        'EXPERIENCE'|'SHIELD'|'ADVANTAGE'  default 'EXPERIENCE'
//   'amount'       int    default 1   (SHIELD grants exactly one — amount is not threaded into its
//                                       handler; only EXPERIENCE is exercised today)
//   'may'          bool   default false
//   'traits'       string|array   OR-list; '' or [] = any unit
//   'notTraits'    string|array   default []
//   'excludeSelf'  bool   default false  (true → exclude the source unit; resolves $mzID's UniqueID)
//   'friendlyOnly' bool   default true
//   'block'        int    decision-queue block priority; default 1
//   'prompt'       string choose tooltip; default 'Give_a_token_to_a_unit'
//   'question'     string may yes/no tooltip; default = prompt
if (!function_exists('GiveTokenUpgrade')) {
    function GiveTokenUpgrade(int $player, string $mzID, array $opts = []): void {
        $token = strtoupper($opts['token'] ?? 'EXPERIENCE');
        $cont  = ($token === 'SHIELD') ? 'GIVE_SHIELD' : "GIVE_{$token}";  // SHIELD is bare; EXP/ADV take |N
        $friendlyOnly = array_key_exists('friendlyOnly', $opts) ? $opts['friendlyOnly'] : true;
        $prompt = $opts['prompt'] ?? 'Give_a_token_to_a_unit';
        SWUOfferUnitTarget($player, $mzID, [
            'continuation' => $cont,
            'amount'       => max(1, intval($opts['amount'] ?? 1)),
            'may'          => !empty($opts['may']),
            'traits'       => $opts['traits']    ?? [],
            'notTraits'    => $opts['notTraits'] ?? [],
            'excludeSelf'  => !empty($opts['excludeSelf']),
            'side'         => $friendlyOnly ? 'my' : 'any',
            'block'        => intval($opts['block'] ?? 1),
            'prompt'       => $prompt,
            'question'     => $opts['question'] ?? $prompt,
        ]);
    }
}

// Generalized "collect a set of target mzIDs" for the choose-a-unit-and-apply-effect families.
// Object-aware (trait matching via TraitContains). Units come first in SWUAllUnits order
// (myGround, mySpace, theirGround, theirSpace — filtered by side/arena), then bases, so single-target
// auto-resolve and first-target picks match the historical inline loops.
//   'side'         'my'|'their'|'any'          default 'any'
//   'arena'        'Ground'|'Space'|null(both) default null
//   'traits'       string|array   OR-list; ''/[] = any
//   'notTraits'    string|array   exclude if it matches any; default []
//   'nonLeader'    bool           exclude IsLeaderUnit units; default false
//   'excludeUID'   int|null       exclude this UniqueID; default null
//   'extraFilter'  callable(object):bool   per-target predicate on the unit object; default null
//   'includeBases' bool           append base mzIDs to the result; default false
//   'baseSide'     'my'|'their'|'any'   which base(s) when includeBases; default 'any'
if (!function_exists('_SWUCollectUnitTargets')) {
    function _SWUCollectUnitTargets(int $player, array $opts = []): array {
        global $playerID; $playerID = intval($player);
        $side  = $opts['side']  ?? 'any';
        $arena = $opts['arena'] ?? null;
        $traits    = $opts['traits']    ?? [];
        $notTraits = $opts['notTraits'] ?? [];
        if (is_string($traits))    $traits    = ($traits === '')    ? [] : [$traits];
        if (is_string($notTraits)) $notTraits = ($notTraits === '') ? [] : [$notTraits];
        $nonLeader   = !empty($opts['nonLeader']);
        $excludeUID  = (array_key_exists('excludeUID', $opts) && $opts['excludeUID'] !== null)
            ? intval($opts['excludeUID']) : null;
        $extraFilter = $opts['extraFilter'] ?? null;
        // 'ofSeat' => int (Twin Suns): restrict the pool to ONE named seat's units. For text that scopes
        // to a specific player after a pick or a comparison — "a unit THAT OPPONENT controls", "a
        // non-leader unit THEY control (the defending player)". Distinct from 'side' => 'their', which
        // above two seats fans out across EVERY opponent and so OFFERS ILLEGAL TARGETS.
        // ⚠ That over-widening is the sweep's inverse defect: the pool GREW, so nothing looks broken —
        // no prompt goes missing and no effect fizzles; it only shows up as a target that should not
        // have been selectable. At ≤2 seats 'their' and 'ofSeat'=>theOneOpponent are the same set,
        // which is exactly why it stayed invisible.
        $ofSeat = (array_key_exists('ofSeat', $opts) && $opts['ofSeat'] !== null) ? intval($opts['ofSeat']) : null;
        // 'my'       -> SELF-ONLY  ("a unit you control")
        // 'friendly' -> TEAM-WIDE  ("a friendly unit") — Team Suns; identical to 'my' elsewhere
        // 'their'    -> opponents (already team-aware via OpponentsOf)
        // 'any'      -> the whole table
        $sideArg = ($side === 'any') ? null : (($side === 'friendly') ? 'team' : $side);
        $out = [];
        foreach (SWUAllUnits($sideArg, $arena) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if ($ofSeat !== null && SWUMzOwner($mz, intval($player)) !== $ofSeat) continue;
            if ($excludeUID !== null && intval($o->UniqueID ?? -1) === $excludeUID) continue;
            if ($nonLeader && IsLeaderUnit($o)) continue;
            $skip = false;
            foreach ($notTraits as $nt) { if (TraitContains($o, $nt)) { $skip = true; break; } }
            if ($skip) continue;
            if (!empty($traits)) {
                $match = false;
                foreach ($traits as $t) { if (TraitContains($o, $t)) { $match = true; break; } }
                if (!$match) continue;
            }
            if ($extraFilter !== null && !$extraFilter($o)) continue;
            $out[] = $mz;
        }
        if (!empty($opts['includeBases'])) {
            foreach (SWUAllBaseMzIDs(intval($player), $opts['baseSide'] ?? 'any') as $bmz) $out[] = $bmz;
        }
        return $out;
    }
}

// Canonical "choose one target and apply an effect" helper. Collects via _SWUCollectUnitTargets,
// no-ops on empty, then queues a mandatory (SWUQueueChooseTarget) or optional (may) pick whose
// continuation applies the effect to the chosen mzID.
//   (all _SWUCollectUnitTargets opts, plus:)
//   'continuation' string   REQUIRED — universal handler, e.g. 'DEFEAT_UNIT','BOUNCE_UNIT','DEAL_TARGET'
//   'amount'       int      default 1 — appended as '|N' iff continuation is amount-taking
//                           (GIVE_EXPERIENCE|GIVE_ADVANTAGE|HEAL_TARGET|DEAL_TARGET) and has no '|' yet
//   'excludeSelf'  bool     exclude the source unit (resolves $mzID's UID → excludeUID); default false
//   'may'          bool     default false
//   'block'        int      default 1
//   'prompt'       string   choose tooltip; default 'Choose_a_target'
//   'question'     string   may yes/no tooltip; default = prompt
if (!function_exists('SWUOfferUnitTarget')) {
    function SWUOfferUnitTarget(int $player, string $mzID, array $opts = []): void {
        global $playerID; $playerID = intval($player);
        $may    = !empty($opts['may']);
        $block  = intval($opts['block'] ?? 1);
        $amount = max(1, intval($opts['amount'] ?? 1));
        $excludeUID = !empty($opts['excludeSelf'])
            ? SWUObjUID(GetZoneObject($mzID))
            : ((array_key_exists('excludeUID', $opts) && $opts['excludeUID'] !== null) ? intval($opts['excludeUID']) : null);
        $targets = _SWUCollectUnitTargets($player, [
            'side'         => $opts['side']         ?? 'any',
            'arena'        => $opts['arena']        ?? null,
            'traits'       => $opts['traits']       ?? [],
            'notTraits'    => $opts['notTraits']    ?? [],
            'nonLeader'    => !empty($opts['nonLeader']),
            'excludeUID'   => $excludeUID,
            'extraFilter'  => $opts['extraFilter']  ?? null,
            'includeBases' => !empty($opts['includeBases']),
            'baseSide'     => $opts['baseSide']     ?? 'any',
            // ⚠ This forward list is an explicit WHITELIST — an option missing from it is silently
            // dropped, and the caller looks correct while the filter never runs. Keep it in sync with
            // _SWUCollectUnitTargets' option set.
            'ofSeat'       => (array_key_exists('ofSeat', $opts) && $opts['ofSeat'] !== null) ? intval($opts['ofSeat']) : null,
        ]);
        if (empty($targets)) return;
        $cont = (string)($opts['continuation'] ?? '');
        static $amountTaking = ['GIVE_EXPERIENCE' => 1, 'GIVE_ADVANTAGE' => 1, 'HEAL_TARGET' => 1, 'DEAL_TARGET' => 1, 'DEAL_UNIT_DAMAGE' => 1, 'DEAL_BASE_DAMAGE' => 1];
        if (strpos($cont, '|') === false && isset($amountTaking[$cont])) $cont .= "|{$amount}";
        $prompt   = $opts['prompt']   ?? 'Choose_a_target';
        $question = $opts['question'] ?? $prompt;
        if ($may) SWUQueueMayChooseTarget(intval($player), $targets, $question, $prompt, $cont, $block);
        else      SWUQueueChooseTarget(intval($player), $targets, $prompt, $cont, $block);
    }
}

// Choose one BASE (my/their/any) and apply an effect — the base-only sibling of SWUOfferUnitTarget,
// for effects whose target set is just the base slots (DEAL_BASE_DAMAGE, base heals). Continuation
// applies to the chosen base mzID (the universal handlers route base-vs-unit off the mzID string).
//   'continuation' string   REQUIRED (e.g. 'DEAL_BASE_DAMAGE', 'HEAL_TARGET')
//   'baseSide'     'my'|'their'|'any'   default 'any' (both bases)
//   'amount'       int      default 1 — appended '|N' for amount-taking handlers (DEAL_BASE_DAMAGE/HEAL_TARGET/DEAL_TARGET)
//   'may'          bool     default false
//   'block'        int      default 1
//   'prompt'       string   choose tooltip; default 'Choose_a_base'
//   'question'     string   may yes/no tooltip; default = prompt
// EVERY base a $player-perspective effect may target, as mzIDs.
// ⚠ THE reason this exists: ~30 sites across the codebase spelled the pool as the literal
// ['myBase-0','theirBase-0']. That is exactly two bases, so in Twin Suns a card reading "deal damage
// to A BASE" could only ever reach your own base and the ONE opponent currently in view — reported
// live on LAW_058 Honor-Bound Partisan ("didn't allow to choose other bases than P1").
// ZoneSearch already solves this: in an N-player game "their<Zone>" fans out to one seat-specific
// p{n}<Zone> per LIVE opponent (GameLogic's Twin Suns Phase 3 union), and in a 2-player game it
// returns the single theirBase-0 — so routing through it is byte-identical for premier.
// $side: 'any' (default) | 'my' | 'their'.
// Live opponents of $player holding at least one card — THE eligibility list for every "an opponent
// discards a card" effect. Two jobs, and the second is the one people forget:
//   * it stops an "an opponent" pick from fizzling against an empty hand, and
//   * with exactly one such opponent SWUQueueChooseOpponent auto-resolves, so no prompt is shown —
//     which is what keeps 2-player (and an early, empty Twin Suns board) silent.
// Gate on this being NON-EMPTY before charging any cost: with none eligible the picker queues nothing.
if (!function_exists('SWUOpponentsWithCards')) {
    function SWUOpponentsWithCards(int $player): array {
        $out = [];
        foreach (OpponentsOf(intval($player)) as $opp) {
            foreach (GetHand($opp) as $c) {
                if (empty($c->removed)) { $out[] = $opp; break; }
            }
        }
        return $out;
    }
}

if (!function_exists('SWUAllBaseMzIDs')) {
    function SWUAllBaseMzIDs(int $player, string $side = 'any'): array {
        global $playerID; $playerID = intval($player);
        $out = [];
        if ($side === 'my' || $side === 'any') $out[] = 'myBase-0';
        // ⚠ 'any' means EVERY LIVE SEAT'S base, so it cannot be 'my' + 'their': "their" is ZoneSearch's
        // OPPONENT fan-out and deliberately excludes a TEAMMATE. That is the identical hole SWUAllUnits()
        // already documents ("once 'their' excludes a teammate, 'my' + 'their' no longer covers the
        // table") — it was fixed for units and left open for bases, silently deleting the partner's base
        // from ~35 unqualified "a base" offers. SWUTeammatesOf returns [] outside a team game, so
        // 2-player and free-for-all Twin Suns stay byte-identical.
        if ($side === 'any') {
            foreach (SWUTeammatesOf(intval($player)) as $mate) $out[] = "p{$mate}Base-0";
        }
        if ($side === 'their' || $side === 'any') {
            foreach (ZoneSearch('theirBase', null) as $mz) $out[] = $mz;
        }
        return $out;
    }
}

if (!function_exists('SWUOfferBaseTarget')) {
    function SWUOfferBaseTarget(int $player, array $opts = []): void {
        global $playerID; $playerID = intval($player);
        $targets = SWUAllBaseMzIDs(intval($player), $opts['baseSide'] ?? 'any');
        if (empty($targets)) return;
        $amount = max(1, intval($opts['amount'] ?? 1));
        $cont   = (string)($opts['continuation'] ?? '');
        static $amountTaking = ['DEAL_BASE_DAMAGE' => 1, 'HEAL_TARGET' => 1, 'DEAL_TARGET' => 1];
        if (strpos($cont, '|') === false && isset($amountTaking[$cont])) $cont .= "|{$amount}";
        $prompt   = $opts['prompt']   ?? 'Choose_a_base';
        $question = $opts['question'] ?? $prompt;
        $block    = intval($opts['block'] ?? 1);
        if (!empty($opts['may'])) SWUQueueMayChooseTarget(intval($player), $targets, $question, $prompt, $cont, $block);
        else                      SWUQueueChooseTarget(intval($player), $targets, $prompt, $cont, $block);
    }
}

// Choose one card from a hand (opponent's or your own, optionally filtered) and discard it — the
// hand-target sibling of SWUOfferUnitTarget. For 'opp' it routes through SWULookAtOpponentHand (which
// also reveals the opponent's hand to the caster, per the "look at an opponent's hand" text). No-ops
// when no card matches. The universal DISCARD_FROM_OPP_HAND / DISCARD_FROM_OWN_HAND handlers do the
// discard on the chosen mzID.
//   'from'   'opp' | 'own'   default 'opp'
//   'filter' callable(string $cardID): bool   optional (unit-only, event-only, aspect-match, …)
//   'may'    bool   default false
//   'block'  int    default 1
//   'prompt' string ;  'question' string (may yes/no; default = prompt)
if (!function_exists('SWUOfferDiscard')) {
    function SWUOfferDiscard(int $player, array $opts = []): void {
        global $playerID; $playerID = intval($player);
        $from   = $opts['from'] ?? 'opp';
        $filter = $opts['filter'] ?? null;
        $oppSeat = isset($opts['opp']) ? intval($opts['opp']) : null;
        if ($from === 'own') {
            $targets = [];
            foreach (ZoneSearch('myHand', null) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if ($filter !== null && !$filter($o->CardID ?? '')) continue;
                $targets[] = $mz;
            }
            $cont    = 'DISCARD_FROM_OWN_HAND|' . intval($player);
            $default = "Discard_a_card_from_your_hand";
        } else {
            // 'opp' (Twin Suns): WHICH opponent's hand. Null = the legacy single opponent, so every
            // pre-existing caller is byte-identical; a converted card queues SWUQueueChooseOpponent
            // first and passes the picked seat in.
            $targets = SWULookAtOpponentHand(intval($player), $filter, $oppSeat);
            $cont    = 'DISCARD_FROM_OPP_HAND';
            $default = "Discard_a_card_from_the_opponent's_hand";
        }
        // Viper-Probe presentation: when the discard auto-resolves (0 or 1 legal target → no MZCHOOSE, so
        // the player never sees the hand), explicitly show it — queued AFTER the discard (as the original
        // sites did) and fired even on 0 targets. Only meaningful for 'opp'.
        //
        // ⚠ ON BY DEFAULT for 'opp' since 2026-08-18. Every from=opp caller in the repo is a printed
        // "LOOK AT an opponent's hand and discard …" effect, and the look is an entitlement in its own
        // right — it is not contingent on the discard finding a target. Three of the seven callers
        // (Spark of Rebellion, Unmasking the Conspiracy, Charged with Espionage, Hold For Questioning)
        // never passed the flag, so on a 1-card hand — or, for the FILTERED ones, on any board with ≤1
        // matching card — the player was told to discard something they were never shown.
        // Opt out with 'showHandIfAuto' => false if a future card discards from a hand it may not look at.
        $showHand = (($opts['showHandIfAuto'] ?? true) && count($targets) <= 1);
        if (!empty($targets)) {
            $prompt   = $opts['prompt']   ?? $default;
            $question = $opts['question'] ?? $prompt;
            $block    = intval($opts['block'] ?? 1);
            if (!empty($opts['may'])) SWUQueueMayChooseTarget(intval($player), $targets, $question, $prompt, $cont, $block);
            else                      SWUQueueChooseTarget(intval($player), $targets, $prompt, $cont, $block);
        }
        // Same seat as the look above — otherwise the popup shows a different hand than the one the
        // player is picking from.
        if ($showHand) SWUQueueShowOpponentHand(intval($player), $oppSeat ?? null);
    }
}

// ─── SWUNestedPlay — play a card as part of ANOTHER card's resolution ────────────────────────────────
// "Play a unit from your discard pile", "play a card from your hand" and friends, WITHOUT handing the
// acting player a free extra action.
//
// ActivateCard finalises the action itself, and so does the outer effect (an event's FINISH_PLAY_CARD,
// a unit's entry-trigger flush). Two finalisations = two turn swaps = a second action off one play.
// There are TWO after-actions to neutralise, and they need DIFFERENT guards:
//
//   1. the IMMEDIATE one — ActivateCard's own. The JTL_089#1 $gTurnPlayer/PASS save-restore.
//   2. the DEFERRED one — if the played card arms an ENTRY TRIGGER, a SWU_TRIGGER_RESUME is queued and
//      finalises LATER, after the restore has already run, so leg 1 cannot reach it. HMW_171 Trap Field
//      makes this reachable for essentially every "play a unit" card: it reacts to ANY non-leader ground
//      unit entering play, either player's, and is owned by the base owner. Found as bug #997.
//
// The leg-2 flag is set ONLY when a resume was actually queued. Setting it unconditionally leaks — with
// no trigger nobody consumes it and it would suppress the finalisation of some LATER action — and
// clearing it here cannot work, because the resume runs in a later drain, long after this returns.
//
// ⚠ DO NOT USE where the outer context DELEGATES its whole action to the play. A base Epic Action or a
// leader Action with no trailing SWU_AFTER_ACTION (SOR_022 Energy Conversion Lab, JTL_003 Lando,
// JTL_005 Piett, JTL_011 Vonreg, SOR_003 Chewbacca) relies on ActivateCard's after-action being the
// action's ONLY one — suppressing it there strands the turn instead of fixing anything.
//
// Args mirror ActivateCard's leading parameters so migrating a call site is a rename.
if (!function_exists('SWUNestedPlay')) {
    function SWUNestedPlay(int $player, string $mzID, bool $ignoreCost = false, int $discount = 0, int $prepaid = 0): void {

        // The play runs as a NESTED frame, so its own after-action cannot end the outer action, and
        // the action-close stamp blocks the DEFERRED leg (a queued SWU_TRIGGER_RESUME finalising in a
        // later request). Both legs are the gate's job now — see SWUSim/docs/action-close-ownership.md.
        SWUWithNestedActionFrame(fn() => ActivateCard($player, $mzID, $ignoreCost, $discount, $prepaid));
    }
}

// How many SWU_TRIGGER_RESUME entries are queued right now? Scans EVERY live seat, because the trigger that armed
// it may belong to an OPPONENT — Trap Field is owned by the base owner, i.e. the non-active player in
// the reported case.
if (!function_exists('_SWUNestedPlayResumeCount')) {
    function _SWUNestedPlayResumeCount(): int {
        $n = 0;
        foreach (GetLiveSeatsArray() as $s) {
            foreach (GetDecisionQueue($s) as $entry) {
                if (strpos(strval($entry->Param ?? ''), 'SWU_TRIGGER_RESUME') === 0) $n++;
            }
        }
        return $n;
    }
}
