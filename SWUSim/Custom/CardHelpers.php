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
// That shipped in three HMW cards (HMW_171 Trap Field, HMW_158 Ezra Bridger, HMW_060 Rampart's
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
// historical inline merges: SWUAllUnits() == array_merge(myGround, mySpace,
// theirGround, theirSpace); SWUAllUnits('my') == array_merge(myGround, mySpace);
// SWUAllUnits(null,'Ground') == array_merge(myGround, theirGround); etc.
if (!function_exists('SWUAllUnits')) {
    function SWUAllUnits(?string $of = null, ?string $arena = null): array {
        $sides  = $of    !== null ? [$of]    : ['my', 'their'];
        $arenas = $arena !== null ? [$arena] : [GroundArena, SpaceArena]; // "Ground","Space"
        $out = [];
        foreach ($sides as $s) {
            foreach ($arenas as $a) {
                $out = array_merge($out, ZoneSearch($s . $a . 'Arena', AnyUnitFilter));
            }
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
        $sideArg = ($side === 'any') ? null : $side;   // SWUAllUnits: 'my'|'their'|null
        $out = [];
        foreach (SWUAllUnits($sideArg, $arena) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
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
            $baseSide = $opts['baseSide'] ?? 'any';
            if ($baseSide === 'my'    || $baseSide === 'any') $out[] = 'myBase-0';
            if ($baseSide === 'their' || $baseSide === 'any') $out[] = 'theirBase-0';
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
if (!function_exists('SWUOfferBaseTarget')) {
    function SWUOfferBaseTarget(int $player, array $opts = []): void {
        global $playerID; $playerID = intval($player);
        $baseSide = $opts['baseSide'] ?? 'any';
        $targets = [];
        if ($baseSide === 'my'    || $baseSide === 'any') $targets[] = 'myBase-0';
        if ($baseSide === 'their' || $baseSide === 'any') $targets[] = 'theirBase-0';
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
            $targets = ($filter !== null)
                ? SWULookAtOpponentHand(intval($player), $filter)
                : SWULookAtOpponentHand(intval($player));
            $cont    = 'DISCARD_FROM_OPP_HAND';
            $default = "Discard_a_card_from_the_opponent's_hand";
        }
        // Viper-Probe presentation: when the discard auto-resolves (0 or 1 legal target → no MZCHOOSE, so
        // the player never sees the hand), explicitly show it — queued AFTER the discard (as the original
        // sites did) and fired even on 0 targets. Only meaningful for 'opp'.
        $showHand = (!empty($opts['showHandIfAuto']) && count($targets) <= 1);
        if (!empty($targets)) {
            $prompt   = $opts['prompt']   ?? $default;
            $question = $opts['question'] ?? $prompt;
            $block    = intval($opts['block'] ?? 1);
            if (!empty($opts['may'])) SWUQueueMayChooseTarget(intval($player), $targets, $question, $prompt, $cont, $block);
            else                      SWUQueueChooseTarget(intval($player), $targets, $prompt, $cont, $block);
        }
        if ($showHand) SWUQueueShowOpponentHand(intval($player));
    }
}
