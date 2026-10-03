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

// PROPOSAL 'mgtrade' (default OFF): what killing $def is worth BEYOND the card — the damage it will not deal.
// The kill term prices a target by its VALUE, a cost proxy, so a cheap high-power body (Battlefield Marine, 2 for
// 3/3) is worth 1.80 to a midrange seat while hitting the base for 4 is worth 2.40 — and the bot hits the base.
// Measured 2026-09-23: 73% of a midrange bot's attacks went at the base across 40 traced games while it lost the
// board 3.1 units to 4.8 and died in round 6. Here a kill also earns one round of the base damage it prevents
// (target power x $W['threat'], which SWUBotWeights sets to $W['base'] for a midrange seat and 0 for everyone
// else), so a swing and a kill are finally priced in the same currency.
// ONLY WHILE BEHIND ON BOARD POWER (owner A1 "trade up while behind", A5b "judge the board by POWER, not bodies").
// Ahead or level, the term is zero and the seat races exactly as it does today.
// ⚠ Not the older 'tradewhenbehind' (above): that one only DISCOUNTS the loss side of a trade and counts BODIES.
// This one pays the kill side, counts POWER, and reaches 'kill-survive' — which that one never touched.
function _SWUBotThreatRemoved(array $att, array $def, array $W): float {
    $rate = floatval($W['threat'] ?? 0.0);
    if ($rate <= 0.0 || !function_exists('SWUBotUnits')) return 0.0;
    $seat = intval($att['controller']);
    $power = function (int $s): int { $n = 0; foreach (SWUBotUnits($s) as $v) $n += intval($v['attackPower']); return $n; };
    if ($power(SWUBotOpponent($seat)) <= $power($seat)) return 0.0;
    return $rate * intval($def['attackPower']);
}

