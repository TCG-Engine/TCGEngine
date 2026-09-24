<?php
require_once __DIR__ . '/BotFeatures.php';
require_once __DIR__ . '/BotFlavours.php';
// Resourcing engine v0 for the heuristic bots — the RL bots spec's settled rules: the floor, the per-style
// card choice (Control's revised 2026-09-13: keep a castable hand plus one bomb), the Plot budget.
// Phase 1b part 2 (owner rulings 2026-09-14): key cards stay out of resources, and each style has a resource stop —
// both GUIDES that training learns past; Normal's card choice, stops and mulligans are ultimately learned.
// Keep value: LOWER = resource it first.

// Keep bonus per point of board-dependent power surplus (proposal 'ctxpower', SWUBotContextSurplus).
const SWU_BOT_CTXPOWER_KEEP = 2.0;
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("The resourcing engine").

// The resource count at which one leader can deploy right now, or 0 when it has no resource gate left to
// reach. MIRRORS the deploy gate in SWUDeployLeader() / SWUComputeActionsData() (Custom/GameLogic.php) —
// a new non-standard deploy added there belongs here too.
function _SWUBotLeaderThreshold(int $seat, $leader): int {
    $cid = strval($leader->CardID ?? '');
    if (!empty($leader->Deployed) && strval($leader->Deployed) !== 'false') return 0;
    $epicUsed = !empty($leader->EpicActionUsed) && strval($leader->EpicActionUsed) !== 'false';
    switch ($cid) {
        case 'JTL_014': return 6;   // Admiral Trench: repeatable Action, control 6+ resources (no Epic)
        case 'SEC_008': return 4;   // Bail Organa: repeatable Action, control 4+ resources (no Epic)
        case 'ASH_018': return 0;   // Grogu: deploys only from his trigger — no resource gate
        case 'TWI_017': return 0;   // "Flipatine": both faces are flip Actions — never deploys
    }
    if ($epicUsed) return 0;        // the once-per-game Epic Action is spent (a defeated leader stays home)
    $printed = intval(CardCost($cid));
    if ($cid === 'LOF_007') {       // Avar Kriss: resources + Force uses this phase ≥ 9
        return max(0, $printed - intval(GlobalEffectCount($seat, 'SWU_FORCE_USED_THIS_PHASE')));
    }
    if ($cid === 'ASH_010') {       // Bo-Katan: resources + friendly Mandalorian units ≥ 10
        $mandos = 0;
        foreach (GetUnitsInPlay($seat) as $u) { if (empty($u->removed) && TraitContains($u, 'Mandalorian')) $mandos++; }
        return max(0, $printed - $mandos);
    }
    return $printed;                // standard Epic threshold = printed cost (LAW_013 Chewbacca: a real cost of 4)
}

// Twin Suns seats hold two leaders: the floor holds until BOTH can deploy (the larger threshold).
function SWUBotLeaderDeployThreshold(int $seat): int {
    $t = 0;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $t = max($t, _SWUBotLeaderThreshold($seat, $l));
    }
    return $t;
}

// Does this card have Sentinel as a PRINTED KEYWORD? Read from card text, not HasKeyword_Sentinel(): that
// reads an in-play object's Controller and FATALS on a hand object (memory `haskeyword-fatals-on-hand-objects`),
// and the resourcing decision is made entirely on cards in hand.
//
// Anchored to the start of a line because the keyword is printed on its own line, while its own reminder text
// ("Units in this arena can't attack your NON-SENTINEL units…") and unrelated references ("defeat a Sentinel
// unit") appear mid-sentence. Measured over the full card pool: the anchor yields 110 printed Sentinels and
// rejects 143 mere references. A CONDITIONAL grant ("while this unit is undamaged, it gains Sentinel",
// SOR_048) is deliberately NOT counted — the card in hand is not yet a Sentinel.
function _SWUBotHasPrintedSentinel(string $cardID): bool {
    return (bool)preg_match('/(^|\n)\s*Sentinel\b/i', strval(CardText($cardID)));
}

// A second copy of a UNIQUE card already in hand is redundant — only one can ever be in play (CR uniqueness),
// so the duplicate is safe to resource. Keyed on hand INDEX so exactly one copy keeps the bonus: the earliest
// index is the keeper and any later copy is redundant.
function _SWUBotRedundantUniqueInHand(int $seat, string $cardID, int $index): bool {
    if (!CardUnique($cardID)) return false;
    foreach (GetHand($seat) as $j => $o) {
        if ($j >= $index || $o === null || !empty($o->removed)) continue;
        if (strval($o->CardID ?? '') === $cardID) return true;   // an earlier copy is already the keeper
    }
    return false;
}

