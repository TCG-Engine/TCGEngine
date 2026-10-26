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
    if (SeatCountForGame() > 2) return SWUBotRuleTwinSunsLethal($ctx);
    if (_SWUBotIsFreePlay($ctx)) {
        if (!SWUBotLethalNow($seat, $opp)) return null;
        // Feature 'lethalrace' (p34): not a lethal they can race in the gaps between my attacks — rules 4/5 decide instead.
        if (SWUBotFeatureOn('lethalrace') && !SWUBotLethalRaceSafe($seat, $opp)) return null;
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
    if (SWUBotOpponentCanGetSentinel($opp)) return null;
    // Feature 'chipfirst' (p64): the claim passes away every attack I have left this round. While a ready unit can still hit their
    // base, attack first — it readies for next round anyway and their base only gets lower; claim once the attacks are spent.
    if (SWUBotFeatureOn('chipfirst') && _SWUBotFind($ctx, function ($a) use ($seat) {
        if (SWUBotActionKind($a) !== 'attack') return false;
        $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
        return $att !== null && SWUBotAttackTargets($seat, $att)['base'];
    }) !== null) { _SWUBotFired('chipfirst', $seat); return null; }
    return $init;
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
    $seat = intval($ctx['seat']); $opp = SWUBotMostDangerousOpponent($seat);   // 2 seats: ctx['opp']
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

// 'epicwipe' (p37): a relevant UNBOUNDED wipe in hand that I cannot cast now but can through my base's "ignoring 1 of its … aspect
// penalties" Epic, and that is worth it: it defeats 2+ enemy units it can defeat, costs them more value than me, and stabilises (their
// clock after the wipe, from what survives it, is 3+ rounds and longer than now). ['index' => hand index, 'cid' => card] or null.
function _SWUBotWaiverUnlocksWipe(int $seat): ?array {
    if (!function_exists('_SWUCommonBaseWaivePenalty')) return null;
    $base = GetBase($seat)[0] ?? null;
    if ($base === null || !preg_match('/ignoring 1 of its/i', strval(CardText(strval($base->CardID ?? ''))))) return null;
    $cap = SWUTotalPaymentCapacity($seat); $opp = SWUBotOpponent($seat);
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!in_array('wipe', SWUBotCardTags($cid), true) || !_SWUBotWipeIsRelevant($seat, $cid)
            || preg_match('/with a total of \d+ or less/i', strval(CardText($cid)))) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $cap || $cost - min(_SWUCommonBaseWaivePenalty($seat, $cid), SWUAspectPenalty($seat, $cid)) > $cap) continue;
        $arenas = _SWUBotWipeArenas($cid);
        $dies = fn(array $v) => in_array($v['arena'], $arenas, true) && !SWUBotDefeatFizzles($v);   // a deployed leader dies too
        $killed = count(array_filter(SWUBotUnits($opp), $dies));
        [$mine, $theirs] = _SWUBotWipeLosses($seat, $cid);
        $left = 0;
        foreach (SWUBotUnits($opp) as $v) if (!$dies($v)) $left += SWUBotUnitBaseThreat($seat, $v);
        $after = $left > 0 ? max(1, intdiv(SWUBaseRemainingHp($seat) + $left - 1, $left)) : SWU_BOT_NO_CLOCK;
        if ($killed >= 2 && $theirs > $mine && SWUBotStabilises(SWUBotClock($opp, $seat), $after)) return ['index' => $i, 'cid' => $cid];
    }
    return null;
}