function SWUBotTargetValue(array $att, ?array $def, array $W): float {
    if ($def === null) return $W['base'] * $att['attackPower'];
    $lossF = _SWUBotLossFactor($att);
    switch (SWUBotCombatOutcome($att, $def)) {
        case 'kill-survive':
            $v = $W['kill'] * SWUBotUnitValue($def) + _SWUBotThreatRemoved($att, $def, $W);
            // The Overwhelm excess reaches the base — which also makes Aggro prefer the lowest-HP kill.
            if (SWUBotOverwhelmKills($att, $def)) $v += $W['base'] * ($att['attackPower'] - $def['remaining']);
            return $v;
        case 'trade':
            return $W['kill'] * SWUBotUnitValue($def) + _SWUBotThreatRemoved($att, $def, $W)
                   - $lossF * $W['loss'] * SWUBotUnitValue($att);
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
        $enemy = SWUBotIsEnemyMz($seat, $mz);   // owner seat, not a "their" prefix (p{n} at 3-4 seats)
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
// ⚠ 'DEAL_UNIT_DAMAGE' was MISSING here until 2026-09-29 (prod bug #1100, game 1402804: the bot used Luke
// JTL_012's mandatory "deal 1 damage to a unit" Action with no enemy units on board and killed its own
// Y-Wing). It is the most common continuation in the tree — 141 sites, against 102 across all five that were
// listed — so the refusal guard in _SWUBotAbilityValue was blind to more call sites than it covered.
// The target PICKER was never blind: its classifier (below, ~:257) falls back to _SWUBotTooltipEffect(),
// which reads "Deal_1_damage_to_a_unit" as hostile. Only the ACTIVATION guard had no such fallback.
const SWU_BOT_HOSTILE_CONTINUATIONS    = ['DEFEAT_UNIT', 'DEAL_TARGET', 'BOUNCE_UNIT', 'GIVE_WEAKNESS', 'EXHAUST_UNIT', 'DEAL_UNIT_DAMAGE'];
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
                // An upgrade with an owner-ruled host policy (feature 'hostpolicy'): held when no legal host is one it
                // may go on; otherwise its side of the table is already decided, so it is not re-judged as a gift (a
                // Bounty on an enemy unit reads as "+1 upgrade" to the gift read).
                $policyHosts = SWUBotFeatureOn('hostpolicy') ? _SWUBotPolicyHosts($seat, $cid) : null;
                if ($policyHosts !== null && empty($policyHosts)) return -0.5;
                if ($policyHosts === null && SWUBotFeatureOn('nogift') && _SWUBotPlayIsGift($seat, $action, $cid, $W)) return -0.5;
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
                    // 'gives-advantage' is the tags-v3 name for this exact clause: v2 folded Advantage into
                    // `buff`, and keying on `buff` alone silently stopped the deduction the moment the tag split
                    // (ASH_044 Barriss Offee went from ['buff'] to ['gives-advantage']).
                    if (array_intersect($tags, ['buff', 'gives-advantage']) && stripos($t, 'Advantage token') !== false
                        && !SWUBotIsRacing($seat, SWUBotOpponent($seat))) $v -= $W['buff'];
                }
                // PROPOSAL 'playsurvivor' (default OFF): prefer a body that SURVIVES the opponent's best attacker.
                // Control keeps trading fresh units away; a unit that dies to the first swing bought nothing.
                if (SWUBotProposalOn('playsurvivor') && str_contains(strval(CardType($cid)), 'Unit')) {
                    $hp = intval(CardHp($cid));
                    $worst = 0;
                    foreach (SWUBotEnemyUnits($seat) as $e) $worst = max($worst, intval($e['attackPower']));
                    if ($hp > 0 && $worst >= $hp) $v -= 1.0;
                }
                // PROPOSAL 'mgsentinel' — the PLAY half (owner, 2026-09-23, rulings 2 and 4). "Playing: priority
                // against aggro — a Sentinel goes down ahead of a bigger non-Sentinel body", and "play a body
                // before attacking ONLY for Sentinels; otherwise judge the board by POWER, not by body count."
                // The magnitude is W['attackFirst'] (6.00), this engine's existing "this decides the action" weight,
                // because the ruling is a PRIORITY and not a preference: a plain bonus the size of a deploy (1.50)
                // loses to any expensive body — an 8-drop Reinforcement Walker scores 6.40 against Captain Typho's
                // 1.60 — and would leave both rulings unexpressed. At 6.00 the Sentinel also outranks the attacks
                // in the same list, which is ruling 4.
                // Traced 2026-09-23: Luke ASH resourced Sentinels 32 times and played 17, while losing the board
                // every round. The resourcing half lives in BotResourcing.php.
                // ⚠ Reads the opposite way to 'sentineltiming' below, which holds a Sentinel back until the
                // attacks are done. They are alternatives, never a pair: run at most one of them in an arm.
                if (SWUBotProposalOn('mgsentinel') && SWUBotStyleRank(strval($ctx['style'] ?? '')) === 2
                    && _SWUBotHasPrintedSentinel($cid) && SWUBotOpponentIsAggroLeader($seat)) {
                    $v += $W['attackFirst'];
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
    // ⚠ A PAIRED defeat ("a friendly unit AND an enemy … defeat those units") is a TRADE, not a sacrifice: declining
    // the friendly pick cancels the enemy kill too. Priced as a pure sacrifice, Chimaera's When Played resolved in only
    // 19% of 3,895 baseline plays (2026-10-01, found from the owner's Karabast logs). $pairedGain = the best enemy kill.
    $pairedGain = null;
    if ($effect === '' && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $m)) {
        if (stripos($tip, 'friendly') !== false && preg_match('/\bdefeat\b/i', strval(CardText($m[1])))) $effect = 'sacrifice';
        elseif (SWUBotFeatureOn('targeting')) $effect = _SWUBotCardTextEffect($m[1]);
    }
    // Checked whenever the prompt is a sacrifice, HOWEVER it was classed: Chimaera's own tooltip
    // ("Defeat_a_friendly_and_an_enemy_unit?") is already 'sacrifice' from the tooltip reader, so a check inside the
    // branch above never ran (caught by bot_chimaera_test).
    if ($effect === 'sacrifice' && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $pm)
        && preg_match('/\bfriendly\b[^.]*\band an enemy\b[^.]*\.\s*If you do, defeat those units/i', strval(CardText($pm[1])))) {
        $pairedGain = _SWUBotBestEnemyNonLeaderValue($seat);
    }
    // p{n}: another seat's zone at 3-4 seats (ZoneSearch). Missing it here sent every Twin Suns enemy target past the
    // hostile/beneficial scoring to the flat first-legal value below (SWUSim/docs/todo-twinsuns-fill-bot.md, research A).
    $onBoard = (bool)preg_match('/^(my|their|p\d+)(GroundArena|SpaceArena|Base)-/', $c);
    // Part 21 'namecard': "Name a card" — the opponent's likeliest / most impactful card (BotNameCard.php).
    if ($type === 'NAMECARD' && SWUBotFeatureOn('namecard')) return SWUBotNameCardScore($seat, $c, (array)($ctx['following'] ?? []));
    // "Defeat a friendly unit" as a cost: declining is worth giving up 1 point of unit value, so a MAY
    // sacrifice takes only a token, a cheap unit, or one whose When Defeated pays it back.
    if ($c === 'PASS') {
        if ($pairedGain !== null) return 0.0;   // declining a TRADE forfeits nothing but the trade itself
        if ($effect === 'sacrifice') return -1.0;
        return SWUBotAtResourceStop($ctx) ? $W['stopPass'] : 0.0;   // guide: the style's resource stop (rule 11)
    }
    // PROPOSAL 'landomill' (default OFF): Lando's "choose an aspect, then discard a card from a deck" — which aspect,
    // and whose deck. Inert unless the proposal is on and this seat's leader carries that Action.
    $lando = _SWUBotLandoDecisionScore($ctx, $c, $index);
    if ($lando !== null) return $lando;
    // Split damage and the indirect player pick (feature 'splits'). A heal split is not damage.
    if ($type === 'MZSPLITASSIGN' && stripos($tip, 'damage') !== false && stripos($tip, 'heal') === false && SWUBotFeatureOn('splits')) {
        return _SWUBotSplitScore($seat, $c, $W, stripos($tip, 'indirect') !== false);
    }
    // A Weakness-token split (HMW_071 Ravage) is -1/-1 per token: scored as unpreventable damage, so the tokens go on
    // enemy units and never on mine (owner report 2026-09-15, Bot Practice game 189011 — Ravage defeated its own 0-0-0).
    if ($type === 'MZSPLITASSIGN' && stripos($tip, 'Weakness') !== false && SWUBotFeatureOn('splits')) {
        // Feature 'weakness': the kill a token makes, then the strongest body it shrinks — not a flat chip per token.
        if (SWUBotFeatureOn('weakness')) return _SWUBotWeaknessSplitScore($seat, $c, $W);
        return _SWUBotSplitScore($seat, $c, $W, true);
    }
    // "Choose a player" at 3-4 seats ("You&P2&P3", SWUPlayerPickerLabels). The fallback took the first option — "You" —
    // so every harmful pick landed on the bot itself. Harmful → the healthiest enemy; beneficial → me, then a teammate.
    // 2 seats ("You&Opponent") never reaches this: Arenabot's picks are unchanged.
    if ($type === 'OPTIONCHOOSE' && preg_match('/^(You|P\d+)$/', $c) && preg_match('/(^|&)P\d+(&|$)/', strval($ctx['param'] ?? ''))) {
        return _SWUBotTSPlayerPick($seat, $c, $tip, strval($ctx['param'] ?? ''));
    }
    if ($type === 'OPTIONCHOOSE' && $tip === 'Choose_a_player_to_deal_indirect_damage' && SWUBotFeatureOn('splits')) {
        return $c === 'You' ? -1.0 : $W['base'];
    }
    // PROPOSAL 'piettcheat' (default OFF): a discounted play-from-hand prompt (Piett's "Play_a_Capital_Ship_unit_(costs_1_less)")
    // plays the card worth most, not the first legal one.
    // 'piettcheat' (Piett's discounted leader Action) and 'aspectwaiver' (the eight LAW common bases' penalty
    // waiver) share one problem: a "Play_a_…" prompt otherwise falls through to `0.01 - $index * 1e-6`, the
    // enumeration-order tiebreak, so the bot spends the unlock on whatever sits lowest in hand. Traced on
    // Daimyo's Palace: myHand-0 / myHand-4 every time, including the round-5 game that held LAW_044.
    // 'aspectwaiver' is a SHIPPED FEATURE (p16) and default ON, so a "Play_a_..." prompt now always
    // picks by value rather than by hand index. That index tiebreak is what chose myHand-0 every time.
    if ((SWUBotProposalOn('piettcheat') || SWUBotFeatureOn('aspectwaiver'))
        && str_starts_with($tip, 'Play_a_') && str_starts_with($c, 'myHand-')) {
        $o = GetHand($seat)[intval(substr($c, strlen('myHand-')))] ?? null;
        if ($o !== null) return 0.5 + 0.01 * _SWUBotPlayValue($seat, strval($o->CardID ?? ''), $W);
    }
    // Guide: the opening two resources are the resourcing engine's two lowest keep values.
    if ($tip === 'Choose_2_cards_to_resource') return _SWUBotSameSelection($c, SWUBotChooseResourceCards($ctx, 2)) ? 1.0 : -$index * 1e-6;
    // Part 22 'powersource': a friendly unit picked as the damage SOURCE — "Choose (another) friendly unit to deal damage
    // equal to its power" (LAW_008 Krennic's When Deployed, SOR_127 Strike True, HMW_114 Breach, ASH_139 Hold Them Off),
    // "…equal to its Raid" (Volley Fire), "A friendly space unit deals its power…" (Turbolaser Salvo). The tooltip reads
    // as "deal damage" + a friendly candidate, which the classifier priced as SELF-HARM — the cheapest body won, so the
    // bot struck with a 0-power Spy token (owner report 2026-10-03, game 1438045). The friendly unit is the one that
    // DEALS the damage: score it by how much. (Self-harm COSTS — "Defeat another friendly unit to…", "Deal 1 to a
    // friendly unit…" — do not match and keep their pricing.)
    if ($onBoard && SWUBotFeatureOn('powersource') && !SWUBotIsEnemyMz($seat, $c)
        && preg_match('/friendly_(?:space_|ground_)?unit_(?:to_deal|deals)_(?:damage_equal_to_)?its_(power|Raid)/i', $tip, $pm)) {
        $v = SWUBotViewForMz($seat, $c);
        if ($v !== null) return 1.0 + (strcasecmp($pm[1], 'Raid') === 0 ? max(0, $v['attackPower'] - $v['power']) : max(0, $v['power'])) - $index * 1e-6;
    }
    if ($effect === 'sacrifice' && $onBoard) {
        // A token/upgrade COST ("Defeat a friendly token", LAW_019 Alliance Outpost): pay the CHEAPEST one. Without
        // this every candidate scored null and the first was paid — the Shield on the reported Secretive Sage.
        if (SWUBotFeatureOn('upgradepicks') && ($uv = _SWUBotUpgradeValue($c)) !== null) return -$uv;
        $v = str_starts_with($c, 'my') ? SWUBotViewForMz($seat, $c) : null;
        // A one-time trade can't wait: an unused unit only loses ties ('unusedsac').
        if ($v !== null && $pairedGain !== null) return $pairedGain - SWUBotSacrificeCost($v) - 0.01 * SWUBotUnusedSacPremium($v);   // the trade: what dies on their side, less what I give
        return $v === null ? -$index * 1e-6 : -(SWUBotSacrificeCost($v) + SWUBotUnusedSacPremium($v));
    }

    if ($tip === 'Choose_an_attack_target' || $tip === 'Choose_Ambush_target') {
        $att = _SWUBotDecisionAttacker($ctx);
        if ($att === null) return -$index * 1e-6;
        // 3-4 seats: between enemy bases, the HEALTHIEST — the seat between me and the win (CR 12.7). A tiny tie-break,
        // so it never outweighs the style's base-vs-unit choice. 2 seats: one base, score unchanged.
        if (str_contains($c, 'Base-')) return SWUBotTargetValue($att, null, $W)
            + (SeatCountForGame() > 2 ? 1e-4 * SWUBaseRemainingHp(SWUMzOwner($c, $seat)) : 0.0);
        $def = SWUBotViewForMz($seat, $c);
        return $def === null ? -$index * 1e-6 : SWUBotTargetValue($att, $def, $W);
    }

    // Twin Suns (3-4 seats) targeting rules (owner, 2026-10-01): which unit/base for an exhaust, bounce, capture, defeat,
    // heal, buff or base ping. Null = no rule for this prompt/candidate, and the ordinary scoring below decides.
    if ($onBoard && SeatCountForGame() > 2 && in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE'], true)) {
        $ts = _SWUBotTSUnitPick($seat, $c, $tip, $head, strval(($ctx['following'] ?? [])[0] ?? ''), $W);
        if ($ts !== null) return $ts;
    }

    // A known continuation, or an unknown one whose prompt reads hostile / beneficial. Hurting only your own
    // units scores below PASS, so an optional "deal damage to a unit" with no enemy target is declined.
    if ($head === 'ATTACH_UPGRADE' && $onBoard && SWUBotFeatureOn('picks')) {
        $s = _SWUBotAttachScore($seat, $c, strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? ''), $W);
        return $s ?? -$index * 1e-6;
    }
    if ($type === 'TOPDECKSEARCH' && $tip === 'Search_top_cards' && SWUBotFeatureOn('picks')) return _SWUBotSearchScore($seat, $c, $W);
    // Part 20 'buffspread': a Support leader's flip turn — who makes the Support attack, and where its phase buffs go.
    if ($onBoard && SWUBotFeatureOn('buffspread')) {
        if ($head === 'SWUSupportChooseAttacker') {
            $v = SWUBotViewForMz($seat, $c);
            return $v === null ? -$index * 1e-6 : _SWUBotSupportAttackerScore($seat, $v);
        }
        if ($head === 'APPLY_PHASE_BUFF' && ($s = _SWUBotBuffSpreadScore($ctx, $seat, $c)) !== null) return $s;
    }
    $hostile = $effect === 'hostile';
    if (($hostile || $effect === 'beneficial') && $onBoard) {
        // An UPGRADE candidate ("Defeat an upgrade", SEC_163 Outer Rim Constable): whose it is decides the SIGN.
        // Hostile + enemy, or beneficial + mine, is the good half; the other two are self-harm. A lone friendly
        // candidate therefore scores below PASS (0), so a "you may" is declined rather than aimed at myself.
        if (SWUBotFeatureOn('upgradepicks') && ($uv = _SWUBotUpgradeValue($c)) !== null) {
            return ($hostile === SWUBotIsEnemyMz($seat, $c)) ? $uv : -$uv;
        }
        $next = strval(($ctx['following'] ?? [])[0] ?? '');
        $s = _SWUBotTargetsScore($seat, $c, $hostile, _SWUBotEffectAmount($tip, $next), $head, $W, $hostile ? _SWUBotWeaknessCount($tip, $next) : 0);
        // PROPOSAL 'removalready' (default OFF): among enemy targets, prefer a READY one — an exhausted unit cannot
        // attack this round, so removing it saves nothing until the regroup. The same logic as the shipped
        // 'shrinkfirst' rule (p6), applied to every hostile target choice rather than to one play.
        if ($s !== null && $hostile && SWUBotProposalOn('removalready') && SWUBotIsEnemyMz($seat, $c)) {
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
        // "Deal N damage to your base" as a cost (TWI_146 Steela Gerrera's search) that would defeat my own base: never.
        // At 3-4 seats that eliminated the bot mid-action (found by DevTools/SWUSimTwinSunsSelfPlay.php, seed ts-19).
        if (preg_match('/deal_(\d+)(_damage)?_to_your_base/i', $tip, $m) && SWUBaseRemainingHp($seat) <= intval($m[1])) return $c === 'NO' ? 0.1 : 0.0;
        // An optional draw that would deck me out first is declined (the deck-out guard).
        if (stripos($tip, 'draw') !== false && SWUBotDrawMultiplier($seat) < 0) return $c === 'NO' ? 0.1 : 0.0;
        // "Use the Force to …" something hostile with no enemy unit to hit: keep the Force (feature 'force').
        if (SWUBotFeatureOn('force') && stripos($tip, 'Use_the_Force') !== false && preg_match('/-\d+\/-\d+|deal|defeat/i', $tip)
            && empty(array_filter(SWUBotEnemyUnits($seat), fn($v) => !$v['isLeader']))) return $c === 'NO' ? 0.1 : 0.0;
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

// ══ TWIN SUNS (3-4 seats) TARGETING RULES ═════════════════════════════════════════════════════════════════════════
// Owner, 2026-10-01 (SWUSim/docs/todo-twinsuns-fill-bot.md, "Targeting rules"). Scores are only ever compared within one
// prompt; a hostile pick on my own side scores below PASS (0) so an optional one is declined. Never reached at 2 seats.

// A "pseudo-random" opponent (rules 10/11 — mill, look at a hand: "it doesn't matter to the bot"). Derived from the game,
// the round and the prompt rather than any RNG, so it never touches the game's RNG stream (which the no-op hash and
// undo depend on) and a replayed decision picks the same seat.
function _SWUBotTSRandomOpponent(int $seat, string $tip): int {
    $opps = SWUBotOpponents($seat);
    global $gameName;
    return $opps[crc32(strval($gameName) . '|' . intval(GetTurnNumber()) . '|' . $tip . '|' . $seat) % count($opps)];
}

// Would $dmg to $victim's base defeat it, and if so does that kill win (SWUBotKillWins)? null = not a kill.
function _SWUBotTSBaseKill(int $seat, int $victim, int $dmg): ?bool {
    if ($dmg <= 0 || $dmg < SWUBaseRemainingHp($victim)) return null;
    return SWUBotKillWins($seat, $victim);
}

// A "choose a player" pick ("You&P2&P3" or "P1&P2&P3").
function _SWUBotTSPlayerPick(int $seat, string $c, string $tip, string $param): float {
    $pick  = $c === 'You' ? $seat : intval(substr($c, 1));
    $t     = strtolower(str_replace('_', ' ', $tip));
    $enemy = SWUIsEnemySeat($seat, $pick);
    $mine  = $pick === $seat;
    // 10/11 — mill / discard from a DECK, look at or reveal a hand or deck: a pseudo-random opponent.
    if (preg_match('/\b(mill|deck)\b/', $t) || preg_match('/\b(look at|reveal)\b/', $t)) {
        if (!$enemy) return -1.0;
        return $pick === _SWUBotTSRandomOpponent($seat, $tip) ? 1.0 : 0.5;
    }
    // 2 — damage to a base ("Deal_2_to_which_opponent's_base?"): the healthiest; a defeat only if it wins.
    if (preg_match('/\bbase\b/', $t) && preg_match('/\b(deal|damage)\b/', $t) && !preg_match('/\bheal\b/', $t)) {
        if (!$enemy) return -1.0;
        $kill = _SWUBotTSBaseKill($seat, $pick, preg_match('/(\d+)/', $t, $m) ? intval($m[1]) : 0);
        if ($kill === false) return -0.5;
        return ($kill === true ? 2.0 : 1.0) + 1e-4 * SWUBaseRemainingHp($pick);
    }
    // 1 — discard from HAND: the opponent holding the most cards.
    if (preg_match('/\bdiscard/', $t)) {
        if (!$enemy) return -1.0;
        return 1.0 + 0.01 * count(array_filter(GetHand($pick), fn($o) => $o !== null && empty($o->removed))) + 1e-5 * SWUBaseRemainingHp($pick);
    }
    $helps = (bool)preg_match('/\b(draws?|ready|heal|gains?|for free|play|create)\b/', $t) && !preg_match('/\b(exhaust|damage|defeat)\b/', $t);
    if ($helps) {
        if ($mine) return 1.0;
        if (!$enemy) return 0.5;   // a teammate
        // 9 — a benefit that must name an enemy (TS26 Count Dooku's second pick): the WEAKEST — fewest units, then least HP.
        return -1.0 - 0.01 * count(SWUBotUnits($pick)) - 1e-4 * SWUBaseRemainingHp($pick);
    }
    if (!$enemy) return $mine ? -1.0 : -0.5;
    return ($pick === SWUBotOpponent($seat) ? 1.0 : 0.5) + 1e-4 * SWUBaseRemainingHp($pick);
}

// One unit/base candidate of an MZCHOOSE / MZMAYCHOOSE. null = no Twin Suns rule for it.
function _SWUBotTSUnitPick(int $seat, string $c, string $tip, string $head, string $next, array $W): ?float {
    $t = strtolower(str_replace('_', ' ', $tip));
    $enemy = SWUBotIsEnemyMz($seat, $c);
    $mine  = str_starts_with($c, 'my');
    // 2 — a ping to a base: the healthiest enemy; a defeat only if it wins.
    if (str_contains($c, 'Base-')) {
        $heals = $head === 'HEAL_TARGET' || preg_match('/\bheal\b/', $t);   // "Heal_3_damage_from_a_unit_or_base" says "damage" too
        if (!$heals && ($head === 'DEAL_BASE_DAMAGE' || (preg_match('/\b(deal|damage)\b/', $t) && str_contains($t, 'base')))) {
            if (!$enemy) return -1.0;
            $owner = SWUMzOwner($c, $seat);
            $dmg = _SWUBotEffectAmount($tip, $next);
            if ($dmg <= 0) $dmg = intval(explode('|', $next)[1] ?? 0);
            $kill = _SWUBotTSBaseKill($seat, $owner, $dmg);
            if ($kill === false) return -0.5;
            return ($kill === true ? 2.0 : 1.0) + 1e-4 * SWUBaseRemainingHp($owner);
        }
        // 7 — heal: my base when it is the LOWEST remaining HP at the table.
        if ($heals) {
            if (!$mine) return -1.0;
            $myHp = SWUBaseRemainingHp($seat);
            foreach (SWUBotOpponents($seat) as $o) if (SWUBaseRemainingHp($o) < $myHp) return 0.2;
            return 3.0;
        }
        return null;
    }
    $v = SWUBotViewForMz($seat, $c);
    if ($v === null) return null;
    $hostileVs = function (float $metric) use ($enemy): float { return $enemy ? 1.0 + 0.01 * $metric : -1.0; };
    // 4 — bounce (return to hand): the highest-HP enemy SENTINEL, else the highest-power enemy unit. Leaders cannot be
    // bounced; a TOKEN can (it leaves play), and every upgrade on the target goes with it (a Voltron'd Spy token).
    if ($head === 'BOUNCE_UNIT' || preg_match('/\breturn\b.*\bhand\b|\bbounce\b/', $t)) {
        if ($v['isLeader']) return -1.0;
        return $enemy ? ($v['sentinel'] ? 2.0 + 0.01 * $v['hp'] : 1.0 + 0.01 * ($v['power'] + $v['upgrades'])) : -1.0;
    }
    // 5 — capture / take control: the most valuable enemy unit.
    if (preg_match('/\bcaptur|\btake control\b/', $t) || str_starts_with($head, 'CAPTURE')) {
        return $hostileVs(SWUBotUnitValue($v));
    }
    // 3 — exhaust: the highest-power READY enemy unit (exhausting an exhausted one does nothing).
    if ($head === 'EXHAUST_UNIT' || preg_match('/^exhaust\b|\bexhaust (a|an|another|up to|the|target)\b/', $t)) {
        if (!$enemy) return -1.0;
        return $v['ready'] ? 1.0 + 0.01 * $v['power'] : 0.05;
    }
    // 6 — defeat: the highest threat (power, then value).
    if ($head === 'DEFEAT_UNIT' || preg_match('/^defeat (a|an|another|up to)\b/', $t)) {
        return $hostileVs($v['power'] + 0.1 * SWUBotUnitValue($v));
    }
    // 7 — heal a unit: my most-damaged unit.
    if ($head === 'HEAL_TARGET' || preg_match('/\bheal\b/', $t)) {
        if ($enemy) return -1.0;
        $damage = $v['hp'] - $v['remaining'];
        return ($mine ? 1.0 : 0.5) + 0.01 * $damage;
    }
    // 8 — Shield / Experience / Advantage / a buff on my unit: my strongest READY attacker.
    if (in_array($head, ['GIVE_SHIELD', 'GIVE_EXPERIENCE', 'GIVE_ADVANTAGE', 'APPLY_PHASE_BUFF'], true)
        || preg_match('/\b(shield|experience|advantage)\b|\+\d+\/\+\d+/', $t)) {
        if ($enemy) return -1.0;
        return ($mine ? 1.0 : 0.5) + ($v['ready'] ? 0.5 : 0.0) + 0.01 * $v['attackPower'];
    }
    return null;
}

// What an unknown continuation's prompt does to its target, read from the raw tooltip: 'sacrifice'
// (defeat one of your own units as a cost), 'hostile', 'beneficial', or '' when the wording says neither.
function _SWUBotTooltipEffect(string $tooltip): string {
    $t = strtolower(str_replace('_', ' ', $tooltip));
    if (preg_match('/\bdefeat (a |an |another )?friendly\b/', $t)) return 'sacrifice';
    // "Give a unit -2/-2" is HOSTILE despite "give" (feature 'targeting'; Talzin debuffed its own units 229 times).
    if (SWUBotFeatureOn('targeting') && preg_match('/-\d+\/-\d+/', $t)) return 'hostile';
    // So is "Give a Weakness token" (HMW_T02, -1/-1): HMW_100 Torrent's own continuation sends it here rather than via
    // GIVE_WEAKNESS, and "give" read as beneficial — the bot Torrented its own Sentinel to death (game 1438045).
    if (SWUBotFeatureOn('targeting') && preg_match('/\bweakness\b/', $t)) return 'hostile';
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

// How many Weakness tokens a hostile prompt gives its ONE target (feature 'weakness'): "GIVE_WEAKNESS|N" (Talzin's
// Shuttle), "Give_N_Weakness_tokens…" (Torrent off a Naboo base names its count), any other "…Weakness_token…" = 1.
// 0 = not a Weakness give. Damage in the same prompt (Inferno Squad's "Deal_1_damage…and_give_a_Weakness_token") stays
// in _SWUBotEffectAmount: a Shield stops that half, never this one.
function _SWUBotWeaknessCount(string $tip, string $next): int {
    if (!SWUBotFeatureOn('weakness')) return 0;
    $p = explode('|', $next);
    if (($p[0] ?? '') === 'GIVE_WEAKNESS') return max(1, intval($p[1] ?? 1));
    if (preg_match('/give_(\d+)_weakness_tokens/i', $tip, $m)) return intval($m[1]);
    return preg_match('/weakness_token/i', $tip) ? 1 : 0;
}

// What $weak Weakness tokens (plus $dmg damage a Shield may stop) on the unit view $v are worth to $seat (feature
// 'weakness'). One scale for a single pick and for each part of a spread, so a spread sums cleanly:
//   CLEAN UP — the tokens defeat it: an enemy kill is priced as every other targeted kill; my own unit, as a sacrifice.
//   SOFTEN UP — it survives: each token takes 1 HP and 1 power for the rest of the game; chip-priced (2 points a token),
//   and scaled by the unit's power so the tokens CONCENTRATE on the strongest body (a token on a 1/5 is worth less than
//   one on a 3/5).
function _SWUBotWeaknessScore(int $seat, array $v, bool $enemy, int $dmg, string $head, int $weak, array $W): float {
    $shieldStopsDamage = !str_starts_with($head, 'APPLY_PHASE_DEBUFF') && $v['shields'] > 0;
    $loss = $weak + ($shieldStopsDamage ? 0 : $dmg);
    $kill = SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0;
    $soften = $W['chip'] * 2 * $weak * (1.0 + 0.1 * max(0, intval($v['power'])))
            + ($shieldStopsDamage ? 0.0 : SWUBotUnitValue($v) * $W['chip'] * $dmg / max(1, $v['hp']));
    if ($enemy) {
        if ($loss >= $v['remaining']) return $kill;
        if (SWUBotFeatureOn('setup') && !$v['isLeader'] && $v['remaining'] - $loss <= _SWUBotFinisherHP($seat)) return max($soften, 0.8 * $kill);
        return $soften;
    }
    if ($loss >= $v['remaining']) return SWUBotFeatureOn('fodder') ? -SWUBotSacrificeCost($v) : -SWUBotUnitValue($v);
    return -$soften;
}

// A Weakness spread ("mz:n,mz:n", MZSPLITASSIGN — HMW_071 Ravage), part by part on _SWUBotWeaknessScore's scale.
function _SWUBotWeaknessSplitScore(int $seat, string $candidate, array $W): float {
    $s = 0.0;
    foreach (explode(',', $candidate) as $pair) {
        $bits = explode(':', $pair);
        if (count($bits) < 2 || intval($bits[1]) <= 0) continue;
        $v = SWUBotViewForMz($seat, trim($bits[0]));
        if ($v === null) continue;
        $s += _SWUBotWeaknessScore($seat, $v, SWUBotIsEnemyMz($seat, trim($bits[0])), 0, 'GIVE_WEAKNESS', intval($bits[1]), $W);
    }
    return $s;
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
function _SWUBotTargetScore(int $seat, string $mz, bool $hostile, int $amount, string $head, array $W, int $weak = 0): ?float {
    $enemy = SWUBotIsEnemyMz($seat, $mz);
    $good = $hostile ? $enemy : !$enemy;
    if (str_contains($mz, 'Base-')) return $good ? $W['base'] : -$W['base'];
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null) return null;
    // $weak Weakness tokens (feature 'weakness'): HP reduction a Shield does not stop — clean up, else soften up.
    if ($weak > 0 && $hostile) return _SWUBotWeaknessScore($seat, $v, $enemy, $amount, $head, $weak, $W);
    $score = $good ? SWUBotUnitValue($v) : -SWUBotUnitValue($v);
    // A hostile effect aimed at MY OWN board is a sacrifice: price it as fodder, not as a loss of printed value
    // (feature 'fodder'), so the unit whose defeat pays me back is the one that goes.
    if (!$good && $hostile && !$enemy && SWUBotFeatureOn('fodder')) {
        $score = -SWUBotSacrificeCost($v);
        // ⚠ …but fodder pricing assumes the unit DIES. For a DAMAGE effect that the unit survives it costs only
        // the damage, so pricing every friendly candidate as a sacrifice made the most-hurt unit look like the
        // cheapest one — and a 1-damage ping then picked the ship it KILLS over one it would merely chip
        // (prod bug #1100, game 1402804: Luke JTL_012 shot a 1-HP Y-Wing while a healthy Black One stood next
        // to it). Mirrors the enemy branch below, on the same chip scale. $amount is 0 for defeat/bounce/
        // exhaust (_SWUBotEffectAmount), so those keep the pure sacrifice price, which is correct for them.
        if ($amount > 0 && SWUBotFeatureOn('targeting')) {
            $absorbed = !str_starts_with($head, 'APPLY_PHASE_DEBUFF') && $v['shields'] > 0;
            if ($absorbed) $score = -0.1 * SWUBotUnitValue($v);            // a Shield eats it; the shield is the cost
            elseif ($amount < $v['remaining']) $score = -SWUBotUnitValue($v) * $W['chip'] * $amount / max(1, $v['hp']);
        }
    }
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
function _SWUBotTargetsScore(int $seat, string $candidate, bool $hostile, int $amount, string $head, array $W, int $weak = 0): ?float {
    $parts = (str_contains($candidate, '&') && SWUBotFeatureOn('targeting')) ? explode('&', $candidate) : [$candidate];
    $sum = 0.0;
    foreach ($parts as $p) {
        $s = _SWUBotTargetScore($seat, $p, $hostile, $amount, $head, $W, $weak);
        if ($s === null) return null;
        $sum += $s;
    }
    return $sum;
}

// ── PROPOSAL 'landomill' (default OFF) — owner ruling 2026-09-23, Lando Calrissian Full Sabacc (LAW_018) ───────────
// "Lando names my own deck early on to ramp up to bombs early. after flip turn (and after my leader unit is killed and
// returned to the leader zone), i either don't use his ability, or i will mill the opponent based on their deck and
// what is left in their deck" — with a spare resource left after the plays you wanted, and when their deck is low the
// mill IS the win condition. Traced 2026-09-23: the shipped bot mills its OWN deck 50/50 times, right before the flip
// and wrong after it.
// ⚠ The Action is a FRONT-side ability, so it is unavailable while deployed: the phases are pre-flip
// (EpicActionUsed false) and flipped-and-returned (EpicActionUsed true, Deployed false).
// ⚠ PUBLIC INFORMATION ONLY. A live Arenabot game does not know the opponent's list, so "their deck" is read from
// what they have SHOWN: their discard pile, their units in play, then their leader and base. Reading GetDeck($opp)
// would be peeking at hidden information and would not transfer out of self-play.
const SWU_BOT_LANDO_ACTION = '/Choose an aspect, then discard a card from a deck/i';

// [$isLandoLeader, $postFlip] for this seat's undeployed leader.
function _SWUBotLandoPhase(int $seat): array {
    $l = GetLeader($seat)[0] ?? null;
    if ($l === null) return [false, false];
    $deployed = !empty($l->Deployed) && strval($l->Deployed) !== 'false';
    if ($deployed || !preg_match(SWU_BOT_LANDO_ACTION, strval(CardText(strval($l->CardID ?? ''))))) return [false, false];
    $flipped = !empty($l->EpicActionUsed) && strval($l->EpicActionUsed) !== 'false';
    return [true, $flipped];
}

// How often an aspect appears across a bag of CardIDs.
function _SWUBotAspectCounts(array $cardIDs): array {
    $out = [];
    foreach ($cardIDs as $id) {
        foreach (explode(',', strval(CardAspect(strval($id)) ?? '')) as $a) {
            $a = trim($a);
            if ($a !== '') $out[$a] = ($out[$a] ?? 0) + 1;
        }
    }
    return $out;
}

// The aspect worth naming: my own deck before the flip, what they have SHOWN after it.
function _SWUBotLandoAspectCounts(int $seat, bool $postFlip): array {
    if (!$postFlip) {
        return _SWUBotAspectCounts(array_map(fn($o) => strval($o->CardID ?? ''),
            array_filter(GetDeck($seat), fn($o) => $o !== null && empty($o->removed))));
    }
    $opp = SWUBotOpponent($seat);
    $seen = array_map(fn($o) => strval($o->CardID ?? ''), array_filter(GetDiscard($opp), fn($o) => $o !== null && empty($o->removed)));
    foreach (SWUBotUnits($opp) as $v) $seen[] = strval($v['cardID']);
    $counts = _SWUBotAspectCounts($seen);
    if (!empty($counts)) return $counts;
    // Nothing shown yet: their leader and base are the only public read on what their deck is made of.
    $ids = [];
    foreach (GetLeader($opp) as $l) { if ($l !== null && empty($l->removed)) $ids[] = strval($l->CardID ?? ''); }
    $b = GetBase($opp)[0] ?? null;
    if ($b !== null) $ids[] = strval($b->CardID ?? '');
    return _SWUBotAspectCounts($ids);
}

// The two prompts the Action raises. Returns null when this is not a landomill decision.
function _SWUBotLandoDecisionScore(array $ctx, string $c, int $index): ?float {
    if (!SWUBotProposalOn('landomill')) return null;
    $seat = intval($ctx['seat']);
    [$isLando, $post] = _SWUBotLandoPhase($seat);
    if (!$isLando) return null;
    $tip = strval($ctx['tooltip'] ?? '');
    if ($tip === 'Choose_an_aspect') {
        $counts = _SWUBotLandoAspectCounts($seat, $post);
        return 1.0 + 0.01 * floatval($counts[$c] ?? 0) - $index * 1e-6;
    }
    if ($tip === 'Discard_from_which_deck?') {
        $want = $post ? "Opponent's_deck" : 'Your_deck';
        if ($c === $want) return 1.0;
        return $c === 'PASS' || $c === '-' ? -1.0 : -$index * 1e-6;   // never decline the mill itself
    }
    return null;
}

// WHEN to use the Action once the leader has come back (the pre-flip half is already the bot's behaviour):
//   - skip it while resources + Credits already cover the biggest card in hand — the Credit buys nothing;
//   - with their deck nearly out, the mill IS the win condition, so it goes FIRST;
//   - otherwise it is a spare-resource play: below the round's real plays, above passing.
function _SWUBotLandoAbilityScore(array $ctx, array $W): ?float {
    if (!SWUBotProposalOn('landomill')) return null;
    $seat = intval($ctx['seat']);
    [$isLando, $post] = _SWUBotLandoPhase($seat);
    if (!$isLando || !$post) return null;
    $theirDeck = count(array_filter(GetDeck(SWUBotOpponent($seat)), fn($o) => $o !== null && empty($o->removed)));
    if ($theirDeck <= SWU_BOT_LANDO_MILL_KILL) return 10.0;            // milling them out is the plan now
    $biggest = 0;
    foreach (GetHand($seat) as $o) { if ($o !== null && empty($o->removed)) $biggest = max($biggest, intval(CardCost(strval($o->CardID ?? '')))); }
    if ($biggest > 0 && SWUTotalPaymentCapacity($seat) >= $biggest) return -0.5;   // nothing left to ramp toward
    return 0.02;                                                       // the spare-resource play, after everything else
}

// Their deck at or under this, and the mill outranks the round's plays: each card they cannot draw is damage.
const SWU_BOT_LANDO_MILL_KILL = 5;

// What losing one of my units costs: its value (cost + what dies with it), less what its When Defeated
// ability gives back. Owner: Krennic's sacrificial ramp spends cheap units with beneficial When Defeated.
// The most valuable enemy NON-LEADER unit, in SWUBotUnitValue's currency — what a paired defeat (Chimaera) kills when its
// enemy pick goes to the best target. Every opposing seat (_SWUAllEnemyUnits is team-aware). 0 with none.
function _SWUBotBestEnemyNonLeaderValue(int $seat): float {
    $best = 0.0;
    if (!function_exists('_SWUAllEnemyUnits')) return $best;
    foreach (_SWUAllEnemyUnits($seat) as $u) {
        $v = SWUBotUnitView($u);
        if (!empty($v['isLeader'])) continue;
        $best = max($best, SWUBotUnitValue($v));
    }
    return $best;
}

// Feature 'doomedsac' (p18, owner rulings 2a/2b 2026-10-01). A unit that is going to die anyway costs little to sacrifice;
// a deployed leader is still priced above a 2-cost body, so it goes only when no other fodder is on offer.
const SWU_BOT_DOOMED_SAC_COST = 0.5;          // below a healthy 1-cost body (1.0), above fodder that pays back (<= 0)
const SWU_BOT_DOOMED_LEADER_SAC_COST = 2.5;   // above any 2-cost body or token: "assumes no other fodder unit is available"
const SWU_BOT_DOOMED_HP = 2;                  // "2 or less remaining HP"

// "It will die anyway": 2 or less HP left, no Shield, and an enemy unit in its arena hits that hard.
function SWUBotUnitIsDoomed(array $v): bool {
    if (intval($v['remaining']) > SWU_BOT_DOOMED_HP || intval($v['shields']) > 0 || !function_exists('_SWUAllEnemyUnits')) return false;
    foreach (_SWUAllEnemyUnits(intval($v['controller'])) as $u) {
        $e = SWUBotUnitView($u);
        if ($e['arena'] === $v['arena'] && $e['power'] >= intval($v['remaining'])) return true;
    }
    return false;
}

// Feature 'unusedsac' (p18, task 3 from the owner's Online games): a unit still READY in the action phase has not
// attacked yet this round, so sacrificing it now throws that attack away. Measured: the bot's Krennic sacrificed an
// unused unit 31% of the time, the human 0% — attack first, then cash the exhausted body in. Charged only where the
// sacrifice can WAIT (a repeatable Action: the use decision and its pick); for a one-time trade (Chimaera) it only breaks
// ties, so a good trade is never declined over it.
const SWU_BOT_UNUSED_SAC_PER_POWER = 0.5;

function SWUBotUnusedSacPremium(array $v): float {
    if (!SWUBotFeatureOn('unusedsac') || empty($v['ready']) || intval($v['attackPower']) <= 0) return 0.0;
    if (!function_exists('GetCurrentPhase') || strval(GetCurrentPhase()) !== 'MAIN') return 0.0;
    return SWU_BOT_UNUSED_SAC_PER_POWER * intval($v['attackPower']);
}

function SWUBotSacrificeCost(array $v): float {
    if (SWUBotFeatureOn('doomedsac')) {
        if (!empty($v['isLeader'])) {
            // 2a: Condemned ("loses all abilities") — sending it back restores the leader's front side.
            $condemned = isset($v['obj']) && function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($v['obj'], 'SEC_038');
            if ($condemned || SWUBotUnitIsDoomed($v)) return min(SWU_BOT_DOOMED_LEADER_SAC_COST, SWUBotUnitValue($v));
        } elseif (SWUBotUnitIsDoomed($v)) {
            // 2b: dying anyway — unless the When Defeated pricing below already makes it cheaper still.
            return min(SWU_BOT_DOOMED_SAC_COST, _SWUBotSacrificeCostByValue($v));
        }
    }
    return _SWUBotSacrificeCostByValue($v);
}

function _SWUBotSacrificeCostByValue(array $v): float {
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
    $notDying = !SWUBotLethalNextRound(SWUBotMostDangerousOpponent($seat), $seat);
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

function _SWUBotPlayValue(int $seat, string $cid, array $W, string $fromZone = 'hand'): float {
    // PROPOSAL 'cardvalue' (default OFF): the whole valuation comes from BotCardValue.php instead — Body +
    // Effect - SelfCost, read off the board, in expected base damage. It already includes the
    // `develop x cost` floor and the unitPlay term, so this returns outright rather than adding to the
    // flat tag sum below; a half-migrated valuation would be two models disagreeing.
    // ⚠ The bomb-timing and ctxpower terms below still apply on top — they are about WHEN to commit a card
    // and about board-dependent POWER, neither of which the card model prices.
    if (function_exists('SWUBotProposalOn') && SWUBotProposalOn('cardvalue')) {
        return SWUBotCardValue($seat, $cid, $fromZone, 'midrange', $W)
             + _SWUBotBombTimingValue($seat, $cid, $W)
             + $W['base'] * SWUBotContextSurplus($seat, $cid);
    }
    $v = $W['develop'] * intval(CardCost($cid));
    foreach (SWUBotCardTags($cid) as $t) {
        if ($t === 'debuff-all-enemy-units' || $t === 'heal-on-enemy-defeat') continue;   // board-scaled below, never flat
        $v += ($W[$t] ?? 0.0) * ($t === 'draw' ? SWUBotDrawMultiplier($seat) : 1.0);
    }
    // "Give each enemy unit -X/-X" (owner 2026-10-01): worth the weight once PER enemy unit the shrink would kill —
    // zero on a board of big units, the most on a wide board of weak ones.
    if (in_array('debuff-all-enemy-units', SWUBotCardTags($cid), true)) {
        $v += ($W['debuff-all-enemy-units'] ?? 0.0) * SWUBotDebuffAllEnemyKills($seat, $cid);
    }
    // HEAL-ON-ENEMY-DEFEAT (owner 2026-10-01: Chimaera's lifegain "is what makes the card so good"). Per point of life:
    //   the ENGINE itself — N × the enemy kills it can expect: its own When Played kill plus the removal/sweeps in hand;
    //   any OTHER card while I control engines — N × the kills it makes (Lost and Forgotten heals 5 with Chimaera out).
    // Capped at the base's damage + a round of headroom: life past that is wasted.
    $wHeal = floatval($W['heal-on-enemy-defeat'] ?? 0.0);
    if ($wHeal > 0.0) {
        if (in_array('heal-on-enemy-defeat', SWUBotCardTags($cid), true)) {
            $kills = SWUBotPlayEnemyKills($seat, $cid); $skipped = false;
            foreach (GetHand($seat) as $o) {
                if ($o === null || !empty($o->removed)) continue;
                $hc = strval($o->CardID ?? '');
                if ($hc === $cid && !$skipped) { $skipped = true; continue; }   // the engine's own copy
                $kills += SWUBotPlayEnemyKills($seat, $hc);
            }
            $v += $wHeal * min(SWUBotHealOnDefeatAmount($cid) * $kills, SWUBotHealCap($seat));
        } elseif (($per = SWUBotHealPerEnemyKill($seat)) > 0) {
            $v += $wHeal * min($per * SWUBotPlayEnemyKills($seat, $cid), SWUBotHealCap($seat));
        }
    }
    // POWER STRIKE (owner 2026-10-01): worthless without a striker — the damage-enemy-unit value it carries is taken
    // back — and worth a KILL when the striker's power can defeat an enemy unit. Never a flat weight.
    if (in_array('power-strike', SWUBotCardTags($cid), true)) {
        $p = SWUBotPowerStrikePower($seat, $cid);
        if ($p <= 0) $v -= ($W['damage-enemy-unit'] ?? 0.0) * (in_array('damage-enemy-unit', SWUBotCardTags($cid), true) ? 1.0 : 0.0);
        elseif (SWUBotPowerStrikeCanKill($seat, $p)) $v += $W['kill'];
    }
    // PROPOSAL 'creditbank' (default OFF): paying for this card out of BANKED CREDITS is a real cost when
    // those Credits are part of a line (owner 2026-09-29, #1099: 6R + 2 Credits + the waiver casts SRI).
    // Zero when nothing is banked, or when no card in hand is reachable with the bank — a Credit that is
    // part of no plan is still worth nothing to hold.
    if (SWUBotProposalOn('creditbank')) $v -= _SWUBotCreditSpendCost($seat, $cid, $W);
    if (str_contains(strval(CardType($cid)), 'Unit')) $v += $W['unitPlay'];
    $v += _SWUBotBombTimingValue($seat, $cid, $W);
    // Proposal 'ctxpower': this function prices a unit by its COST, so a card whose power depends on the
    // board is worth the same here whether it is a 3/3 or a 9/9. Add the board-dependent SURPLUS, priced
    // per point like any other power (W['base'] — the same currency SWUBotTargetValue uses for a swing).
    // Zero for every card without an arm, and zero on an empty board, so the default-OFF proposal changes
    // nothing until it is switched on.
    $v += $W['base'] * SWUBotContextSurplus($seat, $cid);
    // Part 23 'bigcredit': a credit-RAMP deck spends a banked Credit only on a big play (owner 2026-10-03, game 1438045:
    // "krennic didn't bank credits. wasted them right away on Onyx Squad Brute").
    if (SWUBotFeatureOn('bigcredit') && SWUBotCreditSpendFor($seat, $cid) > 0 && SWUBotBanksCredits($seat) && !SWUBotCreditWorthy($cid)) {
        $v = min($v, -0.5);
    }
    return $v;
}

// ── Part 23 'bigcredit' ───────────────────────────────────────────────────────────────────────────────────────────
// Owner ruling 2026-10-03: a banked Credit is for a BIG play — a bomb, removal or a wipe — never the shortfall on a cheap
// body (the round-1 Krennic bot sacrificed a unit for a Credit and spent it at once on a 2-cost Onyx Squadron Brute).
// Chosen over the two 'creditbank' proposals, which protected a whole 6R line and measured WORSE vs Ahsoka Blue (they
// pushed plays below PASS everywhere); this one holds only a cheap play that NEEDS the Credit, so the bot still plays
// every card its resources pay for.
// Only a credit-RAMP deck banks: 'credit-ramp' without 'tempo' (BotFlavours.php) — Krennic. Lando's Credits are a
// tempo engine ('credit-ramp' + 'tempo') and keep being spent.
const SWU_BOT_BIGCREDIT_MIN_COST = 5;
const SWU_BOT_BIGCREDIT_TAGS = ['removal', 'wipe', 'debuff-all-enemy-units', 'damage-enemy-unit'];

function SWUBotBanksCredits(int $seat): bool {
    $f = function_exists('SWUBotDeckFlavours') ? SWUBotDeckFlavours($seat) : [];
    return in_array('credit-ramp', $f, true) && !in_array('tempo', $f, true);
}

// How many banked Credits playing hand card $cid takes: the shortfall the ready resources cannot cover (the engine
// spends Credits automatically, and only then — "defeated 1 Credit token to pay 1 less").
function SWUBotCreditSpendFor(int $seat, string $cid): int {
    if (!function_exists('SWUUsableCreditTokenMzIDs') || !function_exists('SWUComputePlayCost')) return 0;
    $banked = count(SWUUsableCreditTokenMzIDs($seat));
    if ($banked <= 0) return 0;
    foreach (GetHand($seat) as $h) {
        if ($h === null || !empty($h->removed) || strval($h->CardID ?? '') !== $cid) continue;
        return max(0, min($banked, intval(SWUComputePlayCost($seat, $h)) - SWUResourceCount($seat, true)));
    }
    return 0;
}

function SWUBotCreditWorthy(string $cid): bool {
    return intval(CardCost($cid)) >= SWU_BOT_BIGCREDIT_MIN_COST || !empty(array_intersect(SWUBotCardTags($cid), SWU_BOT_BIGCREDIT_TAGS));
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
// Could I already cast this card WITHOUT the action being scored? Proposal 'aspectwaiver' — an action that only
// lets me do something I could already do has enabled nothing.
function _SWUBotCardAlreadyPlayable(int $seat, $obj): bool {
    if (!function_exists('SWUComputePlayCost') || !function_exists('SWUTotalPaymentCapacity')) return false;
    return intval(SWUComputePlayCost($seat, $obj)) <= SWUTotalPaymentCapacity($seat);
}

function _SWUBotEnabledPlayValue(int $seat, array $handBefore, array $after, array $W): float {
    $d = $after['decision'] ?? null;
    if ($d !== null) {
        $parts = array_values(array_filter(explode('&', strval($d['param'] ?? ''))));
        if (empty($parts) || count(array_filter($parts, fn($p) => !preg_match('/^myHand-\d+$/', $p))) > 0) return 0.0;
        $best = 0.0;
        foreach ($parts as $p) {
            $o = GetHand($seat)[intval(substr($p, strlen('myHand-')))] ?? null;
            if ($o === null) continue;
            // PROPOSAL 'aspectwaiver': only credit a card this action actually UNLOCKS.
            // ⚠ THIS IS WHY A ONCE-PER-GAME WAIVER GETS BURNED IN ROUND 1. The value is otherwise the best card
            // the prompt offers whether or not I could already cast it, so LAW_020 Daimyo's Palace scored 0.6 for
            // "enabling" an on-aspect 2-drop it never needed — above PASS, so it was spent immediately. Traced:
            // six of eight games burned it in round 1 at capacity 2, and one game reached round 5 holding LAW_044
            // with the waiver already gone. Reading the cost here is safe: SWUBotLookahead has already RESTORED
            // the pre-action state, so this is "could I cast it WITHOUT this action?".
            if (SWUBotFeatureOn('aspectwaiver') && _SWUBotCardAlreadyPlayable($seat, $o)) continue;
            $best = max($best, _SWUBotPlayValue($seat, strval($o->CardID), $W));
        }
        // Nothing unlocked → the action enables nothing, so it is worth nothing here.
        if (SWUBotFeatureOn('aspectwaiver') && $best <= 0.0) return 0.0;
        return $best + 0.1;
    }
    // ⚠ THE SAME "did it actually unlock anything?" TEST IS NEEDED HERE. This branch runs when the prompt
    // AUTO-RESOLVED because there was one legal card — which is the common case for a waiver in the early game,
    // and exactly the round-1 board that burned it. Without the filter the action is credited for the cheap
    // on-aspect card it happened to play, which it could have played anyway.
    $gone = $handBefore;
    foreach ((array)($after['hand'] ?? []) as $cid) { $k = array_search($cid, $gone, true); if ($k !== false) unset($gone[$k]); }
    $inPlay = array_map(fn($u) => $u[0], (array)($after['sig']['mine'] ?? []));
    $alreadyCastable = [];
    if (SWUBotFeatureOn('aspectwaiver')) {
        foreach (GetHand($seat) as $o) {
            if ($o !== null && empty($o->removed) && _SWUBotCardAlreadyPlayable($seat, $o)) $alreadyCastable[] = strval($o->CardID ?? '');
        }
    }
    $v = 0.0;
    foreach ($gone as $cid) {
        if (!in_array($cid, $inPlay, true)) continue;
        if (in_array($cid, $alreadyCastable, true)) continue;
        $v += _SWUBotPlayValue($seat, $cid, $W);
    }
    return $v;
}

// Where an upgrade goes (feature 'picks'): a helpful upgrade on the strongest friendly attacker (ready first); a
// harmful one (negative stats, or "attached unit can't / cannot / loses") on the most valuable enemy.
// Feature 'hostpolicy' (p17). Every attachment is called an "Upgrade", but some are DOWNGRADES — and the board read
// cannot see most of what makes them one (a Bounty, a lost ability, "can't ready"), so neither the gift check nor the
// host pick could tell. Owner rulings 2026-10-01 place each listed card; anything unlisted keeps the generic scoring.
//   enemy      — only on an enemy unit
//   own        — only on my own unit
//   own-small  — only on my own unit whose printed stats it raises (LOF_056: printed 5/5 — "doesn't see play")
//   entrenched — an enemy unit WITHOUT Overwhelm, or my own Sentinel (SOR_072, "the only one so far that is truly mixed")
//   condemn    — SEC_038: an enemy unit not already Condemned, or MY unit that already is. A second copy's "loses all
//                other abilities" stops the first copy's ability being gained (official reminder), so on my unit it
//                cancels the defender's -6/-0 disclose — and on an enemy unit it would cancel my own Condemn.
const SWU_BOT_UPGRADE_HOST_POLICY = [
    // Grants a Bounty, or pays the opponent when the unit is defeated.
    'SHD_221' => 'enemy', 'SHD_068' => 'enemy', 'SHD_071' => 'enemy', 'SHD_123' => 'enemy', 'SHD_125' => 'enemy',
    'SHD_173' => 'enemy', 'SHD_176' => 'enemy', 'SHD_222' => 'enemy', 'SHD_226' => 'enemy', 'SHD_261' => 'enemy',
    'LAW_141' => 'enemy',
    // Strips abilities / stops or taxes readying.
    'SHD_072' => 'enemy', 'SEC_054' => 'enemy', 'SHD_193' => 'enemy', 'LAW_077' => 'enemy',
    'JTL_192' => 'enemy', 'ASH_088' => 'enemy',
    // Negative stats, or damages its host.
    'TWI_070' => 'enemy', 'LAW_127' => 'enemy', 'ASH_054' => 'enemy', 'ASH_085' => 'enemy', 'ASH_150' => 'enemy',
    'ASH_198' => 'enemy',   // Nowhere to Hide: "no one ever plays it on their own unit"
    'SOR_122' => 'enemy',   // Traitorous: its whole value is taking control of an enemy unit
    // Owner: always on my own unit.
    'ASH_228' => 'own',     // Preparation — on a unit that just entered play or already attacked (already exhausted)
    'LOF_139' => 'own', 'JTL_260' => 'own', 'LOF_138' => 'own', 'LAW_225' => 'own',
    'LOF_056' => 'own-small',
    'SOR_072' => 'entrenched',
    'SEC_038' => 'condemn',
];

// Is $cid a DOWNGRADE — worse for the unit it is attached to (feature 'weakness', p19)? The Weakness token (HMW_T02,
// -1/-1) and every enemy-only policy card. Not SOR_122 Traitorous: once attached, its host is MINE, so it is my upgrade.
function SWUBotIsDowngrade(string $cid): bool {
    if ($cid === 'HMW_T02') return true;
    return $cid !== 'SOR_122' && (SWU_BOT_UPGRADE_HOST_POLICY[$cid] ?? '') === 'enemy';
}

// May $upgradeID go on the unit view $v (on $enemy's side)? null = the card has no policy.
function _SWUBotHostAllowed(string $upgradeID, array $v, bool $enemy): ?bool {
    $policy = SWU_BOT_UPGRADE_HOST_POLICY[$upgradeID] ?? null;
    if ($policy === null) return null;
    switch ($policy) {
        case 'enemy': return $enemy;
        case 'own':   return !$enemy;
        case 'own-small':
            $p = intval(CardPower($v['cardID'])); $h = intval(CardHp($v['cardID']));
            return !$enemy && $p <= 5 && $h <= 5 && ($p < 5 || $h < 5);
        case 'entrenched': return $enemy ? !$v['overwhelm'] : $v['sentinel'];
        case 'condemn':    return $enemy !== _SWUUnitHasUpgrade($v['obj'], 'SEC_038');
    }
    return null;
}

// The legal hosts for $cid the policy allows, or null when the card has no policy.
function _SWUBotPolicyHosts(int $seat, string $cid): ?array {
    if (!isset(SWU_BOT_UPGRADE_HOST_POLICY[$cid])) return null;
    $out = [];
    foreach (SWUGetUpgradeValidTargets($seat, $cid) as $mz) {
        $v = SWUBotViewForMz($seat, $mz);
        if ($v !== null && _SWUBotHostAllowed($cid, $v, SWUBotIsEnemyMz($seat, $mz))) $out[] = $mz;
    }
    return $out;
}

function _SWUBotAttachScore(int $seat, string $mz, string $upgradeID, array $W): ?float {
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null) return null;
    $enemy = SWUBotIsEnemyMz($seat, $mz);
    if (SWUBotFeatureOn('hostpolicy') && ($allowed = _SWUBotHostAllowed($upgradeID, $v, $enemy)) !== null) {
        if (!$allowed) return -100.0;
        if ($enemy) return SWUBotUnitValue($v);          // the bigger enemy, the more it takes
        if (SWU_BOT_UPGRADE_HOST_POLICY[$upgradeID] === 'own-small')            // the most printed stats gained
            return 10.0 - intval(CardPower($v['cardID'])) - intval(CardHp($v['cardID']));
        // "When Played: Exhaust attached unit" (Preparation) costs nothing on a unit that is already exhausted.
        $exhaustsHost = preg_match('/When Played: Exhaust attached unit/i', strval(CardText($upgradeID))) === 1;
        return $v['attackPower'] + 0.1 * SWUBotUnitValue($v)
             + ($exhaustsHost ? ($v['ready'] ? -$W['ready'] : $W['ready']) : ($v['ready'] ? $W['ready'] : 0.0));
    }
    $harmful = intval(CardUpgradePower($upgradeID)) < 0 || intval(CardUpgradeHp($upgradeID)) < 0
        || preg_match("/attached unit (can't|cannot|loses)/i", strval(CardText($upgradeID)))
        || (SWUBotFeatureOn('targeting2') && preg_match('/loses all (other )?abilities|gets -\d+\/-\d+/i', strval(CardText($upgradeID))));
    if ($harmful) return $enemy ? SWUBotUnitValue($v) : -SWUBotUnitValue($v);
    if ($enemy) return -SWUBotUnitValue($v);
    // Part 20 'readyhost': "When played: attack with attached unit" — only a READY host can make that attack, worth what
    // it hits for with the upgrade on. An exhausted host gets nothing from it (JTL_203 Han Solo went on an exhausted T-6).
    $attack = (SWUBotFeatureOn('readyhost') && $v['ready'] && in_array('grants-attack', SWUBotCardTags($upgradeID), true))
        ? 1.0 + $v['attackPower'] + max(0, intval(CardUpgradePower($upgradeID))) : 0.0;
    return $v['attackPower'] + ($v['ready'] ? $W['ready'] : 0.0) + 0.1 * SWUBotUnitValue($v) + $attack;
}

// ── Part 20 'buffspread' — a Support leader's flip turn (owner rulings 2026-10-02, Ahsoka ASH_009) ──────────────────
// Arenas where an attack cannot reach a base: some opponent has a Sentinel there (exhausted ones still guard).
function _SWUBotBlockedArenas(int $seat): array {
    $out = ['Ground' => false, 'Space' => false];
    foreach (SWUBotOpponents($seat) as $opp) {
        foreach (_SWUBotSentinelArenas($opp) as $arena => $has) if ($has) $out[$arena] = true;
    }
    return $out;
}

// My Support attack is still to come: a live Support trigger of mine on the effect stack.
function _SWUBotSupportPending(int $seat): bool {
    foreach (GetEffectStack() as $e) {
        if (empty($e->removed) && strval($e->TriggerType ?? '') === 'Support' && intval($e->Controller ?? 0) === $seat) return true;
    }
    return false;
}

// The unit that should make the Support attack: a ready non-leader unit ("another unit"), in an arena that reaches the
// base if any does, the strongest attacker there (Raid is lent to whoever it is, so it never changes the pick).
function _SWUBotSupportPlanUid(int $seat): int {
    $blocked = _SWUBotBlockedArenas($seat);
    $best = null; $bestKey = null;
    foreach (SWUBotUnits($seat) as $v) {
        if (!$v['ready'] || $v['isLeader']) continue;
        $key = [$blocked[$v['arena']] ? 0 : 1, $v['attackPower'], $v['remaining'], -$v['uid']];
        if ($bestKey === null || $key > $bestKey) { $best = $v; $bestKey = $key; }
    }
    return $best === null ? 0 : $best['uid'];
}

// The Support attacker: the strongest in an arena that reaches the base. After the Plot buffs that is the planned unit —
// the same order _SWUBotSupportPlanUid uses — so no separate plan check is needed (a mutation of one proved it inert).
function _SWUBotSupportAttackerScore(int $seat, array $v): float {
    return (_SWUBotBlockedArenas($seat)[$v['arena']] ? 0.1 : 1.0) * (1.0 + 0.1 * $v['attackPower'] + 0.001 * $v['remaining']);
}

// A friendly "+N for this phase" during the flip turn, or null when this is not one (the ordinary scoring decides).
//  · the Support attack is still pending (Jar Jar's Plot): the planned Support attacker. The leader only when there is
//    none — a +2/+2 on her still beats passing (an explicit "avoid the leader" was proved inert by mutation and removed);
//  · the Supported unit's borrowed On Attack (the source is a leader card, the attacker is not a leader): the leader;
//  · the leader's own On Attack: any ready unit that still attacks.
// Everywhere: a buff on an exhausted unit (other than the one attacking now) or on a unit in a Sentinel-blocked arena is
// wasted, so it scores at the bottom of the candidates.
function _SWUBotBuffSpreadScore(array $ctx, int $seat, string $c): ?float {
    if (!preg_match('/^(my|p\d+)(GroundArena|SpaceArena)-\d+$/', $c)) return null;
    $v = SWUBotViewForMz($seat, $c);
    if ($v === null || intval($v['controller']) !== $seat) return null;
    $src = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[3] ?? '');
    $pending = _SWUBotSupportPending($seat);
    // The attacker of an On Attack prompt rides in the combat resume step ("SWU_TRIGGER_RESUME|1|COMBAT|<attacker>|…"):
    // _SWUBotDecisionAttacker reads only the attack-target / Ambush prompts and returns null here.
    $att = _SWUBotDecisionAttacker($ctx);
    foreach ((array)($ctx['following'] ?? []) as $f) {
        if ($att === null && preg_match('/^SWU_TRIGGER_RESUME\|\d+\|COMBAT\|([^|]+)\|/', strval($f), $m)) $att = SWUBotViewForMz($seat, $m[1]);
    }
    $fromLeader = $src !== '' && stripos(strval(CardType($src)), 'Leader') !== false && $att !== null;
    if (!$pending && !$fromLeader) return null;
    $blocked = _SWUBotBlockedArenas($seat)[$v['arena']];
    $attackingNow = $att !== null && $att['uid'] === $v['uid'];
    if ((!$v['ready'] && !$attackingNow) || $blocked) return 0.001 + 0.0001 * SWUBotUnitValue($v);   // wasted, but no worse than PASS
    $score = 1.0 + 0.01 * SWUBotUnitValue($v);
    if ($pending && !$fromLeader) {
        if ($v['uid'] === _SWUBotSupportPlanUid($seat)) $score += 10.0;   // the leader gets hers from the Supported attack
    } elseif (!$att['isLeader'] && $v['isLeader']) {
        $score += 10.0;                                                      // the Supported unit buffs the leader: she attacks next
    }
    return $score;
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
// An UPGRADE / TOKEN candidate ("myGroundArena-0.u0") → what that attachment is worth, or null when the mzID is not
// a subcard at all (so callers can fall through to the unit paths unchanged).
//
// ⚠ SWUBotViewForMz CANNOT SEE THESE. It resolves with GetZoneObject, which returns null for a ".uN" mzID by design
// (Core/CoreZoneModifiers.php spells out why: the generic resolver is MZResolveObject, and an un-taught caller gets a
// clean miss rather than auto-vivifying a bogus key). Without this the whole hostile/sacrifice branch scored null for
// every upgrade candidate and fell through to the enumeration-order tiebreak — feature 'upgradepicks'.
//
// The value is the HOST'S OWN ACCOUNTING, not a new scale: _SWUBotUnitValueV1 already prices a unit's attachments as
// 1.0 per upgrade plus another 0.5 for a Shield, so a Shield token is 1.5 and any other token 1.0 — the same numbers
// that decide whether the host is worth killing. A real upgrade CARD is floored at its printed cost instead, because
// a 3-cost upgrade is worth more than a token and the host's flat +1 undersells it.
// SOR_T02 / SOR_T01 are the CANONICAL Shield / Experience ids: DoGiveShieldToken and DoGiveExperienceToken always
// create those two, and every game-logic shield count keys on SOR_T02, so the other sets' printings never reach play.
function _SWUBotUpgradeValue(string $mz): ?float {
    if (!function_exists('MZIsSubcardID') || !MZIsSubcardID($mz)) return null;
    $o = MZResolveObject($mz);
    if (!is_object($o)) return null;                       // gone, or a host that has left play
    $cid = strval($o->CardID ?? '');
    $uv = max(1.0, floatval(intval(CardCost($cid)))) + ($cid === 'SOR_T02' ? 0.5 : 0.0);
    // A downgrade is worth NEGATIVE to its host's side: defeating the Weakness on my unit is the good pick, on theirs
    // the bad one — every caller's sign flips with it (feature 'weakness').
    return (SWUBotFeatureOn('weakness') && SWUBotIsDowngrade($cid)) ? -$uv : $uv;
}

function _SWUBotSideValue(int $p): float {
    $v = 0.0;
    foreach (SWUBotUnits($p) as $u) {
        $hp = max(1, intval(CardHp($u['cardID'])));
        $v += SWUBotUnitValue($u) * min(1.0, max(0, $u['remaining']) / $hp) * ($u['ready'] ? 1.0 : 0.8) + 0.1 * max(0, $u['power']);
    }
    return $v;
}

// 'owed' = the opponent must answer next (an indirect-damage assignment, a discard): the effect is still landing.
// 3-4 seats: 'theirs' and 'theirBase' are SUMMED over every live enemy, so a change to ANY of them registers (the dud,
// gift, mode and ability lookaheads all read deltas of this). 2 seats: the one opponent, unchanged.
function _SWUBotBoardRead(int $seat): array {
    $opps = SWUBotOpponents($seat);
    $theirs = 0.0; $theirBase = 0;
    foreach ($opps as $o) { $theirs += _SWUBotSideValue($o); $theirBase += SWUBaseRemainingHp($o); }
    return ['theirs' => $theirs, 'mine' => _SWUBotSideValue($seat), 'theirBase' => $theirBase,
            'owed' => (function_exists('SWUBotPendingDecisionSeat') && in_array(SWUBotPendingDecisionSeat(), $opps, true)) ? 1 : 0];
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
    if (!empty(array_intersect($tags, ['removal', 'wipe', 'damage-enemy-unit', 'debuff-all-enemy-units', 'exhaust', 'bounce', 'damage-enemy-base']))) return true;
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
    if (SWUBotClock(SWUBotMostDangerousOpponent($seat), $seat) <= 2) return false;
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
    $v = 0.0;
    foreach (SWUBotOpponents($seat) as $opp) $v += floatval(SWUBaseRemainingHp($opp));   // every live enemy (3-4 seats)
    foreach (SWUBotEnemyUnits($seat) as $u) {
        // A downgrade (a Weakness, a Bounty) is not something the opponent gained (feature 'weakness').
        $ups = $u['upgrades'] - (SWUBotFeatureOn('weakness') ? intval($u['downgrades'] ?? 0) : 0);
        $v += 2.0 + max(0, $u['remaining']) + max(0, $u['power']) + 2.0 * $u['shields'] + $ups + ($u['ready'] ? 1.0 : 0.0);
    }
    return $v;
}

// Play the card in the lookahead (its choices made for the best board change for me) and hold it when even that
// line leaves the opponent better off. Only cards whose text can help a unit are tried (the lookahead is not free).
function _SWUBotPlayIsGift(int $seat, array $action, string $cid, array $W): bool {
    if (!function_exists('SWUBotLookaheadBest')) return false;
    // Every UPGRADE is tried: its benefit is often only its PRINTED stats, which no text matches — LAW_129 Mastery
    // ("costs 1 resource less to play on a <uq> unit", +3/+3) went on the owner's Huyang when the bot had no unit
    // left (game 1438045). A debuff upgrade still passes: on an enemy host it lowers the gift read.
    if (!str_contains(strval(CardType($cid)), 'Upgrade')
        && !preg_match('/\b(heal|give|gives|ready|attach|gets \+)/i', strval(CardText($cid)))) return false;
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
    $opps = SWUBotOpponents($seat);   // 2 seats: [the opponent] — every array below is then exactly [me, opp] as before
    $live = fn($z) => count(array_filter($z, fn($o) => $o !== null && empty($o->removed)));
    $units = function ($p) { $o = []; foreach (SWUBotUnits($p) as $v) $o[$v['uid']] = [$v['cardID'], $v['power'], $v['remaining'], $v['ready'], $v['shields'], $v['upgrades']]; ksort($o); return $o; };
    // ⚠ CREDITS MUST BE IN THE SIGNATURE (feature 'creditvalue'). Without them an Action whose ONLY effect is
    // "create a Credit token" leaves the signature unchanged, and _SWUBotAbilityValue reads that as "this action
    // changed nothing" and returns -0.5 — a ramp effect scored as a dud. 'resources' counts the resource ZONE,
    // which excludes Credit tokens (CR 3.13, see SWUTotalPaymentCapacity).
    $credits = fn($p) => function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($p)) : 0;
    $theirs = [];
    foreach ($opps as $o) $theirs += $units($o);   // keyed by UniqueID, which is game-wide
    ksort($theirs);
    $per = function (callable $f) use ($seat, $opps) { $out = [$f($seat)]; foreach ($opps as $o) $out[] = $f($o); return $out; };
    return ['mine' => $units($seat), 'theirs' => $theirs, 'bases' => $per(fn($p) => SWUBaseRemainingHp($p)),
            'hands' => $per(fn($p) => $live(GetHand($p))), 'resources' => $per(fn($p) => $live(GetResources($p))),
            'credits' => $per($credits),
            'decks' => $per(fn($p) => $live(GetDeck($p))), 'discards' => $per(fn($p) => $live(GetDiscard($p)))];
}

// What N new Credits are worth to $seat RIGHT NOW: the best card in hand they bring from unaffordable to
// affordable, priced with the ordinary play value and scaled by W['creditRamp']; otherwise a flat ramp value.
//
// This is the whole point of the feature — a Credit is not worth a fixed amount, it is worth the thing it buys.
// On the traced Krennic board (7 capacity, LAW_044 at cost 10) one Credit buys NOTHING and the bot is right to
// refuse; at 9 capacity the same Credit is the difference between casting a board wipe and not.
// ⚠ Uses SWUComputePlayCost, so it already accounts for aspect penalties — which is why LAW_044 reads as 10
// here and not its printed 8. It does NOT know about a base's once-per-game aspect waiver (Daimyo's Palace),
// so a line that needs the waiver is still invisible; that is the separate "save the unlock" gap.
// The most Credits a plan may assume. Measured, not chosen: across the owner's 15 games the bank
// NEVER exceeded 2, and the SRI line needs exactly 2 (6 resources + 2 + the waiver = 8).
const SWU_BOT_CREDIT_MAX_BANK = 2;

// ── BANKED CREDITS: the engine CLOSES AT DEPLOY ──────────────────────────────────────────────────────────
// Owner 2026-09-29 (#1099) + their own 15 games of the deck. Krennic's Credit engine is on the LEADER'S
// FRONT SIDE ONLY — "Action [Exhaust, defeat a friendly unit]: Create a Credit token". The DEPLOYED side is
// "When Deployed: another friendly unit deals damage equal to its power", with no Credit ability at all.
// DEPLOYING ENDS BANKING, PERMANENTLY.
//
// ⚠ MEASURED IN THE OWNER'S OWN PLAY (SWUSim/BotData, 15 human-piloted Krennic games):
//   · 29 Credits created BEFORE deploy, 1 after — and that one was a leader DEFEATED AND RETURNED to the
//     leader zone (game 1401546: deployed round 7, back to undeployed round 8, Credit round 10), i.e. the
//     front side was available again. Effectively 29/29.
//   · Peak bank NEVER exceeded 2 — matching the line exactly: 6 resources + 2 Credits + the aspect waiver
//     casts LAW_044 (8 printed, +2 Aggression waived).
//   · Deploy landed round 4-6.
//
// ⚠ THE FIRST VERSION OF THIS FUNCTION GOT THE ECONOMICS WRONG and measured a null (-0.8pp, 91% of games
// IDENTICAL). It used a flat 3-turn lookahead with no deadline, no cap, and the same price either side of
// deploy. All three are wrong: the plan has a HARD DEADLINE (deploy), the bank is worth nothing beyond what
// the line needs, and credits are REPLACEABLE while the engine is open but finite once it closes.

// The credit plan: what the bank is protecting, how many Credits the line needs, and whether it is still
// reachable. Returns [planValue, creditsNeeded, reachable].
function _SWUBotCreditPlan(int $seat, array $W): array {
    static $depth = 0;
    if ($depth > 0) return [0.0, 0, false];
    if (!function_exists('SWUComputePlayCost') || !function_exists('SWUBotLeaderDeployThreshold')) return [0.0, 0, false];
    $res      = SWUResourceCount($seat);
    $banked   = function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($seat)) : 0;
    // ⚠ THE DEPLOY DEADLINE RIDES SWUBotLeaderDeployThreshold, which returns 0 once the leader is DEPLOYED
    // (or its Epic is spent) — see _SWUBotLeaderThreshold, BotResourcing.php. So post-deploy $deployAt is 0,
    // which collapses $planRes to today's resources and $makeable to zero all by itself: the engine being
    // shut is expressed by the threshold, not by a separate flag.
    // An explicit `$engineOpen` check was written here first and measured NON-LOAD-BEARING in both places
    // for exactly that reason; it was removed rather than left as decoration. The test pins the property
    // this now leans on (section G asserts the threshold is 0 once deployed).
    $deployAt = SWUBotLeaderDeployThreshold($seat);
    // Resources on the LAST turn before the engine closes. The owner casts on the 6R turn and deploys at 7.
    $planRes  = max($res, $deployAt - 1);
    // Credits still creatable: one a turn at most (the Action exhausts), and none once the threshold is 0.
    $makeable = max(0, $deployAt - $res);
    $base     = GetBase($seat)[0] ?? null;
    $waiver   = $base !== null && empty($base->EpicActionUsed)
                && preg_match('/ignoring 1 of its/i', strval(CardText(strval($base->CardID ?? ''))));
    $bestVal = 0.0; $bestNeed = 0;
    $depth++;
    try {
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $cid     = strval($o->CardID ?? '');
            $actual  = intval(SWUComputePlayCost($seat, $o));
            $penalty = max(0, $actual - intval(CardCost($cid)));
            $waived  = $actual - ($waiver ? min(2, $penalty) : 0);   // the waiver removes ONE aspect's penalty
            // Already affordable with what is in hand RIGHT NOW? Then it is not something being banked FOR —
            // without this a 4-drop became its own "plan" and protected the very Credit about to buy it.
            if ($waived <= $res + $banked) continue;
            $need    = $waived - $planRes;
            if ($need <= 0) continue;                       // resources alone get there: the bank is not needed
            // ⚠ CAP the plan at a believable bank. The owner's 15 games never held more than 2 Credits, and
            // the line needs exactly 2 — each one costs a turn AND a body to the sacrifice. Without this,
            // "6 resources + 4 Credits" reads as reachable and every expensive card becomes the plan.
            if ($need > SWU_BOT_CREDIT_MAX_BANK) continue;
            if ($need > $banked + $makeable) continue;      // unreachable even with everything: not the plan
            $v = _SWUBotPlayValue($seat, $cid, $W);
            if ($v > $bestVal) { $bestVal = $v; $bestNeed = $need; }
        }
    } finally { $depth--; }
    if ($bestVal <= 0.0) return [0.0, 0, false];
    return [($W['creditRamp'] ?? 0.0) * $bestVal, $bestNeed, true];
}

// What playing $cid costs in BANKED CREDITS. Credits cover only the shortfall the ready resources cannot
// (mirroring _SWUSpendAltPaymentShortfall), and only the Credits the LINE actually needs are charged for —
// a surplus beyond the plan is spent first and is free.
function _SWUBotCreditSpendCost(int $seat, string $cid, array $W): float {
    if (!function_exists('SWUUsableCreditTokenMzIDs')) return 0.0;
    $banked = count(SWUUsableCreditTokenMzIDs($seat));
    if ($banked <= 0) return 0.0;
    $obj = null;
    foreach (GetHand($seat) as $h) if (strval($h->CardID ?? '') === $cid) { $obj = $h; break; }
    if ($obj === null) return 0.0;
    $cost  = intval(SWUComputePlayCost($seat, $obj));
    $spend = max(0, min($banked, $cost - SWUResourceCount($seat, true)));
    if ($spend <= 0) return 0.0;
    [$plan, $need, $reachable] = _SWUBotCreditPlan($seat, $W);
    if (!$reachable || $need <= 0) return 0.0;
    // Spend the SURPLUS first: Credits beyond what the line needs are free (the owner never banks past 2).
    $surplus    = max(0, $banked - $need);
    $chargeable = max(0, $spend - $surplus);
    if ($chargeable <= 0) return 0.0;
    return $plan * ($chargeable / $need);
}

function _SWUBotCreditUnlockValue(int $seat, int $credits, array $W): float {
    if ($credits <= 0) return 0.0;
    if (!function_exists('SWUTotalPaymentCapacity') || !function_exists('SWUComputePlayCost')) return 0.0;
    $cap  = SWUTotalPaymentCapacity($seat);
    $best = 0.0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $cap || $cost > $cap + $credits) continue;   // already affordable, or still out of reach
        $best = max($best, _SWUBotPlayValue($seat, strval($o->CardID ?? ''), $W));
    }
    // ⚠ ZERO WHEN NOTHING IS UNLOCKED, deliberately — no flat "ramp is nice" term. A first version gave one
    // (0.5 per Credit) and it made the bot pay a unit and an action for a Credit it could not spend: measured over
    // 24 games vs Ahsoka, base damage dealt fell 7.9 → 5.8 while the win rate stayed 0. A Credit is worth the card
    // it buys or it is worth nothing.
    return $best > 0.0 ? ($W['creditRamp'] ?? 0.0) * $best : 0.0;
}