// PROPOSAL 'mgcost' (default OFF, "@try-mgcost") — THE COST THIS SEAT ACTUALLY PAYS.
// Owner ruling 2026-09-23 (6): "off-aspect cards cost +2 and must be judged at that cost. Chimaera technically
// costs 9 for Luke (ASH) DV. it would be one of the first to go in an opening hand."
// The whole resourcing engine reads PRINTED cost — CardCost() — so an off-aspect card is ranked as if the seat
// could pay its printed price. Chimaera is treated as a 7-drop the deck can cast on 7 resources when it needs 9,
// which corrupts both the "castable soon" horizon and the bomb pick. This is not a midrange bug: it reaches every
// deck with an off-aspect card, which is most of them. Hence a proposal with its own safety check, not a fix.
// The two sites NOT converted are the Plot budget (SWUBotChooseResourceCards, GetResources loop) and the leader
// deploy thresholds above: both are printed-cost rules in the engine they mirror, and changing them here would
// desync the bot from SWUDeployLeader().
function _SWUBotSeatCost(int $seat, string $cid): int {
    $c = intval(CardCost($cid));
    if (!SWUBotProposalOn('mgcost') || !function_exists('SWUAspectPenalty')) return $c;
    return $c + intval(SWUAspectPenalty($seat, $cid));
}

// Owner, 2026-09-23 (ruling 2): "a 2 power sentinel is almost pointless. but a 3+ power sentinel is good power to
// start the race against control." Proposals 'mgsentinel' (resourcing) and its play half in BotFallback.php.
const SWU_BOT_MG_SENTINEL_POWER = 3;

// A body worth keeping on curve (proposal 'mgkeep'): a unit whose power + HP is at least twice its cost — the
// shape of the owner's example, "a second Koska Reeves (4 cost, 4/4) was the wrong pick to resource".
function _SWUBotIsEfficientBody(int $seat, string $cid): bool {
    if (strval(CardType($cid)) !== 'Unit') return false;
    $cost = _SWUBotSeatCost($seat, $cid);
    return $cost > 0 && intval(CardPower($cid)) + intval(CardHp($cid)) >= 2 * $cost;
}

function SWUBotResourceFloorApplies(int $seat): bool {
    $t = SWUBotLeaderDeployThreshold($seat);
    return $t > 0 && SWUResourceCount($seat) < $t;
}

