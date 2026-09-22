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
// Proposal 'wipethreat': the base damage per round a wipe must prevent to qualify on damage alone. Roughly the
// owner's finisher level ("swinging with Ahsoka or another 5-power unit like Amidala", 2026-09-18); the owner's
// Boba example prevents 8+. A first guess — tune after measuring.
const SWU_BOT_WIPE_THREAT_WORTH = 5;

function SWUBotRuleControlWipe(array $ctx): ?array {
    // The control wing (rank 3-4): soft and hard control both plan wipes.
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    if (!function_exists('SWUBotLookaheadBest')) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    $byValue = SWUBotFeatureOn('wipegate');
    $uids = fn(int $p) => array_map(fn($v) => $v['uid'], SWUBotUnits($p));
    $valueOf = fn(int $p) => array_sum(array_map(fn($v) => SWUBotUnitValue($v), SWUBotUnits($p)));
    $mineBefore = $uids($seat); $theirsBefore = $uids($opp);
    if (!$byValue && empty($mineBefore)) return null;
    $vMine = $valueOf($seat); $vTheirs = $valueOf($opp);
    $clockBefore = SWUBotClock($opp, $seat);
    // Base damage each side's board threatens per round, before the wipe (for proposal 'wipethreat' below).
    $oppPotBefore = SWUBotBasePotential($opp, $seat, false);
    $myPotBefore = SWUBotBasePotential($seat, $opp, false);
    $read = function () use ($seat, $opp, $uids, $valueOf, $mineBefore, $theirsBefore, $vMine, $vTheirs) {
        $own = count(array_diff($mineBefore, $uids($seat)));
        return ['ownLost' => $own, 'defeated' => $own + count(array_diff($theirsBefore, $uids($opp))),
                'net' => ($vTheirs - $valueOf($opp)) - ($vMine - $valueOf($seat)), 'oppClock' => SWUBotClock($opp, $seat),
                'oppPot' => SWUBotBasePotential($opp, $seat, false), 'myPot' => SWUBotBasePotential($seat, $opp, false)];
    };
    $countGate = $byValue
        ? fn(array $r) => $r['defeated'] >= 2 && SWUBotStabilises($clockBefore, $r['oppClock']) && ($r['net'] >= 0 || $clockBefore <= 2)
        : fn(array $r) => $r['defeated'] >= 2 && $r['ownLost'] >= 1 && SWUBotStabilises($clockBefore, $r['oppClock']);
    // PROPOSAL 'wipethreat' (default OFF). Owner ruling 2026-09-18: "the botwipeisrelevant algorithm should be
    // improved. because sometimes, paying 7 to only wipe Boba Fett on a 4+ cost ship is the only play to mitigate
    // 8+ damage from them swinging a second time post-deploy." The count gate above (defeated >= 2) can NEVER
    // qualify a one-unit wipe however much damage it prevents. So a wipe ALSO qualifies when it prevents at least
    // SWU_BOT_WIPE_THREAT_WORTH base damage per round AND removes more of their damage potential than of mine —
    // both measured in the same currency (base damage), by the lookahead that resolves the wipe.
    $threatGate = SWUBotFeatureOn('wipethreat')
        ? fn(array $r) => ($oppPotBefore - $r['oppPot']) >= SWU_BOT_WIPE_THREAT_WORTH
                          && ($oppPotBefore - $r['oppPot']) > ($myPotBefore - $r['myPot'])
        : fn(array $r) => false;
    $qualifies = fn(array $r) => $countGate($r) || $threatGate($r);
    $score = fn(array $r) => $qualifies($r) ? 1000.0 + $r['oppClock'] * 10 + ($byValue ? $r['net'] : -$r['ownLost']) : -1.0;
    $isWipe = function (array $a) use ($seat) {
        if (SWUBotActionKind($a) !== 'play') return false;
        $o = _SWUBotHandObject($seat, $a);
        return $o !== null && in_array('wipe', SWUBotCardTags(strval($o->CardID)), true);
    };
    // Diagnostics (read-only; land in SWUBOT_METRICS coverage). 'wipe:castable' = this rule saw at least one wipe it
    // could play — the rule only ever sees CASTABLE wipes, so a gate that never fires may simply never be reached.
    // 'wipe:threat-only' = a line qualified on damage prevented but NOT on the count gate: the case 'wipethreat'
    // exists for. Recorded in the $ok check only ($score also calls $qualifies, once per lookahead leaf).
    // ⚠ OFF unless SWUBOT_WIPE_DIAG is set: coverage keys are a CONTRACT — bot_rules_test asserts the EXACT key
    // list (`=== ['myHand-0!FSM!', ['rule:control-wipe']]`), so an always-on diagnostic key broke two rule-5
    // assertions with the rule's decision unchanged. They were gated on the 'wipethreat' PROPOSAL while it was
    // measured; now that it ships ON by default, that gate would make them always-on, so they get their own
    // switch. `docker exec -e SWUBOT_WIPE_DIAG=1 …` to read them.
    $diag = (bool)getenv('SWUBOT_WIPE_DIAG');
    if ($diag && !empty(array_filter($ctx['actions'], $isWipe))) SWUBotRecordCoverage($seat, 'wipe:castable');
    $ok = function (array $r) use ($countGate, $threatGate, $seat, $diag) {
        $c = $countGate($r); $t = $threatGate($r);
        if ($diag && $t && !$c) SWUBotRecordCoverage($seat, 'wipe:threat-only');
        return $c || $t;
    };
    return _SWUBotBestLine($ctx, $read, $score, $ok, $isWipe);
}