function SWUBotRuleControlWipe(array $ctx): ?array {
    // The control wing (rank 3-4): soft and hard control both plan wipes.
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    if (!function_exists('SWUBotLookaheadBest')) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    $byValue = SWUBotFeatureOn('wipegate');
    $uids = fn(int $p) => array_map(fn($v) => $v['uid'], SWUBotUnits($p));
    $valueOf = fn(int $p) => array_sum(array_map(fn($v) => SWUBotUnitValue($v), SWUBotUnits($p)));
    // Team Suns: "mine" is OUR side, so a wipe that clears the teammate's board pays for it. [$seat] elsewhere.
    $ours = SWUBotFriendlySeats($seat);
    $ourUids = function () use ($ours, $uids) { $o = []; foreach ($ours as $f) $o = array_merge($o, $uids($f)); return $o; };
    $ourValue = function () use ($ours, $valueOf) { $v = 0.0; foreach ($ours as $f) $v += $valueOf($f); return $v; };
    $mineBefore = $ourUids(); $theirsBefore = $uids($opp);
    if (!$byValue && empty($mineBefore)) return null;
    $vMine = $ourValue(); $vTheirs = $valueOf($opp);
    $clockBefore = SWUBotClock($opp, $seat);
    // Base damage each side's board threatens per round, before the wipe (for proposal 'wipethreat' below).
    $oppPotBefore = SWUBotBasePotential($opp, $seat, false);
    $myPotBefore = SWUBotBasePotential($seat, $opp, false);
    $read = function () use ($seat, $opp, $uids, $valueOf, $ourUids, $ourValue, $mineBefore, $theirsBefore, $vMine, $vTheirs) {
        $own = count(array_diff($mineBefore, $ourUids()));
        return ['ownLost' => $own, 'defeated' => $own + count(array_diff($theirsBefore, $uids($opp))),
                'net' => ($vTheirs - $valueOf($opp)) - ($vMine - $ourValue()), 'oppClock' => SWUBotClock($opp, $seat),
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
    // LEVER 'widewipe' (Custom/levers/controlread_widewipe.php; owner 2026-10-19 "when you see the opponent going wide, it is a good time to
    // wipe"): most wipe decisions are this rule's, so the lever's play-scorer bonus alone fired once in 132 smoke games. With the proposal on,
    // a wipe also qualifies when it defeats 3+ of their units, at least 2 more of theirs than mine, and more of their value than mine.
    $wideGate = SWUBotFeatureOn('widewipe')   // SHIPPED 2026-10-19 (p56)
        ? fn(array $r) => ($r['defeated'] - $r['ownLost']) >= 3 && ($r['defeated'] - 2 * $r['ownLost']) >= 2 && $r['net'] > 0
        : fn(array $r) => false;
    $qualifies = fn(array $r) => $countGate($r) || $threatGate($r) || $wideGate($r);
    $score = fn(array $r) => $qualifies($r) ? 1000.0 + $r['oppClock'] * 10 + ($byValue ? $r['net'] : -$r['ownLost']) : -1.0;
    // Feature 'wipeaware' (p30): a wipe that leaves me in a SHOWN per-unit wipe's lethal range — Pre Vizsla's tokens — is
    // not this rule's to play (owner's game 1483356 R19); the fallback holds it the same way.
    $per = SWUBotFeatureOn('wipeaware') ? _SWUBotShownPerUnitWipe($seat) : 0;
    $isWipe = function (array $a) use ($seat, $per) {
        if (SWUBotActionKind($a) !== 'play') return false;
        $o = _SWUBotHandObject($seat, $a);
        if ($o === null) return false;
        $tags = SWUBotCardTags(strval($o->CardID));
        // FEATURE 'gloom' (p44) (owner 2026-10-12): "Give each enemy unit -N/-N" (Lawbringer, Bo-Katan SEC_051, Disruptive Burst) is a gloom, not a
        // wipe — "they might wipe, but it's not guaranteed unless the opponent has 2 or 3 hp units". It is a candidate here, and the lookahead's
        // gates below (2+ defeated, stabilises) decide whether THIS board makes it one. Krennic Splash: Lawbringer by R6 vs aggro 6-0.
        $gloom = SWUBotFeatureOn('gloom') && in_array('debuff-all-enemy-units', $tags, true);
        if (!in_array('wipe', $tags, true) && !$gloom) return false;
        return !($per > 0 && _SWUBotPlayEntersWipeRange($seat, $a, $per));
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
    $ok = function (array $r) use ($countGate, $threatGate, $wideGate, $seat, $diag) {
        $c = $countGate($r); $t = $threatGate($r);
        if ($diag && $t && !$c) SWUBotRecordCoverage($seat, 'wipe:threat-only');
        $w = $wideGate($r);
        if ($w && !$c && !$t) _SWUBotFired('widewipe', $seat);
        return $c || $t || $w;
    };
    $line = _SWUBotBestLine($ctx, $read, $score, $ok, $isWipe);
    if ($line !== null && SWUBotFeatureOn('gloom') && ($go = _SWUBotHandObject($seat, $line)) !== null
        && !in_array('wipe', SWUBotCardTags(strval($go->CardID)), true)) _SWUBotFired('gloom', $seat);
    if ($line !== null || !SWUBotFeatureOn('epicwipe')) return $line;
    // Feature 'epicwipe' (p37): no castable wipe qualifies — one castable only THROUGH the base's aspect-waiver Epic may (Krennic Splash:
    // Single Reactor Ignition 10 -> 8 with the Daimyo's Palace waiver; owner "the 5R turn + 3 credits or 6R + 2C"). Judged on the board
    // by the same tests (2+ enemy units defeated, more of their value than mine, stabilises), not by the lookahead: its line through the
    // Epic's prompt stored a plan that played the WRONG card there. Left alone, the fallback plays the wipe at the prompt (with Rey also
    // unlocked too — bot_epicwipe_test B2).
    $epic = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'base-epic');
    // Against an aggro leader, only on (or after) their FLIP turn — owner follow-up 2026-10-06: "wipe on HER flip turn only". Before it, a
    // round-4/5 wipe into a half board fired in 12 of 20 traced games and was null: she rebuilt 4–6 units the next round. (Facing lethal,
    // rule 4 'break-lethal' already plays whatever saves the game.)
    $flipped = !empty(array_filter(SWUBotUnits($opp), fn($v) => $v['isLeader']));
    if (SWUBotOpponentIsAggroLeader($seat) && !$flipped) return null;
    return ($epic !== null && _SWUBotWaiverUnlocksWipe($seat) !== null) ? $epic : null;
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
// 2026-10-11 Krennic workshop, proposal 'claimbomb' — the owner's version of this rule (Q2): "in a control deck i sometimes claim early if i
// have removal in hand and they just played a bomb and i know they won't play anything else or attack with anything else." The proposal
// above lost three times; it never asked whether the opponent was DONE, so its claim gave up a round the opponent kept using. Under
// 'claimbomb' (and not 'initiative'): only while the opponent is spent (_SWUBotOpponentSpent), only for a bomb (printed cost >= 5), and
// whichever seat holds the still-unclaimed initiative — a spent opponent can still take it, which is why the owner claims early.
const SWU_BOT_CLAIM_BOMB_COST = 5;
function SWUBotRuleInitiativeForAnswer(array $ctx): ?array {
    $bombOnly = SWUBotProposalOn('claimbomb') && !SWUBotProposalOn('initiative');
    if ((!SWUBotProposalOn('initiative') && !$bombOnly) || !_SWUBotIsFreePlay($ctx)) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3) return null;
    $init = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'initiative');
    if ($init === null) return null;
    $counter = strval(GetInitiativeCounter() ?? '');
    // (An already-CLAIMED initiative needs no check: the engine stops offering the action, so $init is null above.)
    if ($bombOnly) {
        if (!_SWUBotOpponentSpent($seat)) return null;
    } elseif ($counter !== 'P' . $opp . '_UNCLAIMED') return null;
    $W = SWUBotWeights(strval($ctx['style']), $seat);
    $capNow = SWUTotalPaymentCapacity($seat);
    $capNext = SWUResourceCount($seat) + 1 + count(SWUUsableCreditTokenMzIDs($seat));
    $threat = 0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $capNow || $cost > $capNext) continue;
        foreach (SWUBotUnits($opp) as $u) {
            if ($bombOnly && intval($u['cost']) < SWU_BOT_CLAIM_BOMB_COST) continue;   // 'claimbomb': their bomb, not any threat
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
        if (!SWUBotIsEnemyMz($seat, $c) || str_contains($c, 'Base')) continue;
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
// PROPOSAL 'mgremoval' (default OFF, "@try-mgremoval") — owner ruling 2026-09-23 (3). The midrange seat gets the
// same rule with a DIFFERENT bar: "same shape as shrinkfirst, but the target must have COST 5+ — usually best to
// remove a threat they just wasted resources on." So the filter is the enemy's INVESTMENT, not its power: a ready
// 5-drop is worth answering before it swings even at 3 power, and a cheap 4-power body is not worth a card.
// Cost is the cost THEY paid, so the aspect penalty is theirs — _SWUBotSeatCost is called for the opponent's seat.
const SWU_BOT_MG_REMOVAL_COST = 5;

// PROPOSAL 'mgsentinel' — the PLAY-ORDER half (the resourcing half is in BotResourcing.php, the "ahead of a
// bigger body" half is a fallback bonus in BotFallback.php). Owner rulings 2026-09-23, 2 and 4:
//   "Playing: priority against aggro — a Sentinel goes down ahead of a bigger non-Sentinel body."
//   "Board presence when behind: play a body before attacking ONLY for SENTINELS. Otherwise judge the board by
//    POWER, not by body count."
// This is the owner's CORRECTION of 'blockerfirst' above, which plays ANY unit before attacking whenever it is
// behind on BODIES. Two differences, both from the ruling: the body must be a Sentinel, and "behind" is measured
// in power. Midrange only, and only against an aggro leader — against control the Sentinel is not what is holding
// the game together, and the ruling says so.
function SWUBotRuleMgSentinel(array $ctx): ?array {
    if (!SWUBotProposalOn('mgsentinel') || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) !== 2) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (!SWUBotOpponentIsAggroLeader($seat)) return null;
    $power = fn(int $s) => array_sum(array_map(fn($u) => intval($u['attackPower']), SWUBotUnits($s)));
    if ($power($opp) <= $power($seat)) return null;                 // ruling 4: only while behind, and by POWER
    $hasAttack = false;
    foreach ($ctx['actions'] as $a) { if (SWUBotActionKind($a) === 'attack') { $hasAttack = true; break; } }
    if (!$hasAttack) return null;                                   // nothing to go before: the fallback plays it
    $plays = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = _SWUBotHandObject($seat, $a);
        if ($o !== null && _SWUBotHasPrintedSentinel(strval($o->CardID))) $plays[] = $a;
    }
    return empty($plays) ? null : SWUBotFallbackChoose(array_merge($ctx, ['actions' => $plays]));
}