// The $n hand mzIDs ("myHand-i") to resource, lowest keep value first; ties go to the lowest hand index.
// A Plot card is resourced first while the Plot cards in resources plus it still fit the deploy threshold
// — the leader's deploy then Plays them out (spec: the Plot budget; the owner's Palpatine + Jar Jar = 5).
// HasKeyword_Plot reads a facedown RESOURCE object correctly (it keys on CardID; checked by the test).
function SWUBotChooseResourceCards(array $ctx, int $n): array {
    $seat = intval($ctx['seat']);
    $budget = SWUBotLeaderDeployThreshold($seat);            // 0 once deployed → no Plot priority
    $plotInResources = 0;
    foreach (GetResources($seat) as $r) {
        if ($r === null || !empty($r->removed)) continue;
        if (HasKeyword_Plot($r)) $plotInResources += intval(CardCost(strval($r->CardID ?? '')));
    }
    // Control keeps a hand it can CAST: every card castable within ~2 regroups of the resources it will have
    // after this pick, plus ONE copy of its biggest card (the bomb it builds toward). Everything else goes
    // to resources, the card furthest from castable first. Owner ruling 2026-09-13, replacing v0's "resource
    // the cheapest": on real control lists (fixture krennic_splash) that resourced every cheap answer and
    // left six 8–11-cost cards on 6 resources in round 5, so the deck cast nothing for three rounds.
    $soon = SWUResourceCount($seat) + $n + 2;
    $bombIndex = null; $bombCost = -1;
    foreach (GetHand($seat) as $i => $c) {
        if ($c === null || !empty($c->removed)) continue;
        $cost = _SWUBotSeatCost($seat, strval($c->CardID ?? ''));
        if ($cost > $bombCost) { $bombCost = $cost; $bombIndex = $i; }
    }
    // Feature 'wipekeep' (owner ruling 2026-09-16) refines that rule for WIPES — see _SWUBotProtectedWipe.
    $rank = SWUBotStyleRank(strval($ctx['style']));
    $wipeRule = $rank >= 3 && SWUBotFeatureOn('wipekeep');
    $early = SWUResourceCount($seat) <= 5;
    $protectedWipe = ($wipeRule && $early) ? _SWUBotProtectedWipe($seat, strval($ctx['style'])) : null;
    $stabilized = $wipeRule && !$early && _SWUBotIsStabilized($seat);
    // Proposal 'mgkeep': the earliest copy of each card in hand. Every later copy is the spare duplicate.
    $firstIndexOf = [];
    foreach (GetHand($seat) as $i => $c) {
        if ($c === null || !empty($c->removed)) continue;
        $firstIndexOf[strval($c->CardID ?? '')] ??= $i;
    }
    $ranked = [];
    foreach (GetHand($seat) as $i => $c) {
        if ($c === null || !empty($c->removed)) continue;
        $cid = strval($c->CardID ?? '');
        $cost = _SWUBotSeatCost($seat, $cid);
        // FEATURE 'mgbomb' (group p12, SHIPPED 2026-09-24, +3.14pp for midrange at p=0.0000 against both jitter
        // nulls): the owner's castable-soon + one-bomb rule reaches MIDRANGE too. Only the KEEP VALUE moves — the
        // `resourcing3` tier sort below stays gated at rank >= 3, so this changed exactly one thing.
        $castableRule = $rank >= 3 || ($rank === 2 && SWUBotFeatureOn('mgbomb'));
        $keep = $castableRule
            ? ($i === $bombIndex ? 200.0 : ($cost <= $soon ? 100.0 + $cost : (float)($soon - $cost)))
            : (float)-$cost;   // the aggro wing resources its most expensive; midrange: FALLBACK default, not a rule
        // Key cards (answers, burn, the flavour's key cards) go to resources after filler (feature 'keep'). Control
        // keeps its owner rule first — castable soon + one bomb (2026-09-13) — so its bonus (50) lifts a key card
        // only above FAR filler, never above a card it can cast soon; the other styles keep key cards over all filler.
        // PROPOSAL 'keepequal' (default OFF, owner-approved for data 2026-09-20): give control the same +150 every
        // other archetype gets. Today control's answers rank BELOW any castable card, so it resources its own
        // removal while keeping filler. ⚠ This contradicts the owner's 2026-09-13 "castable soon + one bomb" ruling.
        if (SWUBotFeatureOn('keep') && SWUBotIsKeyCard($seat, $cid)) {
            $keep += ($rank >= 3 && !SWUBotProposalOn('keepequal')) ? 50.0 : 150.0;
        }
        // PROPOSAL 'ctxpower' (default OFF): a card whose power depends on the board, judged ON the board.
        // The list is sorted ASCENDING and the first entries are resourced, so a positive bonus pulls a card
        // AWAY from the resource pick — it can only ever rescue one, never bury one.
        // 2.0 a point against the aggro wing's `-$cost` means a 4-cost card needs a surplus of 2 before it is
        // safe and 3 to clear the hand outright, which is the owner's line: "a 4/3/3 would be weak and easy to
        // resource if i have no board. but when my board has 3+ units, this is a big unit" (2026-09-24).
        // Proportional, not a threshold — a 9-power Clone Combat Squadron must outrank a 6-power one.
        $keep += SWU_BOT_CTXPOWER_KEEP * SWUBotContextSurplus($seat, $cid);
        // PROPOSAL 'sentinelkeep' (default OFF, "@try-sentinelkeep"). Owner ruling 2026-09-18: "Sentinels in
        // general are good to keep… unless you have two of the same unique unit Sentinel. then it should be safe
        // to resource one." A Sentinel is how control mitigates early damage, and the deficit is a SURVIVAL
        // problem (memory `control-loses-by-not-reaching-round-8`). Control wing only, so the arm is one-sided.
        // +50 matches the key-card bonus deliberately — a blocker is an answer — rather than inventing a new
        // magnitude; tune it only after the first measurement.
        // PROPOSAL 'holdanswers' (default OFF). Owner ruling 2026-09-19 (Q2-D: Lando vs a space-heavy Chewbacca board):
        // "since it looks like they are going space heavy, hold both the HSD and the Chimaera." Against a space-heavy
        // board (2+ enemy space units, at least as many as on the ground), a card that answers space — a wipe of
        // space units, or unrestricted removal of an enemy unit — is kept above every castable-soon card.
        if (SWUBotProposalOn('holdanswers') && $rank >= 3 && _SWUBotOpponentSpaceHeavy($seat) && _SWUBotAnswersSpace($cid)) $keep += 150.0;
        // 'sentinelkeepall' (default OFF) lifts the control-wing gate on the shipped p4 Sentinel keep.
        if (SWUBotFeatureOn('sentinelkeep') && ($rank >= 3 || SWUBotProposalOn('sentinelkeepall')) && _SWUBotHasPrintedSentinel($cid)
            && !_SWUBotRedundantUniqueInHand($seat, $cid, $i)) {
            $keep += 50.0;
        }
        // ── THE MIDRANGE RESOURCING RULES (owner, 2026-09-23). Rank 2 has never had any: its keep value is the
        // aggro wing's FALLBACK, -$cost, which says only "resource the most expensive card". Both arms below are
        // midrange-only and default OFF, so no shipped bot moves.
        // PROPOSAL 'mgkeep' (ruling 6): "resourcing ONE duplicate is fine… but do not resource an efficient
        // on-curve body — a second Koska Reeves (4 cost, 4/4) was the wrong pick." The two clauses are ordered:
        // an efficient body is kept even when it IS the duplicate, which is exactly the owner's example.
        // MEASURED 2026-09-23 (`2026-09-23_midrange_arms_result.md`): +8.0 vs Krennic (BH q=0.027), +1.8 vs Ahsoka,
        // the only one of the seven midrange arms to clear correction. THE SPLIT, per the ship rules — a two-clause
        // arm is never shipped whole:
        //   'mgkeepbody' — the efficient-body keep alone.
        //   'mgkeepdup'  — the spare-duplicate resource alone. Note it carries NO body exception: isolating the
        //                  clause means a duplicate efficient body IS resourced under this arm, which is the
        //                  behaviour the ordering in 'mgkeep' exists to prevent. That contrast is the measurement.
        $mgkBody = SWUBotFeatureOn('mgkeep') || SWUBotProposalOn('mgkeepbody');
        $mgkDup  = SWUBotFeatureOn('mgkeep') || SWUBotProposalOn('mgkeepdup');
        if ($rank === 2 && ($mgkBody || $mgkDup)) {
            if ($mgkBody && _SWUBotIsEfficientBody($seat, $cid)) $keep += 100.0;
            elseif ($mgkDup && ($i !== ($firstIndexOf[$cid] ?? $i))) $keep -= 50.0;   // a later copy: the spare duplicate
        }
        // PROPOSAL 'mgsentinel' — the RESOURCING half (ruling 2): "keep them against AGGRO. Against control it
        // depends on the Sentinel — a 2 power sentinel is almost pointless, but a 3+ power sentinel is good power
        // to start the race against control." The shipped 'sentinelkeep' (p4) is control-wing only and flat.
        if (SWUBotProposalOn('mgsentinel') && $rank === 2 && _SWUBotHasPrintedSentinel($cid)
            && !_SWUBotRedundantUniqueInHand($seat, $cid, $i)
            && (SWUBotOpponentIsAggroLeader($seat) || intval(CardPower($cid)) >= SWU_BOT_MG_SENTINEL_POWER)) {
            $keep += 100.0;
        }
        // PROPOSAL 'earlyremoval' — the RESOURCING half. Owner, Q13: "Crushing Blow only works on 2-cost non-leader
        // units. so late game, it's an auto resource." From round 6 (7 resources — past the owner's "5R turn") a
        // RESTRICTED removal event is resourced FIRST: -100 sinks it below every other card in hand.
        if ((SWUBotProposalOn('earlyremoval') || SWUBotProposalOn('restrictedearly')) && $rank >= 3 && intval(GetTurnNumber()) >= 6
            && (SWUBotRemovalClass($cid)[0] ?? '') === 'restricted') {
            $keep = -100.0;
        }
        // 2R–5R: the one relevant wipe is held like the bomb — just below it, above every castable card. ONE copy:
        // a second wipe falls back to the ordinary rule.
        if ($protectedWipe !== null && $cid === $protectedWipe && $i !== $bombIndex) { $keep = 190.0; $protectedWipe = null; }
        // 6R+: once I have stabilized, a wipe that would cost me as much as it costs them is no longer needed — it
        // may go. Self-damage alone is not enough: "if i have one or two weenie space units and they have a swarm,
        // then i would still keep it and use it" (owner, 2026-09-16), so a trade clearly in my favour stays.
        if ($stabilized && in_array('wipe', SWUBotCardTags($cid), true)) {
            [$mine, $theirs] = _SWUBotWipeLosses($seat, $cid);
            if ($mine > 0.0 && $mine >= $theirs) $keep = -1.0;
        }
        // `$budget > 0` states the intent; the sum test alone already refuses every Plot card (all cost ≥ 1).
        if ($budget > 0 && HasKeyword_Plot($c) && $plotInResources + $cost <= $budget) $keep = -1000.0 + $cost;
        $ranked[] = [$keep, $i];
    }
    // PROPOSAL 'resourcing2' — the owner's resourcing rulings as ORDERED TIERS for the control wing. Tiers come first,
    // the keep value above only breaks ties inside a tier.
    // FEATURE 'resourcing3' (group p8) applies the tiers only vs an AGGRO-LEADER opponent: every ruling behind them was
    // given for a position vs Vader, and vs a slow matchup the pre-p8 resourcer (which keeps the bombs) stands.
    // '@try-resourcing2' still reproduces the measured forerunner (it takes precedence and switches the v3 rules off).
    $r2 = SWUBotProposalOn('resourcing2');
    if ($rank >= 3 && ($r2 || (SWUBotFeatureOn('resourcing3') && SWUBotOpponentIsAggroLeader($seat)))) {
        $tiers = _SWUBotResourcing2Tiers($ctx, $seat, !$r2);
        usort($ranked, fn($a, $b) => ($tiers[$a[1]][0] <=> $tiers[$b[1]][0]) ?: ($tiers[$a[1]][1] <=> $tiers[$b[1]][1])
                                     ?: ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]));
        return array_map(fn($r) => 'myHand-' . $r[1], array_slice($ranked, 0, $n));
    }
    usort($ranked, fn($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);
    return array_map(fn($r) => 'myHand-' . $r[1], array_slice($ranked, 0, $n));
}