// PROPOSAL 'initiative' (default OFF, "@try-initiative"). Owner ruling 2026-09-18 (Q16 / 5.2): taking the
// initiative can be worth MORE than a card play or an attack when it lets control remove a threat BEFORE it swings.
// Two scenarios on record:
//   (a) they hold the initiative and play Allegiant General Pryde (JTL_133, 2/3); I hold Latts Razzi (LAW_039, 3)
//       and an Imperial Dark Trooper (SEC_080, 2) → take the initiative, then Latts Razzi their Pryde first thing.
//   (b) mid-game, one 4-power unit ready, resources spent, their last play a 6/6, a removal in hand not castable
//       now but castable as next round's first action → take the initiative rather than swing for 4 (assuming no
//       Sentinel of mine absorbs their attack).
// Every condition is read from those two scenarios:
//   - the control wing, not racing (a racing seat is winning its race: its remaining actions are worth more);
//   - the OPPONENT holds the initiative (unclaimed — a claimed one is not on offer). If nobody claims it, they
//     act first next round and the threat swings before my answer;
//   - an enemy unit threatens my base (SWUBotUnitBaseThreat: 0 behind my Sentinel — the owner's caveat);
//   - a card in my hand kills it (SWUBotHandCardKills), is NOT castable now (else the fallback just plays it) and
//     IS castable next round (every resource ready + the regroup's resource + my Credits);
//   - the swing prevented (W['base'] × its base threat — the same per-point rate as every base hit) beats what
//     taking the initiative forgoes: every free attack I have left, plus the best other play. Guides are held
//     out of that sum — attackFirst's flat 6.00 is a SEQUENCING bonus, not a value.
// Placed ahead of rule 8 on purpose: scenario (b) leaves only attack / pass / initiative, which rule 8 answers
// with the free attack before the fallback ever sees the initiative.
function SWUBotRuleInitiativeForAnswer(array $ctx): ?array {
    if (!SWUBotProposalOn('initiative') || !_SWUBotIsFreePlay($ctx)) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3) return null;
    $init = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'initiative');
    if ($init === null || strval(GetInitiativeCounter() ?? '') !== 'P' . $opp . '_UNCLAIMED') return null;
    $W = SWUBotWeights(strval($ctx['style']), $seat);
    $capNow = SWUTotalPaymentCapacity($seat);
    $capNext = SWUResourceCount($seat) + 1 + count(SWUUsableCreditTokenMzIDs($seat));
    $threat = 0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $capNow || $cost > $capNext) continue;
        foreach (SWUBotUnits($opp) as $u) {
            if (SWUBotHandCardKills(strval($o->CardID), $u)) $threat = max($threat, SWUBotUnitBaseThreat($seat, $u));
        }
    }
    if ($threat <= 0) return null;
    // What the initiative gives up: the rest of this round.
    $noGuides = array_merge($ctx, ['_guides' => ['attackFirst' => [], 'maxUnits' => '']]);
    $attacks = 0.0; $other = 0.0;
    foreach ($ctx['actions'] as $i => $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'pass' || $k === 'initiative') continue;
        $s = max(0.0, SWUBotScoreAction($noGuides, $a, $i));
        if ($k === 'attack') $attacks += $s; else $other = max($other, $s);
    }
    return $W['base'] * $threat > $attacks + $other ? $init : null;
}