// Does the card those Credits unlock defeat MY OWN units too? Then the body I pay for the Credit was going to
// die anyway, so it is not a real cost.
//
// This is the insight the traced Krennic board turns on. LAW_044 Single Reactor Ignition is "Defeat ALL units":
// sacrificing a 3/7 to cast it costs nothing, because the 3/7 is in the blast. Without this the scorer priced the
// body at its full sacrifice value (4.0, less the 1.0 allowance) and refused — the unlock was worth 1.53 against
// a 3.0 penalty, so the Action still scored -1.07 and the stack still would not ramp.
// Uses the tags-v3 friendly-wipe halves, which is exactly what they were split out for.
function _SWUBotUnlockWipesMyBoard(int $seat, int $credits): bool {
    if ($credits <= 0 || !function_exists('SWUTotalPaymentCapacity') || !function_exists('SWUComputePlayCost')) return false;
    $cap = SWUTotalPaymentCapacity($seat);
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost <= $cap || $cost > $cap + $credits) continue;
        if (array_intersect(SWUBotCardTags(strval($o->CardID ?? '')), ['damage-wipe-friendly', 'defeat-wipe-friendly'])) return true;
    }
    return false;
}

// How many Credits an Action's own text creates, or 0. Read from the SOURCE text so it is the acting ability
// that is measured, not the card's other faces.
function _SWUBotCreditsCreated(string $text): int {
    if (!preg_match('/create (a|an|\d+|two|three) credit tokens?/i', $text, $m)) return 0;
    $n = strtolower($m[1]);
    return match ($n) { 'a', 'an' => 1, 'two' => 2, 'three' => 3, default => max(1, intval($n)) };
}