function SWUBotRuleShrinkFirst(array $ctx): ?array {
    $mg = SWUBotProposalOn('mgremoval') && SWUBotStyleRank(strval($ctx['style'] ?? '')) === 2;
    if ((!SWUBotFeatureOn('shrinkfirst') && !SWUBotProposalOn('shrinkfirst2') && !$mg)
        || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $bar = SWUBotProposalOn('shrinkfirst2') ? 2 : SWU_BOT_THREAT_WORTH;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    // The CONTROL WING as measured: the archetype rank gates WHO uses the rule (soft/hard control — the only seats
    // that ever carried it in the one-sided arms), the racing rank keeps the measured behaviour WITHIN those seats
    // (a control seat that is racing drops below 3 and stops). Without the archetype gate, shipping would also switch
    // the rule on for a 'tempo'-flavoured MIDRANGE deck, whose racing rank reaches 3 — unmeasured behaviour: the
    // ship check caught exactly that (mid 104/126 until the opponent was held at @no-p6, then 126/126).
    // PROPOSAL 'shrinkfirstall' (default OFF) lifts the control-wing gate: does the shipped p6 rule help every
    // archetype? It was only ever measured on control seats.
    if (!$mg && !SWUBotProposalOn('shrinkfirstall')
        && (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3)) return null;
    if (!function_exists('SWUBotLookaheadBest')) return null;
    // 'mgremoval': the bar is the target's COST, not its power (owner ruling 3).
    $threats = $mg
        ? array_values(array_filter(SWUBotUnits($opp), fn($u) => $u['ready']
              && _SWUBotSeatCost($opp, strval($u['cardID'])) >= SWU_BOT_MG_REMOVAL_COST))
        : array_values(array_filter(SWUBotUnits($opp), fn($u) => $u['ready'] && $u['attackPower'] >= $bar));
    if (empty($threats)) return null;
    // The line is worth taking when it removes the SMALLEST threat that qualified — under the cost bar a 5-drop
    // may well hit for 2, and the power bar would then refuse every line that answered it.
    if ($mg) $bar = max(1, min(array_map(fn($u) => intval($u['attackPower']), $threats)));
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

// Feature 'neelcombo' (p47) — the Neel combo. Owner, Ahsoka Yellow w Neel (2026-10-13): "this list should now try to get the 'Neel
// combo' on Turn 1. the absolute best opener is Neel plus Captain Tarpals. play Neel first, then Tarpals, and buff Tarpals for a free 4
// damage to base." ASH_248 Neel (When Played/On Attack: the next unit you play this phase with 1 or less power enters play ready) →
// HMW_254 Captain Tarpals (0 power, Raid 2) enters ready → ASH_009 Ahsoka's Action (+2/+0 to a unit with less power than a friendly
// unit) → 4 at the base.
// 'enablerfirst' (p10) only PRICES the order, and the go-wide guide outweighs it: baseline 2026-10-13 (1,610 round-1 games, 104 distinct
// hands holding both) — the combo landed in ~56; 21 played BD-1 as the payoff (3 damage), ~20 played a payoff BEFORE Neel (0), 4 had
// resourced a piece. So this is a hard order, like 'wall-first'. The payoff is the unit worth the most attack (power + Raid — Tarpals 2
// beats BD-1 1); a unit that enters ready on its own (HMW_208 Luke in round 1) gains nothing and is no payoff.
//   ROUND 1: Neel from hand, then the payoff. The buff and the swing are the fallback's (it already picks both).
//   LATER, a ready Neel in play (owner, same day): "attack with Neel first, and then play a <=1 printed Power unit … so that it comes in
//   ready. if there are no immediate threats, then Neel is also a good candidate to buff … so if the bot knows Neel can survive, it
//   should go for buff Neel, swing with Neel, play a 1-power unit to ready and attack with it. another line … if Neel won't survive the
//   buff is to just swing with Neel, and then play the weak unit. and then buff that ready unit."
//     Line A (Neel SAFE): Ahsoka's Action on Neel → Neel swings → the payoff (ready) → it swings.
//     Line B: Neel swings → the payoff (ready) → the fallback's buff + swing.
//   Both add the same +2; the line decides which unit sits buffed through the opponent's next action. "Safe" (my reading) = no READY
//   enemy unit in Neel's arena could defeat him with its attack (a Shield always saves him). An enemy SENTINEL in Neel's arena is the other
//   "immediate threat": his swing has to go into it, so the line stands down and the fallback plays the turn — unless he has SABOTEUR
//   (BD-1's grant; Bug Report game 1647080). A payoff that must attack into a Sentinel still enters ready, at a token value.
// Only under a leader whose Action finishes the line.
const SWU_BOT_NEEL_COMBO_LEADERS = ['ASH_009'];
function _SWUBotNeelGate(int $seat): bool {
    if (!SWUBotFeatureOn('neelcombo')) return false;
    $leader = GetLeader($seat)[0] ?? null;
    return $leader !== null && in_array(strval($leader->CardID ?? ''), SWU_BOT_NEEL_COMBO_LEADERS, true);
}
// The best payoff in hand for a "with $maxPower or less power enters play ready" grant, affordable out of $left: a hand index or null.
// Worth its attack (power + Raid), +2 when Ahsoka's buff can then reach it — less power than $friendMax, the strongest friendly unit
// that will be in play (seed s163: an A-Wing, 1 + Raid 1, tied Tarpals' 0 + Raid 2, was picked first, and could not take the buff
// under a 1-power Neel — 2 damage, not 4).
// A unit whose ready attack would have to go into an enemy Sentinel (no Saboteur of its own) reaches no base, but entering ready never
// costs anything, so it stays a payoff at a token 0.5 — any base-reaching payoff outranks it. Bug Report game 1647080: "since Neel has
// saboteur from BD-1, it should have attacked first before playing Luke" — a Moff Gideon Sentinel held the ground, and the old veto
// dropped Luke as a payoff, which switched the whole Neel line off.
function _SWUBotNeelPayoff(int $seat, int $maxPower, int $left, ?int $exclude = null, int $friendMax = -1): ?int {
    $payoff = null; $bestSwing = 0.0;
    foreach (GetHand($seat) as $j => $o) {
        if ($j === $exclude || $o === null || !empty($o->removed)) continue;
        $pid = strval($o->CardID ?? ''); $text = strval(CardText($pid));
        // A unit that enters ready ON ITS OWN gains nothing — but only while its condition holds: HMW_208 Luke Skywalker enters ready "while
        // it's the first round of the game", so from round 2 he is a payoff like any other (game 1647080).
        $selfReady = stripos($text, 'enters play ready') !== false
                     && !(preg_match('/first round of the game/i', $text) && intval(GetTurnNumber()) !== 1);
        if (!str_contains(strval(CardType($pid)), 'Unit') || intval(CardPower($pid)) > $maxPower || $selfReady) continue;
        if (intval(SWUComputePlayCost($seat, $o)) > $left) continue;
        // + an Experience token it gives itself On Attack (LAW_037 Han Solo, 1/1 Shielded: owner 2026-10-13, Ahsoka Blue — "the free 2
        // damage to base plus shielded 2/2"); it lands before damage.
        $swing = intval(CardPower($pid)) + (preg_match('/\bRaid (\d+)/i', $text, $r) ? intval($r[1]) : 0)
               + (preg_match('/On Attack:[^.]*Give an Experience token to this unit/i', $text) ? 1 : 0)
               + (intval(CardPower($pid)) < $friendMax ? 2 : 0);
        if (_SWUBotNeelSentinelIn($seat, _SWUBotPlayArena($pid)) && !preg_match('/\bSaboteur\b/', $text)) $swing = 0.5;
        if ($swing > $bestSwing) { $bestSwing = $swing; $payoff = $j; }
    }
    return $payoff;
}
// An enemy Sentinel in $arena (exhausted or not — Sentinel binds attacks either way): a unit there without Saboteur cannot reach the base.
// Smoke run 2026-10-13: Neel swung into an exhausted Captain Typho Sentinel and died (s02) — so NEEL'S swing stands down behind one
// unless he has Saboteur (LOF_191 BD-1 grants it; game 1647080).
function _SWUBotNeelSentinelIn(int $seat, string $arena): bool {
    foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['sentinel']) && $e['arena'] === $arena) return true;
    return false;
}
function _SWUBotNeelFriendMax(int $seat): int {
    $max = -1;
    foreach (SWUBotUnits($seat) as $v) $max = max($max, intval($v['power']));
    return $max;
}
// The hand half, as hand indices: ['enabler' => i|null, 'payoff' => j], or null. 'enabler' is null once the grant is ARMED this phase
// (Neel played or swung — any round), and then only the payoff must fit. Unarmed, it is round 1 only: Neel and the payoff from hand.
// $capacity defaults to what can be paid now; the opening resourcer passes the 2 resources it is about to make.
function _SWUBotNeelComboPlan(int $seat, ?int $capacity = null): ?array {
    if (!_SWUBotNeelGate($seat)) return null;
    $capacity ??= SWUTotalPaymentCapacity($seat);
    if (GlobalEffectCount($seat, 'SWU_ASH248_READY') > 0) {   // the engine's armed flag is ASH_248's own: 1 or less power
        $p = _SWUBotNeelPayoff($seat, 1, $capacity, null, _SWUBotNeelFriendMax($seat));
        return $p === null ? null : ['enabler' => null, 'payoff' => $p];
    }
    if (intval(GetTurnNumber()) !== 1) return null;
    $enabler = null; $maxPower = -1;
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed)) continue;
        if (!preg_match('/When Played[^.]*next unit you play this phase with (\d+) or less power enters play ready/i', strval(CardText(strval($o->CardID ?? ''))), $m)) continue;
        if ($enabler === null || intval(SWUComputePlayCost($seat, $o)) < intval(SWUComputePlayCost($seat, GetHand($seat)[$enabler]))) { $enabler = $i; $maxPower = intval($m[1]); }
    }
    if ($enabler === null) return null;
    $p = _SWUBotNeelPayoff($seat, $maxPower, $capacity - intval(SWUComputePlayCost($seat, GetHand($seat)[$enabler])), $enabler,
                           max(_SWUBotNeelFriendMax($seat), intval(CardPower(strval(GetHand($seat)[$enabler]->CardID ?? '')))));
    return $p === null ? null : ['enabler' => $enabler, 'payoff' => $p];
}
// The board half: a READY friendly unit whose ON ATTACK arms the grant, with an affordable payoff in hand to spend it on (the swing costs
// nothing, so the whole capacity is left). ['neel' => view, 'safe' => bool, 'buffable' => bool] or null. 'buffable' = Ahsoka's Action can
// target him (a friendly unit with more power).
function _SWUBotNeelSwingLine(int $seat): ?array {
    if (!_SWUBotNeelGate($seat) || GlobalEffectCount($seat, 'SWU_ASH248_READY') > 0) return null;
    $units = SWUBotUnits($seat);
    foreach ($units as $v) {
        if (!$v['ready'] || !preg_match('/On Attack[^.]*next unit you play this phase with (\d+) or less power enters play ready/i', strval(CardText($v['cardID'])), $m)) continue;
        if (empty($v['saboteur']) && _SWUBotNeelSentinelIn($seat, $v['arena'])) continue;
        if (_SWUBotNeelPayoff($seat, intval($m[1]), SWUTotalPaymentCapacity($seat), null, _SWUBotNeelFriendMax($seat)) === null) continue;
        $safe = intval($v['shields']) > 0;
        if (!$safe) {
            $safe = true;
            foreach (SWUBotEnemyUnits($seat) as $e) if ($e['ready'] && $e['arena'] === $v['arena'] && intval($e['attackPower']) >= intval($v['remaining'])) { $safe = false; break; }
        }
        $buffable = false;
        foreach ($units as $f) if ($f['uid'] !== $v['uid'] && intval($f['power']) > intval($v['power'])) { $buffable = true; break; }
        return ['neel' => $v, 'safe' => $safe, 'buffable' => $buffable];
    }
    return null;
}
// Ahsoka's buff target in Line A: Neel himself (the decision branch, BotFallback.php, gives this unit the pick). Null outside Line A.
function SWUBotNeelBuffTargetUid(int $seat): ?int {
    $line = _SWUBotNeelSwingLine($seat);
    return ($line !== null && $line['safe'] && $line['buffable']) ? intval($line['neel']['uid']) : null;
}
function SWUBotRuleNeelCombo(array $ctx): ?array {
    if (!_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $seat = intval($ctx['seat']);
    $plan = _SWUBotNeelComboPlan($seat);
    if ($plan !== null) {
        $want = 'myHand-' . ($plan['enabler'] ?? $plan['payoff']);
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) === 'play' && SWUBotActionMz($a) === $want) { _SWUBotFired('neelcombo', $seat); return $a; }
        }
        return null;
    }
    $line = _SWUBotNeelSwingLine($seat);
    if ($line === null) return null;
    if ($line['safe'] && $line['buffable']) {
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) === 'leader-ability') { _SWUBotFired('neelbuff', $seat); return $a; }
        }
    }
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'attack') continue;
        $v = SWUBotViewForMz($seat, SWUBotActionMz($a));
        if ($v !== null && $v['uid'] === $line['neel']['uid']) { _SWUBotFired($line['safe'] ? 'neelswing' : 'neelswingfirst', $seat); return $a; }
    }
    return null;
}