// PROPOSAL 'freekill' (default OFF). Owner ruling 2026-09-19 (loss mining, Q1): "control should always take the free
// kill if the unit is ready. if it has already attacked, then it needs to think about other threats and how to stop
// them." At a control-wing attacker's target prompt: if an enemy unit that is READY can be defeated while the
// attacker survives (kill-survive), attack it — the most valuable such unit. Exhausted targets are left to the
// normal scorer. Seen in lost games: Helgait 6/4 hit the base with a 3/1 free kill on offer (took 17 next round);
// The Mandalorian 4/6 hit the base at 14 HP instead of killing a 5/4 carrying 3 upgrades.
function SWUBotRuleFreeKill(array $ctx): ?array {
    if (!SWUBotProposalOn('freekill') || ($ctx['tooltip'] ?? '') !== 'Choose_an_attack_target') return null;
    $seat = intval($ctx['seat']);
    if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3) return null;
    $attMz = SWUBotAttackerMz($ctx);
    $att = $attMz !== null ? SWUBotViewForMz($seat, $attMz) : null;
    if ($att === null) return null;
    $best = null; $bestV = -1.0;
    foreach ($ctx['actions'] as $a) {
        $c = strval($a['cardID'] ?? '');
        if (!str_starts_with($c, 'their') || str_contains($c, 'Base')) continue;
        $u = SWUBotViewForMz($seat, $c);
        if ($u === null || !$u['ready'] || SWUBotCombatOutcome($att, $u) !== 'kill-survive') continue;
        $val = SWUBotUnitValue($u);
        if ($val > $bestV) { $bestV = $val; $best = $a; }
    }
    return $best;
}

// PROPOSAL 'shrinkfirst' (default OFF). Owner ruling 2026-09-19 (Q2, position C — Knowledge and Defense vs a ready
// Lepi Lookout 3/1 behind a Shield): "if that Lepi was ready, it might be best to shrink it to kill before it
// attacks. the draw is also very valuable to control." At free play, a control-wing seat with a castable card that
// can defeat a READY enemy threat (≥ SWU_BOT_THREAT_WORTH power) plays it first. Judged by the sequence lookahead
// (the play and its target answer), so the plan follows the line that actually removes the threat; among lines,
// more ready enemy power removed wins, then the one that also draws.
// 'shrinkfirst2' is the same rule with the threat bar at 2 power instead of 3 — the threshold test.
function SWUBotRuleShrinkFirst(array $ctx): ?array {
    if ((!SWUBotFeatureOn('shrinkfirst') && !SWUBotProposalOn('shrinkfirst2')) || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $bar = SWUBotProposalOn('shrinkfirst2') ? 2 : SWU_BOT_THREAT_WORTH;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    // The CONTROL WING as measured: the archetype rank gates WHO uses the rule (soft/hard control — the only seats
    // that ever carried it in the one-sided arms), the racing rank keeps the measured behaviour WITHIN those seats
    // (a control seat that is racing drops below 3 and stops). Without the archetype gate, shipping would also switch
    // the rule on for a 'tempo'-flavoured MIDRANGE deck, whose racing rank reaches 3 — unmeasured behaviour: the
    // ship check caught exactly that (mid 104/126 until the opponent was held at @no-p6, then 126/126).
    // PROPOSAL 'shrinkfirstall' (default OFF) lifts the control-wing gate: does the shipped p6 rule help every
    // archetype? It was only ever measured on control seats.
    if (!SWUBotProposalOn('shrinkfirstall')
        && (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3)) return null;
    if (!function_exists('SWUBotLookaheadBest')) return null;
    $threats = array_values(array_filter(SWUBotUnits($opp), fn($u) => $u['ready'] && $u['attackPower'] >= $bar));
    if (empty($threats)) return null;
    $readyPow = fn() => array_sum(array_map(fn($u) => $u['ready'] ? $u['attackPower'] : 0, SWUBotUnits($opp)));
    $before = $readyPow();
    $handBefore = count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed)));
    $consider = function (array $a) use ($seat, $threats) {
        if (SWUBotActionKind($a) !== 'play') return false;
        $o = _SWUBotHandObject($seat, $a);
        if ($o === null) return false;
        foreach ($threats as $u) { if (SWUBotHandCardKills(strval($o->CardID), $u)) return true; }
        return false;
    };
    $read = fn() => ['pow' => $readyPow(), 'hand' => count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed)))];
    $score = fn(array $r) => ($before - $r['pow']) * 10 + ($r['hand'] >= $handBefore ? 1 : 0);   // hand kept its size = it drew
    $ok = fn(array $r) => ($before - $r['pow']) >= $bar;
    return _SWUBotBestLine($ctx, $read, $score, $ok, $consider);
}