// ── Feature 'wipekeep' — control keeps the wipe it will need ─────────────────────────────────────
// Owner ruling 2026-09-16: "on the 2R turn to the 5R turn, keep the cheapest wipe that is relevant to the opponent
// (HSD for space, SRI for mixed or ground aggro). for 6R up, gauge whether it's needed. if you stabilized enough to
// not need it, then resource it if it will also hurt your own board. however, if it only affects their arena for
// example, HSD, then keep it."
// Found by the 2026-09-16 fidelity baseline: control ran 11.6 points cold, and a probe showed Lando's resourcer
// throwing Hyperspace Disaster away on round 2 — Bo-Katan held the one bomb slot and HSD (7) sat just past the
// 6-resource horizon, so the card control most needs against space aggro was the first one resourced.

// The effect clause of a wipe — its FIRST sentence. Riders must not leak into the reading: Single Reactor Ignition
// is "Defeat all units. For each enemy unit defeated this way, deal 1 damage…", and a whole-text search for "enemy
// unit" would call that one-sided when it defeats every unit on the board, mine included.
function _SWUBotWipeClause(string $cid): string {
    $t = strtolower(str_replace("\n", ' ', strval(CardText($cid))));
    $dot = strpos($t, '.');
    return $dot === false ? $t : substr($t, 0, $dot);
}

