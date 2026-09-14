<?php
// Layer 2 of the RL bots spec's decision stack — the CONFIDENT RULES. Each is fn(array $ctx): ?array and
// answers only when clearly right: it returns a member of $ctx['actions'], or null to abstain. The stack
// (BotHeuristic.php) runs SWUBotRulesBeforeFilter(), then the style filter, then SWUBotRulesAfterFilter(),
// each in array order — which is the spec's priority order.
// Layer 2 holds only rules that are nearly always right: the model never learns the decisions they answer.
// Owner ruling 2026-09-13: the opening-resources pick, the floor's CARD choice, and rules 6 (attack before
// developing), 7 (Aggro maximises units) and 11 (Aggro's resourcing ceiling — since removed, 2026-09-14) became GUIDES in layer 4
// (BotGuides.php, BotFallback.php), so the learned layer and the deck layers can find their exceptions.
// The floor itself (rule 10) stays fixed as a constraint: it removes PASS (SWUBotResourceFloorFilter).
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("Layer 2"). Rules 4 (break
// lethal) and 5 (Control wipes) run on the lookahead's sequence form (BotLookahead.php, Phase 1b); the answers
// along the line they chose are followed by the planned-answer rule.
// CR = .claude/SWUSim/refs/comprehensive-rules.md.






// ── Rules ────────────────────────────────────────────────────────────────────────────────────────

// Rule 1 — only one legal move: take it.
function SWUBotRuleSingle(array $ctx): ?array {
    return count($ctx['actions']) === 1 ? $ctx['actions'][0] : null;
}

// Rule 2 — take lethal now (all styles). Runs BEFORE the style filter, so a Control bot still takes the
// winning base attack. At the target prompt the attacker is already exhausted, so its power is added back.
function SWUBotRuleLethalNow(array $ctx): ?array {
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (_SWUBotIsFreePlay($ctx)) {
        if (!SWUBotLethalNow($seat, $opp)) return null;
        $best = null; $bestPow = -1;
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) !== 'attack') continue;
            $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
            if ($att === null || !SWUBotAttackTargets($seat, $att)['base']) continue;
            if ($att['attackPower'] > $bestPow) { $best = $a; $bestPow = $att['attackPower']; }
        }
        return $best;
    }
    if (($ctx['tooltip'] ?? '') !== 'Choose_an_attack_target') return null;
    $base = _SWUBotFind($ctx, fn($a) => str_contains(strval($a['cardID'] ?? ''), 'Base-'));
    $attMz = SWUBotAttackerMz($ctx);
    $att = $attMz !== null ? SWUBotViewForMz($seat, $attMz) : null;
    if ($base === null || $att === null) return null;
    $pot = SWUBotBasePotential($seat, $opp, true) + ($att['ready'] ? 0 : $att['attackPower']);
    return $pot >= SWUBaseRemainingHp($opp) ? $base : null;
}


// Rule 3 — take the initiative for guaranteed lethal next round (all styles; CR 1.15.5: the initiative
// holder acts first next round). Not lethal now, lethal once everything readies (CR 5.5.1d), and the
// opponent cannot put a Sentinel in the way (CR 7.5.11).
function SWUBotRuleInitiativeForLethal(array $ctx): ?array {
    if (!_SWUBotIsFreePlay($ctx)) return null;
    $init = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'initiative');
    if ($init === null) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (SWUBotLethalNow($seat, $opp) || !SWUBotLethalNextRound($seat, $opp)) return null;
    return SWUBotOpponentCanGetSentinel($opp) ? null : $init;
}