// PROPOSAL 'killfirst' (default OFF) — ORDERING, the family every shipped win came from. When an attack this turn
// would defeat an enemy unit and survive, make THAT attack before any attack that would go to the base: the
// defender is removed before it can be buffed, healed or used, and the base is still there later. The scorer
// compares attacks by value but has no notion of doing one first.
function SWUBotRuleKillFirst(array $ctx): ?array {
    if (!SWUBotProposalOn('killfirst') || !_SWUBotIsFreePlay($ctx)) return null;
    $seat = intval($ctx['seat']);
    $best = null; $bestV = 0.0;
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'attack') continue;
        $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
        if ($att === null) continue;
        foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
            if ($k === 'base' || SWUBotCombatOutcome($att, $u) !== 'kill-survive') continue;
            $v = SWUBotUnitValue($u);
            if ($v > $bestV) { $bestV = $v; $best = $a; }
        }
    }
    return $best;
}

// PROPOSAL 'blockerfirst' (default OFF) — the loss-mining signature: in games control loses it is 1.4 units behind
// by round 3 and 2.3 by round 5 (2026-09-19). While behind on bodies, put one down BEFORE attacking; the attack is
// still available afterwards, the body is not (a removal spell in their turn takes the play away).
function SWUBotRuleBlockerFirst(array $ctx): ?array {
    if (!SWUBotProposalOn('blockerfirst') || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (count(SWUBotUnits($seat)) >= count(SWUBotUnits($opp))) return null;
    $hasAttack = false;
    foreach ($ctx['actions'] as $a) { if (SWUBotActionKind($a) === 'attack') { $hasAttack = true; break; } }
    if (!$hasAttack) return null;
    $plays = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = _SWUBotHandObject($seat, $a);
        if ($o !== null && str_contains(strval(CardType(strval($o->CardID))), 'Unit')) $plays[] = $a;
    }
    return empty($plays) ? null : SWUBotFallbackChoose(array_merge($ctx, ['actions' => $plays]));
}

// PROPOSAL 'krennicramp' (default OFF) — the owner's Krennic plan vs Vader (rulings K1-K4, 2026-09-22). The leader
// (LAW_008 Director Krennic, "Action [Exhaust, defeat a friendly unit]: Create a Credit token") turns a cheap body
// into ramp every round: T1 play a unit, sacrifice it → 2R+1C; T2 → 3R+2C; T3 → 4R+3C = 7, so Hyperspace Disaster
// lands early and Chimaera still follows. Before the leader FLIP only. With fodder on board (a cheap unit, or one with
// a When Defeated — Ant Droid, Expendable Mercenary) use the leader; with none, put a cheap body down first. Which
// unit the ability defeats is left to the existing sacrifice scoring ('fodder').
function SWUBotRuleKrennicRamp(array $ctx): ?array {
    if (!SWUBotProposalOn('krennicramp') || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $seat = intval($ctx['seat']);
    $leader = GetLeader($seat)[0] ?? null;
    if ($leader === null || !preg_match('/defeat a friendly unit\]:\s*Create a Credit token/i', strval(CardText(strval($leader->CardID ?? ''))))) return null;
    if (!empty($leader->Deployed) && strval($leader->Deployed) !== 'false') return null;
    $threshold = SWUBotLeaderDeployThreshold($seat);
    if ($threshold > 0 && SWUResourceCount($seat) >= $threshold) return null;
    $isFodder = fn(array $v) => !$v['isLeader'] && ($v['cost'] <= 2 || stripos(strval(CardText($v['cardID'])), 'When Defeated') !== false);
    $ability = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'leader-ability');
    if ($ability !== null && array_filter(SWUBotUnits($seat), $isFodder)) return $ability;
    // No fodder yet: put down the cheapest body that would be one.
    $best = null; $bestCost = PHP_INT_MAX;
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = _SWUBotHandObject($seat, $a);
        if ($o === null) continue;
        $cid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit')) continue;
        $c = intval(CardCost($cid));
        if ($c <= 2 || stripos(strval(CardText($cid)), 'When Defeated') !== false) { if ($c < $bestCost) { $bestCost = $c; $best = $a; } }
    }
    // Only worth it while the leader can still cash the body in this round. The ability is NOT on offer while there is
    // nothing to sacrifice, so readiness is read from the leader itself rather than from the action list.
    $ready = !isset($leader->Ready) || (strval($leader->Ready) !== 'false' && $leader->Ready !== false && strval($leader->Ready) !== '0');
    return $ready ? $best : null;
}