// Feature 'wallfirst' (p39) — control vs an aggro leader puts its Sentinel down FIRST in the early rounds. Owner, Krennic (LAW) Blue
// 2026-10-06: vs aggro the deck stabilises with "Sentinel wall + trades", and the opener is "hold for R2 Sentinel" (R2 Moff Gideon from
// 3 resources). Traced: with Gideon/Commando in hand going into R2, one was played on R2 in 16 of 38 aggro games — Latts Razzi went first.
// Rounds 1-4, no friendly Sentinel yet in that arena; only a play the fallback wants (score > 0), so its holds stand.
const SWU_BOT_WALL_FIRST_ROUNDS = 4;
function SWUBotRuleWallFirst(array $ctx): ?array {
    if (!SWUBotFeatureOn('wallfirst') || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    // PROPOSAL 'rewall' (2026-10-14): through round 6 — Ahsoka's base damage spiked in R4-R5, 81% of it with no Krennic Sentinel up.
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < 3 || intval(GetTurnNumber()) > (SWUBotProposalOn('rewall') ? 6 : SWU_BOT_WALL_FIRST_ROUNDS)) return null;
    $seat = intval($ctx['seat']);
    if (!SWUBotOpponentIsAggroLeader($seat)) return null;
    $guarded = [];
    foreach (SWUBotUnits($seat) as $u) if (!empty($u['sentinel'])) $guarded[$u['arena']] = true;
    // Feature 'wallarena' (p43; 2026-10-07 gap screen): against a SPACE-flavoured deck, a wall stands only in an arena the opponent is in — a
    // ground Commando played into four Vader ships blocks nothing (Aurra s012 R4; diagnosis .claude/tmp/diag_vader).
    if (SWUBotFeatureOn('wallarena') && in_array('space', SWUBotDeckFlavours(intval($ctx['opp'])), true)) {
        $occupied = [];
        foreach (SWUBotUnits(intval($ctx['opp'])) as $u) $occupied[$u['arena']] = true;
        foreach (['Ground', 'Space'] as $ar) if (empty($occupied[$ar])) $guarded[$ar] = true;   // nothing to wall off there
    }
    $plays = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = _SWUBotHandObject($seat, $a);
        $cid = $o !== null ? strval($o->CardID) : '';
        // Feature 'wallstack' (p42): HARD CONTROL keeps stacking — a Sentinel goes down even where one already stands (owner, Mando vs
        // aggro 2026-10-09: "sentinel up most turns. T2 Loth wolf · T3 another Loth wolf or droid laser turret · … Zeb for 5R").
        // Only in an arena the opponent is IN: against space ("early let's call it wars, direct hit on 5R … survive … HSD") a second ground
        // wall blocks nothing.
        $stack = SWUBotFeatureOn('wallstack') && SWUBotStyleRank(strval($ctx['style'] ?? '')) >= 4 && $cid !== ''
                 && (bool)array_filter(SWUBotUnits(intval($ctx['opp'])), fn($u) => $u['arena'] === _SWUBotPlayArena($cid));
        if ($cid !== '' && str_contains(strval(CardType($cid)), 'Unit') && _SWUBotHasPrintedSentinel($cid) && ($stack || empty($guarded[_SWUBotPlayArena($cid)]))) $plays[] = $a;
    }
    if (empty($plays)) return null;
    $sub = array_merge($ctx, ['actions' => $plays]); $sub['_guides'] = _SWUBotGuides($sub);
    $best = null; $bestScore = 0.0;
    foreach ($plays as $j => $a) {
        $sc = SWUBotScoreAction($sub, $a, $j);
        if ($sc > $bestScore) { $best = $a; $bestScore = $sc; }
    }
    return $best;
}

