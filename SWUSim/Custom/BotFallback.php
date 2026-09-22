<?php
// Layer 4 of the RL bots spec's decision stack — the FALLBACK SCORER. Only reached when no layer-2 rule
// answered. Scores every candidate with the per-style weights (SWUBotWeights) and the guides; the highest
// score wins, ties go to the lowest index. Deterministic: no RNG anywhere in the heuristic stack.
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("Layer 4", "The guides").

// What attacking $def (an enemy unit view) or the base ($def === null) with $att is worth.
// PROPOSAL 'leaderrisk' (default OFF): a deployed LEADER unit that is defeated RETURNS to its leader zone
// exhausted (memory `leader-units-are-defeated-then-return`) — the player loses a body for a round, not a card,
// so pricing its loss like a real unit makes the bot under-attack with leaders.
// PROPOSAL 'tradewhenbehind' (default OFF): while behind on units, an even trade is worth taking (owner Q10,
// "most of the time a 1-to-1 trade is good") — as a CONDITION, where the flat loss weight could not express it.
function _SWUBotLossFactor(array $att): float {
    $f = 1.0;
    if (SWUBotProposalOn('leaderrisk') && !empty($att['isLeader'])) $f *= 0.3;
    if (SWUBotProposalOn('tradewhenbehind') && function_exists('SWUBotUnits')) {
        $seat = intval($att['controller']);
        if (count(SWUBotUnits($seat)) < count(SWUBotUnits(SWUBotOpponent($seat)))) $f *= 0.5;
    }
    return $f;
}

function SWUBotTargetValue(array $att, ?array $def, array $W): float {
    if ($def === null) return $W['base'] * $att['attackPower'];
    $lossF = _SWUBotLossFactor($att);
    switch (SWUBotCombatOutcome($att, $def)) {
        case 'kill-survive':
            $v = $W['kill'] * SWUBotUnitValue($def);
            // The Overwhelm excess reaches the base — which also makes Aggro prefer the lowest-HP kill.
            if (SWUBotOverwhelmKills($att, $def)) $v += $W['base'] * ($att['attackPower'] - $def['remaining']);
            return $v;
        case 'trade':
            return $W['kill'] * SWUBotUnitValue($def) - $lossF * $W['loss'] * SWUBotUnitValue($att);
        case 'bounce':
            // Guide: pop a Shield with the smallest attacker.
            if ($def['shields'] > 0 && !$att['saboteur']) return $W['chip'] - 0.05 * $att['attackPower'];
            $dmg = min($att['attackPower'], $def['remaining']);
            return $W['chip'] * $dmg - ($def['grit'] ? $W['grit'] * $dmg : 0.0);   // guide: don't feed Grit
        default: // 'die'
            return -$lossF * $W['loss'] * SWUBotUnitValue($att);
    }
}

// A split-damage answer ("mz:a,mz:b", MZSPLITASSIGN), part by part: an enemy unit defeated is worth kill × its
// value, an enemy unit only damaged chip × the damage, the enemy base base × the damage; my own units and base
// cost the mirror (a lost unit loss × value; my base SWU_BOT_OWN_BASE_DAMAGE a point, whatever the style — the
// style weights say how much I want to HIT a base, not how much I can afford to take). A defeated unit on either
// side counts 0.5 more: a body on the board. $unpreventable (indirect damage, CR 35.3) ignores Shields; otherwise
// a Shield absorbs the whole instance. Feature 'splits'.
const SWU_BOT_OWN_BASE_DAMAGE = 0.5;
function _SWUBotSplitScore(int $seat, string $candidate, array $W, bool $unpreventable): float {
    $s = 0.0;
    foreach (explode(',', $candidate) as $pair) {
        $bits = explode(':', $pair);
        if (count($bits) < 2) continue;
        $mz = trim($bits[0]); $amt = intval($bits[1]);
        if ($amt <= 0) continue;
        $enemy = str_starts_with($mz, 'their');
        if (str_contains($mz, 'Base')) { $s += $enemy ? $W['base'] * $amt : -SWU_BOT_OWN_BASE_DAMAGE * $amt; continue; }
        $v = SWUBotViewForMz($seat, $mz);
        if ($v === null) continue;
        if (!$unpreventable && $v['shields'] > 0) continue;
        if ($amt >= $v['remaining']) $s += ($enemy ? $W['kill'] : -$W['loss']) * SWUBotUnitValue($v) + ($enemy ? 0.5 : -0.5);
        else $s += ($enemy ? 1 : -1) * $W['chip'] * $amt;
    }
    return $s;
}

// Continuations whose target is HURT (an enemy is the good pick) vs HELPED (a friendly is).
const SWU_BOT_HOSTILE_CONTINUATIONS    = ['DEFEAT_UNIT', 'DEAL_TARGET', 'BOUNCE_UNIT', 'GIVE_WEAKNESS', 'EXHAUST_UNIT'];
const SWU_BOT_BENEFICIAL_CONTINUATIONS = ['GIVE_EXPERIENCE', 'GIVE_SHIELD', 'GIVE_ADVANTAGE', 'HEAL_TARGET', 'READY_UNIT'];

function _SWUBotContinuationHead(array $ctx): string {
    return explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[0];
}

// A pending decision's attacker: the attack-target prompt's SWUResolveAttack param, or an Ambush's
// "SWUAmbushAnswer|{mz}|{targets}".
function _SWUBotDecisionAttacker(array $ctx): ?array {
    $mz = SWUBotAttackerMz($ctx);
    if ($mz === null) {
        foreach ((array)($ctx['following'] ?? []) as $p) {
            if (str_starts_with(strval($p), 'SWUAmbushAnswer|')) { $mz = explode('|', strval($p))[1] ?? null; break; }
        }
    }
    return $mz !== null ? SWUBotViewForMz(intval($ctx['seat']), $mz) : null;
}

function _SWUBotControlsReadyVehicle(int $seat): bool {
    foreach (SWUBotUnits($seat) as $v) { if ($v['ready'] && TraitContains($v['obj'], 'Vehicle')) return true; }
    return false;
}

// Choose_trigger_to_resolve: buffs before the attack — Ambush and Support wait. The LOF_231 Darth Tyranus
// exception (spec): a Shielded trigger whose unit's pending Ambush already has a kill-survive target without
// the Shield goes AFTER the Ambush, so the Shield is still there afterwards.
function _SWUBotTriggerScore(array $ctx, string $candidate): float {
    if (!preg_match('/EffectStack-(\d+)$/', $candidate, $m)) return 0.0;
    $stack = GetEffectStack();
    $e = $stack[intval($m[1])] ?? null;
    if ($e === null) return 0.0;
    $type = strval($e->TriggerType ?? '');
    if ($type === 'Ambush' || $type === 'Support') return 0.0;
    if ($type === 'Shielded') {
        $mz = explode('|', strval($e->Params ?? ''))[0];
        foreach ($stack as $o) {
            if ($o === $e || !empty($o->removed) || strval($o->TriggerType ?? '') !== 'Ambush') continue;
            if (intval($o->Controller) !== intval($e->Controller) || explode('|', strval($o->Params ?? ''))[0] !== $mz) continue;
            $att = SWUBotViewForMz(intval($ctx['seat']), $mz);
            if ($att === null) break;
            foreach (SWUBotAttackTargets(intval($ctx['seat']), $att)['units'] as $u) {
                if (SWUBotCombatOutcome($att, $u) === 'kill-survive') return -1.0;
            }
        }
    }
    return 1.0;
}