// A leader / unit / base Action, judged by applying it with the lookahead (BotLookahead.php):
//   - it would change nothing and raise no decision (an Epic Action with nothing to play) → never use it;
//   - it costs friendly units, either directly or through a "defeat a friendly unit" choice → the flat
//     ability value, less whatever sacrifice goes beyond 1 point of value;
//   - otherwise → the flat ability value.
// If the lookahead cannot apply the action, the flat value stands (the pre-2026-09-13 behaviour).
function _SWUBotAbilityValue(array $ctx, array $action, array $W): float {
    $seat = intval($ctx['seat']);
    // PROPOSAL 'landomill' (default OFF): once Lando has flipped and come back, the Action is a spare-resource play —
    // or a skip, or the win condition when their deck is nearly out. Pre-flip it is left exactly as it was.
    if (SWUBotActionKind($action) === 'leader-ability' && ($l = _SWUBotLandoAbilityScore($ctx, $W)) !== null) return $l;
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
    // An Action can wait until after the attacks, so an unused unit it costs carries its attack too ('unusedsac').
    // Kept apart from $lost: the 1.0 allowance below must not swallow it — a resource-back Mercenary costs -1, so
    // -1 + its attack would vanish under the allowance, and payback fodder is most of what Krennic sacrifices.
    $unused = 0.0;
    foreach (SWUBotUnits($seat) as $v) { if (!isset($after['sig']['mine'][$v['uid']])) { $lost += SWUBotSacrificeCost($v); $unused += SWUBotUnusedSacPremium($v); } }
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
        // p{n}: another seat's unit at 3-4 seats. Without it a my…+p3… list read as "all mine", and a hostile Action
        // with real enemy targets was refused (SWUSim/docs/todo-twinsuns-fill-bot.md, research A).
        $cands = array_values(array_filter(explode('&', strval($d['param'])), fn($m) => preg_match('/^(my|their|p\d+)\w*Arena-\d+$/', $m)));
        $helps = $head === 'APPLY_PHASE_BUFF' || in_array($head, SWU_BOT_BENEFICIAL_CONTINUATIONS, true);
        $hurts = $head === 'APPLY_PHASE_DEBUFF' || in_array($head, SWU_BOT_HOSTILE_CONTINUATIONS, true);
        $allTheirs = !empty($cands) && count(array_filter($cands, fn($m) => SWUBotIsEnemyMz($seat, $m))) === count($cands);
        $allMine = !empty($cands) && count(array_filter($cands, fn($m) => str_starts_with($m, 'my'))) === count($cands);
        // ⚠ A prompt that DECLARES a friendly target is a designed COST, not a misfire, so all-mine candidates
        // are the intended state and refusing would disable the card. SHD_028 Doctor Pershing is a unit Action
        // — "Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card" — whose every candidate is always
        // mine; adding DEAL_UNIT_DAMAGE to the hostile list above would otherwise have stopped the bot ever
        // drawing with him. The discriminator is the prompt's own wording: Pershing asks
        // "Deal_1_damage_to_a_friendly_unit", Luke JTL_012 asks "Deal_1_damage_to_a_unit" and is all-mine only
        // because the enemy board happens to be empty. Such a cost falls through to the sacrifice/lookahead
        // pricing below, which weighs it against what the ability actually buys.
        $declaredFriendly = (bool)preg_match('/\bfriendly\b/i', str_replace('_', ' ', strval($d['tooltip'] ?? '')));
        if (($helps && $allTheirs) || ($hurts && $allMine && !$declaredFriendly)) return -0.5;
    }
    if ($d !== null && _SWUBotTooltipEffect($d['tooltip']) === 'sacrifice') {
        // The candidate the pick will take: the cheapest once its unused attack is counted (as the pick scores it).
        $cheapest = null; $cheapestUnused = 0.0;
        foreach (array_filter(explode('&', $d['param'])) as $mz) {
            $v = SWUBotViewForMz($seat, $mz);
            if ($v === null) continue;
            $c = SWUBotSacrificeCost($v); $u = SWUBotUnusedSacPremium($v);
            if ($cheapest === null || $c + $u < $cheapest + $cheapestUnused) { $cheapest = $c; $cheapestUnused = $u; }
        }
        $lost += $cheapest ?? 0.0;
        $unused += $cheapestUnused;
    }
    $value = $W['ability'];
    // PROPOSAL 'creditvalue' (default OFF): an Action that RAMPS is worth what the Credit buys. Added to the flat
    // ability value rather than max()'d with it, because the ramp is IN ADDITION to whatever else the Action does —
    // and it is what offsets the sacrifice deducted below. Traced refusal before this: -2.6 on the reported board.
    if (SWUBotProposalOn('creditvalue')) {
        $n = _SWUBotCreditsCreated(_SWUBotActionSourceText($seat, $action));
        if ($n > 0) {
            $value += _SWUBotCreditUnlockValue($seat, $n, $W);
            // The unlocked card wipes MY board too, so the fodder was already dead — drop the sacrifice penalty.
            if (_SWUBotUnlockWipesMyBoard($seat, $n)) $lost = min($lost, 0.0);
        }
    }
    if (SWUBotFeatureOn('enablers')) {
        $enabled = _SWUBotEnabledPlayValue($seat, $handBefore, $after, $W);
        // PROPOSAL 'aspectwaiver': a base Epic Action whose whole effect is "play a card, ignoring an aspect
        // penalty" is ONCE PER GAME, so it is worth exactly what it unlocks — with NO flat ability floor. The
        // floor is what burned it: W['ability'] is 0.40 on any board, which beats PASS, so the waiver was spent in
        // round 1 in six of eight traced games. Dropping the floor is the whole "save it" mechanism.
        if (SWUBotFeatureOn('aspectwaiver') && SWUBotActionKind($action) === 'base-epic'
            && preg_match('/ignoring 1 of its/i', strval(CardText(strval((GetBase($seat)[0] ?? null)->CardID ?? ''))))) {
            return $enabled - max(0.0, $lost - 1.0);
        }
        $value = max($value, $enabled);
    }
    // FEATURE 'buffattack', group p7 (owner report #1052, game 690588): an Action that BUFFS a unit which can still attack
    // this phase is worth what the buff adds to that attack. Flat W['ability'] (0.40) sits BELOW the attack it
    // would improve (W['base'] x power), so the bot attacked with Gungi for 2 and then spent Ahsoka's Action on
    // him — and "+2/+0 for this phase" on a unit that has already swung does nothing at all.
    // …and an Action whose ONLY effect is +N/+0 for this phase, with no ready unit to spend it, is not used at all
    // (owner report 2026-09-21, Petranaki Arenabot: Ahsoka's Action "wasted" on an exhausted unit).
    if (SWUBotFeatureOn('buffattack')) {
        $gain = _SWUBotBuffAttackGain($ctx, $seat, $before, $after, $W, $action);
        if ($gain <= 0.0 && _SWUBotIsPowerOnlyPhaseBuff($before, $after, $handBefore)) return -0.5;
        $value = max($value, $W['ability'] + $gain);
    }
    // An Action that costs the Force (Talzin's -1/-1): never onto an empty enemy board, and held when its -N/-N kills
    // nothing while another card needs the Force (feature 'force').
    $text = _SWUBotActionSourceText($seat, $action);
    if (SWUBotFeatureOn('force') && preg_match('/Action \[[^\]]*use the Force/i', $text)) {
        $enemies = array_values(array_filter(SWUBotEnemyUnits($seat), fn($v) => !$v['isLeader']));
        if (empty($enemies)) return -0.5;
        $n = preg_match('/-\d+\/-(\d+)/', $text, $m) ? intval($m[1]) : 0;
        $kills = count(array_filter($enemies, fn($v) => $v['remaining'] <= $n)) > 0;
        if (!$kills && _SWUBotHasOtherForceUse($seat)) $value = min($value, 0.02);   // below the initiative (0.05)
    }
    return $value - max(0.0, $lost - 1.0) - $unused;
}