// The arenas a wipe defeats: "space unit(s)" → Space, "ground unit(s)" → Ground, otherwise both.
function _SWUBotWipeArenas(string $cid): array {
    $clause = _SWUBotWipeClause($cid);
    if (str_contains($clause, 'space unit')) return ['Space'];
    if (str_contains($clause, 'ground unit')) return ['Ground'];
    return ['Space', 'Ground'];
}

// Does the wipe spare my units BY ITS WORDING? Either it names enemies, or I choose the victims ("any number of").
function _SWUBotWipeIsOneSided(string $cid): bool {
    $clause = _SWUBotWipeClause($cid);
    return str_contains($clause, 'enemy') || str_contains($clause, 'any number of');
}

function _SWUBotUnitArenas(int $seat): array {
    return array_values(array_unique(array_map(fn($v) => strval($v['arena']), SWUBotUnits($seat))));
}

// What this wipe would cost each side if it resolved now: [my unit value lost, their unit value lost], by
// SWUBotUnitValue over the arenas it covers. Judged against the actual board, not a static label: Hyperspace
// Disaster reads "Defeat all space units" — both sides — and costs me nothing exactly when I control no space unit,
// the usual state of these ground-based control decks and what the owner's example assumes. A one-sided wipe
// costs me nothing by its wording.
function _SWUBotWipeLosses(int $seat, string $cid): array {
    $arenas = _SWUBotWipeArenas($cid);
    $sum = function (int $s) use ($arenas) {
        $v = 0.0;
        foreach (SWUBotUnits($s) as $u) { if (in_array(strval($u['arena']), $arenas, true)) $v += SWUBotUnitValue($u); }
        return $v;
    };
    return [_SWUBotWipeIsOneSided($cid) ? 0.0 : $sum($seat), $sum(SWUBotOpponent($seat))];
}