function SWUBotScoreAction(array $ctx, array $action, int $index): float {
    $seat = intval($ctx['seat']);
    $W = SWUBotWeights(strval($ctx['style']), $seat);
    $c = strval($action['cardID'] ?? '');

    if (($ctx['kind'] ?? '') !== 'decision') {
        switch (SWUBotActionKind($action)) {
            case 'pass':       return 0.0;
            case 'initiative': return $W['initiative'];
            case 'deploy':     return $W['deploy'] + (SWUBotFeatureOn('enablers') ? _SWUBotDeployDiscount($seat, $action, $W) : 0.0)
                                                  + (SWUBotFeatureOn('pilotdeploy') ? _SWUBotPilotDeployValue($seat, $action, $W) : 0.0)
                                                  + (SWUBotFeatureOn('plotdeploy') ? _SWUBotPlotDeployValue($seat, $W) : 0.0);
            case 'leader-ability': case 'unit-action': case 'base-epic': return _SWUBotAbilityValue($ctx, $action, $W);
            case 'attack':
                $att = SWUBotViewForMz($seat, SWUBotActionMz($action));
                if ($att === null) return 0.0;
                $best = null;
                foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
                    $tv = SWUBotTargetValue($att, $k === 'base' ? null : $u, $W);
                    $best = $best === null ? $tv : max($best, $tv);
                }
                $guides = $ctx['_guides'] ?? _SWUBotGuides($ctx);
                return ($best ?? 0.0) + (in_array(strval($action['cardID'] ?? ''), $guides['attackFirst'], true) ? $W['attackFirst'] : 0.0);
            case 'play':
                $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
                $obj = GetHand($seat)[$i] ?? null;
                if ($obj === null) return 0.0;
                $cid = strval($obj->CardID ?? '');
                // An effect event that would change nothing, or too little, on the enemy side is held (feature 'dudgate').
                if (SWUBotFeatureOn('dudgate') && _SWUBotIsEffectEvent($cid) && _SWUBotEventIsDud($seat, $action, $cid, $W)) return -0.5;
                // Any other event the ENGINE would log as "had no effect" (feature 'noeffect'; the Armorer fixture's
                // round-1 Reforge with no friendly upgrade to defeat).
                if (SWUBotFeatureOn('noeffect') && str_contains(strval(CardType($cid)), 'Event') && !_SWUBotIsEffectEvent($cid)
                    && _SWUBotEventHadNoEffect($seat, $action, $cid, $W)) return -0.5;
                // A play whose best line still helps the opponent is held (feature 'nogift'; Bug #1066, Perseverance
                // with only an enemy unit to heal and shield).
                if (SWUBotFeatureOn('nogift') && _SWUBotPlayIsGift($seat, $action, $cid, $W)) return -0.5;
                $v = _SWUBotPlayValue($seat, $cid, $W);
                // Play an enabler BEFORE the unit it improves (feature 'enablerfirst').
                if (SWUBotFeatureOn('enablerfirst')) $v += _SWUBotEnablerFirstBonus($seat, $action, $cid, $W);
                // PROPOSAL 'earlyremoval' (default OFF). _SWUBotPlayValue is TARGET-BLIND — a removal event scores
                // develop x cost + W['removal'] whether the best target is a 2-drop or a bomb. This gives it a target.
                // 'threatholdall' (default OFF) lifts the control-wing gate on the shipped p5 hold.
                if ((SWUBotProposalOn('earlyremoval') || SWUBotFeatureOn('threathold') || SWUBotProposalOn('restrictedearly'))
                    && (SWUBotStyleRank(strval($ctx['style'] ?? '')) >= 3 || SWUBotProposalOn('threatholdall'))) {
                    $held = _SWUBotEarlyRemovalAdjust($seat, $cid, $v, $W);
                    if ($held === null) return -0.5;
                    $v = $held;
                }
                // PROPOSAL 'holdanswers', play half (owner, Q2-D): "Barriss Offee will not be valuable here because they
                // most likely won't damage your ground units with their own units. and since you are not playing the
                // aggro, then you do not need Advantage tokens to race with." Control wing: a heal that targets a UNIT
                // earns nothing while none of my units is damaged, and Advantage tokens earn nothing while not racing.
                if (SWUBotProposalOn('holdanswers') && SWUBotStyleRank(strval($ctx['style'] ?? '')) >= 3) {
                    $t = strval(CardText($cid));
                    $tags = SWUBotCardTags($cid);
                    if (in_array('heal', $tags, true) && preg_match('/heal[^.]*from a unit/i', $t) && !preg_match('/base/i', $t)
                        && !array_filter(SWUBotUnits($seat), fn($u) => $u['remaining'] < $u['hp'])) $v -= $W['heal'];
                    if (in_array('buff', $tags, true) && stripos($t, 'Advantage token') !== false && !SWUBotIsRacing($seat, SWUBotOpponent($seat))) $v -= $W['buff'];
                }
                // PROPOSAL 'playsurvivor' (default OFF): prefer a body that SURVIVES the opponent's best attacker.
                // Control keeps trading fresh units away; a unit that dies to the first swing bought nothing.
                if (SWUBotProposalOn('playsurvivor') && str_contains(strval(CardType($cid)), 'Unit')) {
                    $hp = intval(CardHp($cid));
                    $worst = 0;
                    foreach (SWUBotUnits(SWUBotOpponent($seat)) as $e) $worst = max($worst, intval($e['attackPower']));
                    if ($hp > 0 && $worst >= $hp) $v -= 1.0;
                }
                // PROPOSAL 'sentineltiming' (default OFF): a Sentinel is played to guard the OPPONENT'S turn, so it
                // belongs at the END of my round — while I still have attacks to make, playing it early only exposes
                // it to removal. Held back while any attack is still on offer.
                if (SWUBotProposalOn('sentineltiming') && function_exists('_SWUBotHasPrintedSentinel') && _SWUBotHasPrintedSentinel($cid)) {
                    foreach ($ctx['actions'] as $other) { if (SWUBotActionKind($other) === 'attack') { $v -= 1.0; break; } }
                }
                // A Force card without the Force: its effect cannot happen — only the body counts (feature 'force').
                if (SWUBotFeatureOn('force') && function_exists('PlayerHasTheForce') && !PlayerHasTheForce($seat) && _SWUBotNeedsTheForce($cid)) {
                    $v = $W['develop'] * intval(CardCost($cid)) + (str_contains(strval(CardType($cid)), 'Unit') ? $W['unitPlay'] : 0.0);
                }
                // A second copy of a unique unit I control defeats one (the uniqueness rule) — feature 'picks'.
                if (SWUBotFeatureOn('picks') && _SWUBotUniqueClash($seat, $cid)) $v -= $W['develop'] * intval(CardCost($cid)) + 1.0;
                // …and over an UNDAMAGED copy it gains nothing: held (feature 'unique'; owner report 2026-09-14, Bot
                // Practice game 183227 — Sabine's Masterpiece played over a healthy one). A damaged copy is a heal.
                // A refinement of 'picks', so it needs 'picks' on too (bot_picks_test compares picks on/off).
                if (SWUBotFeatureOn('picks') && SWUBotFeatureOn('unique') && _SWUBotUniqueClash($seat, $cid) && _SWUBotUniqueCopyHealthy($seat, $cid)) return -0.5;
                $guides = $ctx['_guides'] ?? _SWUBotGuides($ctx);
                if ($guides['maxUnits'] === strval($action['cardID'] ?? '')) $v += $W['maxUnits'];
                return $v;
        }
        return -$index * 1e-6;
    }

    $tip = strval($ctx['tooltip'] ?? '');
    $type = strval($ctx['type'] ?? '');
    $head = _SWUBotContinuationHead($ctx);
    $debuff = $head === 'APPLY_PHASE_DEBUFF' && SWUBotFeatureOn('targeting');
    $buff = $head === 'APPLY_PHASE_BUFF' && SWUBotFeatureOn('buffs');   // "+N/+N for this phase" helps its target
    $effect = (in_array($head, SWU_BOT_HOSTILE_CONTINUATIONS, true) || $debuff) ? 'hostile'
            : ((in_array($head, SWU_BOT_BENEFICIAL_CONTINUATIONS, true) || $buff) ? 'beneficial' : _SWUBotTooltipEffect($tip));
    // A neutral prompt behind a card's own continuation ("ASH_052#0"): read that card's text. Picking a friendly unit
    // for a card that defeats is a sacrifice — ASH_052 Chimaera, "You may choose a friendly unit and an enemy
    // non-leader unit. If you do, defeat those units." A card that deals N damage or gives -N/-N is hostile.
    if ($effect === '' && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $m)) {
        if (stripos($tip, 'friendly') !== false && preg_match('/\bdefeat\b/i', strval(CardText($m[1])))) $effect = 'sacrifice';
        elseif (SWUBotFeatureOn('targeting')) $effect = _SWUBotCardTextEffect($m[1]);
    }
    $onBoard = (bool)preg_match('/^(my|their)(GroundArena|SpaceArena|Base)-/', $c);
    // "Defeat a friendly unit" as a cost: declining is worth giving up 1 point of unit value, so a MAY
    // sacrifice takes only a token, a cheap unit, or one whose When Defeated pays it back.
    if ($c === 'PASS') {
        if ($effect === 'sacrifice') return -1.0;
        return SWUBotAtResourceStop($ctx) ? $W['stopPass'] : 0.0;   // guide: the style's resource stop (rule 11)
    }
    // Split damage and the indirect player pick (feature 'splits'). A heal split is not damage.
    if ($type === 'MZSPLITASSIGN' && stripos($tip, 'damage') !== false && stripos($tip, 'heal') === false && SWUBotFeatureOn('splits')) {
        return _SWUBotSplitScore($seat, $c, $W, stripos($tip, 'indirect') !== false);
    }
    // A Weakness-token split (HMW_071 Ravage) is -1/-1 per token: scored as unpreventable damage, so the tokens go on
    // enemy units and never on mine (owner report 2026-09-15, Bot Practice game 189011 — Ravage defeated its own 0-0-0).
    if ($type === 'MZSPLITASSIGN' && stripos($tip, 'Weakness') !== false && SWUBotFeatureOn('splits')) {
        return _SWUBotSplitScore($seat, $c, $W, true);
    }
    if ($type === 'OPTIONCHOOSE' && $tip === 'Choose_a_player_to_deal_indirect_damage' && SWUBotFeatureOn('splits')) {
        return $c === 'You' ? -1.0 : $W['base'];
    }
    // PROPOSAL 'piettcheat' (default OFF): a discounted play-from-hand prompt (Piett's "Play_a_Capital_Ship_unit_(costs_1_less)")
    // plays the card worth most, not the first legal one.
    if (SWUBotProposalOn('piettcheat') && str_starts_with($tip, 'Play_a_') && str_starts_with($c, 'myHand-')) {
        $o = GetHand($seat)[intval(substr($c, strlen('myHand-')))] ?? null;
        if ($o !== null) return 0.5 + 0.01 * _SWUBotPlayValue($seat, strval($o->CardID ?? ''), $W);
    }
    // Guide: the opening two resources are the resourcing engine's two lowest keep values.
    if ($tip === 'Choose_2_cards_to_resource') return _SWUBotSameSelection($c, SWUBotChooseResourceCards($ctx, 2)) ? 1.0 : -$index * 1e-6;
    if ($effect === 'sacrifice' && $onBoard) {
        $v = str_starts_with($c, 'my') ? SWUBotViewForMz($seat, $c) : null;
        return $v === null ? -$index * 1e-6 : -SWUBotSacrificeCost($v);
    }

    if ($tip === 'Choose_an_attack_target' || $tip === 'Choose_Ambush_target') {
        $att = _SWUBotDecisionAttacker($ctx);
        if ($att === null) return -$index * 1e-6;
        if (str_contains($c, 'Base-')) return SWUBotTargetValue($att, null, $W);
        $def = SWUBotViewForMz($seat, $c);
        return $def === null ? -$index * 1e-6 : SWUBotTargetValue($att, $def, $W);
    }

    // A known continuation, or an unknown one whose prompt reads hostile / beneficial. Hurting only your own
    // units scores below PASS, so an optional "deal damage to a unit" with no enemy target is declined.
    if ($head === 'ATTACH_UPGRADE' && $onBoard && SWUBotFeatureOn('picks')) {
        $s = _SWUBotAttachScore($seat, $c, strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? ''), $W);
        return $s ?? -$index * 1e-6;
    }
    if ($type === 'TOPDECKSEARCH' && $tip === 'Search_top_cards' && SWUBotFeatureOn('picks')) return _SWUBotSearchScore($seat, $c, $W);
    $hostile = $effect === 'hostile';
    if (($hostile || $effect === 'beneficial') && $onBoard) {
        $s = _SWUBotTargetsScore($seat, $c, $hostile, _SWUBotEffectAmount($tip, strval(($ctx['following'] ?? [])[0] ?? '')), $head, $W);
        // PROPOSAL 'removalready' (default OFF): among enemy targets, prefer a READY one — an exhausted unit cannot
        // attack this round, so removing it saves nothing until the regroup. The same logic as the shipped
        // 'shrinkfirst' rule (p6), applied to every hostile target choice rather than to one play.
        if ($s !== null && $hostile && SWUBotProposalOn('removalready') && str_starts_with($c, 'their')) {
            $tv = SWUBotViewForMz($seat, $c);
            if ($tv !== null && !$tv['ready']) $s -= 0.5;
        }
        // A "+N/+0 for this phase" on an EXHAUSTED friendly unit expires unused — unless it is the unit attacking
        // right now (an On Attack buff lands before damage). Worth nothing, so any ready candidate outranks it
        // whatever the bodies' values (feature 'buffattack'; owner report 2026-09-21, Ahsoka ASH_009).
        if ($s !== null && $head === 'APPLY_PHASE_BUFF' && str_starts_with($c, 'my') && SWUBotFeatureOn('buffattack')
            && intval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[2] ?? 0) === 0) {
            $tv = SWUBotViewForMz($seat, $c);
            $att = _SWUBotDecisionAttacker($ctx);
            if ($tv !== null && !$tv['ready'] && ($att === null || $att['uid'] !== $tv['uid'])) $s = -$index * 1e-6;
        }
        return $s ?? -$index * 1e-6;
    }

    if ($type === 'YESNO') {
        // Mulligans are LEARNED (spec); the fallback keeps. Any other yes/no: take the optional effect.
        if (str_starts_with($tip, 'Take_a_mulligan')) {
            $mull = _SWUBotShouldMulligan($seat, strval($ctx['style'] ?? ''));
            if ($mull !== null) return $c === ($mull ? 'YES' : 'NO') ? 0.1 : 0.0;
            return $c === 'NO' ? 0.1 : 0.0;   // default: the bot has never mulliganed
        }
        // An optional draw that would deck me out first is declined (the deck-out guard).
        if (stripos($tip, 'draw') !== false && SWUBotDrawMultiplier($seat) < 0) return $c === 'NO' ? 0.1 : 0.0;
        // "Use the Force to …" something hostile with no enemy unit to hit: keep the Force (feature 'force').
        if (SWUBotFeatureOn('force') && stripos($tip, 'Use_the_Force') !== false && preg_match('/-\d+\/-\d+|deal|defeat/i', $tip)
            && empty(array_filter(SWUBotUnits(SWUBotOpponent($seat)), fn($v) => !$v['isLeader']))) return $c === 'NO' ? 0.1 : 0.0;
        return $c === 'YES' ? 0.1 : 0.0;
    }
    // A card's own modal choice (its continuation "CARD#n"): judged by what each option does (feature 'modes').
    if ($type === 'OPTIONCHOOSE' && SWUBotFeatureOn('modes') && strval($ctx['param'] ?? '') !== 'Unit&Pilot'
        && $tip !== 'Choose_a_player_to_deal_indirect_damage' && preg_match('/^[A-Z0-9]+_[A-Z0-9]+#/', $head)) {
        $s = _SWUBotOptionDelta($ctx, $action, $W);
        if ($s !== null) return $s - $index * 1e-6;
    }
    if ($type === 'OPTIONCHOOSE' && strval($ctx['param'] ?? '') === 'Unit&Pilot') {
        // Guide: a Pilot goes on a ready Vehicle.
        $want = _SWUBotControlsReadyVehicle($seat) ? 'Pilot' : 'Unit';
        return $c === $want ? 0.2 : 0.0;
    }
    if ($tip === 'Choose_trigger_to_resolve') return _SWUBotTriggerScore($ctx, $c);
    if ($tip === 'Resource_up_to_1_card') {
        // Every style keeps resourcing up to its stop (SWUBotAtResourceStop, a guide: owner ruling 2026-09-14).
        return $c === (SWUBotChooseResourceCards($ctx, 1)[0] ?? null) ? 1.0 : 0.0;
    }
    // Anything else: take it, in first-legal order. Every candidate scores above PASS (0), so an optional
    // "you may" is accepted by default. Owner: "typically it's beneficial"; the harmful cases are caught
    // above by the prompt's wording.
    return 0.01 - $index * 1e-6;
}