// PROPOSAL 'blockerfirst' (default OFF) — the loss-mining signature: in games control loses it is 1.4 units behind
// by round 3 and 2.3 by round 5 (2026-09-19). While behind on bodies, put one down BEFORE attacking; the attack is
// still available afterwards, the body is not (a removal spell in their turn takes the play away).
function SWUBotRuleBlockerFirst(array $ctx): ?array {
    // PROMOTED 2026-10-05 to a shipped feature (p37, every style) — owner: "promote it for all. hyperaggro may not even play
    // sentinels." Fidelity screen: four styles toward the real table (softcontrol +2.5, softaggro +2.3, midrange +1.9, hardcontrol
    // +1.2 pp). '@try-blockerfirst' still names it; '@no-blockerfirst' switches it off.
    if (!(SWUBotProposalOn('blockerfirst') || SWUBotFeatureOn('blockerfirst')) || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (count(SWUBotUnits($seat)) >= count(SWUBotUnits($opp))) return null;
    // FEATURE 'removalfirst' (p53, 2026-10-17, G1-07): the removal 'shrink-first' would cast on a READY 3+ power enemy goes before the body — this
    // rule was promoted ahead of it, so Mando Colossus put down ground bodies three rounds running while a 7-power ship hit the base.
    if (SWUBotFeatureOn('removalfirst') && ($sf = SWUBotRuleShrinkFirst($ctx)) !== null) { _SWUBotFired('removalfirst', $seat); return $sf; }
    $hasAttack = false;
    foreach ($ctx['actions'] as $a) { if (SWUBotActionKind($a) === 'attack') { $hasAttack = true; break; } }
    if (!$hasAttack) return null;
    $plays = [];
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = _SWUBotHandObject($seat, $a);
        if ($o !== null && str_contains(strval(CardType(strval($o->CardID))), 'Unit')) $plays[] = $a;
    }
    // The fallback's own pick among the unit plays (guides computed ONCE, each play scored once), and only a body it WANTS: one it
    // holds (<= 0 — a shown wipe, a unique clash, a fodder wait…) stays held, or this rule overrode every hold (bot_wipeaware_test F:
    // a Pre Vizsla held for a shown wipe was played into it). Scoring every play twice cost ~20% per round (measured 2026-10-05).
    $sub = array_merge($ctx, ['actions' => $plays]); $sub['_guides'] = _SWUBotGuides($sub);
    $best = null; $bestScore = 0.0;
    foreach ($plays as $j => $a) {
        $sc = SWUBotScoreAction($sub, $a, $j);
        if ($sc > $bestScore) { $best = $a; $bestScore = $sc; }
    }
    // Feature 'playdefeatwd' (p45): Maul's "play it, then defeat it" on a unit whose When Defeated kills their bodies now beats a body of
    // mine (game 1647080: Snub Fighter Squadron over The Legacy Run's 6 divided onto Disposable B1 + Supreme Council Aide) — the fallback picks.
    if ($best !== null && SWUBotFeatureOn('playdefeatwd') && _SWUBotPlayDefeatKillBeats($ctx, $bestScore)) return null;
    return $best;
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

// PROPOSALS 'mercramp' + 'krennicmerc' (2026-10-14, helpers in BotCreditEngine.php) — the owner's two Expendable Mercenary lines, while the
// Credit leader is on its front side, ready, and resources are few (SWU_BOT_FODDER_KEEP_UNTIL_RES):
//   1. a Mercenary in play is cashed in now — the Action takes it (its sacrifice price is the lowest: it comes back as a resource);
//   2. a castable Mercenary is played (Credits pay the shortfall — rule 'bank-credits');
//   3. round 1 with a Mercenary in hand sets up round 2: 'krennicmerc' plays the discount engine (JTL_032, Mercenary then costs 3 from 3
//      resources); 'mercramp' plays a weenie and sacrifices it (round 2 then has 3 resources + 1 Credit).
function SWUBotRuleMercLine(array $ctx): ?array {
    $ramp = SWUBotProposalOn('mercramp'); $kmerc = SWUBotProposalOn('krennicmerc');
    if (!($ramp || $kmerc) || !_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return null;
    $seat = intval($ctx['seat']);
    $l = SWUBotCreditSacLeaderFront($seat);
    if ($l === null || !_SWUBotLeaderReady($l) || SWUResourceCount($seat) >= SWU_BOT_FODDER_KEEP_UNTIL_RES) return null;
    $name = $kmerc ? 'krennicmerc' : 'mercramp';
    $ability = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'leader-ability');
    $units = array_filter(SWUBotUnits($seat), fn($v) => empty($v['isLeader']));
    if ($ability !== null && array_filter($units, fn($v) => SWUBotIsResourceLoopUnit($v['cardID']))) { _SWUBotFired("$name-sac", $seat); return $ability; }
    $playOf = function (callable $want) use ($ctx, $seat): ?array {
        $best = null; $bestCost = PHP_INT_MAX;
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) !== 'play') continue;
            $o = _SWUBotHandObject($seat, $a);
            $cid = $o === null ? '' : strval($o->CardID ?? '');
            if ($cid === '' || !$want($cid)) continue;
            $c = intval(CardCost($cid)) - (SWUBotIsCreditFodder($cid) ? 1 : 0);   // fodder first among equals
            if ($c < $bestCost) { $bestCost = $c; $best = $a; }
        }
        return $best;
    };
    if (($p = $playOf('SWUBotIsResourceLoopUnit')) !== null) { _SWUBotFired("$name-play", $seat); return $p; }
    if (intval(GetTurnNumber()) !== 1) return null;
    $mercInHand = false;
    foreach (GetHand($seat) as $o) if ($o !== null && empty($o->removed) && SWUBotIsResourceLoopUnit(strval($o->CardID ?? ''))) { $mercInHand = true; break; }
    if (!$mercInHand) return null;
    if ($kmerc) {
        if (array_filter($units, fn($v) => SWUBotIsDiscountEngine($v['cardID']))) return null;
        if (($p = $playOf('SWUBotIsDiscountEngine')) !== null) { _SWUBotFired('krennicmerc-engine', $seat); return $p; }
        return null;
    }
    if ($ability !== null && array_filter($units, fn($v) => SWUBotIsWeenie($v['cardID']))) { _SWUBotFired('mercramp-r1sac', $seat); return $ability; }
    if (($p = $playOf('SWUBotIsWeenie')) !== null) { _SWUBotFired('mercramp-weenie', $seat); return $p; }
    return null;
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
    $wd = stripos(strval(CardText($cid)), 'When Defeated') !== false;
    if ($cost <= 2 || $wd) return 1 + $cost;
    return null;
}

function _SWUBotIsHSD(string $cid): bool {
    return (bool)preg_match('/^Defeat all space units/i', strval(CardText($cid)));
}

// K1 + K3, as a filter (runs after the style filter, before the rules): drop the plays that would spend a Credit on a
// card under 7, and hold the leader's sacrifice while a friendly unit can still attack or a Mercenary can still be
// played with ready resources.
// Is a mass defeat in hand that the base's aspect waiver has not yet made affordable? Then the waiver is being
// saved for it. Returns false once the wipe IS castable through the waiver (so T5 can spend it) and false when no
// wipe is in hand at all.
function _SWUBotKrennicHoldsWaiverForWipe(int $seat): bool {
    if (!function_exists('_SWUCommonBaseWaivePenalty')) return false;
    $cap = SWUTotalPaymentCapacity($seat);
    $holding = false;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!array_intersect(SWUBotCardTags($cid), ['defeat-wipe-enemy', 'damage-wipe-enemy'])) continue;
        $eff = max(0, intval(SWUComputePlayCost($seat, $o)) - min(_SWUCommonBaseWaivePenalty($seat, $cid), SWUAspectPenalty($seat, $cid)));
        if ($eff <= $cap) return false;   // castable through the waiver NOW — spend it
        $holding = true;                  // in hand but not yet affordable — wait
    }
    return $holding;
}

function SWUBotKrennicPlanFilter(array $ctx): array {
    if (!_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN') return $ctx['actions'];
    // ⚠ THE BANKING HALF IS WHY THE SCRIPT COULD NOT REACH ROUND 5 WITH 2 CREDITS. K1 below is the only thing
    // that stops a Credit being cashed on a 2-drop, and it sat behind _SWUBotKrennicPlanOn's SPACE gate — so
    // against ground aggro the bot made ~26 Credits over 12 games and spent ~25 of them immediately, never
    // holding more than ONE in any round. Capacity at round 5 was 6R + 1C = 7 against a waived SRI cost of 8.
    // Proposal 'krennicscript' turns the same filter on without the space requirement (owner, 2026-09-28).
    $script = _SWUBotKrennicScriptApplies($ctx);
    if (!$script && !_SWUBotKrennicPlanOn($ctx)) return $ctx['actions'];
    $seat = intval($ctx['seat']);
    $bank = $script || _SWUBotKrennicPlanOn($ctx, 'bank'); $order = $script || _SWUBotKrennicPlanOn($ctx, 'order');
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
        // ⚠ RESERVE THE ONCE-PER-GAME WAIVER FOR THE WIPE. With banking on, capacity reaches 8 by round 5 — but
        // the waiver was still being spent in rounds 2 and 4 on some OTHER off-aspect card, so by the time SRI was
        // affordable at its waived 8 the waiver was gone and it cost 10 again. Traced: Epic Action at rounds
        // [4,4,2,2,4,4], SRI cast 0 times, despite capacity 8 at round 5.
        // Held only while a mass defeat is actually IN HAND — with nothing to save it for there is nothing to
        // reserve, so the waiver stays available for its ordinary use.
        if ($script && $k === 'base-epic' && _SWUBotKrennicHoldsWaiverForWipe($seat)) continue;
        $out[] = $a;
    }
    return $out;
}