// PROPOSAL 'krennicplan' (default OFF) — owner rulings K1-K3, 2026-09-22 (bot-sweeps/2026-09-22_krennic_rulings.md),
// Krennic vs Vader Yellow. Tournament data: 56.5% of matches; the bot won 7.8%. Traces: Krennic used its leader almost
// every round but spent each Credit at once (~0.6 held at the start of rounds 3-5), so Hyperspace Disaster (7) came a
// Credit short while the swarm grew. `krennicramp` (−15) put bodies down to feed the leader but still spent the Credits.
//   K1 bank:    Credits pay only for 7+ cards (HSD, Chimaera, Lawbringer). Develop with ready resources; a cheap When
//               Defeated body is played and sacrificed so next round reaches 7 (Q2 follow-up: "Ant Droid only … so i
//               can have 5R + 2C next round and play HSD").
//   K2 HSD now: Hyperspace Disaster as soon as it is castable vs 3+ ships that threaten lethal within 2 rounds ("even a
//               HSD on round 5 is better than waiting"). Not while I have a space unit of my own (Q8.1: it depends).
//   K3 order:   attack with a unit BEFORE sacrificing it; Expendable Mercenary is sacrificed the round it is played
//               ("save your leader ability to sac the Expendable Merc the same round it can be played").
// Scope: a seat whose leader is an undeployed "defeat a friendly unit: create a Credit" leader, vs an aggro leader
// playing space (flavour 'space', or 3+ ships out) — the only matchup the rulings were given for.
const SWU_BOT_CREDIT_WORTHY_COST = 7;

// The plan's parts, so the 2026-09-22 canary loss (−44) can be split: 'bank' (K1 Credits for 7+ only), 'hsd' (K2),
// 'order' (K3 attack before the sacrifice, the Mercenary pick), 'ramp' (K1's forced fodder play + leader cash-in).
const SWU_BOT_KRENNIC_PLAN_ARMS = [
    'krennicplan' => ['bank', 'hsd', 'order', 'ramp'],
    'kpbank'      => ['bank'],
    'kphsd'       => ['hsd'],
    'kporder'     => ['order'],
    'kpnoramp'    => ['bank', 'hsd', 'order'],
];

function _SWUBotKrennicPlanParts(): array {
    $on = [];
    foreach (SWU_BOT_KRENNIC_PLAN_ARMS as $prop => $parts) { if (SWUBotProposalOn($prop)) $on = array_merge($on, $parts); }
    return array_values(array_unique($on));
}

function _SWUBotKrennicPlanOn(array $ctx, string $part = ''): bool {
    $parts = _SWUBotKrennicPlanParts();
    if (empty($parts) || ($part !== '' && !in_array($part, $parts, true))) return false;
    $seat = intval($ctx['seat']);
    $leader = GetLeader($seat)[0] ?? null;
    if ($leader === null || !preg_match('/defeat a friendly unit\]:\s*Create a Credit token/i', strval(CardText(strval($leader->CardID ?? ''))))) return false;
    if (!empty($leader->Deployed) && strval($leader->Deployed) !== 'false') return false;
    $opp = SWUBotOpponent($seat);
    if (!function_exists('SWUBotOpponentIsAggroLeader') || !SWUBotOpponentIsAggroLeader($seat)) return false;
    $ships = count(array_filter(SWUBotUnits($opp), fn($v) => $v['arena'] === 'Space'));
    return in_array('space', SWUBotDeckFlavours($opp), true) || $ships >= 3;
}