// What an unknown continuation's prompt does to its target, read from the raw tooltip: 'sacrifice'
// (defeat one of your own units as a cost), 'hostile', 'beneficial', or '' when the wording says neither.
function _SWUBotTooltipEffect(string $tooltip): string {
    $t = strtolower(str_replace('_', ' ', $tooltip));
    if (preg_match('/\bdefeat (a |an |another )?friendly\b/', $t)) return 'sacrifice';
    // "Give a unit -2/-2" is HOSTILE despite "give" (feature 'targeting'; Talzin debuffed its own units 229 times).
    if (SWUBotFeatureOn('targeting') && preg_match('/-\d+\/-\d+/', $t)) return 'hostile';
    if (preg_match('/\b(deal|damage|defeat|exhaust|capture)\b/', $t) || preg_match('/\breturn\b.*\bhand\b/', $t)) return 'hostile';
    if (preg_match('/\b(heal|give|ready|shield|experience|advantage|attach)\b/', $t)) return 'beneficial';
    return '';
}

// The damage (or HP reduction) a hostile prompt deals: "Deal_N_damage…", "…-P/-N…", or the APPLY_PHASE_DEBUFF
// continuation "APPLY_PHASE_DEBUFF|P|N|src". 0 = no amount (exhaust, bounce, defeat, capture, or unknown).
function _SWUBotEffectAmount(string $tip, string $next): int {
    if (preg_match('/deal_(\d+)_damage/i', $tip, $m)) return intval($m[1]);
    if (SWUBotFeatureOn('targeting2') && preg_match('/deal_(\d+)_to_/i', $tip, $m)) return intval($m[1]);   // "Deal_3_to_a_unit"
    if (preg_match('/-\d+\/-(\d+)/', $tip, $m)) return intval($m[1]);
    $p = explode('|', $next);
    return ($p[0] ?? '') === 'APPLY_PHASE_DEBUFF' ? intval($p[2] ?? 0) : 0;
}

// "If it costs N or less, defeat it" on the continuation's card (The Tree Remembers): N, else null. Feature 'targeting2'.
function _SWUBotDefeatIfCostAtMost(string $head): ?int {
    if (!preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $m)) return null;
    return preg_match('/if it costs (\d+) or less, defeat it/i', strval(CardText($m[1])), $c) ? intval($c[1]) : null;
}

// A neutral prompt ("Choose_a_unit") behind a card's own continuation: its printed text decides.
function _SWUBotCardTextEffect(string $cardID): string {
    $text = strval(CardText($cardID));
    if (preg_match('/deal \d+ damage to (a|an|another|that) (\w+ )?unit|-\d+\/-\d+/i', $text)) return 'hostile';
    // targeting2: "…If it costs N or less, defeat it." / "loses all abilities" are hostile too.
    if (SWUBotFeatureOn('targeting2') && preg_match('/\bdefeat it\b|loses all abilities/i', $text)) return 'hostile';
    return '';
}

// One on-board pick. An enemy unit hurt by a KNOWN amount: a defeat is worth the whole unit and more; a hit that
// does not defeat, the share of the unit it removes (feature 'targeting'). A Shield stops damage, not a -N/-N.
function _SWUBotTargetScore(int $seat, string $mz, bool $hostile, int $amount, string $head, array $W): ?float {
    $enemy = str_starts_with($mz, 'their');
    $good = $hostile ? $enemy : !$enemy;
    if (str_contains($mz, 'Base-')) return $good ? $W['base'] : -$W['base'];
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null) return null;
    $score = $good ? SWUBotUnitValue($v) : -SWUBotUnitValue($v);
    // A hostile effect aimed at MY OWN board is a sacrifice: price it as fodder, not as a loss of printed value
    // (feature 'fodder'), so the unit whose defeat pays me back is the one that goes.
    if (!$good && $hostile && !$enemy && SWUBotFeatureOn('fodder')) $score = -SWUBotSacrificeCost($v);
    // "If it costs N or less, defeat it": a unit it defeats is a kill; any other gets only the rider (feature 'targeting2').
    if ($hostile && $enemy && SWUBotFeatureOn('targeting2') && ($n = _SWUBotDefeatIfCostAtMost($head)) !== null) {
        return intval($v['cost']) <= $n ? SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0 : 0.1 * SWUBotUnitValue($v);
    }
    if ($hostile && $enemy && $amount > 0 && SWUBotFeatureOn('targeting')) {
        $blocked = !str_starts_with($head, 'APPLY_PHASE_DEBUFF') && $v['shields'] > 0;
        if ($blocked) $score = 0.0;
        elseif ($amount >= $v['remaining']) $score = SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0;
        elseif (SWUBotFeatureOn('setup') && !$v['isLeader'] && $v['remaining'] - $amount <= _SWUBotFinisherHP($seat))
            $score = 0.8 * (SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0);   // my finisher defeats it next
        else $score = SWUBotUnitValue($v) * $W['chip'] * $amount / max(1, $v['hp']);
    }
    // Exhaust a READY enemy; buffs go on units that attack this round (a ready friendly unit).
    if ($v['ready'] && (($hostile && $enemy && $head === 'EXHAUST_UNIT') || (!$hostile && !$enemy))) $score += $W['ready'];
    return $score;
}