// ── PROPOSAL 'krennicscript' — the owner's written game plan for Krennic Blue Splash vs an AGGRO deck ──────────
// Owner, 2026-09-28, after the 0/24 trace vs ahsoka-tano_ash_blue:
//   T1) play a unit that benefits from sac (like Ant Droid)
//   T2) play a Sentinel
//   T3) play any unit that benefits from sac — another Ant Droid or Expendable Merc
//   T4) keep playing defensively; value trades or removal
//   T5) after Ahsoka flips, play SRI using 6R + 2C or 7R + 1C
//
// ⚠ WHY THE EXISTING 'krennicplan' COULD NEVER DO THIS: _SWUBotKrennicPlanOn ends with
// `in_array('space', SWUBotDeckFlavours($opp)) || $ships >= 3` — the whole plan is scoped to a SPACE opponent
// (it was built for the Vader matchup and finishes on Hyperspace Disaster). Against ground go-wide it is inert by
// design, which is why `@try-krennicplan` changed the sweep by literally nothing.
// This is the same shape for ANY aggro opponent, finishing on a mass defeat instead of HSD.
//
// ⚠ T5's ARITHMETIC IS THE POINT. LAW_044 Single Reactor Ignition is printed 8 but costs 10 here (the deck is
// Vigilance base + Command/Villainy leader, so Aggression is off-aspect). 6R+2C and 7R+1C are both 8, so the line
// only exists THROUGH the base's aspect waiver — hence the pairing with 'aspectwaiver', which stops the waiver
// being burned in round 1 and makes its prompt pick the wipe.
// T4 is deliberately NOT implemented: "value trades or removal" is what the fallback scorer already does, and a
// rule that pre-empted it would be replacing a measured scorer with a guess.
// Does the owner's Krennic script apply to this seat? A Credit-sacrifice leader, still undeployed, facing an
// AGGRO leader — deliberately with NO space requirement, which is the gate that made 'krennicplan' inert here.
function _SWUBotKrennicScriptApplies(array $ctx): bool {
    if (!SWUBotProposalOn('krennicscript')) return false;
    $seat = intval($ctx['seat']);
    $leader = GetLeader($seat)[0] ?? null;
    if ($leader === null || !preg_match('/defeat a friendly unit\]:\s*Create a Credit token/i', strval(CardText(strval($leader->CardID ?? ''))))) return false;
    return function_exists('SWUBotOpponentIsAggroLeader') && SWUBotOpponentIsAggroLeader($seat);
}

function SWUBotRuleKrennicScript(array $ctx): ?array {
    if (!_SWUBotIsFreePlay($ctx) || strval(GetCurrentPhase()) !== 'MAIN' || !_SWUBotKrennicScriptApplies($ctx)) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);

    $cap = SWUTotalPaymentCapacity($seat);
    $mine = array_filter(SWUBotUnits($seat), fn($v) => !$v['isLeader']);

    // ── T5 — the finisher, once they have committed. Highest priority: it is the whole plan.
    // "After Ahsoka flips" = their leader is a deployed unit. A mass defeat is read off the tags-v3 enemy half.
    $theirLeaderOut = !empty(array_filter(SWUBotUnits($opp), fn($v) => $v['isLeader']));
    if ($theirLeaderOut) {
        $wipe = _SWUBotFind($ctx, function ($a) use ($seat, $cap) {
            if (SWUBotActionKind($a) !== 'play') return false;
            $o = _SWUBotHandObject($seat, $a);
            if ($o === null) return false;
            if (!array_intersect(SWUBotCardTags(strval($o->CardID)), ['defeat-wipe-enemy', 'damage-wipe-enemy'])) return false;
            return intval(SWUComputePlayCost($seat, $o)) <= $cap;          // castable right now
        });
        if ($wipe !== null) return $wipe;
        // Not castable at full price — the base's aspect waiver is what closes the gap (6R+2C / 7R+1C).
        $epic = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'base-epic');
        if ($epic !== null && function_exists('_SWUCommonBaseWaivePenalty')) {
            foreach (GetHand($seat) as $o) {
                if ($o === null || !empty($o->removed)) continue;
                $cid = strval($o->CardID ?? '');
                if (!array_intersect(SWUBotCardTags($cid), ['defeat-wipe-enemy', 'damage-wipe-enemy'])) continue;
                $eff = max(0, intval(SWUComputePlayCost($seat, $o)) - min(_SWUCommonBaseWaivePenalty($seat, $cid), SWUAspectPenalty($seat, $cid)));
                if ($eff <= $cap) return $epic;
            }
        }
    }

    // ── T1 + T3 — two sac bodies on board before anything else. Best fodder rank first (the Mercenary is 0).
    $fodderOut = count(array_filter($mine, fn($v) => _SWUBotFodderRank($v['cardID'], intval($v['cost'])) !== null));
    if ($fodderOut < 2) {
        $best = null; $bestRank = PHP_INT_MAX;
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) !== 'play') continue;
            $o = _SWUBotHandObject($seat, $a);
            if ($o === null) continue;
            $cid = strval($o->CardID);
            $rank = _SWUBotFodderRank($cid, intval(CardCost($cid)));
            if ($rank === null || intval(SWUComputePlayCost($seat, $o)) > $cap) continue;
            if ($rank < $bestRank) { $bestRank = $rank; $best = $a; }
        }
        if ($best !== null) return $best;
    }

    // ── T2 — a Sentinel to slow the clock, once there is fodder to feed the leader.
    if (empty(array_filter($mine, fn($v) => !empty($v['sentinel'])))) {
        $best = null; $bestCost = PHP_INT_MAX;
        foreach ($ctx['actions'] as $a) {
            if (SWUBotActionKind($a) !== 'play') continue;
            $o = _SWUBotHandObject($seat, $a);
            if ($o === null) continue;
            $cid = strval($o->CardID);
            if (!preg_match('/^Sentinel\b/m', strval(CardText($cid)))) continue;
            $c = intval(SWUComputePlayCost($seat, $o));
            if ($c > $cap || $c >= $bestCost) continue;
            $bestCost = $c; $best = $a;
        }
        if ($best !== null) return $best;
    }
    return null;   // T4: leave trades and removal to the fallback scorer
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
    // 'defeatimmune' (p36): a defeat (not take-control-then-defeat) fizzles on a unit that can't be defeated by enemy abilities.
    if (preg_match('/\bdefeat\b/i', $t) && !preg_match('/take control/i', $t) && SWUBotDefeatFizzles($u)) return false;
    if (stripos($t, 'space unit') !== false && $u['arena'] !== 'Space') return false;
    if (stripos($t, 'ground unit') !== false && $u['arena'] !== 'Ground') return false;
    [$cls, $kind, $n] = SWUBotRemovalClass($cid);
    if ($cls === 'bombkiller') return true;
    if ($cls === 'restricted') {
        $val = $kind === 'cost' ? $u['cost'] : ($kind === 'remaining' ? $u['remaining'] : $u['power']);
        if ($val > $n) return false;
        if (preg_match('/\bdefeat\b/i', $t)) return true;
    }
    if (preg_match('/-(\d+)\/-\d+/', $t, $m)) return SWUBotDamageKills($u, intval($m[1]));
    if ($u['shields'] > 0) return false;
    if (preg_match('/deals? (\d+) damage to (a|an) (enemy )?(non-leader )?(ground |space )?unit/i', $t, $m)) return SWUBotDamageKills($u, intval($m[1]));
    if (str_contains(strval(CardType($cid)), 'Unit') && preg_match('/damage equal to (her|his|its) power/i', $t)) {
        $p = intval(CardPower($cid)) + (stripos($t, 'Experience token to this unit') !== false ? 1 : 0);
        return SWUBotDamageKills($u, $p);
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
    // Feature 'pilotchoice' (p42): a pilot leader's Unit/Pilot answer and host are the fallback's own rules (the audit: this rule answered
    // Unit 140 times with a ready Vehicle out) — the plan step is dropped and the fallback decides.
    if (SWUBotFeatureOn('pilotchoice') && in_array(strval($ctx['tooltip'] ?? ''), ['Deploy_as_Unit_or_Pilot?', 'Choose_a_Vehicle_to_deploy_onto'], true)
        && ($ctx['tooltip'] ?? '') === $step['tooltip']) { $GLOBALS['SWUBotPlan'][$seat] = $plan; return null; }
    $pick = (($ctx['kind'] ?? '') === 'decision' && ($ctx['tooltip'] ?? '') === $step['tooltip'])
        ? _SWUBotFind($ctx, fn($a) => strval($a['cardID'] ?? '') === $step['answer']) : null;
    if ($pick === null) { unset($GLOBALS['SWUBotPlan'][$seat]); return null; }
    // A step the lookahead could not tell apart ('tied', BotLookahead.php): the fallback chooses among the tied answers,
    // since the plan's 'answer' is only the first one listed. Game 1647080 gave a Weakness to the bot's own unit that way.
    $tied = (array)($step['tied'] ?? []);
    if (count($tied) > 1) {
        $among = array_values(array_filter($ctx['actions'], fn($a) => in_array(strval($a['cardID'] ?? ''), $tied, true)));
        if (count($among) > 1) $pick = SWUBotFallbackChoose(['actions' => $among] + $ctx) ?? $pick;
    }
    // Feature 'plantruncate' (p42): a step that left answers untried has not seen them — the fallback chooses among ALL of them.
    if (SWUBotFeatureOn('plantruncate') && !empty($step['truncated'])) $pick = SWUBotFallbackChoose($ctx) ?? $pick;
    // Team Suns: the lookahead reads clocks, and a teammate's board is on neither — so hitting (or stealing from) the teammate ties with
    // or beats hitting my own unit (self-play 2026-10-12: Traitorous took the teammate's unit). An answer naming a teammate's card goes
    // to the fallback, which prices the teammate (SWU_BOT_TEAMMATE_HARM). Inert outside a team game.
    if (preg_match_all('/\bp\d+[A-Za-z]+-\d+/', strval($step['answer'] ?? ''), $mm)
        && !empty(array_filter($mm[0], fn($m) => SWUBotIsTeammateMz($seat, $m)))) $pick = SWUBotFallbackChoose($ctx) ?? $pick;
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
        $v = SWUBotIsEnemyMz($seat, $c) ? SWUBotViewForMz($seat, $c) : null;
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
    foreach (SWUBotEnemyUnits($seat) as $v) { if (in_array($v['cardID'], SWU_BOT_CREDIT_PREDATORS, true)) { $preyed = true; break; } }
    $n = $preyed ? min($max, $cost) : min($max, max(0, $cost - SWUResourceCount($seat, true)));
    return _SWUBotFind($ctx, fn($a) => SWUBotSelectionCount($a) === $n);
}