// A body worth feeding to the leader: Expendable Mercenary first (it resources itself), then any cheap or When
// Defeated unit. Lower is better; null = not fodder.
function _SWUBotFodderRank(string $cid, int $cost): ?int {
    if (stripos(strval(CardText($cid)), 'When Defeated: You may resource this unit') !== false) return 0;
    if ($cost <= 2 || stripos(strval(CardText($cid)), 'When Defeated') !== false) return 1 + $cost;
    return null;
}

function _SWUBotIsHSD(string $cid): bool {
    return (bool)preg_match('/^Defeat all space units/i', strval(CardText($cid)));
}

// K1 + K3, as a filter (runs after the style filter, before the rules): drop the plays that would spend a Credit on a
// card under 7, and hold the leader's sacrifice while a friendly unit can still attack or a Mercenary can still be
// played with ready resources.
function SWUBotKrennicPlanFilter(array $ctx): array {
    if (!_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN' || !_SWUBotKrennicPlanOn($ctx)) return $ctx['actions'];
    $seat = intval($ctx['seat']);
    $bank = _SWUBotKrennicPlanOn($ctx, 'bank'); $order = _SWUBotKrennicPlanOn($ctx, 'order');
    $ready = SWUResourceCount($seat, true);
    $canAttack = false; $mercPlayable = false;
    foreach ($ctx['actions'] as $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'attack') $canAttack = true;
        if ($k === 'play' && ($o = _SWUBotHandObject($seat, $a)) !== null
            && _SWUBotFodderRank(strval($o->CardID), intval(CardCost(strval($o->CardID)))) === 0
            && intval(SWUComputePlayCost($seat, $o)) <= $ready) $mercPlayable = true;
    }
    $out = [];
    foreach ($ctx['actions'] as $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'play' && ($o = _SWUBotHandObject($seat, $a)) !== null) {
            $cid = strval($o->CardID);
            if ($bank && intval(CardCost($cid)) < SWU_BOT_CREDIT_WORTHY_COST && intval(SWUComputePlayCost($seat, $o)) > $ready) continue;   // K1
        }
        if ($order && $k === 'leader-ability' && ($canAttack || $mercPlayable)) continue;   // K3
        $out[] = $a;
    }
    return $out;
}

// K2 + K1's ramp + K3's Mercenary pick, as a rule.
function SWUBotRuleKrennicPlan(array $ctx): ?array {
    if (!_SWUBotKrennicPlanOn($ctx)) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    // K3: the sacrifice prompt takes the Mercenary when it is on offer.
    if (($ctx['kind'] ?? '') === 'decision') {
        if (!_SWUBotKrennicPlanOn($ctx, 'order') || ($ctx['tooltip'] ?? '') !== 'Defeat_a_friendly_unit_to_create_a_Credit') return null;
        return _SWUBotFind($ctx, function ($a) use ($seat) {
            $v = SWUBotViewForMz($seat, strval($a['cardID'] ?? ''));
            return $v !== null && _SWUBotFodderRank($v['cardID'], intval($v['cost'])) === 0;
        });
    }
    if (!_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    // K2: Hyperspace Disaster now.
    $ships = array_filter(SWUBotUnits($opp), fn($v) => $v['arena'] === 'Space');
    $mine = array_filter(SWUBotUnits($seat), fn($v) => $v['arena'] === 'Space');
    if (_SWUBotKrennicPlanOn($ctx, 'hsd') && count($ships) >= 3 && empty($mine)) {
        $hsd = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'play' && ($o = _SWUBotHandObject($seat, $a)) !== null && _SWUBotIsHSD(strval($o->CardID)));
        if ($hsd !== null && (2 * SWUBotBasePotential($opp, $seat, false) >= SWUBaseRemainingHp($seat) || count($ships) >= 5)) return $hsd;
    }
    // K1 ramp: short of 7 next round (every resource + the regroup's + my Credits) and the leader is ready to cash a
    // body in this round → the leader, once nothing can still attack (the filter holds it until then); with no
    // fodder on board, the best castable fodder body first.
    $credits = count(SWUUsableCreditTokenMzIDs($seat));
    if (!_SWUBotKrennicPlanOn($ctx, 'ramp') || SWUResourceCount($seat) + 1 + $credits >= SWU_BOT_CREDIT_WORTHY_COST) return null;
    $ability = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'leader-ability');
    $fodderOnBoard = array_filter(SWUBotUnits($seat), fn($v) => !$v['isLeader'] && _SWUBotFodderRank($v['cardID'], intval($v['cost'])) !== null);
    if ($ability !== null && $fodderOnBoard) return $ability;
    $leader = GetLeader($seat)[0] ?? null;
    $leaderReady = $leader !== null && (!isset($leader->Ready) || (strval($leader->Ready) !== 'false' && $leader->Ready !== false && strval($leader->Ready) !== '0'));
    if (!$leaderReady || $fodderOnBoard) return null;
    $best = null; $bestRank = PHP_INT_MAX;
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) continue;
        $cid = strval($o->CardID);
        if (!str_contains(strval(CardType($cid)), 'Unit')) continue;
        $r = _SWUBotFodderRank($cid, intval(CardCost($cid)));
        if ($r !== null && $r < $bestRank) { $bestRank = $r; $best = $a; }
    }
    return $best;
}