// A candidate: one pick, or an '&'-joined multi-select scored as the sum of its picks (feature 'targeting').
function _SWUBotTargetsScore(int $seat, string $candidate, bool $hostile, int $amount, string $head, array $W): ?float {
    $parts = (str_contains($candidate, '&') && SWUBotFeatureOn('targeting')) ? explode('&', $candidate) : [$candidate];
    $sum = 0.0;
    foreach ($parts as $p) {
        $s = _SWUBotTargetScore($seat, $p, $hostile, $amount, $head, $W);
        if ($s === null) return null;
        $sum += $s;
    }
    return $sum;
}

// What losing one of my units costs: its value (cost + what dies with it), less what its When Defeated
// ability gives back. Owner: Krennic's sacrificial ramp spends cheap units with beneficial When Defeated.
function SWUBotSacrificeCost(array $v): float {
    $value = SWUBotUnitValue($v);
    $text = strval(CardText($v['cardID']));
    if (stripos($text, 'When Defeated') === false) return $value;
    if (!SWUBotFeatureOn('fodder')) return max(0.0, $value - 1.5);
    // Feature 'fodder': price the When Defeated by WHAT IT DOES, not a flat allowance. The flat 1.5 collapsed the
    // choice to printed cost, so the cheapest body always went — measured over 48 Krennic games, LAW_159 Expendable
    // Mercenary ("When Defeated: You may resource this unit") was played 40 times and sacrificed 0, while a 1-cost
    // Imperial Door Technician went 39/30. Owner 2026-09-15: sacrificing the Mercenary to Krennic is the deck's
    // ramp — "gaining an exhausted resource and a Credit".
    preg_match('/When Defeated:(.*)$/is', $text, $m);
    $wd = strval($m[1] ?? '');
    // The unit comes BACK (as a resource, into play, or to hand): defeating it is a gain, so it is the first fodder.
    if (preg_match('/\bresource this unit\b|\bplay this unit\b|\breturn this unit\b/i', $wd)) return -1.0;
    // It pays something back: heal, draw, or a replacement body.
    if (preg_match('/\bheal \d+|\bdraw (a card|\d+)|\bcreate \d+/i', $wd)) return max(0.0, $value - 2.5);
    return max(0.0, $value - 1.5);
}

// Owner's draw rule (2026-09-13): draw freely in the early and mid game, but watch the deck-out clock.
// An empty deck costs 3 damage per card not drawn, so 6 per regroup. Measured after drawing $k more, against
// the OPPONENT's deck — a Data Vault deck starts 10 cards deeper, so a long game there ends with me decking first.
//   1.0  — at least 6 regroups of cards left (12), even after allowing for any deficit vs the opponent
//   0.0  — enough cards for now, but the deficit vs the opponent eats the buffer: stop seeking extra draw
//   0.25 — under 12 cards, but the opponent decks out no later than me
//  -1.0  — under 12 cards and I deck out first: drawing only speeds up my own clock
function SWUBotDrawMultiplier(int $seat, int $k = 2): float {
    $live = fn($p) => count(array_filter(GetDeck($p), fn($o) => $o !== null && empty($o->removed)));
    $mine = $live($seat) - $k;
    $theirs = $live(SWUBotOpponent($seat));
    if ($mine >= 12 + max(0, $theirs - $mine)) return 1.0;
    if ($mine >= 12) return 0.0;
    return $mine >= $theirs ? 0.25 : -1.0;
}

// Feature 'bombtiming' (owner, 2026-09-17: commit a big unit "when it advances the clock").
// Diagnosis: hard control sees ~7 cards costing 6+ a game and plays 2.33 of them, and dealt only 21-24% of a kill
// across 6-8 rounds. Both conditions must hold, and condition 2 is what makes this "advances the clock" rather than
// merely "is safe": a 6-cost 2-power body that survives everything but does not move the clock earns nothing.
const SWU_BOT_BOMB_COST = 6;
function _SWUBotBombTimingValue(int $seat, string $cid, array $W): float {
    if (!SWUBotFeatureOn('bombtiming')) return 0.0;
    if (intval(CardCost($cid)) < SWU_BOT_BOMB_COST) return 0.0;
    if (!str_contains(strval(CardType($cid)), 'Unit')) return 0.0;
    $opp = SWUBotOpponent($seat);
    $arena = str_contains(strval(CardArena($cid)), 'Space') ? 'Space' : 'Ground';
    $power = intval(CardPower($cid)); $hp = intval(CardHp($cid));
    // A synthetic defender view: SWUBotCombatOutcome reads only shields / power / remaining from the defender.
    $bomb = ['shields' => 0, 'power' => $power, 'attackPower' => $power, 'remaining' => $hp, 'saboteur' => false];
    // 1. It survives: no single enemy unit in its arena defeats it in one attack.
    foreach (SWUBotUnits($opp) as $e) {
        if ($e['arena'] !== $arena) continue;
        if (in_array(SWUBotCombatOutcome($e, $bomb), ['kill-survive', 'trade'], true)) return 0.0;
    }
    // 2. It shortens the clock: recompute SWUBotClock's arithmetic with this unit's power added.
    $guarded = _SWUBotSentinelArenas($opp);
    $pot = SWUBotBasePotential($seat, $opp, false);
    $with = $pot + (($guarded[$arena] ?? false) ? 0 : $power);
    $hpLeft = SWUBaseRemainingHp($opp);
    $clockNow  = $pot  <= 0 ? SWU_BOT_NO_CLOCK : max(1, intdiv($hpLeft + $pot  - 1, $pot));
    $clockWith = $with <= 0 ? SWU_BOT_NO_CLOCK : max(1, intdiv($hpLeft + $with - 1, $with));
    if ($clockWith >= $clockNow) return 0.0;
    // Worth the same per-point rate the attack case uses, so it stays style-scaled and learnable.
    return $W['base'] * $power;
}

// What playing $cid is worth: develop × printed cost, its tag weights, and Aggro's bonus for a unit.
// PROPOSAL 'earlyremoval' — the PLAY half (the resourcing half is in BotResourcing.php). Owner ruling 2026-09-18
// (Q13/Q14). Returns the adjusted play value, or NULL to HOLD the card (the caller scores it -0.5, as the dud gate
// does).
//   bomb-killer + the best enemy target is cheap (cost <= 3) → HOLD it for a bomb, unless the opponent threatens
//     lethal next round (then survival beats saving it — owner, Q15: you hold only "if there is not immediate
//     threat to losing the game").
//   restricted + round <= 4 + a legal cheap target → +W['removal']: spend it now, it goes dead later.
// Cost 3 as "cheap" matches the restricted caps themselves (Crushing Blow 2, The Tree Remembers 3); round 4 is the
// owner's "5R turn … where aggro gets close to finishing the game" (round N = N+1 resources).
const SWU_BOT_CHEAP_TARGET_COST = 3;
const SWU_BOT_EARLY_REMOVAL_LAST_ROUND = 4;
// Three proposals share this hook (all default OFF):
//   'earlyremoval'    — the ORIGINAL, kept byte-for-byte so its measured −22 (p=0.011) stays reproducible:
//                       cost-only hold + restricted-early bonus.
//   'threathold'      — the THREAT-AWARE hold only (SWUBotShouldHoldBombKiller, BotFlavours.php), owner 2026-09-18.
//                       SHIPPED 2026-09-19: now a FEATURE (default ON, '@no-threathold'), not a proposal.
//   'restrictedearly' — the restricted-early bonus only (plus its late auto-resource half in BotResourcing.php):
//                       the split, to learn whether these halves were ever part of earlyremoval's loss.
function _SWUBotEarlyRemovalAdjust(int $seat, string $cid, float $v, array $W): ?float {
    [$cls] = SWUBotRemovalClass($cid);
    $notDying = !SWUBotLethalNextRound(SWUBotOpponent($seat), $seat);
    if ($cls === 'bombkiller') {
        if (SWUBotProposalOn('earlyremoval')) {
            $cheap = SWUBotBestEnemyTargetCost($seat, $cid) <= SWU_BOT_CHEAP_TARGET_COST;
            return ($cheap && $notDying) ? null : $v;
        }
        if (SWUBotFeatureOn('threathold')) {   // SHIPPED 2026-09-19 (feature group 'p5')
            return (SWUBotShouldHoldBombKiller($seat, $cid) && $notDying) ? null : $v;
        }
        return $v;
    }
    if ($cls === 'restricted' && (SWUBotProposalOn('earlyremoval') || SWUBotProposalOn('restrictedearly'))
        && intval(GetTurnNumber()) <= SWU_BOT_EARLY_REMOVAL_LAST_ROUND
        && SWUBotRestrictedRemovalHasTarget($seat, $cid)) {
        return $v + $W['removal'];
    }
    return $v;
}

function _SWUBotPlayValue(int $seat, string $cid, array $W): float {
    $v = $W['develop'] * intval(CardCost($cid));
    foreach (SWUBotCardTags($cid) as $t) $v += ($W[$t] ?? 0.0) * ($t === 'draw' ? SWUBotDrawMultiplier($seat) : 1.0);
    if (str_contains(strval(CardType($cid)), 'Unit')) $v += $W['unitPlay'];
    $v += _SWUBotBombTimingValue($seat, $cid, $W);
    return $v;
}