// Relevant = it covers EVERY arena the opponent is using. So Hyperspace Disaster answers a pure space board, but
// against a mixed board it leaves the ground half standing and Single Reactor Ignition is the answer — the owner's
// "HSD for space, SRI for mixed or ground aggro". With no enemy unit yet the need is unknown, and every wipe counts
// as relevant: throwing an answer away before seeing the threat is the mistake being fixed.
function _SWUBotWipeIsRelevant(int $seat, string $cid): bool {
    $theirs = _SWUBotUnitArenas(SWUBotOpponent($seat));
    return empty($theirs) || empty(array_diff($theirs, _SWUBotWipeArenas($cid)));
}

// The cheapest relevant wipe in hand, or null. Control only — the ruling is about control's resourcing.
function _SWUBotProtectedWipe(int $seat, string $style): ?string {
    if (SWUBotStyleRank($style) < 3) return null;   // the control wing only — the ruling is about control's resourcing
    $best = null; $bestCost = PHP_INT_MAX;
    foreach (GetHand($seat) as $c) {
        if ($c === null || !empty($c->removed)) continue;
        $cid = strval($c->CardID ?? '');
        if (!in_array('wipe', SWUBotCardTags($cid), true) || !_SWUBotWipeIsRelevant($seat, $cid)) continue;
        $cost = _SWUBotSeatCost($seat, $cid);
        if ($cost < $bestCost) { $best = $cid; $bestCost = $cost; }
    }
    return $best;
}

// "Stabilized enough to not need it": the opponent is at least 3 rounds from killing me — the same threshold
// rule 5 uses for a wipe that stabilises (SWUBotStabilises).
function _SWUBotIsStabilized(int $seat): bool {
    return SWUBotClock(SWUBotOpponent($seat), $seat) >= 3;
}

// Rule 10 — the resourcing floor (CR 5.5.1c): resource every regroup until the leader can deploy. A fixed
// CONSTRAINT, not an answer (owner ruling 2026-09-13): it removes PASS from the
// regroup's "Resource up to 1 card" prompt and leaves WHICH card to the fallback's keep values in live play,
// and to the learned layer in training. The stack applies it next to the style filter (BotHeuristic.php).
function SWUBotResourceFloorFilter(array $ctx): array {
    $acts = $ctx['actions'];
    if (($ctx['tooltip'] ?? '') !== 'Resource_up_to_1_card' || !SWUBotResourceFloorApplies(intval($ctx['seat']))) return $acts;
    $cards = array_values(array_filter($acts, fn($a) => strval($a['cardID'] ?? '') !== 'PASS'));
    return empty($cards) ? $acts : $cards;
}

// Rule 11 as a GUIDE (owner ruling 2026-09-14): "aggro stops at 6 or 7. midrange/normal stops at 8 or 9. control
// stops at 9 - 11 depending on whether or not they have 11-cost cards in their deck or if they need to play two
// removal pieces like No Glory and Lost and Forgotten". The deck decides within its range: its most expensive
// card, and for Control also its two most expensive answers of cost 6 or less, cast together in one round.
function SWUBotResourceStop(int $seat, string $style): int {
    $top = 0; $answers = [];
    foreach (SWUBotOwnCardIDs($seat) as $cid) {
        $cost = _SWUBotSeatCost($seat, $cid);
        $top = max($top, $cost);
        if ($cost <= 6 && array_intersect(SWUBotCardTags($cid), ['removal', 'wipe'])) $answers[] = $cost;
    }
    rsort($answers);
    $pair = count($answers) >= 2 ? $answers[0] + $answers[1] : 0;
    // Five archetypes, five stops (spec, "The archetype is an ordered scale"): the aggro wing stops early,
    // hard control banks for its bombs and its answer pair. Floors come from the spec; the ceiling still
    // follows the deck, so a deck holding an 11-cost card can still bank enough to cast it.
    $rank = SWUBotStyleRank($style);
    return match ($rank) {
        0 => max(6, min(7, $top)),
        1 => max(7, min(8, $top)),
        2 => max(8, min(9, $top)),
        3 => max(9, min(11, max($top, $pair))),
        default => max(11, min(12, max($top, $pair))),
    };
}