// Can hand card $cid, once played, defeat the enemy unit $u? Read from PRINTED TEXT, for proposal 'initiative'.
// Covers the answer shapes in the pool: removal events (SWUBotRemovalClass — an uncapped defeat, or a printed cap),
// "deal N damage to … unit", a unit dealing "damage equal to her/his/its power" (+1 if it can give itself an
// Experience token — LAW_039 Latts Razzi), and "-N/-N". Honours "non-leader", "enemy", and a named arena.
// Damage is stopped by a Shield (CR 3.7.6); a defeat or a -N/-N is not. Conservative: anything else is false.
function SWUBotHandCardKills(string $cid, array $u): bool {
    $t = strval(CardText($cid));
    if ($t === '' || stripos($t, 'chooses') !== false) return false;
    if ($u['isLeader'] && stripos($t, 'non-leader') !== false) return false;
    if (stripos($t, 'space unit') !== false && $u['arena'] !== 'Space') return false;
    if (stripos($t, 'ground unit') !== false && $u['arena'] !== 'Ground') return false;
    [$cls, $kind, $n] = SWUBotRemovalClass($cid);
    if ($cls === 'bombkiller') return true;
    if ($cls === 'restricted') {
        $val = $kind === 'cost' ? $u['cost'] : ($kind === 'remaining' ? $u['remaining'] : $u['power']);
        if ($val > $n) return false;
        if (preg_match('/\bdefeat\b/i', $t)) return true;
    }
    if (preg_match('/-(\d+)\/-\d+/', $t, $m)) return intval($m[1]) >= $u['remaining'];
    if ($u['shields'] > 0) return false;
    if (preg_match('/deals? (\d+) damage to (a|an) (enemy )?(non-leader )?(ground |space )?unit/i', $t, $m)) return intval($m[1]) >= $u['remaining'];
    if (str_contains(strval(CardType($cid)), 'Unit') && preg_match('/damage equal to (her|his|its) power/i', $t)) {
        $p = intval(CardPower($cid)) + (stripos($t, 'Experience token to this unit') !== false ? 1 : 0);
        return $p >= $u['remaining'];
    }
    return false;
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
        'krennic-plan'             => 'SWUBotRuleKrennicPlan',           // proposal 'krennicplan' — inert unless "@try-krennicplan"
        'control-wipe'             => 'SWUBotRuleControlWipe',
        'initiative-for-answer'    => 'SWUBotRuleInitiativeForAnswer',   // proposal 'initiative' — inert unless "@try-initiative"
        'kill-first'               => 'SWUBotRuleKillFirst',             // proposal 'killfirst'
        'krennic-ramp'             => 'SWUBotRuleKrennicRamp',           // proposal 'krennicramp'
        'blocker-first'            => 'SWUBotRuleBlockerFirst',          // proposal 'blockerfirst'
        'free-kill'                => 'SWUBotRuleFreeKill',              // proposal 'freekill' — inert unless "@try-freekill"
        'shrink-first'             => 'SWUBotRuleShrinkFirst',           // proposal 'shrinkfirst' — inert unless "@try-shrinkfirst"
        'no-unused-attacks'        => 'SWUBotRuleNoUnusedAttacks',
        'nothing-left'             => 'SWUBotRuleNothingLeft',
        'decline-losing-ambush'    => 'SWUBotRuleDeclineLosingAmbush',
        'free-benefit'             => 'SWUBotRuleFreeBenefit',
        'exhaust-ready-enemies'    => 'SWUBotRuleExhaustReadyEnemies',
        'bank-credits'             => 'SWUBotRuleBankCredits',
    ];
}