// An ENABLER — a card whose WHEN PLAYED text improves "the next unit you play this phase" — is worth what it adds
// to that next unit, so it is played BEFORE it (feature 'enablerfirst'; Bug Report game 1105765, ASH_248 Neel and
// HMW_254 Captain Tarpals). Two priced benefits, both read off the card text:
//   · "enters play ready" → the attack that unit can now make this phase, priced like any attack (W['base'] x power,
//     Raid included — a 0-power Tarpals with Raid 2 swings for 2);
//   · "costs N resource(s) less" → the resources saved (W['develop'] x N).
// Zero unless an ELIGIBLE payoff is in hand AND still affordable after this card is paid for — an unused grant is
// worth nothing, and promoting the enabler then would just reorder two plays for no reason. Other benefits
// ("gains Hidden / Shielded") are deliberately not priced: no number to price them with.
function _SWUBotEnablerFirstBonus(int $seat, array $action, string $cid, array $W): float {
    $text = strval(CardText($cid));
    // The grant must come from PLAYING this card (When Played). "On Attack" / "When Defeated" versions of the same
    // sentence say nothing about which card to play first.
    if (!preg_match('/When Played/i', $text)) return 0.0;
    if (!preg_match('/next unit you play this phase(.*?)\./is', $text, $m)) return 0.0;
    $clause = $m[1];
    $readies = stripos($clause, 'enters play ready') !== false;
    $cheaper = preg_match('/costs (\d+) resource/i', $clause, $c) ? intval($c[1]) : 0;
    if (!$readies && $cheaper <= 0) return 0.0;
    // A condition this cannot evaluate ("if it shares a keyword with a friendly unit") — do not guess.
    if (preg_match('/\bif\b/i', $clause)) return 0.0;
    $maxPower = preg_match('/with (\d+) or less power/i', $clause, $p) ? intval($p[1]) : PHP_INT_MAX;
    $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
    $self = GetHand($seat)[$i] ?? null;
    if ($self === null) return 0.0;
    $left = SWUTotalPaymentCapacity($seat) - intval(SWUComputePlayCost($seat, $self));
    $best = 0.0;
    foreach (GetHand($seat) as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed)) continue;
        $pid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($pid)), 'Unit')) continue;
        $power = intval(CardPower($pid));
        if ($power > $maxPower) continue;
        $cost = intval(SWUComputePlayCost($seat, $o)) - $cheaper;
        if ($cost > $left) continue;                       // the grant would expire unused
        $raid = preg_match('/\bRaid (\d+)/i', strval(CardText($pid)), $r) ? intval($r[1]) : 0;
        $gain = $readies ? $W['base'] * max(0, $power + $raid) : $W['develop'] * $cheaper;
        $best = max($best, $gain);
    }
    return $best;
}

// Deploying a leader that makes cards in hand cheaper — Piett: "Each Capital Ship unit you play costs 2 resources
// less." — comes before hard-casting them (feature 'enablers'). Judged by the lookahead: each hand card's play
// cost before and after the deploy.
function _SWUBotDeployDiscount(int $seat, array $action, array $W): float {
    if (!function_exists('SWUBotLookahead')) return 0.0;
    $costs = function () use ($seat) {
        $out = [];
        foreach (GetHand($seat) as $i => $o) { if ($o !== null && empty($o->removed)) $out[$i] = intval(SWUComputePlayCost($seat, $o)); }
        return $out;
    };
    $before = $costs();
    $after = SWUBotLookahead($seat, $action, fn() => ['c' => $costs()]);
    if ($after === null) return 0.0;
    $saved = 0;
    foreach ($before as $i => $c) $saved += max(0, $c - ($after['c'][$i] ?? $c));
    return $saved > 0 ? 3.0 + $W['develop'] * $saved : 0.0;
}

// Deploying a leader ONTO a Vehicle — the pilot side — is worth the damage it adds to a swing I have not made yet
// (feature 'pilotdeploy'). Owner report 2026-09-16 (game 469688): with 6 resources, Boba Fett (upgrade side 4/4) and
// two ready Vehicles, the bot attacked with both ships and left Boba in the leader zone. A deploy scored a flat
// W['deploy'] (1.5) while attacking with a 4-power ship scored W['base'] x 4 = 4.0, so attacking always won and the
// pilot slot went to waste — the same blind spot feature 'enablers' fixes for cost reductions, one step later.
//
// The bonus is the upgrade side's power at the same per-point rate the attack case uses, so it is style-scaled:
// Aggro pays 1.0 a point and deploys first, Control pays 0.3 and still prefers a removal Action. Only a READY host
// counts — a pilot on an exhausted ship adds nothing until next round, which the flat deploy weight already covers.
function _SWUBotPilotDeployValue(int $seat, array $action, array $W): float {
    if (!function_exists('CardLeaderCanDeployAsUpgrade') || !function_exists('SWUGetLeaderPilotVehicles')) return 0.0;
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $li = intval(explode('-', explode('!', strval($action['cardID'] ?? ''), 2)[0])[1] ?? 0);
    $lObj = (GetZone('myLeader')[$li] ?? null);
    $leaderCid = ($lObj === null) ? '' : strval($lObj->CardID ?? '');
    $ready = false;
    if ($leaderCid !== '' && CardLeaderCanDeployAsUpgrade($leaderCid)) {
        // SWUGetLeaderPilotVehicles is the ENGINE's own eligibility list (friendly, still a Vehicle, pilot seats
        // free), so a host that already carries a Pilot — or has lost the trait — never reaches this.
        foreach (SWUGetLeaderPilotVehicles($seat) as $mz) {
            $host = GetZoneObject($mz);
            if ($host !== null && intval($host->Status ?? 0) === 1) { $ready = true; break; }
        }
    }
    $playerID = $saved;
    return $ready ? $W['base'] * floatval(CardUpgradePower($leaderCid) ?? 0) : 0.0;
}

// Deploying a leader while Plot cards sit in my resources is worth the cards it PLAYS (feature 'plotdeploy').
// Plot: "When you deploy a leader, you may play this card from your resources, paying its cost." The deploy itself
// spends nothing (an Epic Action gates on resources CONTROLLED), so the whole pool is still there to pay with — but
// only if the deploy comes FIRST. Owner deck guide 2026-09-16: Ahsoka's flip turn plots Jar Jar (2) and the Naboo
// Starship (4) out of exactly 6 resources, "+12 damage on flip turn and closing the game here". Any ordinary play
// made before the deploy takes the Plot budget with it.
//
// Measured over 60 games: Ahsoka deployed on round 5 every time — the right turn — yet plotted nothing in 24 of 42
// deploys, and the only discriminator was ordering (0.56 cards played before the deploy when it plotted, 1.75 when
// it did not). The resourcer already banks Plot cards first (SWUBotChooseResourceCards), so the cards were there;
// the scorer just had no reason to deploy before spending. Cheapest-first, because plotting two cheap cards beats
// plotting one expensive one — Plot is capped by the budget, not by a card limit.
// The Plot cards in $seat's resources it could actually PAY for if it deployed right now, cheapest first —
// cheapest because Plot is capped by the resource budget, not by a card limit, so two cheap cards beat one dear
// one. Shared by the deploy's score and by the max-units guide, which must agree about what a deploy produces.
function _SWUBotAffordablePlots(int $seat): array {
    if (!function_exists('HasKeyword_Plot')) return [];
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $cap = function_exists('SWUTotalPaymentCapacity') ? intval(SWUTotalPaymentCapacity($seat)) : intval(SWUResourceCount($seat));
    $plots = [];
    foreach (GetResources($seat) as $r) {
        if ($r === null || !empty($r->removed) || !HasKeyword_Plot($r)) continue;
        $cid = strval($r->CardID ?? '');
        if ($cid === '') continue;
        // The printed cost plus this deck's aspect penalty — the same figure the Plot payment will face.
        $cost = function_exists('SWUComputePlayCost') ? intval(SWUComputePlayCost($seat, $r)) : intval(CardCost($cid));
        $plots[] = [$cost, $cid];
    }
    $playerID = $saved;
    usort($plots, fn($a, $b) => $a[0] <=> $b[0]);
    $spent = 0; $out = [];
    foreach ($plots as [$cost, $cid]) {
        if ($cost < 0 || $spent + $cost > $cap) continue;
        $spent += $cost;
        $out[] = [$cost, $cid];
    }
    return $out;
}

function _SWUBotPlotDeployValue(int $seat, array $W): float {
    $value = 0.0;
    foreach (_SWUBotAffordablePlots($seat) as [$cost, $cid]) $value += _SWUBotPlayValue($seat, $cid, $W);
    return $value;
}

// An Action that PLAYS a card from hand (Piett's front side) is worth the best card it plays: a pending choice of
// hand cards → the best of them; no choice (one legal card auto-resolves) → the cards that left the hand and are now
// my units (feature 'enablers').
function _SWUBotEnabledPlayValue(int $seat, array $handBefore, array $after, array $W): float {
    $d = $after['decision'] ?? null;
    if ($d !== null) {
        $parts = array_values(array_filter(explode('&', strval($d['param'] ?? ''))));
        if (empty($parts) || count(array_filter($parts, fn($p) => !preg_match('/^myHand-\d+$/', $p))) > 0) return 0.0;
        $best = 0.0;
        foreach ($parts as $p) {
            $o = GetHand($seat)[intval(substr($p, strlen('myHand-')))] ?? null;
            if ($o !== null) $best = max($best, _SWUBotPlayValue($seat, strval($o->CardID), $W));
        }
        return $best + 0.1;
    }
    $gone = $handBefore;
    foreach ((array)($after['hand'] ?? []) as $cid) { $k = array_search($cid, $gone, true); if ($k !== false) unset($gone[$k]); }
    $inPlay = array_map(fn($u) => $u[0], (array)($after['sig']['mine'] ?? []));
    $v = 0.0;
    foreach ($gone as $cid) { if (in_array($cid, $inPlay, true)) $v += _SWUBotPlayValue($seat, $cid, $W); }
    return $v;
}