// Shared by rules 4 and 5. Tries every candidate except pass and taking the initiative (they never move the
// board) — or only those $consider() admits — with the sequence lookahead; returns the candidate whose best line
// $ok() accepts and $score() rates highest (the first wins a tie: deterministic), and stores that line's
// follow-up answers as the seat's plan for SWUBotRulePlannedAnswer().
function _SWUBotBestLine(array $ctx, callable $read, callable $score, callable $ok, ?callable $consider = null): ?array {
    $seat = intval($ctx['seat']);
    $cands = array_values(array_filter($ctx['actions'], function ($a) use ($consider) {
        $k = SWUBotActionKind($a);
        return $k !== 'pass' && $k !== 'initiative' && ($consider === null || $consider($a));
    }));
    // SWU_BOT_LOOKAHEAD_BUDGET is shared out evenly (BotLookahead.php says why it must be); every candidate gets
    // at least its own move, so a board with more candidates than budget still tries each one once.
    $left = SWU_BOT_LOOKAHEAD_BUDGET;
    $best = null; $bestLine = null;
    foreach ($cands as $i => $a) {
        $share = max(1, intdiv($left, count($cands) - $i));
        $before = intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0);
        $line = SWUBotLookaheadBest($seat, $a, $read, $score, SWU_BOT_LOOKAHEAD_DEPTH, $share);
        $left -= intval($GLOBALS['SWUBotLookaheadCalls'] ?? 0) - $before;
        if ($line === null || !$ok($line)) continue;
        if ($bestLine === null || $line['_score'] > $bestLine['_score']) { $best = $a; $bestLine = $line; }
    }
    if ($best !== null) $GLOBALS['SWUBotPlan'][$seat] = $bestLine['_path'];
    return $best;
}

// Rule 4 — break lethal (all styles). Facing lethal next round (their clock is 1), take the move that breaks
// it — a Sentinel in the threatened arena, removal that kills a threat, healing that lifts the base out of
// range: whatever the sequence lookahead shows leaves their clock above 1. Prefers the longest clock left to
// them, then the widest race (their clock − mine). No breaking move → abstain. Action phase only (a regroup
// choice cannot move the board). Sentinel guards even while exhausted (CR 7.5.11) — SWUBotArenaHasSentinel()
// does not read readiness. Works at free play and at the seat's own prompts (a removal's target).
function SWUBotRuleBreakLethal(array $ctx): ?array {
    if (strval(GetCurrentPhase()) !== 'MAIN' || !function_exists('SWUBotLookaheadBest')) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (SWUBotClock($opp, $seat) !== 1) return null;
    $read  = fn() => ['oppClock' => SWUBotClock($opp, $seat), 'myClock' => SWUBotClock($seat, $opp)];
    $score = fn(array $r) => $r['oppClock'] * 1000 + ($r['oppClock'] - $r['myClock']);
    return _SWUBotBestLine($ctx, $read, $score, fn(array $r) => $r['oppClock'] > 1);
}