// ── Features 'wipeinit' + 'wipedraw' (p36) — claim the initiative so next round's wipe goes FIRST ───────────────────────
// Owner 2026-10-04: "this can be for both HSD or SRI … it might be best to not let the opponent take initiative and get another
// 5+ damage in if you claim and wipe a space aggro deck"; and of Hemlock players claiming every round with no wipe in hand:
// "they claim in hopes of drawing a wipe". Ninin's games: claimed R5 -> Hyperspace Disaster R6 (vs Vader Yellow); claimed R6 ->
// Single Reactor Ignition R7 (vs Luke ASH Data Vault). The existing 'initiative-for-answer' could not see a wipe and counted only
// the single biggest threat; these count every enemy unit the wipe kills that reaches my base.

// The base damage the wipe's arenas threaten (SWUBotUnitBaseThreat: my Sentinels guard, Saboteurs ignore them).
function _SWUBotWipeThreat(int $seat, array $arenas): int {
    $t = 0;
    foreach (SWUBotEnemyUnits($seat) as $u) if (in_array($u['arena'], $arenas, true) && !SWUBotDefeatFizzles($u)) $t += SWUBotUnitBaseThreat($seat, $u);
    return $t;
}
// My resources next round: every resource readied + the regroup's resource + my Credits.
function _SWUBotCapNextRound(int $seat): int {
    return SWUResourceCount($seat) + 1 + count(SWUUsableCreditTokenMzIDs($seat));
}
// 'wipeinit': the best RELEVANT wipe in hand that is NOT castable now but IS next round, and costs them more than me.
function _SWUBotWipeNextRound(int $seat): ?array {
    $capNow = SWUTotalPaymentCapacity($seat); $capNext = _SWUBotCapNextRound($seat);
    $best = null;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!in_array('wipe', SWUBotCardTags($cid), true) || !_SWUBotWipeIsRelevant($seat, $cid)) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $capNow || $cost > $capNext) continue;
        [$mine, $theirs] = _SWUBotWipeLosses($seat, $cid);
        if ($theirs <= $mine) continue;
        $arenas = _SWUBotWipeArenas($cid); $threat = _SWUBotWipeThreatOf($seat, $cid);
        if ($best === null || $threat > $best['threat']) $best = ['cid' => $cid, 'arenas' => $arenas, 'threat' => $threat];
    }
    return $best;
}
// 'wipedraw': the chance of drawing a relevant wipe castable next round in the regroup's 2 draws, from the deck's CONTENTS (a
// player knows their list; the order is never read). Best p x threat over distinct wipes: ['cid', 'p', 'threat'], or null.
function _SWUBotWipeDrawChance(int $seat): ?array {
    $deck = array_values(array_filter(GetDeck($seat), fn($o) => $o !== null && empty($o->removed)));
    $d = count($deck);
    if ($d === 0) return null;
    $copies = [];
    foreach ($deck as $o) { $c = strval($o->CardID ?? ''); $copies[$c] = ($copies[$c] ?? 0) + 1; }
    $choose2 = fn(int $n) => $n < 2 ? 0 : $n * ($n - 1) / 2;
    $capNext = _SWUBotCapNextRound($seat); $best = null;
    foreach ($copies as $cid => $k) {
        if (!in_array('wipe', SWUBotCardTags($cid), true) || !_SWUBotWipeIsRelevant($seat, $cid) || _SWUBotSeatCost($seat, $cid) > $capNext) continue;
        [$mine, $theirs] = _SWUBotWipeLosses($seat, $cid);
        if ($theirs <= $mine) continue;
        $p = $d >= 2 ? 1.0 - $choose2($d - $k) / $choose2($d) : 1.0;
        $threat = _SWUBotWipeThreatOf($seat, $cid);
        if ($best === null || $p * $threat > $best['p'] * $best['threat']) $best = ['cid' => $cid, 'p' => $p, 'threat' => $threat];
    }
    return $best;
}

function SWUBotRuleInitiativeForWipe(array $ctx): ?array {
    $hand = SWUBotFeatureOn('wipeinit'); $draw = SWUBotFeatureOn('wipedraw');
    if ((!$hand && !$draw) || !_SWUBotIsFreePlay($ctx)) return null;
    $seat = intval($ctx['seat']); $opp = intval($ctx['opp']);
    if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3) return null;
    $init = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'initiative');
    if ($init === null || strval(GetInitiativeCounter() ?? '') !== 'P' . $opp . '_UNCLAIMED') return null;
    // '@try-claimlethal' (2026-10-07 gap screen): their ready attackers already have lethal this round — the wipe next round never comes
    // (Lando s007 R5 claimed at 9 HP into 10 ready damage; diagnosis .claude/tmp/diag_vader). Leave the round's plays to the other rules.
    if (SWUBotProposalOn('claimlethal') && SWUBotBasePotential($opp, $seat, true) >= SWUBaseRemainingHp($seat)) return null;
    // A wipe castable NOW needs no set-up: 'control-wipe' runs earlier, and its play is in the forgone sum below.
    $gain = 0.0;
    if ($hand && ($w = _SWUBotWipeNextRound($seat)) !== null) $gain = max($gain, floatval($w['threat']));
    if ($draw && ($w = _SWUBotWipeDrawChance($seat)) !== null) $gain = max($gain, $w['p'] * $w['threat']);
    if ($gain <= 0.0) return null;
    // What claiming forgoes: the rest of this round (every free attack + the best other play), guides held out — the same
    // accounting as 'initiative-for-answer'.
    $W = SWUBotWeights(strval($ctx['style']), $seat);
    $noGuides = array_merge($ctx, ['_guides' => ['attackFirst' => [], 'maxUnits' => '']]);
    $attacks = 0.0; $other = 0.0;
    foreach ($ctx['actions'] as $i => $a) {
        $k = SWUBotActionKind($a);
        if ($k === 'pass' || $k === 'initiative') continue;
        $s = max(0.0, SWUBotScoreAction($noGuides, $a, $i));
        if ($k === 'attack') $attacks += $s; else $other = max($other, $s);
    }
    return $W['base'] * $gain > $attacks + $other ? $init : null;
}