// Where an upgrade goes (feature 'picks'): a helpful upgrade on the strongest friendly attacker (ready first); a
// harmful one (negative stats, or "attached unit can't / cannot / loses") on the most valuable enemy.
function _SWUBotAttachScore(int $seat, string $mz, string $upgradeID, array $W): ?float {
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null) return null;
    $harmful = intval(CardUpgradePower($upgradeID)) < 0 || intval(CardUpgradeHp($upgradeID)) < 0
        || preg_match("/attached unit (can't|cannot|loses)/i", strval(CardText($upgradeID)))
        || (SWUBotFeatureOn('targeting2') && preg_match('/loses all (other )?abilities|gets -\d+\/-\d+/i', strval(CardText($upgradeID))));
    $enemy = str_starts_with($mz, 'their');
    if ($harmful) return $enemy ? SWUBotUnitValue($v) : -SWUBotUnitValue($v);
    if ($enemy) return -SWUBotUnitValue($v);
    return $v['attackPower'] + ($v['ready'] ? $W['ready'] : 0.0) + 0.1 * SWUBotUnitValue($v);
}

// A search's pick ("CardID,CardID"; "" = none): the play value of what it takes, a little more per card.
function _SWUBotSearchScore(int $seat, string $candidate, array $W): float {
    $s = 0.0;
    foreach (array_filter(explode(',', $candidate)) as $cid) $s += _SWUBotPlayValue($seat, trim($cid), $W) + 0.01;
    return $s;
}

// Playing a unique unit I already control (same title and subtitle) defeats one copy (the uniqueness rule).
function _SWUBotUniqueClash(int $seat, string $cid): bool {
    if (!CardUnique($cid)) return false;
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && strval(CardSubtitle($v['cardID'])) === strval(CardSubtitle($cid))) return true;
    }
    return false;
}

// The copy of unique $cid I already control is at full HP (nothing to refresh). Feature 'unique'.
function _SWUBotUniqueCopyHealthy(int $seat, string $cid): bool {
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && strval(CardSubtitle($v['cardID'])) === strval(CardSubtitle($cid)))
            return $v['remaining'] >= $v['hp'];
    }
    return false;
}

// What an effect can change, as numbers (Phase 1b part 3). A side's value: each unit's SWUBotUnitValue scaled by the
// share of its PRINTED HP it has left (so a -2/-2 that leaves a 4/5 at 3 HP counts — the current HP drops with it),
// 0.8 when exhausted, plus a tenth of its power (a -N/-0 counts a little).
function _SWUBotSideValue(int $p): float {
    $v = 0.0;
    foreach (SWUBotUnits($p) as $u) {
        $hp = max(1, intval(CardHp($u['cardID'])));
        $v += SWUBotUnitValue($u) * min(1.0, max(0, $u['remaining']) / $hp) * ($u['ready'] ? 1.0 : 0.8) + 0.1 * max(0, $u['power']);
    }
    return $v;
}

// 'owed' = the opponent must answer next (an indirect-damage assignment, a discard): the effect is still landing.
function _SWUBotBoardRead(int $seat): array {
    $opp = SWUBotOpponent($seat);
    return ['theirs' => _SWUBotSideValue($opp), 'mine' => _SWUBotSideValue($seat), 'theirBase' => SWUBaseRemainingHp($opp),
            'owed' => (function_exists('SWUBotPendingDecisionSeat') && SWUBotPendingDecisionSeat() === $opp) ? 1 : 0];
}

// Enemy value removed + base damage − my value lost; an effect the opponent still has to resolve counts 1.
function _SWUBotBoardDelta(array $before, array $after, array $W): float {
    return ($before['theirs'] - $after['theirs']) + $W['base'] * ($before['theirBase'] - $after['theirBase'])
         - ($before['mine'] - $after['mine']) + floatval($after['owed'] ?? 0);
}

// Events whose whole value is what they do to the board (tags v2) — and events that attack ("Attack with a unit…",
// SEC_179 Aggressive Negotiations: untagged, and cast with no ready unit it does nothing; owner report 2026-09-14).
function _SWUBotIsEffectEvent(string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Event')) return false;
    $tags = SWUBotCardTags($cid);
    if (!empty(array_intersect($tags, ['removal', 'wipe', 'damage', 'exhaust', 'bounce', 'burn']))) return true;
    // An attack or Weakness event whose whole value is that effect. One that also draws (SOR_150 Heroic Sacrifice,
    // "Draw a card, then attack with a unit") is worth its draw even with no attack, so it stays out.
    return !in_array('draw', $tags, true)
        && preg_match('/\bAttack with an? (\w+ )?unit\b|\bWeakness tokens?\b/i', strval(CardText($cid))) === 1;
}

// The dud gate (feature 'dudgate'): play the event in the lookahead (its targets chosen for the best board change).
// A dud — held — when it changes nothing on the enemy side; when it is removal taking less than half its printed cost
// in enemy value; or when it is a wipe costing me more than it takes. Under pressure (the opponent's clock on me is 2
// or less) only the first test applies.
function _SWUBotEventIsDud(int $seat, array $action, string $cid, array $W): bool {
    if (!function_exists('SWUBotLookaheadBest')) return false;
    $before = _SWUBotBoardRead($seat);
    $line = SWUBotLookaheadBest($seat, $action, fn() => _SWUBotBoardRead($seat),
        fn(array $r) => _SWUBotBoardDelta($before, $r, $W), SWU_BOT_LOOKAHEAD_DEPTH, 12);
    if ($line === null) return false;
    $enemyChanged = ($before['theirs'] - $line['theirs']) > 1e-6 || $line['theirBase'] < $before['theirBase'] || !empty($line['owed']);
    if (!$enemyChanged) return true;
    if (SWUBotClock(SWUBotOpponent($seat), $seat) <= 2) return false;
    $tags = SWUBotCardTags($cid);
    $d = floatval($line['_score']);
    if (in_array('wipe', $tags, true)) return $d < 0.0;
    if (in_array('removal', $tags, true)) return $d < 0.5 * intval(CardCost($cid));
    return false;
}

// Feature 'nogift': what the opponent's side is worth to THEM, on the axes a friendly-minded effect improves —
// remaining HP, power, Shields, upgrades, readiness, unit count, base HP. _SWUBotSideValue misses Shields and
// upgrades, so a Shield handed to an undamaged enemy unit would read as nothing.
function _SWUBotGiftRead(int $seat): float {
    $opp = SWUBotOpponent($seat);
    $v = floatval(SWUBaseRemainingHp($opp));
    foreach (SWUBotUnits($opp) as $u) {
        $v += 2.0 + max(0, $u['remaining']) + max(0, $u['power']) + 2.0 * $u['shields'] + $u['upgrades'] + ($u['ready'] ? 1.0 : 0.0);
    }
    return $v;
}

// Play the card in the lookahead (its choices made for the best board change for me) and hold it when even that
// line leaves the opponent better off. Only cards whose text can help a unit are tried (the lookahead is not free).
function _SWUBotPlayIsGift(int $seat, array $action, string $cid, array $W): bool {
    if (!function_exists('SWUBotLookaheadBest')) return false;
    if (!preg_match('/\b(heal|give|gives|ready|attach|gets \+)/i', strval(CardText($cid)))) return false;
    $before = _SWUBotBoardRead($seat);
    $gift0 = _SWUBotGiftRead($seat);
    $line = SWUBotLookaheadBest($seat, $action, function () use ($seat) {
        $r = _SWUBotBoardRead($seat);
        $r['_gift'] = _SWUBotGiftRead($seat);
        return $r;
    }, fn(array $r) => _SWUBotBoardDelta($before, $r, $W), SWU_BOT_LOOKAHEAD_DEPTH, 12);
    return $line !== null && floatval($line['_gift'] ?? $gift0) - $gift0 > 1e-6;
}

// Feature 'noeffect': play the event in the lookahead (its choices made for the best board change) and read the
// ENGINE's verdict — "P1's X had no effect" is logged only when the whole gamestate is unchanged by the ability
// (SWULogNoEffectCheck, SWUSim/Custom/GameLogEvents.php). The dud gate above only sees the enemy side, so an event
// aimed at my own board (ASH_090 Reforge: "Defeat an upgrade on a friendly unit") slipped past it into nothing.
function _SWUBotEventHadNoEffect(int $seat, array $action, string $cid, array $W): bool {
    if (!function_exists('SWUBotLookaheadBest')) return false;
    $logLen = strlen(_SWUBotLogValue());
    $ref = '[[' . $cid . '|';
    $before = _SWUBotBoardRead($seat);
    $line = SWUBotLookaheadBest($seat, $action, function () use ($seat, $logLen, $ref) {
        $r = _SWUBotBoardRead($seat);
        $r['_noEffect'] = false;
        foreach (explode('<NL>', substr(_SWUBotLogValue(), $logLen)) as $e) {
            if (str_contains($e, $ref) && str_contains($e, 'had no effect')) { $r['_noEffect'] = true; break; }
        }
        return $r;
    }, fn(array $r) => _SWUBotBoardDelta($before, $r, $W), SWU_BOT_LOOKAHEAD_DEPTH, 12);
    return $line !== null && !empty($line['_noEffect']);
}

// The game log's raw value ("TYPE|VISIBILITY|text" joined by "<NL>"). It starts as the placeholder '0', which the
// first entry REPLACES, so '0' reads as empty.
function _SWUBotLogValue(): string {
    global $gGameLog;
    $v = is_object($gGameLog) ? strval($gGameLog->Value ?? '') : strval($gGameLog ?? '');
    return $v === '0' ? '' : $v;
}