// What a buff adds to the attacks my READY units can still make this phase — the extra damage it enables,
// priced with the same weights the attack itself is scored with, so the two are directly comparable.
// Two shapes, because the Action's target prompt may or may not have resolved inside the lookahead:
//   · it auto-resolved (one legal target — the reported game) → the power rise shows in the board signature;
//   · it is still pending → read the amount off the APPLY_PHASE_BUFF continuation and try each candidate.
// Zero when the buff lands on an exhausted unit (it cannot attack this phase), on a unit with nothing worth
// attacking, or on the enemy — so this never promotes an Action that does not improve an attack.
function _SWUBotBuffAttackGain(array $ctx, int $seat, array $before, array $after, array $W, ?array $action = null): float {
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
    // PROPOSAL 'mgbuff' (default OFF) — owner ruling 2026-09-23. The branch below only reads the generic
    // APPLY_PHASE_BUFF continuation, so a card that applies its buff inside its OWN handler is invisible and its
    // Action keeps the flat W['ability'] — the T-6 Shuttle (ASH_109) was offered 16 times in 40 traced games and
    // used 0. Six cards share that shape. Read the amount off the ACTION'S PRINTED TEXT instead, then price it with
    // the same machinery, so mgbuff and buffattack agree on what a buff is worth.
    if ($gain <= 0.0 && $d !== null && $action !== null && SWUBotProposalOn('mgbuff')
        && !str_starts_with(strval($d['next'] ?? ''), 'APPLY_PHASE_BUFF')) {
        $text = _SWUBotActionSourceText($seat, $action);
        if (preg_match('/\+(\d+)\/\+\d+ for this phase/i', $text, $m)) {
            foreach (array_filter(explode('&', strval($d['param'] ?? ''))) as $mz) {
                if (!str_starts_with($mz, 'my')) continue;          // buffing THEIR unit adds nothing to my attacks
                $gain = max($gain, $gainFor(SWUBotViewForMz($seat, $mz), intval($m[1])));
            }
        }
    }
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
    // PROPOSAL 'mgmull' (default OFF, "@try-mgmull") — owner ruling 2026-09-23 (5), the MATCHUP-dependent keep.
    // All three traced Luke ASH openings were mulligans ("one probably, two definitely") and the bot kept them all.
    //   vs AGGRO:   a curve PLUS a body that blocks — a Sentinel, or a unit that survives their round-2 attack.
    //   vs CONTROL: set aside the two cards you would resource; the remaining four must produce a play in R1-R3.
    // ⚠ The owner also gave a third test (vs MIDRANGE: three castable by round 3, plus a play on curve in R1-2).
    // It is NOT implemented, because at the mulligan the only read of the opponent is their LEADER and the engine
    // has a list of aggro leaders but none of control ones — a midrange opponent is indistinguishable from a
    // control one here. The control test is the milder of the two, so it is what an unknown opponent gets.
    // Round N carries N+1 resources (CR 5.4): R2 = 3, R3 = 4. "Survives their round-2 attack" = 3+ HP.
    if (SWUBotProposalOn('mgmull') && SWUBotStyleRank($style) === 2) {
        $hp = []; $sentinel = [];
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $cid = strval($o->CardID ?? '');
            $isUnit = strval(CardType($cid)) === 'Unit';
            $hp[] = $isUnit ? intval(CardHp($cid)) : 0;
            $sentinel[] = $isUnit && _SWUBotHasPrintedSentinel($cid);
        }
        if (function_exists('SWUBotOpponentIsAggroLeader') && SWUBotOpponentIsAggroLeader($seat)) {
            $blocks = false;
            foreach ($costs as $k => $c) { if ($c <= 4 && ($sentinel[$k] || $hp[$k] >= 3)) { $blocks = true; break; } }
            return $atMost(3) < 2 || !$blocks;
        }
        $rest = $costs; rsort($rest); $rest = array_slice($rest, 2);        // the two I would resource are the priciest
        return empty($rest) || min($rest) > 4;                             // nothing castable by round 3
    }
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