// True at the regroup's optional resource once the floor is met and the seat has reached its stop (feature 'stop').
function SWUBotAtResourceStop(array $ctx): bool {
    if (!SWUBotFeatureOn('stop') || ($ctx['tooltip'] ?? '') !== 'Resource_up_to_1_card') return false;
    $seat = intval($ctx['seat']);
    return !SWUBotResourceFloorApplies($seat) && SWUResourceCount($seat) >= SWUBotResourceStop($seat, strval($ctx['style']));
}


// 'holdanswers' helpers (owner ruling 2026-09-19, Q2-D).
function _SWUBotOpponentSpaceHeavy(int $seat): bool {
    $u = SWUBotUnits(SWUBotOpponent($seat));
    $space = count(array_filter($u, fn($v) => $v['arena'] === 'Space'));
    return $space >= 2 && $space >= count($u) - $space;
}
function _SWUBotAnswersSpace(string $cid): bool {
    $t = strval(CardText($cid));
    if (preg_match('/defeat all (space )?units/i', $t)) return true;
    if (preg_match('/ground unit/i', $t) && !preg_match('/space unit/i', $t)) return false;
    return SWUBotRemovalClass($cid)[0] === 'bombkiller' || (bool)preg_match('/(defeat|take control of) (a|an)( enemy)?( non-leader)? unit|enemy non-leader unit\. If you do, defeat/i', $t);
}


// ── PROPOSAL 'resourcing2' — the owner's rulings, 2026-09-22 (bot-sweeps/2026-09-21_resourcing_rulings.md) ──────────
// Precedence CONFIRMED by the owner, top wins. Returns [handIndex => [tier, keepWithinTier]]; LOWER is resourced first.
//   PROTECTED (never resourced):  Hyperspace Disaster vs space aggro (ruling 3); Chimaera — A Frightening Reality
//                                 (rulings 5, 10: "the best card in the format as of 2026-09-21" — meta-dependent).
//   tier 0  duplicates: a second copy in hand goes first (ruling 6, Q4: "fine to resource since you have 2 in hand").
//   tier 1  BEFORE THE LEADER FLIP, vs an aggressive opponent: the 7+ drops — "you may not survive to use them"
//           (rulings 1, 9). Inside the tier a card that does not answer the matchup goes before one that does
//           (ruling 2, Q1: Trask Walker before Pre Vizsla, whose multi-kill answers Vader's swarm).
//   tier 2  everything else, by what it is worth to KEEP: removal is crucial vs aggro (Q3), a multi-kill vs a wide
//           board (ruling 2), the cheap plays that keep a curve (ruling 7: "nothing to play in round 2 is worse"),
//           and fewer copies left in the deck (ruling 6: a 3-of is cheaper to resource than a 2-of).
// ⚠ The owner calibrates rulings 6-8 as "generally true, ~75% of the time" — preferences, not laws.
const SWU_BOT_ENGINE_KEEPS = ['ASH_052'];   // Chimaera — A Frightening Reality

// PROPOSAL 'resourcing3': leaders whose decks the meta fixtures label AGGRO (hyperaggro/aggro/softaggro) in most or
// all of their lists. The bot cannot see an opponent's label in live play; its leader is the best proxy. Leaders that
// also head midrange/control lists (Luke JTL_012, Piett, Maul, Talzin…) are left out: a slow matchup is the exception
// the owner's ruling makes ("resource high-cost early unless you can ramp or the matchup is slow").
const SWU_BOT_AGGRO_LEADERS = ['ASH_009', 'ASH_013', 'ASH_017', 'JTL_004', 'JTL_006', 'JTL_008', 'JTL_009', 'JTL_011',
                               'JTL_013', 'JTL_015', 'LAW_002', 'LAW_010', 'LAW_013', 'LAW_016', 'LOF_010', 'SEC_006',
                               'SEC_014'];

function SWUBotOpponentIsAggroLeader(int $seat): bool {
    return in_array(strval((GetLeader(SWUBotOpponent($seat))[0] ?? null)->CardID ?? ''), SWU_BOT_AGGRO_LEADERS, true);
}