// A card's modal option ("Choose one", an arena): play the answer in the lookahead and score the board change.
// Feature 'modes'. null when it cannot be judged.
function _SWUBotOptionDelta(array $ctx, array $action, array $W): ?float {
    if (!function_exists('SWUBotLookaheadBest')) return null;
    $seat = intval($ctx['seat']);
    $before = _SWUBotBoardRead($seat);
    $line = SWUBotLookaheadBest($seat, $action, fn() => _SWUBotBoardRead($seat),
        fn(array $r) => _SWUBotBoardDelta($before, $r, $W), SWU_BOT_LOOKAHEAD_DEPTH, 12);
    return $line === null ? null : floatval($line['_score']);
}

// The Force (feature 'force'). A card NEEDS it when its effect is gated on spending it: "(You may) use the Force
// (lose your Force token). If you do, …". Not "the Force is with you" (that CREATES the token), not a "Choose one"
// with a Force-free mode (Shatterpoint), not "If you do either" / "If you do not" (they do something anyway).
function _SWUBotNeedsTheForce(string $cid): bool {
    $t = strval(CardText($cid));
    return (bool)preg_match('/use the Force \(lose your Force token\)\. If you do(?! either)/i', $t)
        && stripos($t, 'Choose one') === false && stripos($t, 'If you do not') === false;
}

// Another card (in hand or a non-leader unit in play) that needs the Force.
function _SWUBotHasOtherForceUse(int $seat): bool {
    foreach (GetHand($seat) as $o) { if ($o !== null && empty($o->removed) && _SWUBotNeedsTheForce(strval($o->CardID ?? ''))) return true; }
    foreach (SWUBotUnits($seat) as $v) { if (!$v['isLeader'] && _SWUBotNeedsTheForce($v['cardID'])) return true; }
    return false;
}

// The printed text behind a leader or unit Action (the leader's front side while undeployed).
function _SWUBotActionSourceText(int $seat, array $action): string {
    $k = SWUBotActionKind($action);
    if ($k === 'leader-ability') { $l = GetLeader($seat)[0] ?? null; return $l !== null ? strval(CardText(strval($l->CardID ?? ''))) : ''; }
    if ($k === 'unit-action') { $v = SWUBotViewForMz($seat, SWUBotActionMz($action)); return $v !== null ? strval(CardText($v['cardID'])) : ''; }
    return '';
}

// My finisher this round (feature 'setup'): the largest N among ready "Action [Exhaust]: Defeat a non-leader unit
// with N or less remaining HP" sources — the ready undeployed leader's front side (Aurra Sing) or a ready unit.
function _SWUBotFinisherHP(int $seat): int {
    $re = '/Action \[Exhaust\]: Defeat a non-leader unit with (\d+) or less remaining HP/i';
    $n = 0;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $cid = strval($l->CardID ?? '');
        if (_SWULeaderReadyUndeployed($seat, $cid) && preg_match($re, strval(CardText($cid)), $m)) $n = max($n, intval($m[1]));
    }
    foreach (SWUBotUnits($seat) as $v) {
        if ($v['ready'] && preg_match($re, strval(CardText($v['cardID'])), $m)) $n = max($n, intval($m[1]));
    }
    return $n;
}

// A compact view of the board for judging what an ability did (see _SWUBotAbilityValue).
function _SWUBotBoardSignature(int $seat): array {
    $opp = SWUBotOpponent($seat);
    $live = fn($z) => count(array_filter($z, fn($o) => $o !== null && empty($o->removed)));
    $units = function ($p) { $o = []; foreach (SWUBotUnits($p) as $v) $o[$v['uid']] = [$v['cardID'], $v['power'], $v['remaining'], $v['ready'], $v['shields'], $v['upgrades']]; ksort($o); return $o; };
    return ['mine' => $units($seat), 'theirs' => $units($opp), 'bases' => [SWUBaseRemainingHp($seat), SWUBaseRemainingHp($opp)],
            'hands' => [$live(GetHand($seat)), $live(GetHand($opp))], 'resources' => [$live(GetResources($seat)), $live(GetResources($opp))],
            'decks' => [$live(GetDeck($seat)), $live(GetDeck($opp))], 'discards' => [$live(GetDiscard($seat)), $live(GetDiscard($opp))]];
}

// A leader / unit / base Action, judged by applying it with the lookahead (BotLookahead.php):
//   - it would change nothing and raise no decision (an Epic Action with nothing to play) → never use it;
//   - it costs friendly units, either directly or through a "defeat a friendly unit" choice → the flat
//     ability value, less whatever sacrifice goes beyond 1 point of value;
//   - otherwise → the flat ability value.
// If the lookahead cannot apply the action, the flat value stands (the pre-2026-09-13 behaviour).
function _SWUBotAbilityValue(array $ctx, array $action, array $W): float {
    $seat = intval($ctx['seat']);
    // PROPOSAL 'piettcheat' (default OFF). Owner, 2026-09-22: Piett "should cheat out capital ships to be able to trade
    // and stall against Vader". The lookahead stops at the ship prompt, so the discounted play scored as a bare
    // ability (1.5) — no more than playing the same ship at full price. Value it as the best ship it can play, plus
    // the resource it saves.
    if (SWUBotProposalOn('piettcheat') && SWUBotActionKind($action) === 'leader-ability') {
        $l = GetLeader($seat)[0] ?? null;
        $txt = $l !== null ? strval(CardText(strval($l->CardID ?? ''))) : '';
        if (preg_match('/Play an? ([A-Z][\w ]*?) unit from your hand\. It costs (\d+) resources? less/', $txt, $m)
            && function_exists('SWUHandPlayablesAtDiscount')) {
            $best = null;
            foreach (SWUHandPlayablesAtDiscount($seat, ['Unit'], intval($m[2])) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed) || !HasTrait($o->CardID, $m[1])) continue;
                $v = _SWUBotPlayValue($seat, strval($o->CardID), $W);
                $best = $best === null ? $v : max($best, $v);
            }
            if ($best !== null) return $best + 0.1 * intval($m[2]);
        }
    }
    if (!function_exists('SWUBotLookahead')) return $W['ability'];
    $before = _SWUBotBoardSignature($seat);
    $handIDs = fn() => array_values(array_map(fn($o) => strval($o->CardID), array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed))));
    $handBefore = $handIDs();
    $readBefore = _SWUBotBoardRead($seat);
    $after = SWUBotLookahead($seat, $action, function () use ($seat, $handIDs) {
        $d = null;
        $live = array_values(array_filter(GetDecisionQueue($seat), fn($e) => $e !== null && empty($e->removed)));
        if (!empty($live)) $d = ['tooltip' => strval($live[0]->Tooltip ?? ''), 'param' => strval($live[0]->Param ?? ''),
                                 'type' => strval($live[0]->Type ?? ''), 'next' => strval(($live[1] ?? null)->Param ?? '')];
        return ['sig' => _SWUBotBoardSignature($seat), 'decision' => $d, 'hand' => $handIDs(), 'read' => _SWUBotBoardRead($seat)];
    });
    if ($after === null) return $W['ability'];
    if ($after['decision'] === null && $after['sig'] == $before) return -0.5;
    $lost = 0.0;
    foreach (SWUBotUnits($seat) as $v) { if (!isset($after['sig']['mine'][$v['uid']])) $lost += SWUBotSacrificeCost($v); }
    $d = $after['decision'];
    // The Action resolved on its own (a single legal target skips the prompt) and all it did was make the enemy
    // side stronger — nothing gained on mine, no card drawn (feature 'buffs').
    if ($d === null && SWUBotFeatureOn('buffs') && isset($after['read'])) {
        $r0 = $readBefore; $r1 = $after['read'];
        if ($r1['theirs'] - $r0['theirs'] > 1e-6 && $r1['mine'] - $r0['mine'] <= 1e-6 && $after['hand'] == $handBefore) return -0.5;
    }
    // The Action's target prompt can only help the enemy (a buff whose every candidate is theirs) or only hurt
    // me (a hostile effect whose every candidate is mine): don't use it (feature 'buffs'; owner report
    // 2026-09-14, Bot Practice game 183227 — Ahsoka Tano ASH_009 buffing the player's unit).
    if ($d !== null && SWUBotFeatureOn('buffs') && ($d['type'] ?? '') === 'MZCHOOSE') {
        $head = explode('|', strval($d['next'] ?? ''))[0];
        $cands = array_values(array_filter(explode('&', strval($d['param'])), fn($m) => preg_match('/^(my|their)\w*Arena-\d+$/', $m)));
        $helps = $head === 'APPLY_PHASE_BUFF' || in_array($head, SWU_BOT_BENEFICIAL_CONTINUATIONS, true);
        $hurts = $head === 'APPLY_PHASE_DEBUFF' || in_array($head, SWU_BOT_HOSTILE_CONTINUATIONS, true);
        $allTheirs = !empty($cands) && count(array_filter($cands, fn($m) => str_starts_with($m, 'their'))) === count($cands);
        $allMine = !empty($cands) && count(array_filter($cands, fn($m) => str_starts_with($m, 'my'))) === count($cands);
        if (($helps && $allTheirs) || ($hurts && $allMine)) return -0.5;
    }
    if ($d !== null && _SWUBotTooltipEffect($d['tooltip']) === 'sacrifice') {
        $cheapest = null;
        foreach (array_filter(explode('&', $d['param'])) as $mz) {
            $v = SWUBotViewForMz($seat, $mz);
            if ($v !== null) $cheapest = $cheapest === null ? SWUBotSacrificeCost($v) : min($cheapest, SWUBotSacrificeCost($v));
        }
        $lost += $cheapest ?? 0.0;
    }
    $value = $W['ability'];
    if (SWUBotFeatureOn('enablers')) $value = max($value, _SWUBotEnabledPlayValue($seat, $handBefore, $after, $W));
    // FEATURE 'buffattack', group p7 (owner report #1052, game 690588): an Action that BUFFS a unit which can still attack
    // this phase is worth what the buff adds to that attack. Flat W['ability'] (0.40) sits BELOW the attack it
    // would improve (W['base'] x power), so the bot attacked with Gungi for 2 and then spent Ahsoka's Action on
    // him — and "+2/+0 for this phase" on a unit that has already swung does nothing at all.
    // …and an Action whose ONLY effect is +N/+0 for this phase, with no ready unit to spend it, is not used at all
    // (owner report 2026-09-21, Petranaki Arenabot: Ahsoka's Action "wasted" on an exhausted unit).
    if (SWUBotFeatureOn('buffattack')) {
        $gain = _SWUBotBuffAttackGain($ctx, $seat, $before, $after, $W);
        if ($gain <= 0.0 && _SWUBotIsPowerOnlyPhaseBuff($before, $after, $handBefore)) return -0.5;
        $value = max($value, $W['ability'] + $gain);
    }
    // An Action that costs the Force (Talzin's -1/-1): never onto an empty enemy board, and held when its -N/-N kills
    // nothing while another card needs the Force (feature 'force').
    $text = _SWUBotActionSourceText($seat, $action);
    if (SWUBotFeatureOn('force') && preg_match('/Action \[[^\]]*use the Force/i', $text)) {
        $enemies = array_values(array_filter(SWUBotUnits(SWUBotOpponent($seat)), fn($v) => !$v['isLeader']));
        if (empty($enemies)) return -0.5;
        $n = preg_match('/-\d+\/-(\d+)/', $text, $m) ? intval($m[1]) : 0;
        $kills = count(array_filter($enemies, fn($v) => $v['remaining'] <= $n)) > 0;
        if (!$kills && _SWUBotHasOtherForceUse($seat)) $value = min($value, 0.02);   // below the initiative (0.05)
    }
    return $value - max(0.0, $lost - 1.0);
}