// Rule 5 — Control plays board wipes that stabilise, even at the cost of its own good units. A WIPE is a play
// that defeats two or more units; it STABILISES when the opponent's clock afterwards is at least 3 rounds and
// longer than before (SWUBotStabilises). Judged by the sequence lookahead, so a wipe with a choice (Bombing Run's
// arena) is judged by its best answer, which the plan then follows. Candidates are the hand plays tagged 'wipe'
// (Rl/CardTags.php), which keeps lookaheads rare.
// Owner ruling 2026-09-14 (feature 'wipegate'): a wipe must cost the enemy at least as much unit value as it costs
// Control — unless the opponent's clock on Control is 2 or less. "At least one of Control's own" is gone, so the
// planner never picks its own units to qualify (Pre Vizsla had defeated six Annihilators that way) and a one-sided
// sweep qualifies. Prefers the longest clock left to them, then the most value gained.
function SWUBotRuleControlWipe(array $ctx): ?array {
    if (($ctx['style'] ?? '') !== 'control' || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    if (!function_exists('SWUBotLookaheadBest')) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    $byValue = SWUBotFeatureOn('wipegate');
    $uids = fn(int $p) => array_map(fn($v) => $v['uid'], SWUBotUnits($p));
    $valueOf = fn(int $p) => array_sum(array_map(fn($v) => SWUBotUnitValue($v), SWUBotUnits($p)));
    $mineBefore = $uids($seat); $theirsBefore = $uids($opp);
    if (!$byValue && empty($mineBefore)) return null;
    $vMine = $valueOf($seat); $vTheirs = $valueOf($opp);
    $clockBefore = SWUBotClock($opp, $seat);
    $read = function () use ($seat, $opp, $uids, $valueOf, $mineBefore, $theirsBefore, $vMine, $vTheirs) {
        $own = count(array_diff($mineBefore, $uids($seat)));
        return ['ownLost' => $own, 'defeated' => $own + count(array_diff($theirsBefore, $uids($opp))),
                'net' => ($vTheirs - $valueOf($opp)) - ($vMine - $valueOf($seat)), 'oppClock' => SWUBotClock($opp, $seat)];
    };
    $qualifies = $byValue
        ? fn(array $r) => $r['defeated'] >= 2 && SWUBotStabilises($clockBefore, $r['oppClock']) && ($r['net'] >= 0 || $clockBefore <= 2)
        : fn(array $r) => $r['defeated'] >= 2 && $r['ownLost'] >= 1 && SWUBotStabilises($clockBefore, $r['oppClock']);
    $score = fn(array $r) => $qualifies($r) ? 1000.0 + $r['oppClock'] * 10 + ($byValue ? $r['net'] : -$r['ownLost']) : -1.0;
    $isWipe = function (array $a) use ($seat) {
        if (SWUBotActionKind($a) !== 'play') return false;
        $o = _SWUBotHandObject($seat, $a);
        return $o !== null && in_array('wipe', SWUBotCardTags(strval($o->CardID)), true);
    };
    return _SWUBotBestLine($ctx, $read, $score, $qualifies, $isWipe);
}

// Follow the plan a lookahead rule (4, 5) stored for this seat: at a decision whose prompt matches the plan's
// next step and whose candidates include the planned answer, give it. Anything else drops the plan — the game
// went somewhere the lookahead did not foresee. Plans live in memory only: a new request (live play) starts
// with none, and the other rules and the fallback answer as before.
function SWUBotRulePlannedAnswer(array $ctx): ?array {
    $seat = intval($ctx['seat']);
    $plan = $GLOBALS['SWUBotPlan'][$seat] ?? [];
    if (empty($plan)) return null;
    $step = array_shift($plan);
    $pick = (($ctx['kind'] ?? '') === 'decision' && ($ctx['tooltip'] ?? '') === $step['tooltip'])
        ? _SWUBotFind($ctx, fn($a) => strval($a['cardID'] ?? '') === $step['answer']) : null;
    if ($pick === null) { unset($GLOBALS['SWUBotPlan'][$seat]); return null; }
    $GLOBALS['SWUBotPlan'][$seat] = $plan;
    return $pick;
}



// Rule 8 — don't end the round with a free attack unused. No blocking: a defender strikes back whether
// ready or not (CR 6.3.4a), and everything readies at the regroup (CR 5.5), so an unused attack is lost.
// Abstains for a unit with an [Exhaust] Action available (the learned layer weighs that).
function SWUBotRuleNoUnusedAttacks(array $ctx): ?array {
    if (!_SWUBotIsFreePlay($ctx)) return null;
    foreach ($ctx['actions'] as $a) {
        $k = SWUBotActionKind($a);
        if ($k !== 'attack' && $k !== 'pass' && $k !== 'initiative') return null;
    }
    $withActions = (array)(SWUComputeActionsData(intval($ctx['seat']))['unitActions'] ?? []);
    $free = array_values(array_filter(SWUBotFreeAttacks($ctx), fn($a) => !in_array(SWUBotActionMz($a), $withActions, true)));
    return empty($free) ? null : SWUBotFallbackChoose(array_merge($ctx, ['actions' => $free]));
}

// Rule 9 — nothing left to do → take the initiative: never worse than passing, and it gains the first
// action next round (CR 1.15.5).
function SWUBotRuleNothingLeft(array $ctx): ?array {
    if (!_SWUBotIsFreePlay($ctx)) return null;
    $kinds = array_map('SWUBotActionKind', $ctx['actions']);
    sort($kinds);
    return $kinds === ['initiative', 'pass'] ? _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'initiative') : null;
}



// Rule 12 — decline a losing Ambush (Ambush is optional, CR 7.5.5a): every target defeats the attacker
// and survives.
function SWUBotRuleDeclineLosingAmbush(array $ctx): ?array {
    if (($ctx['type'] ?? '') !== 'YESNO' || ($ctx['tooltip'] ?? '') !== 'Ambush_attack?') return null;
    $p = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
    if ($p[0] !== 'SWUAmbushAnswer' || !isset($p[1], $p[2])) return null;
    $seat = intval($ctx['seat']);
    $att = SWUBotViewForMz($seat, $p[1]);
    if ($att === null) return null;
    foreach (array_filter(explode('&', $p[2])) as $t) {
        $def = SWUBotViewForMz($seat, $t);
        if ($def === null || SWUBotCombatOutcome($att, $def) !== 'die') return null;
    }
    return _SWUBotFind($ctx, fn($a) => strval($a['cardID'] ?? '') === 'NO');
}