function _SWUBotResourcing2Tiers(array $ctx, int $seat, bool $v3 = false): array {
    $opp = SWUBotOpponent($seat);
    $oppFlavours = SWUBotDeckFlavours($opp);
    $oppUnits = SWUBotUnits($opp);
    $space = count(array_filter($oppUnits, fn($u) => $u['arena'] === 'Space'));
    $spaceAggro = in_array('space', $oppFlavours, true) || ($space >= 2 && $space >= count($oppUnits) - $space);
    $aggressive = $spaceAggro || in_array('hyper', $oppFlavours, true) || SWUBotBasePotential($opp, $seat, false) >= 4
                  || count($oppUnits) >= 3;
    // resourcing3: the matchup, not the board. A hard-control mirror with 3 units out is not an aggro matchup
    // (resourcing2 vs Dedra: −142 / 1,000).
    if ($v3) { $aggroOpp = SWUBotOpponentIsAggroLeader($seat); $spaceAggro = $spaceAggro && $aggroOpp; $aggressive = $aggroOpp; }
    $capitalDeck = $v3 && in_array('capital-ship', SWUBotDeckFlavours($seat), true);
    $leader = GetLeader($seat)[0] ?? null;
    $deployed = $leader !== null && !empty($leader->Deployed) && strval($leader->Deployed) !== 'false';
    $threshold = SWUBotLeaderDeployThreshold($seat);
    $preflip = !$deployed && ($threshold > 0 ? SWUResourceCount($seat) < $threshold : intval(GetTurnNumber()) <= 5);
    $opening = strval($ctx['tooltip'] ?? '') === 'Choose_2_cards_to_resource';
    $cheapCap = $opening ? 2 : SWUResourceCount($seat) + 1;
    $hand = [];
    foreach (GetHand($seat) as $i => $o) { if ($o !== null && empty($o->removed)) $hand[$i] = strval($o->CardID ?? ''); }
    $firstIdx = []; $inHand = [];
    foreach ($hand as $i => $cid) { $inHand[$cid] = ($inHand[$cid] ?? 0) + 1; if (!isset($firstIdx[$cid])) $firstIdx[$cid] = $i; }
    $inDeck = [];
    foreach (GetDeck($seat) as $o) { if ($o !== null && empty($o->removed)) { $c = strval($o->CardID ?? ''); $inDeck[$c] = ($inDeck[$c] ?? 0) + 1; } }
    // The curve is judged on what STAYS in hand: a duplicate is resourced first (tier 0), so it is not one of the cheap
    // plays being kept. (Counting it made Q3 resource BOTH Night Troopers instead of one Trooper + one No Glory.)
    $cheap = 0;
    foreach ($hand as $i => $cid) { if (_SWUBotSeatCost($seat, $cid) <= $cheapCap && $i === $firstIdx[$cid]) $cheap++; }
    $out = [];
    foreach ($hand as $i => $cid) {
        $tags = SWUBotCardTags($cid);
        $answer = (bool)array_intersect($tags, ['removal', 'wipe']);
        $cost = _SWUBotSeatCost($seat, $cid);
        if ($spaceAggro && preg_match('/defeat all space units/i', strval(CardText($cid)))) { $out[$i] = [9, 0.0]; continue; }
        if (in_array($cid, SWU_BOT_ENGINE_KEEPS, true)) { $out[$i] = [9, 0.0]; continue; }
        // resourcing3: a capital-ship deck cheats its Capital Ships out to trade and stall (owner, Piett vs Vader);
        // they go last, the priciest first if one must go.
        if ($capitalDeck && str_contains(strval(CardTrait($cid) ?? ''), 'Capital Ship')) { $out[$i] = [8, -1.0 * $cost]; continue; }
        if (($inHand[$cid] ?? 0) >= 2 && $i !== $firstIdx[$cid]) { $out[$i] = [0, 0.0]; continue; }
        if (!$opening && $preflip && $aggressive && $cost >= 7) {
            $fitsMatchup = $answer && (in_array('wipe', $tags, true) ? count($oppUnits) >= 3 : true);
            $out[$i] = [1, $fitsMatchup ? 1.0 : 0.0];
            continue;
        }
        $keep = 0.0;
        if ($answer) $keep += 100.0;
        if ($answer && in_array('wipe', $tags, true) && count($oppUnits) >= 3) $keep += 100.0;
        if ($cost <= $cheapCap) $keep += $cheap <= 2 ? 250.0 : 50.0;
        $keep -= 20.0 * ($inDeck[$cid] ?? 0);
        $out[$i] = [2, $keep];
    }
    return $out;
}