// What a buff adds to the attacks my READY units can still make this phase — the extra damage it enables,
// priced with the same weights the attack itself is scored with, so the two are directly comparable.
// Two shapes, because the Action's target prompt may or may not have resolved inside the lookahead:
//   · it auto-resolved (one legal target — the reported game) → the power rise shows in the board signature;
//   · it is still pending → read the amount off the APPLY_PHASE_BUFF continuation and try each candidate.
// Zero when the buff lands on an exhausted unit (it cannot attack this phase), on a unit with nothing worth
// attacking, or on the enemy — so this never promotes an Action that does not improve an attack.
function _SWUBotBuffAttackGain(array $ctx, int $seat, array $before, array $after, array $W): float {
    $views = [];
    foreach (SWUBotUnits($seat) as $v) $views[$v['uid']] = $v;
    $bestAttack = function (array $att) use ($ctx, $W): float {
        $best = 0.0;
        foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) $best = max($best, SWUBotTargetValue($att, $k === 'base' ? null : $u, $W));
        return $best;
    };
    $gainFor = function (?array $v, int $delta) use ($bestAttack): float {
        if ($v === null || $delta <= 0 || !$v['ready']) return 0.0;   // exhausted: the buff expires unused
        $boosted = $v; $boosted['attackPower'] += $delta; $boosted['power'] += $delta;
        return max(0.0, $bestAttack($boosted) - $bestAttack($v));
    };
    $gain = 0.0;
    // Signature index: [cardID, power, remaining, ready, shields, upgrades] (_SWUBotBoardSignature).
    foreach (($after['sig']['mine'] ?? []) as $uid => $row) {
        $prev = $before['mine'][$uid] ?? null;
        if ($prev === null) continue;
        $gain = max($gain, $gainFor($views[$uid] ?? null, intval($row[1]) - intval($prev[1])));
    }
    $d = $after['decision'] ?? null;
    if ($gain <= 0.0 && $d !== null) {
        $parts = explode('|', strval($d['next'] ?? ''));
        if (($parts[0] ?? '') === 'APPLY_PHASE_BUFF') {
            $amount = intval($parts[1] ?? 0);
            foreach (array_filter(explode('&', strval($d['param'] ?? ''))) as $mz) {
                if (!str_starts_with($mz, 'my')) continue;            // a buff on THEIR unit adds nothing to my attacks
                $gain = max($gain, $gainFor(SWUBotViewForMz($seat, $mz), $amount));
            }
        }
    }
    return $gain;
}

// Is the Action's whole effect a "+N/+0 for this phase" power buff? Power only matters to an attack, so such a
// buff is worth nothing unless _SWUBotBuffAttackGain finds a ready unit to spend it. Same two shapes:
//   · still pending → the target prompt's continuation is APPLY_PHASE_BUFF|N|0;
//   · auto-resolved → the only change anywhere is a power rise on my units (same hand, same everything else).
// An HP buff (+N/+N) is NOT power-only: it keeps a unit alive, so it is left to the flat value.
function _SWUBotIsPowerOnlyPhaseBuff(array $before, array $after, array $handBefore): bool {
    $d = $after['decision'] ?? null;
    if ($d !== null) {
        $parts = explode('|', strval($d['next'] ?? ''));
        return ($parts[0] ?? '') === 'APPLY_PHASE_BUFF' && intval($parts[1] ?? 0) > 0 && intval($parts[2] ?? 0) === 0;
    }
    $sig = $after['sig'] ?? null;
    if ($sig === null || ($after['hand'] ?? null) != $handBefore) return false;
    foreach ($before as $k => $v) { if ($k !== 'mine' && ($sig[$k] ?? null) != $v) return false; }
    if (array_keys($sig['mine'] ?? []) != array_keys($before['mine'])) return false;
    $rose = false;
    // Signature index: [cardID, power, remaining, ready, shields, upgrades] (_SWUBotBoardSignature).
    foreach ($before['mine'] as $uid => $row) {
        $now = $sig['mine'][$uid];
        foreach ([0, 2, 3, 4, 5] as $i) { if ($now[$i] != $row[$i]) return false; }
        if (intval($now[1]) < intval($row[1])) return false;
        if (intval($now[1]) > intval($row[1])) $rose = true;
    }
    return $rose;
}

// PROPOSALS 'mullnocast' / 'mullcurve' / 'mullstyle' (all default OFF) — the opening hand. Returns true to
// mulligan, false to keep, or NULL when no mulligan proposal is active (the shipped bot always keeps).
// Costs are PRINTED: at the mulligan there are no resources yet. Round N carries N+1 resources (CR 5.4), so
// "castable by round 2" is cost <= 3. The hand is 6 cards (CR 1.8).
function _SWUBotShouldMulligan(int $seat, string $style): ?bool {
    $costs = [];
    $tags = [];
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        $costs[] = intval(CardCost($cid));
        $tags[] = SWUBotCardTags($cid);
    }
    if (empty($costs)) return null;
    $atMost = fn(int $n) => count(array_filter($costs, fn($c) => $c <= $n));
    $atLeast = fn(int $n) => count(array_filter($costs, fn($c) => $c >= $n));
    if (SWUBotProposalOn('mullnocast')) return $atMost(3) < 2;
    if (SWUBotProposalOn('mullcurve'))  return $atMost(2) === 0 || $atLeast(6) >= 3;
    if (SWUBotProposalOn('mullstyle')) {
        $rank = SWUBotStyleRank($style);
        if ($rank <= 1) return $atMost(2) === 0;                                   // the aggro wing needs an early drop
        $answers = count(array_filter($tags, fn($t) => (bool)array_intersect($t, ['removal', 'wipe'])));
        if ($rank >= 3) return $answers === 0 || $atMost(3) < 2;                   // control needs an answer AND a curve
        return $atMost(3) < 2;                                                     // midrange: just the curve
    }
    return null;
}

// Which moves the guides favour, for this candidate list (BotGuides.php).
//
// Guide-level bisection ("@no-guide:<name>", BotFeatures.php) is gated HERE because this is the single
// chokepoint: the weight applications at lines ~126 and ~150 and the coverage recording in
// BotHeuristic.php all read this array (directly or via $ctx['_guides']). Default-on — with no variant
// every guide is on and behaviour is unchanged.
//
// Why guides need their own switch: the anti-control bias measured 2026-09-18 leaves ~11 points in the CORE
// stack, which is three separate things — the layer-2 RULES ("@no-rule:<name>"), these GUIDES, and the raw
// fallback weights. 'attackFirst' is weight 6.00 FLAT across all five archetypes, an order of magnitude above
// base (0.60) or kill (1.50), so whenever it fires it decides the action outright for control exactly as
// hard as for aggro. That is a hypothesis, not a finding — this switch is how to test it.
function _SWUBotGuides(array $ctx): array {
    $pick = SWUBotFeatureOn('guide:maxUnits') ? SWUBotAggroMaxUnitsPick($ctx) : null;
    return ['attackFirst' => SWUBotFeatureOn('guide:attackFirst')
                ? array_map(fn($a) => strval($a['cardID'] ?? ''), SWUBotAttackFirstAttacks($ctx)) : [],
            'maxUnits' => $pick === null ? null : strval($pick['cardID'] ?? '')];
}

// The highest-scoring candidate; ties go to the lowest index. Never null for a non-empty list.
function SWUBotFallbackChoose(array $ctx): ?array {
    $ctx['_guides'] = _SWUBotGuides($ctx);
    $best = null; $bestScore = 0.0;
    foreach (array_values($ctx['actions']) as $i => $a) {
        $s = SWUBotScoreAction($ctx, $a, $i);
        if ($best === null || $s > $bestScore) { $best = $a; $bestScore = $s; }
    }
    return $best;
}