function SWUBotRulesBeforeFilter(): array {
    return ['single' => 'SWUBotRuleSingle', 'lethal-now' => 'SWUBotRuleLethalNow', 'ts-attack' => 'SWUBotRuleTwinSunsAttack',
            'planned-answer' => 'SWUBotRulePlannedAnswer'];
}

// ══ TWIN SUNS (3-4 seats) ATTACK POLICY ══════════════════════════════════════════════════════════════════════════
// SWUSim/docs/todo-twinsuns-fill-bot.md "Bot policy" (owner decisions 2026-10-01). Every rule here returns null at 2
// seats, so Arenabot is untouched. In priority order, each read per live opponent:
//   1. take a base kill only when it WINS (below); never make one that hands someone else the game;
//   2. a weak attacker (or a Saboteur) breaks a Shield, so the strong units keep their damage for the real hit;
//   3. otherwise a base attack goes to the HEALTHIEST enemy (the fallback's base tie-break, BotFallback.php).

// Would defeating $victim's base WIN for $seat? Free-for-all (CR 12.6.2, 12.7): the eliminator heals 5, and the game
// ends at the end of that phase with the highest remaining base HP winning (ties share, CR 12.7.3) — so only if my
// HP + 5 is at least every OTHER live seat's (owner Decision 1). Team Suns has no HP scoring and ends only when a whole
// team is gone, so an enemy kill always helps.
function SWUBotKillWins(int $seat, int $victim): bool {
    if (function_exists('SWUIsTeamGame') && SWUIsTeamGame()) return SWUIsEnemySeat($seat, $victim);
    $mine = SWUBaseRemainingHp($seat) + 5;
    foreach (GetLiveSeatsArray() as $s) {
        if ($s === $seat || $s === $victim) continue;
        if (SWUBaseRemainingHp($s) > $mine) return false;
    }
    return true;
}

// Team Suns: does killing $victim wipe its whole team (instant win)? Ranks it first among winning kills.
function _SWUBotKillWipesTeam(int $victim): bool {
    if (!function_exists('SWUIsTeamGame') || !SWUIsTeamGame()) return false;
    foreach (GetLiveSeatsArray() as $s) { if ($s !== $victim && SWUTeamOf($s) === SWUTeamOf($victim)) return false; }
    return true;
}

// Free play: commit to a kill that wins, with the strongest attacker that can reach that base. The 3-4 seat
// replacement for rule 2 (SWUBotRuleLethalNow), whose single-opponent read took the FIRST base it was offered.
function SWUBotRuleTwinSunsLethal(array $ctx): ?array {
    $seat = intval($ctx['seat']);
    if (!_SWUBotIsFreePlay($ctx)) return null;   // the target prompt is SWUBotRuleTwinSunsAttack's
    $victims = array_values(array_filter(SWUBotOpponents($seat), fn($o) => SWUBotLethalNow($seat, $o) && SWUBotKillWins($seat, $o)));
    if (empty($victims)) return null;
    $best = null; $bestPow = -1;
    foreach ($ctx['actions'] as $a) {
        if (SWUBotActionKind($a) !== 'attack') continue;
        $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
        if ($att === null || empty(array_intersect($victims, SWUBotAttackTargets($seat, $att)['bases'] ?? []))) continue;
        if ($att['attackPower'] > $bestPow) { $best = $a; $bestPow = $att['attackPower']; }
    }
    return $best;
}

// The target prompt's base candidates: [action, owner seat, remaining HP, does this attack defeat it].
function _SWUBotTargetBases(array $ctx, array $att): array {
    $seat = intval($ctx['seat']); $out = [];
    foreach ($ctx['actions'] as $a) {
        $c = strval($a['cardID'] ?? '');
        if (!str_contains($c, 'Base-')) continue;
        $owner = SWUMzOwner($c, $seat);
        $hp = SWUBaseRemainingHp($owner);
        $out[] = [$a, $owner, $hp, $att['attackPower'] >= $hp];
    }
    return $out;
}

// Is $att the weakest of my ready attackers (another, stronger one is still ready)? At the target prompt the attacker
// is already exhausted, so it is compared with what is still ready.
function _SWUBotIsWeakAttacker(int $seat, array $att): bool {
    $stronger = false;
    foreach (SWUBotUnits($seat) as $v) {
        if ($v['uid'] === $att['uid'] || !$v['ready']) continue;
        if ($v['attackPower'] < $att['attackPower']) return false;
        if ($v['attackPower'] > $att['attackPower']) $stronger = true;
    }
    return $stronger;
}

function SWUBotRuleTwinSunsAttack(array $ctx): ?array {
    if (SeatCountForGame() <= 2 || ($ctx['tooltip'] ?? '') !== 'Choose_an_attack_target') return null;
    $seat = intval($ctx['seat']);
    $attMz = SWUBotAttackerMz($ctx);
    $att = $attMz !== null ? SWUBotViewForMz($seat, $attMz) : null;
    if ($att === null) return null;
    // 1. A kill that wins — in Team Suns, one that wipes a whole team first.
    $wins = array_values(array_filter(_SWUBotTargetBases($ctx, $att), fn($b) => $b[3] && SWUBotKillWins($seat, $b[1])));
    if (!empty($wins)) {
        usort($wins, fn($x, $y) => [_SWUBotKillWipesTeam($y[1]), -$y[2]] <=> [_SWUBotKillWipesTeam($x[1]), -$x[2]]);
        return $wins[0][0];
    }
    // 2. A weak attacker or a Saboteur breaks the most valuable Shield it can reach.
    if ($att['saboteur'] || _SWUBotIsWeakAttacker($seat, $att)) {
        $best = null; $bestV = -1.0;
        foreach ($ctx['actions'] as $a) {
            $c = strval($a['cardID'] ?? '');
            if (str_contains($c, 'Base-') || !SWUBotIsEnemyMz($seat, $c)) continue;
            $v = SWUBotViewForMz($seat, $c);
            if ($v === null || $v['shields'] <= 0) continue;
            if (SWUBotUnitValue($v) > $bestV) { $bestV = SWUBotUnitValue($v); $best = $a; }
        }
        if ($best !== null) return $best;
    }
    return null;   // 3 is a tie-break in the fallback; the style filter drops a kill that would lose (below)
}

// For SWUBotStyleFilter: at 3-4 seats, the base candidates this attack would DEFEAT without winning — making that
// kill hands the game to whoever is then highest (owner Decision 1). Never the only option left.
function SWUBotLosingKillMzs(array $ctx, array $att): array {
    if (SeatCountForGame() <= 2) return [];
    $seat = intval($ctx['seat']); $out = [];
    foreach (_SWUBotTargetBases($ctx, $att) as [$a, $owner, $hp, $kills]) {
        if ($kills && !SWUBotKillWins($seat, $owner)) $out[] = strval($a['cardID'] ?? '');
    }
    return $out;
}

function SWUBotRulesAfterFilter(): array {
    return [
        'initiative-for-lethal'    => 'SWUBotRuleInitiativeForLethal',
        'break-lethal'             => 'SWUBotRuleBreakLethal',
        'neel-combo'               => 'SWUBotRuleNeelCombo',             // feature 'neelcombo' (p47) — round 1 Neel → Tarpals
        'krennic-script'          => 'SWUBotRuleKrennicScript',         // proposal 'krennicscript' — the owner's written curve
        'krennic-plan'             => 'SWUBotRuleKrennicPlan',           // proposal 'krennicplan' — inert unless "@try-krennicplan"
        'control-wipe'             => 'SWUBotRuleControlWipe',
        'initiative-for-answer'    => 'SWUBotRuleInitiativeForAnswer',   // proposal 'initiative' — inert unless "@try-initiative"
        'initiative-for-wipe'      => 'SWUBotRuleInitiativeForWipe',     // features 'wipeinit' + 'wipedraw' (p36)
        'kill-first'               => 'SWUBotRuleKillFirst',             // proposal 'killfirst'
        'krennic-ramp'             => 'SWUBotRuleKrennicRamp',           // proposal 'krennicramp'
        'merc-line'                => 'SWUBotRuleMercLine',              // proposals 'mercramp' + 'krennicmerc'
        'wall-first'               => 'SWUBotRuleWallFirst',             // feature 'wallfirst' (p39)
        'blocker-first'            => 'SWUBotRuleBlockerFirst',          // proposal 'blockerfirst'
        'mg-sentinel'              => 'SWUBotRuleMgSentinel',            // proposal 'mgsentinel' — blockerfirst, corrected
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