// Rule 13 — accept a free "you may" benefit with no downside: every target is friendly.
function SWUBotRuleFreeBenefit(array $ctx): ?array {
    if (($ctx['type'] ?? '') !== 'MZMAYCHOOSE') return null;
    if (!in_array(_SWUBotContinuationHead($ctx), SWU_BOT_BENEFICIAL_CONTINUATIONS, true)) return null;
    $picks = array_values(array_filter($ctx['actions'], fn($a) => strval($a['cardID'] ?? '') !== 'PASS'));
    if (empty($picks)) return null;
    foreach ($picks as $a) { if (!str_starts_with(strval($a['cardID'] ?? ''), 'my')) return null; }
    return SWUBotFallbackChoose(array_merge($ctx, ['actions' => $picks]));
}

// Rule 14 — exhaust effects never target an already-exhausted enemy while a ready one is legal:
// exhausting an exhausted unit changes nothing this round (CR 1.5.4e).
function SWUBotRuleExhaustReadyEnemies(array $ctx): ?array {
    if (!in_array($ctx['type'] ?? '', ['MZCHOOSE', 'MZMAYCHOOSE'], true) || _SWUBotContinuationHead($ctx) !== 'EXHAUST_UNIT') return null;
    $seat = intval($ctx['seat']);
    $keep = []; $readyEnemy = false; $exhaustedEnemy = false;
    foreach ($ctx['actions'] as $a) {
        $c = strval($a['cardID'] ?? '');
        $v = str_starts_with($c, 'their') ? SWUBotViewForMz($seat, $c) : null;
        if ($v !== null && !$v['ready']) { $exhaustedEnemy = true; continue; }
        if ($v !== null) $readyEnemy = true;
        $keep[] = $a;
    }
    return ($readyEnemy && $exhaustedEnemy) ? SWUBotFallbackChoose(array_merge($ctx, ['actions' => $keep])) : null;
}

// Rule 15 — bank Credits: ready resources ready again every round, a spent Credit is gone (CR 3.7.13), so
// Credits pay only the shortfall. While an enemy card that preys on held Credits is in play (LAW_191 Arvel
// Skeen), spend them first. Abstains under SEC_122, whose Droids also join the payment.
const SWU_BOT_CREDIT_PREDATORS = ['LAW_191'];

function SWUBotRuleBankCredits(array $ctx): ?array {
    if (($ctx['type'] ?? '') !== 'MZMULTICHOOSE' || ($ctx['tooltip'] ?? '') !== 'Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each') return null;
    $p = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
    if ($p[0] !== 'CREDIT_PAY' || !isset($p[1], $p[2])) return null;
    $seat = intval($ctx['seat']);
    if (SWUPlayerControlsSEC122($seat)) return null;
    $max = intval($p[1]); $cost = intval($p[2]);
    $preyed = false;
    foreach (SWUBotUnits(intval($ctx['opp'])) as $v) { if (in_array($v['cardID'], SWU_BOT_CREDIT_PREDATORS, true)) { $preyed = true; break; } }
    $n = $preyed ? min($max, $cost) : min($max, max(0, $cost - SWUResourceCount($seat, true)));
    return _SWUBotFind($ctx, fn($a) => SWUBotSelectionCount($a) === $n);
}

function SWUBotRulesBeforeFilter(): array {
    return ['single' => 'SWUBotRuleSingle', 'lethal-now' => 'SWUBotRuleLethalNow', 'planned-answer' => 'SWUBotRulePlannedAnswer'];
}

function SWUBotRulesAfterFilter(): array {
    return [
        'initiative-for-lethal'    => 'SWUBotRuleInitiativeForLethal',
        'break-lethal'             => 'SWUBotRuleBreakLethal',
        'control-wipe'             => 'SWUBotRuleControlWipe',
        'no-unused-attacks'        => 'SWUBotRuleNoUnusedAttacks',
        'nothing-left'             => 'SWUBotRuleNothingLeft',
        'decline-losing-ambush'    => 'SWUBotRuleDeclineLosingAmbush',
        'free-benefit'             => 'SWUBotRuleFreeBenefit',
        'exhaust-ready-enemies'    => 'SWUBotRuleExhaustReadyEnemies',
        'bank-credits'             => 'SWUBotRuleBankCredits',
    ];
}
