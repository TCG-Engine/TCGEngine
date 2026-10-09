<?php
// Layer 4 of the RL bots spec's decision stack — the FALLBACK SCORER. Only reached when no layer-2 rule
// answered. Scores every candidate with the per-style weights (SWUBotWeights) and the guides; the highest
// score wins, ties go to the lowest index. Deterministic: no RNG anywhere in the heuristic stack.
// Spec: docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md, Section 2 ("Layer 4", "The guides").

// What attacking $def (an enemy unit view) or the base ($def === null) with $att is worth.
// Feature 'leaderrisk' (p28, shipped 2026-10-03; @no-leaderrisk): a deployed LEADER unit that is defeated RETURNS to its leader zone
// exhausted (memory `leader-units-are-defeated-then-return`) — the player loses a body for a round, not a card,
// so pricing its loss like a real unit makes the bot under-attack with leaders.
// PROPOSAL 'tradewhenbehind' (default OFF): while behind on units, an even trade is worth taking (owner Q10,
// "most of the time a 1-to-1 trade is good") — as a CONDITION, where the flat loss weight could not express it.
function _SWUBotLossFactor(array $att): float {
    $f = 1.0;
    if (SWUBotFeatureOn('leaderrisk') && !empty($att['isLeader'])) $f *= 0.3;
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

// Feature 'breach' (p26, @no-breach). The attack power a kill of $def OPENS for $att's other units: their ready, non-Saboteur
// units in $def's arena (a Saboteur already ignores Sentinel; an exhausted unit cannot attack this round), and only when
// $def is a Sentinel and its controller has no OTHER Sentinel left there (an exhausted Sentinel still guards).
// ⚠ One step only: a Shielded Sentinel is not breached by one hit (SWUBotCombatOutcome sees no kill), and pop-then-kill
// is not planned. The opponent acting between my attacks is not modelled either.
// Twin Suns: when ANOTHER live opponent has no Sentinel in this arena, my units there already reach that base — the kill
// opens nothing new, so it is worth 0 (review 2026-10-03).
// Reads the ONE arena's raw unit objects, not SWUBotUnits (a full view per unit on both boards): this runs for every
// attacker x Sentinel target the scorer and the buff-gain lookahead price.
function _SWUBotBreachOpened(array $att, array $def): int {
    if (empty($def['sentinel'])) return 0;
    $defSeat = intval($def['controller']); $arena = $def['arena'];
    foreach (GetUnitsInArena($defSeat, $arena) as $u) {
        if (intval($u->UniqueID ?? 0) !== $def['uid'] && HasKeyword_Sentinel($u)) return 0;
    }
    foreach (SWUBotOpponents(intval($att['controller'])) as $o) {
        if ($o !== $defSeat && !(_SWUBotSentinelArenas($o)[$arena] ?? false)) return 0;
    }
    $open = 0;
    foreach (GetUnitsInArena(intval($att['controller']), $arena) as $u) {
        $uid = intval($u->UniqueID ?? 0);
        if ($uid === $att['uid'] || $uid === intval($def['_popper'] ?? 0) || intval($u->Status ?? 0) !== 1 || HasKeyword_Saboteur($u)) continue;
        $open += max(0, intval(ObjectCurrentPower($u))   // = SWUBotUnitView's attackPower
            + (function_exists('GetKeyword_Raid_Value') ? intval(GetKeyword_Raid_Value($u) ?? 0) : 0));
    }
    return $open;
}

// $v with +$bonus power defeats the ONLY Sentinel some opponent has in $v's arena AND that kill opens base damage for my
// other units (the ruling's "if and only if it enables more damage from the other units": a breach with no beneficiary
// is no plan — review 2026-10-03, G3).
function _SWUBotCanBreach(array $v, int $bonus): bool {
    $att = $v; $att['attackPower'] += $bonus; $att['power'] += $bonus;
    foreach (SWUBotOpponents(intval($v['controller'])) as $opp) {
        $sents = array_values(array_filter(SWUBotUnits($opp), fn($u) => $u['arena'] === $v['arena'] && $u['sentinel']));
        if (count($sents) !== 1) continue;
        $o = SWUBotCombatOutcome($att, $sents[0]);
        if (($o === 'kill-survive' || $o === 'trade') && _SWUBotBreachOpened($att, $sents[0]) > 0) return true;
    }
    return false;
}

// Feature 'popkill' (p27, @no-popkill). An attack that only pops $def's ONE Shield (a 'bounce' or 'die' on a Shielded unit, no
// Saboteur) sets up a kill: the best value another READY unit of mine in $def's arena then gets attacking the unshielded
// $def (SWUBotTargetValue on the popped copy — so a breach it opens counts too), or 0 when none of them kills it. A follow-up
// that cannot legally target $def (it is not a Sentinel while its controller has one there) earns nothing. One step: two
// Shields need two pops, and are not credited.
function _SWUBotPopKillCredit(array $att, array $def, array $W): float {
    if (intval($def['shields']) !== 1 || !empty($att['saboteur'])) return 0.0;
    if (empty($def['sentinel']) && SWUBotArenaHasSentinel(intval($def['controller']), $def['arena'])) return 0.0;
    // The popper has attacked (or died) by the follow-up: a breach that kill makes opens nothing for it ('_popper').
    $popped = $def; $popped['shields'] = 0; $popped['_popper'] = $att['uid'];
    $best = 0.0;
    foreach (SWUBotUnits(intval($att['controller'])) as $u) {
        if ($u['uid'] === $att['uid'] || !$u['ready'] || $u['arena'] !== $def['arena']) continue;
        $o = SWUBotCombatOutcome($u, $popped);
        if ($o === 'kill-survive' || $o === 'trade') $best = max($best, SWUBotTargetValue($u, $popped, $W));
    }
    return $best;
}

// Feature 'shieldtrader' (p37): a cheap unit (non-leader, cost <= 2) while it carries a Shield token is a FIGHTER — JTL_032 Director
// Krennic's unit. Owner, Krennic Splash 2026-10-06: "while it is shielded … use it to either soften up sentinels, kill weenies, trade
// shields"; without the Shield, "hit base to chip for 2. then sac it". Traced: shielded, it hit the base in 25 of its 42 attacks.
const SWU_BOT_BARE_FODDER_DISCOUNT = 0.5;     // without it: below a same-value body, still above a 0-power token (owner: the Spy goes first)
function _SWUBotShieldTrader(array $v): bool {
    return SWUBotFeatureOn('shieldtrader') && empty($v['isLeader']) && intval($v['shields']) > 0 && intval($v['cost']) <= 2;
}

// An ANSWER: a removal or wipe card (tags v3). 'traskreturn' (p39).
function _SWUBotIsAnswer(string $cid): bool {
    return $cid !== '' && (bool)array_intersect(SWUBotCardTags($cid), ['removal', 'wipe']);
}

// 'phaseexpiry' (p41): a kill that lands only when the defender's phase buff expires is worth most of a kill — the opponent may
// still heal or bounce it before the phase ends.
const SWU_BOT_PHASE_EXPIRY_KILL = 0.9;

function SWUBotTargetValue(array $att, ?array $def, array $W): float {
    if ($def === null) return $W['base'] * $att['attackPower'] + (SWUBotFeatureOn('baseheal') ? _SWUBotBaseHitHeal($att, $W) : 0.0);
    // 'leaderdraw' (p41): the card a ready Wicket draws when this attack hits a pricier unit.
    $draw = (SWUBotFeatureOn('leaderdraw') && intval($def['cost']) > intval($att['cost']) && _SWUBotLeaderAttackDraws(intval($att['controller'])))
        ? $W['draw'] : 0.0;
    return _SWUBotUnitTargetValue($att, $def, $W) + $draw;
}

// Feature 'leaderdraw' (p41): $seat has a ready, undeployed leader reading "When a friendly unit attacks a unit that costs more than
// it: You may exhaust this leader. If you do, draw a card." (HMW_014 Wicket). Ninin vs Wicket Green R6.
function _SWUBotLeaderAttackDraws(int $seat): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || !empty($l->Deployed) || empty($l->Ready)) continue;
        if (preg_match('/When a friendly unit attacks a unit that costs more than it: You may exhaust this leader\. If you do, draw a card/i',
                strval(CardText(strval($l->CardID ?? ''))))) return true;
    }
    return false;
}

// Feature 'baseheal' (p42): a base hit by a unit reading "When Attack Ends: If this unit dealt combat damage to a base, heal that much
// damage from your base" (ASH_031 Hera Syndulla) also heals — W['heal'] a point, up to the damage on my base. Owner, Mando Colossus:
// "Hera + Aggressive Negotiations to swing big and heal big getting doubly ahead" (the AN went on the bigger Brute).
function _SWUBotBaseHitHeal(array $att, array $W): float {
    $text = $att['isLeader'] ? strval(CardDeployText($att['cardID']) ?? '') : strval(CardText($att['cardID']));
    if (!preg_match('/When Attack Ends: If this unit dealt combat damage to a base, heal that much damage from your base/i', $text)) return 0.0;
    $dmg = intval((GetBase(intval($att['controller']))[0] ?? null)->Damage ?? 0);
    return SWUBotLeverNum('BASEHEAL_MULT', 1.0) * $W['heal'] * min(max(0, intval($att['attackPower'])), $dmg);
}

// Feature 'cleanup' (p42), owner 2026-10-08 (Mando Colossus): "The Mandalorian plays hard control, so prefer to clean up the board until
// you've stabilized against aggro's gameplan." For a HARD CONTROL seat facing an aggro leader (SWU_BOT_AGGRO_LEADERS) whose board can still
// reach my base (SWUBotBasePotential > 0 — not yet stabilised), the base target is capped just under the best unit the attacker defeats
// (a clean kill, or a trade scored ≥ 0). NULL — no cap — when there is nothing to defeat. (A lethal base hit needs no exemption: the
// rule layer takes it first — bot_cleanup_test C.)
function _SWUBotCleanupCap(array $ctx, array $att, array $W): ?float {
    $seat = intval($att['controller']);
    if (SWUBotStyleRank(strval($ctx['style'] ?? '')) < (SWUBotProposalOn('cleanupsoftc') ? 3 : 4)) return null;
    if (!SWUBotProposalOn('cleanupall') && !SWUBotOpponentIsAggroLeader($seat)) return null;
    $threat = false;
    foreach (SWUBotOpponents($seat) as $o) if (SWUBotProposalOn('cleanupclock') ? SWUBotClock($o, $seat) < 4 : SWUBotBasePotential($o, $seat, false) > 0) $threat = true;
    if (!$threat) return null;
    $best = null;
    foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
        if ($k === 'base' || !in_array(SWUBotCombatOutcome($att, $u), SWUBotProposalOn('cleanupkillonly') ? ['kill-survive'] : ['kill-survive', 'trade'], true)) continue;
        $tv = SWUBotTargetValue($att, $u, $W);
        if ($tv >= 0.0) $best = $best === null ? $tv : max($best, $tv);
    }
    return $best === null ? null : $best - 0.01;
}

// Feature 'chewieattack' (p42), owner 2026-10-08 (Chewbacca, LAW_013 deployed): "it should say yes to kill something, soften it up for Red
// Five, or break a shield on a sentinel". True when $n damage on some enemy unit defeats it, leaves it in reach of a ready friendly "On
// Attack: You may deal N damage to a damaged unit" (JTL_151 Red Five), or pops the Shield of an enemy Sentinel. (A Shield on anything else
// just eats the hit.)
function _SWUBotChewieOnAttackWorth(int $seat, int $n): bool {
    $finish = 0;
    foreach (SWUBotUnits($seat) as $v) {
        if (($v['ready'] || SWUBotProposalOn('chewiefinishnext')) && preg_match('/On Attack: You may deal (\d+) damage to a damaged unit/i', strval(CardText($v['cardID'])), $m)) $finish = max($finish, intval($m[1]));
    }
    foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits($o) as $u) {
        if (SWUBotProposalOn('chewiechip')) return true;   // lever: any enemy unit to hit
        if ($u['shields'] > 0) { if ($u['sentinel']) return true; continue; }
        if ($u['remaining'] <= $n || ($finish > 0 && $u['remaining'] <= $n + $finish)) return true;
    }
    return false;
}

// SWUBotTargetValue against a unit, before the leader draw.
function _SWUBotUnitTargetValue(array $att, array $def, array $W): float {
    $lossF = _SWUBotLossFactor($att);
    // Only a KILL breaches, so it is priced in those two branches alone (bounce / die never pay for the scan).
    $breach = fn(): float => SWUBotFeatureOn('breach') ? $W['base'] * _SWUBotBreachOpened($att, $def) : 0.0;
    $out = SWUBotCombatOutcome($att, $def); $late = false;
    // 'phaseexpiry' (p41): a "+N/+N for this phase" runs out at the phase end and the damage stays (Ninin vs Wicket Green R4).
    if (SWUBotFeatureOn('phaseexpiry')) [$out, $late] = _SWUBotPhaseExpiryOutcome($att, $def, $out);
    // A phase-end kill: the defender still stands (and blocks, as a Sentinel) for the rest of this phase, can be saved by a heal
    // or a bounce, and leaves no Overwhelm excess.
    $killF = $late ? SWU_BOT_PHASE_EXPIRY_KILL : 1.0;
    switch ($out) {
        case 'kill-survive':
            // 'lockpiece' (p35): an attack is a free kill — the locked bomb may still come down this round.
            $v = $killF * ($W['kill'] * (SWUBotUnitValue($def) + _SWUBotLockFreeKillExtra($def)) + _SWUBotThreatRemoved($att, $def, $W))
                 + ($late ? 0.0 : $breach());
            // The Overwhelm excess reaches the base — which also makes Aggro prefer the lowest-HP kill.
            if (!$late && SWUBotOverwhelmKills($att, $def)) $v += $W['base'] * ($att['attackPower'] - $def['remaining']);
            return $v;
        case 'trade':
            return $killF * ($W['kill'] * (SWUBotUnitValue($def) + _SWUBotLockFreeKillExtra($def)) + _SWUBotThreatRemoved($att, $def, $W))
                   - $lossF * $W['loss'] * SWUBotUnitValue($att) + ($late ? 0.0 : $breach()) - _SWUBotObserverTax($att, $W);
        case 'bounce':
            // Guide: pop a Shield with the smallest attacker. 'shieldtrader': a Shield for a Shield is priced as the pop is worth.
            if ($def['shields'] > 0 && !$att['saboteur'])
                return (_SWUBotShieldTrader($att) ? _SWUBotShieldPopValue(intval($att['controller']), $def, $W, true) : $W['chip'])
                       - 0.05 * $att['attackPower'] + (SWUBotFeatureOn('popkill') ? _SWUBotPopKillCredit($att, $def, $W) : 0.0);
            $dmg = min($att['attackPower'], $def['remaining']);
            return $W['chip'] * $dmg - ($def['grit'] ? $W['grit'] * $dmg : 0.0);   // guide: don't feed Grit
        default: // 'die'
            // A popper that dies still takes the Shield with it ('popkill').
            return -$lossF * $W['loss'] * SWUBotUnitValue($att)
                   + (SWUBotFeatureOn('popkill') && $def['shields'] > 0 && !$att['saboteur'] ? _SWUBotPopKillCredit($att, $def, $W) : 0.0)
                   - _SWUBotObserverTax($att, $W);
    }
}

// Feature 'observertax' (p41): the base damage $victim takes for each of its units defeated — the sum of N over the opponents'
// units reading "When an enemy unit is defeated: Deal N damage to its controller's base" (LOF_130 HK-47) that still have
// their abilities.
function _SWUBotObserverPings(int $victim): int {
    $n = 0;
    foreach (OpponentsOf($victim) as $o) {
        foreach (SWUBotUnits(intval($o)) as $u) {
            if (function_exists('LostAbilities') && LostAbilities($u['obj'])) continue;
            if (preg_match("/When an enemy unit is defeated: Deal (\\d+) damage to its controller's base/i", strval(CardText($u['cardID'])), $m))
                $n += intval($m[1]);
        }
    }
    return $n;
}

// Feature 'observertax' (p41): what losing attacker $att costs its base through the opponents' HK-47s — a point of base damage
// each, and the game when the pings reach the base's remaining HP (Ninin vs Wicket Green R11: Wicket died into HK-47 at 1 HP).
const SWU_BOT_OBSERVER_LETHAL = 100.0;
function _SWUBotObserverTax(array $att, array $W): float {
    if (!SWUBotFeatureOn('observertax')) return 0.0;
    $seat = intval($att['controller']);
    $pings = _SWUBotObserverPings($seat);
    if ($pings <= 0) return 0.0;
    return $pings >= SWUBaseRemainingHp($seat) ? SWU_BOT_OBSERVER_LETHAL : $W['base'] * $pings;
}

// Feature 'splitpop' (p32) — what popping one Shield on unit $v is worth, from $seat's view. Owner rule of thumb
// (2026-10-04): "see a shield on a unit as adding +P power to the unit for each shield, since it can tank that many hits,
// P being its current power. Popping a shield on a 4-power unit, especially a Sentinel, is very valuable. A shield on a
// 3-power, 1hp unit is not so valuable since 1 indirect ping or 1 Weakness token can clear that unit. That unit's value
// goes down if I have a Saboteur on the board or an ambushing Saboteur in hand like Fennec Shand."
// So: chip × current power (one chip per point of the hit it would absorb), × 1.5 on a Sentinel. Floored to ONE chip when
// the Shield barely protects: a 1-HP unit (a Weakness token or an indirect point clears it through the Shield), or when
// the side that attacks it ignores Shields anyway — for an enemy unit, my Saboteur in its arena or an Ambush Saboteur in
// my hand; for my own unit, an enemy Saboteur in play (their hand is hidden).
function _SWUBotShieldPopValue(int $seat, array $v, array $W, bool $enemy): float {
    $chip = $W['chip'];
    if (intval($v['remaining']) <= 1) return $chip;
    // Who attacks this unit: me, for an enemy unit; my opponents (OpponentsOf — a Team Suns teammate is not one), for mine.
    $attackers = $enemy ? [$seat] : OpponentsOf($seat);
    foreach ($attackers as $a) {
        foreach (SWUBotUnits(intval($a)) as $u) if ($u['saboteur'] && $u['arena'] === $v['arena']) return $chip;
        if ($enemy) {
            foreach (GetHand(intval($a)) as $h) {
                if (!empty($h->removed)) continue;
                $t = strval(CardText(strval($h->CardID)) ?? '');
                if (preg_match('/\bAmbush\b/', $t) && preg_match('/\bSaboteur\b/', $t)) return $chip;
            }
        }
    }
    return $chip * max(1, intval($v['power'])) * (!empty($v['sentinel']) ? 1.5 : 1.0);
}

// A split-damage answer ("mz:a,mz:b", MZSPLITASSIGN), part by part: an enemy unit defeated is worth kill × its
// value, an enemy unit only damaged chip × the damage, the enemy base base × the damage; my own units and base
// cost the mirror (a lost unit loss × value; my base SWU_BOT_OWN_BASE_DAMAGE a point, whatever the style — the
// style weights say how much I want to HIT a base, not how much I can afford to take). A defeated unit on either
// side counts 0.5 more: a body on the board. $unpreventable (indirect damage, CR 35.3) ignores Shields; otherwise
// a Shield absorbs the whole instance. Feature 'splits'.
const SWU_BOT_OWN_BASE_DAMAGE = 0.5;
const SWU_BOT_SPLIT_WASTED_POINT = 0.01;   // 'splitpop': a point a Shield absorbs past the one that popped it — a tie-breaker, not a value
function _SWUBotSplitScore(int $seat, string $candidate, array $W, bool $unpreventable): float {
    $s = 0.0; $toBase = [];
    foreach (explode(',', $candidate) as $pair) {
        $bits = explode(':', $pair);
        if (count($bits) < 2) continue;
        $mz = trim($bits[0]); $amt = intval($bits[1]);
        if ($amt <= 0) continue;
        $enemy = SWUBotIsEnemyMz($seat, $mz);   // owner seat, not a "their" prefix (p{n} at 3-4 seats)
        if (str_contains($mz, 'Base')) {
            $s += $enemy ? $W['base'] * $amt : -SWU_BOT_OWN_BASE_DAMAGE * $amt;
            if ($enemy) $toBase[$mz] = ($toBase[$mz] ?? 0) + $amt;
            continue;
        }
        $v = SWUBotViewForMz($seat, $mz);
        if ($v === null) continue;
        // A Shield absorbs the whole instance — but popping it is worth what the Shield was worth ('splitpop', p32). Scored
        // 0, a pop tied with dumping the same point into an overkill.
        // ONE point pops ONE Shield, whatever is assigned: a target's share of a split is one damage instance, and a Shield
        // prevents the whole instance — a unit with 2 Shields still has one left after it. Every point past the first is
        // wasted, so it costs a little (owner: "don't waste 2 of the split on it. 1 ping is enough to pop one of the
        // shields"); without the cost a wasted point scored 0 and could tie with a point spent somewhere useful.
        if (!$unpreventable && $v['shields'] > 0) {
            if (SWUBotFeatureOn('splitpop')) {
                $s += ($enemy ? 1 : -1) * _SWUBotShieldPopValue($seat, $v, $W, $enemy) - SWU_BOT_SPLIT_WASTED_POINT * ($amt - 1);
            }
            continue;
        }
        if ($amt >= $v['remaining']) $s += ($enemy ? $W['kill'] : -$W['loss']) * SWUBotUnitValue($v) + ($enemy ? 0.5 : -0.5);
        elseif (!$enemy && SWUBotProposalOn('indirectsoak') && _SWUBotSoakIntoReach($seat, $v, $amt)) $s -= SWU_BOT_SOAK_LOSS * $W['loss'] * SWUBotUnitValue($v);
        else $s += ($enemy ? 1 : -1) * $W['chip'] * $amt;
    }
    // Feature 'splitlethal' (p33, bug #1126): points that FINISH an enemy base are the game, not W['base'] each — game 1485163's
    // Devastator spread 4 indirect over two kills while the Krennic base sat on 4 HP. "theirBase" is the opponent (the first
    // one at 3-4 seats); "p<n>Base" names its seat.
    if (SWUBotFeatureOn('splitlethal')) {
        foreach ($toBase as $mz => $amt) {
            $owner = preg_match('/^p(\d+)/', $mz, $pm) ? intval($pm[1]) : (SWUBotOpponents($seat)[0] ?? 0);
            if ($owner > 0 && $amt >= SWUBaseRemainingHp($owner)) $s += SWU_BOT_PING_LETHAL;
        }
    }
    return $s;
}

// '@try-indirectsoak' (2026-10-07 gap screen): $amt damage on my unit $v takes it from out of the reach of the strongest enemy unit in its
// arena to within it (remaining <= that power) — it is then a likely loss, not chip (Krennic vs Boba Blue: indirect soaked onto Moff Gideon,
// then Marrok killed him; diagnosis .claude/tmp/diag_boba). An arena with no enemy unit: nothing can finish it.
const SWU_BOT_SOAK_LOSS = 0.75;
function _SWUBotSoakIntoReach(int $seat, array $v, int $amt): bool {
    $reach = 0;
    foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits(intval($o)) as $u) if ($u['arena'] === $v['arena']) $reach = max($reach, intval($u['attackPower']));
    return $v['remaining'] > $reach && $v['remaining'] - $amt <= $reach;   // reach 0 would need a lethal share, priced above as a loss
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
// the Shield goes AFTER the Ambush, so the Shield is still there afterwards. HMW_216 Insurgent Camp ("ready that
// unit") also goes after the played unit's own Ambush: the Ambush readies it anyway, so a Camp resolved first is
// spent for nothing, while one resolved after readies the unit post-attack.
function _SWUBotTriggerScore(array $ctx, string $candidate): float {
    if (!preg_match('/EffectStack-(\d+)$/', $candidate, $m)) return 0.0;
    $stack = GetEffectStack();
    $e = $stack[intval($m[1])] ?? null;
    if ($e === null) return 0.0;
    $type = strval($e->TriggerType ?? '');
    if ($type === 'Ambush' || $type === 'Support') return 0.0;
    // Feature 'tokenfirst' (p42): a trigger that gives the played unit a token ("…give an Advantage token to that unit", ASH_017 Greef)
    // goes before the unit's own triggers, which may use it (ASH_171 Pegasus Tri-Wing: "defeat a friendly upgrade. If you do, ready this
    // unit" — it resolved first 645 of 645 times and never saw Greef's token).
    if (SWUBotFeatureOn('tokenfirst') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)(#\d+)?$/', $type, $tm)
        && preg_match('/give an? \w+ token to that unit/i', strval(CardText($tm[1])) . ' ' . strval(CardDeployText($tm[1])))) return 1.2;
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
    if ($type === 'HMW_216') {   // Params = the played unit's UID; an Ambush carries "mz|uid"
        $uid = strval($e->Params ?? '');
        foreach ($stack as $o) {
            if ($o === $e || !empty($o->removed) || strval($o->TriggerType ?? '') !== 'Ambush') continue;
            if (intval($o->Controller) === intval($e->Controller) && (explode('|', strval($o->Params ?? ''))[1] ?? '') === $uid) return -1.0;
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
            case 'initiative': return SWUBotFeatureOn('mandoclaim') ? _SWUBotClaimDrawInitiative($ctx, $seat, $W) : $W['initiative'];
            case 'deploy':     if (_SWUBotDeployStrikeWaits($seat)) return -0.4;   // 'deploystrike' (p39)
                               $dv = $W['deploy'] + (SWUBotFeatureOn('enablers') ? _SWUBotDeployDiscount($seat, $action, $W) : 0.0)
                                                  + (SWUBotFeatureOn('pilotdeploy') ? _SWUBotPilotDeployValue($seat, $action, $W) : 0.0)
                                                  + (SWUBotFeatureOn('plotdeploy') ? _SWUBotPlotDeployValue($seat, $W) : 0.0);
                               // Feature 'deployswing' (p42): racing, + the attack the deployed leader unit (it enters ready) makes this turn.
                               // Before the holds below, which must still cap it.
                               if (SWUBotFeatureOn('deployswing') && (SWUBotProposalOn('deployswingall') || _SWUBotRaceIsOn($seat))) $dv += _SWUBotDeploySwingValue($ctx, $action, $W);
                               // Feature 'supportfirst' (p42): a Support deploy carries the planned Support attacker's attack.
                               if (SWUBotFeatureOn('supportfirst')) $dv += _SWUBotSupportDeployValue($ctx, $action, $W);
                               $dv = SWUBotFeatureOn('deployreplay') ? _SWUBotDeployReplayScore($ctx, $action, $dv, $W) : $dv;
                               // Feature 'actionfirst' (p42): the same leader's front Action, while it is worth using, goes before the deploy.
                               if (SWUBotFeatureOn('actionfirst') && ($af = _SWUBotDeployWaitsForAction($ctx, $action)) !== null) $dv = min($dv, $af);
                               // Feature 'landoflip' (p42): a "defeat a friendly Credit token … create 3" deploy never goes with 0 Credits.
                               if (SWUBotFeatureOn('landoflip') && ($fl = _SWUBotActionLeaderOfDeploy($seat, $action)) !== null && _SWUBotCreditFlipLeader($fl)
                                   && _SWUBotUsableCredits($seat) < SWUBotLeverNum('LANDO_FLIP_CREDITS', 1.0)) $dv = min($dv, -0.4);
                               return $dv;
            case 'leader-ability': if (_SWUBotKeepBodyHolds($seat)) return -0.4;   // 'keepbody' (p40)
                               return _SWUBotAbilityValue($ctx, $action, $W);
            case 'unit-action': case 'base-epic': return _SWUBotAbilityValue($ctx, $action, $W);
            case 'attack':
                $att = SWUBotViewForMz($seat, SWUBotActionMz($action));
                if ($att === null) return 0.0;
                $best = null;
                foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
                    $tv = SWUBotTargetValue($att, $k === 'base' ? null : $u, $W);
                    $best = $best === null ? $tv : max($best, $tv);
                }
                $guides = $ctx['_guides'] ?? _SWUBotGuides($ctx);
                $score = ($best ?? 0.0) + (in_array(strval($action['cardID'] ?? ''), $guides['attackFirst'], true) ? $W['attackFirst'] : 0.0);
                // Feature 'healwaste' (p42): a Restore N attack with less than N damage on my base wastes the restore — it waits, by what it wastes,
                // while an enemy unit is ready to hit my base first (owner: "sometimes best to let the opponent attack first when you have 0
                // damage on base if you can restore 1 or 2 after their hit"). Rule 8 still sends it before the round ends.
                if (SWUBotFeatureOn('healwaste') && ($wasted = _SWUBotRestoreWasted($seat, $att)) > 0) $score -= $wasted * $W['heal'] * SWUBotLeverNum('RESTORE_WASTE_MULT', 1.0);
                // Feature 'unitedge' (p42): the unit play that switches on "While you control more units than an opponent" goes first.
                if (SWUBotFeatureOn('unitedge') && ($edgePlay = _SWUBotUnitEdgePlay($ctx, $att)) !== null) $score = min($score, $edgePlay - 0.01);
                // Feature 'forceregen' (p42): holding the Force, a refilling Force-unit attack waits for the Force spender worth using now.
                if (SWUBotFeatureOn('forceregen') && !SWUBotProposalOn('forcerefillonly') && PlayerHasTheForce($seat) && _SWUBotAttackRefillsForce($seat, $att)
                    && ($sp = _SWUBotForceSpenderScore($ctx)) !== null) $score = min($score, $sp - 0.01);
                return $score;
            case 'play':
                $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
                $obj = GetHand($seat)[$i] ?? null;
                if ($obj === null) return 0.0;
                $cid = strval($obj->CardID ?? '');
                // A hand-size attack event is worth the attack it makes (feature 'anvader', p29). With nothing behind it in
                // hand it is a plain attack that spends a card — the ordinary pricing (and the dud gate) judge that.
                if (SWUBotFeatureOn('anvader') && _SWUBotIsHandSizeAttackEvent($cid)) {
                    $h = count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed))) - 1;
                    // No ready unit: NULL, and the dud gate below holds an attack event that cannot attack.
                    if ($h > 0 && ($swing = _SWUBotHandSwingBest($ctx, $seat, $h, $W)) !== null) return $swing;
                }
                // An effect event that would change nothing, or too little, on the enemy side is held (feature 'dudgate').
                // Feature 'anakinpitch' (p42): …unless it switches on an Anakin half with an Anakin in hand to cash it.
                $anakin = SWUBotFeatureOn('anakinpitch') ? _SWUBotAnakinPitchValue($seat, $i, $cid, $W) : 0.0;
                if (SWUBotFeatureOn('dudgate') && _SWUBotIsEffectEvent($cid) && !($anakin > 0.0 && (SWUBotProposalOn('pitchdeck') || _SWUBotAnakinInHand($seat)))
                    && _SWUBotEventIsDud($seat, $action, $cid, $W)) return -0.5;
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
                $v = _SWUBotPlayValue($seat, $cid, $W) + $anakin;
                // Play an enabler BEFORE the unit it improves (feature 'enablerfirst').
                if (SWUBotFeatureOn('enablerfirst')) $v += _SWUBotEnablerFirstBonus($seat, $action, $cid, $W);
                // Feature 'discountfirst' (p40): a static cost reducer (the Krennic unit) goes before the card it makes fit.
                if (SWUBotFeatureOn('discountfirst')) $v += _SWUBotDiscountOrderBonus($seat, $action, $cid);
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
                    // Feature 'curveplay' (p38): the curve surplus must survive this reset, or the WITH-Force copy (which keeps
                    // its surplus) scores below the body-only copy. The pricer already prices the Force-gated effect at 0 here.
                    if (SWUBotFeatureOn('curveplay')) {
                        $v += floatval($W['curve'] ?? 0.0) * (SWUBotCurveSurplus($seat, $cid, intval(round($W['horizon'] ?? SWU_CURVE_STATIC_HORIZON))) ?? 0.0);
                    }
                }
                // A second copy of a unique unit I control defeats one (the uniqueness rule) — feature 'picks'.
                // …unless the copy in play is SPENT and this cheap copy's When Played is worth playing again (feature 'uniquereplay', p31).
                $replay = SWUBotFeatureOn('uniquereplay') && _SWUBotUniqueReplayWorthIt($seat, $cid);
                // 'uniquerefresh' (p41): only what is still intact of a WORN copy is lost — a fully worn one costs nothing to replace.
                if (SWUBotFeatureOn('picks') && !$replay && _SWUBotUniqueClash($seat, $cid))
                    $v -= ($W['develop'] * intval(CardCost($cid)) + 1.0) * (SWUBotFeatureOn('uniquerefresh') ? 1.0 - _SWUBotUniqueWear($seat, $cid) : 1.0);
                // …and over an UNDAMAGED copy it gains nothing: held (feature 'unique'; owner report 2026-09-14, Bot
                // Practice game 183227 — Sabine's Masterpiece played over a healthy one). A damaged copy is a heal.
                // A refinement of 'picks', so it needs 'picks' on too (bot_picks_test compares picks on/off).
                if (SWUBotFeatureOn('picks') && SWUBotFeatureOn('unique') && !$replay && _SWUBotUniqueClash($seat, $cid) && _SWUBotUniqueCopyHealthy($seat, $cid)) return -0.5;
                $guides = $ctx['_guides'] ?? _SWUBotGuides($ctx);
                if ($guides['maxUnits'] === strval($action['cardID'] ?? '')) $v += $W['maxUnits'];
                // Feature 'wipeinit' (p36): a unit played into the arena of the wipe I am setting up for next round dies to it.
                if (SWUBotFeatureOn('wipeinit') && str_contains(strval(CardType($cid)), 'Unit') && ($wp = _SWUBotWipeNextRound($seat)) !== null
                    && in_array(_SWUBotPlayArena($cid), $wp['arenas'], true)) return min($v, -0.4);
                // Feature 'disclosereserve' (p36): playing the last card my Condemn's disclose needs gives back the -6/-0.
                if (SWUBotFeatureOn('disclosereserve') && ($pts = _SWUBotDiscloseReserveCost($seat, $i)) > 0) $v -= $W['base'] * $pts;
                // Feature 'fodderfirst' (p30): the paired-defeat card waits for the cheap unit that will be its price.
                if (SWUBotFeatureOn('fodderfirst') && _SWUBotPairedDefeatWantsFodder($seat, $i, $cid)) return min($v, -0.4);
                // Feature 'etbsetup' (p31): the gated When Played waits for the cheap unit that turns it on.
                if (SWUBotFeatureOn('etbsetup') && _SWUBotEtbWantsSetup($seat, $i, $cid)) return min($v, -0.4);
                // Feature 'lockpiece' (p35): the bomb a Galen blanks waits while an attack can kill that Galen this round.
                if (SWUBotFeatureOn('lockpiece') && _SWUBotBlankedBombWaits($seat, $cid)) return min($v, -0.4);
                // Feature 'observerfirst' (p31): the kill waits for the HK-47 that turns it into base damage.
                if (SWUBotFeatureOn('observerfirst') && _SWUBotKillWaitsForObserver($seat, $i, $cid)) return min($v, -0.4);
                // Feature 'wipeaware' (p30): not into a shown per-unit wipe's lethal range.
                if (SWUBotFeatureOn('wipeaware') && ($per = _SWUBotShownPerUnitWipe($seat)) > 0 && _SWUBotPlayEntersWipeRange($seat, $action, $per)) return min($v, -0.5);
                // Feature 'attackreserve' (p42): the token a ready "On Attack: You may pay N …, create a token" unit would lose.
                if (SWUBotFeatureOn('attackreserve')) $v -= SWUBotLeverNum('ATTACK_RESERVE_MULT', 1.0) * _SWUBotAttackReserveCost($seat, $obj, $W);
                // Feature 'mandoclaim' (p42): a play that spends the resource the claim's draw needs is charged that draw.
                if (SWUBotFeatureOn('mandoclaim') && ($cn = _SWUBotClaimDrawCost($seat)) !== null && SWUTotalPaymentCapacity($seat) >= $cn
                    && SWUTotalPaymentCapacity($seat) - intval(SWUComputePlayCost($seat, $obj)) < $cn) $v -= floatval($W['draw'] ?? 0.0) * SWUBotDrawMultiplier($seat) * SWUBotLeverNum('CLAIM_DRAW_MULT', 1.0);
                // Feature 'landoflip' (p42): before a "triple a Credit" leader flips, the last banked Credit is not spent.
                if (SWUBotFeatureOn('landoflip') && _SWUBotKeepsFlipCredit($seat) && _SWUBotUsableCredits($seat) - SWUBotCreditSpendFor($seat, $cid) < SWUBotLeverNum('LANDO_FLIP_CREDITS', 1.0)) return min($v, -0.5);
                // Feature 'forceregen' (p42): without the Force, a "When Played: You may use the Force" play waits for a refilling attack.
                if (SWUBotFeatureOn('forceregen') && !SWUBotProposalOn('forcespendonly') && preg_match(SWU_BOT_FORCE_WP_RE, strval(CardText($cid))) && !PlayerHasTheForce($seat)
                    && ($rw = _SWUBotForceRefillAttackScore($ctx, $seat)) !== null) $v = min($v, $rw - 0.01);
                // Feature 'deployfirst' (p42): a unit a deploy on offer would discount (Piett's Capital Ships) waits for that deploy.
                if (SWUBotFeatureOn('deployfirst') && _SWUBotDeployWouldDiscount($seat, $cid)) return min($v, -0.4);
                // Feature 'deploybuff' (p42): …and a unit play waits for a deploy whose leader buffs every unit played (ASH_017 Greef).
                if (SWUBotFeatureOn('deploybuff') && _SWUBotDeployBuffsPlay($seat, $cid)) return min($v, -0.4);
                return $v;
        }
        return -$index * 1e-6;
    }

    $tip = strval($ctx['tooltip'] ?? '');
    $type = strval($ctx['type'] ?? '');
    $head = _SWUBotContinuationHead($ctx);
    // Feature 'tuck' (p42): Qui-Gon's return pick (front "LOF_016#0", deployed "you may" "LOF_016#2") is the unit whose return buys the
    // best free play — never the cheapest, which "Return … to hand" read as hostile chose. A unit that buys nothing, or a losing pair,
    // scores below PASS (0), so the deployed "you may" is declined only then.
    if (SWUBotFeatureOn('tuck') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $tm) && str_starts_with($tip, 'Return_a_friendly')
        && ($ex = _SWUBotTuckExcluded($tm[1])) !== null && ($v = SWUBotViewForMz($seat, $c)) !== null) {
        // The FRONT Action's pick keeps the "swing first" rule (a ready unit that can hit the base is not the one returned); the deployed
        // "you may" fires once, after Qui-Gon's own attack, and cannot wait.
        $front = false;
        foreach (GetLeader($seat) as $ql) if (is_object($ql) && strval($ql->CardID ?? '') === $tm[1] && !in_array(strval($ql->Deployed ?? 'false'), ['true', '1'], true)) $front = true;
        $g = _SWUBotTuckGainFor($seat, $v, $ex, $front);
        return $g === null ? -1.0 - $index * 1e-6 : $g - $index * 1e-6;
    }
    // Feature 'doubleplay' (p42): Grievous's "Choose_a_unit_to_play" — each unit scores what playing it directly scores, so a play the
    // bot would hold (a duplicate unique) is held here too; PASS (0) declines when none is worth it. Was the first card in hand.
    if (SWUBotFeatureOn('doubleplay') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $dm) && preg_match('/^myHand-(\d+)$/', $c, $hm)
        && preg_match(SWU_BOT_DOUBLEPLAY_RE, strval(CardText($dm[1])))) {
        if (SWUBotProposalOn('doubleplaycost')) { $dpo = GetHand($seat)[intval($hm[1])] ?? null; return ($dpo === null ? -1.0 : 0.1 * intval(CardCost(strval($dpo->CardID ?? '')))) - $index * 1e-6; }   // lever: the costliest
        return _SWUBotHandPlayScore($ctx, intval($hm[1])) - $index * 1e-6;
    }
    // Feature 'playdefeat' (p42): Maul's "play it, then defeat it" pick — the unit's effects (+ the replay), not its body. A unit with no
    // effect scores below PASS (0). Was the biggest body (the "Play_a_…" play-value pick below).
    if (SWUBotFeatureOn('playdefeat') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $pdm) && preg_match('/^myHand-(\d+)$/', $c, $phm)
        && preg_match(SWU_BOT_PLAYDEFEAT_RE, strval(CardText($pdm[1])), $pdd)) {
        $leader = null;
        foreach (GetLeader($seat) as $lo) if (is_object($lo) && strval($lo->CardID ?? '') === $pdm[1]) $leader = $lo;
        $v = $leader !== null ? _SWUBotPlayDefeatValue($ctx, $leader, intval($phm[1]), intval($pdd[1]), $W) : null;
        return ($v ?? -1.0) - $index * 1e-6;
    }
    // Feature 'aspectpick' (p42): LAW_018 Lando's "Choose an aspect, then discard a card from a deck" names the aspect most of MY remaining
    // deck has (the deck it mills by default — my own list, never its order). Every option tied, so the first listed (Vigilance) went
    // 7,059 of 7,059 times. ('landomill', a proposal, reads the opponent's shown cards after the flip and takes precedence when on.)
    if (SWUBotFeatureOn('aspectpick') && $tip === 'Choose_an_aspect' && !_SWUBotLandomillOn() && (_SWUBotLandoPhase($seat)[0] ?? false)) {
        $counts = _SWUBotAspectCounts(array_map(fn($o) => strval($o->CardID ?? ''), array_filter(GetDeck($seat), fn($o) => $o !== null && empty($o->removed))));
        return 1.0 + 0.01 * floatval($counts[$c] ?? 0) - $index * 1e-6;
    }
    // Feature 'healamount' (p42): "Heal N damage from <a unit> or from your base" (HEAL_TARGET|N; ASH_005 Luke, deployed) — each worth the
    // HP it actually restores: a unit point 1.0, a base point 0.9, or 1.5 with the base at SWU_BOT_HEAL_BASE_PRESSURE or less. Was the unit
    // by value every time (2,202/2,202), ~16% of them a 1-damage unit.
    if (SWUBotFeatureOn('healamount') && $head === 'HEAL_TARGET') {
        $n = intval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? 0);
        if ($n > 0 && $c === 'myBase-0') {
            $dmg = intval((GetBase($seat)[0] ?? null)->Damage ?? 0);
            return min($n, $dmg) * (SWUBaseRemainingHp($seat) <= SWUBotLeverNum('HEAL_BASE_PRESSURE', SWU_BOT_HEAL_BASE_PRESSURE) ? 1.5 : 0.9) - $index * 1e-6;
        }
        if ($n > 0 && ($hv = SWUBotViewForMz($seat, $c)) !== null && !SWUBotIsEnemyMz($seat, $c)) {
            return min($n, intval($hv['hp']) - intval($hv['remaining'])) * SWUBotLeverNum('HEAL_UNIT_RATE', 1.0) - $index * 1e-6;
        }
    }
    // Feature 'creditdeploy' (p42): an option to "create a Credit token" (LAW_019 Alliance Outpost) is taken when one more Credit makes my
    // leader's resource-cost deploy ("Epic Action [N resources]: Deploy this leader", LAW_013 Chewbacca) affordable this round or next —
    // the owner's line: a token defeated in round 1 for the Credit, Chewbacca deployed on 3 resources + the Credit. (14 of 513 traced uses
    // took the Credit: the option pick's board read has no Credits in it.)
    if (SWUBotFeatureOn('creditdeploy') && $type === 'OPTIONCHOOSE' && in_array('Credit', explode('&', strval($ctx['param'] ?? '')), true)
        && _SWUBotCreditAdvancesDeploy($seat)) return $c === 'Credit' ? 1.0 : 0.0;
    // Feature 'advready' (p42): an Advantage token (ASH_T02, +1/+0 until the unit's next attack or defense ends) goes to a READY friendly
    // unit — it swings this round — before an exhausted one (ASH_013 Ezra: 14-20% went to an exhausted unit while a ready one stood by);
    // and Ezra's "Exhaust … to give an Advantage token to a different unit?" is declined when no friendly unit but the attacker exists
    // (the forced give then went to an ENEMY, 228 of 3,536 uses).
    if (SWUBotFeatureOn('advready')) {
        if ($head === 'GIVE_ADVANTAGE' && ($av = SWUBotViewForMz($seat, $c)) !== null) {
            return (SWUBotIsEnemyMz($seat, $c) ? -1.0 : ($av['ready'] ? 10.0 : 1.0) + 0.1 * $av['attackPower']) - $index * 1e-6;
        }
        if ($type === 'YESNO' && str_starts_with($tip, 'Exhaust_') && str_contains($tip, '_give_an_Advantage_token_to_a_different_unit')) {
            $att = SWUBotViewForMz($seat, strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? ''));
            $others = array_filter(SWUBotUnits($seat), fn($u) => $att === null || $u['uid'] !== $att['uid']);
            if (empty($others)) return $c === 'NO' ? 0.1 : 0.0;
        }
    }
    // Feature 'armorerpicks' (p42): ASH_001 The Armorer — "play an upgrade from your resources" on a unit. The upgrade is the one worth
    // most to play; the host is the attach scorer's pick (its host policy already sends Preparation — "When Played: Exhaust attached
    // unit" — to a unit that is exhausted). Was the first-listed resource (163/163) and the first-listed unit.
    if (SWUBotFeatureOn('armorerpicks') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $am)
        && preg_match('/play an upgrade from your resources/i', strval(CardText($am[1])) . ' ' . strval(CardDeployText($am[1])))) {
        if (preg_match('/^myResources-(\d+)$/', $c, $rm)) {
            $u = strval((GetResources($seat)[intval($rm[1])] ?? null)->CardID ?? '');
            return ($u !== '' ? 0.01 + max(0.0, _SWUBotPlayValue($seat, $u, $W, 'resources')) : -1.0) - $index * 1e-6;
        }
        $up = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? '');
        if ($up !== '' && str_contains(strval(CardType($up)), 'Upgrade') && ($s = _SWUBotAttachScore($seat, $c, $up, $W)) !== null) return $s - $index * 1e-6;
    }
    // Feature 'healwaste' (p42, owner 2026-10-08: "don't waste on a heal of 4 or less [Yoda's 5]. that goes for other restores too"): "use the
    // Force to heal N" is declined while my base carries less than N damage — the Force is kept (Qui-Gon's tuck spends it too).
    if (SWUBotFeatureOn('healwaste') && $type === 'YESNO' && preg_match('/use_the_Force_to_heal_(\d+)/i', $tip, $hw)
        && intval((GetBase($seat)[0] ?? null)->Damage ?? 0) < intval($hw[1]) - (SWUBotProposalOn('yodapartial') ? 2 : 0)) return $c === 'NO' ? 0.1 : 0.0;
    // Feature 'thrawnwd' (p42): JTL_002 Thrawn's "use that ability again" — declined when the When Defeated helps an opponent (SEC_215
    // Sheathipede's "Each opponent may ready a resource" was reused 95 times). Otherwise YES, as before.
    if (SWUBotFeatureOn('thrawnwd') && $type === 'YESNO' && $head === 'THRAWN_REUSE') {
        $src = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[2] ?? '');
        preg_match('/When Defeated:(.*)$/is', strval(CardText($src)), $wm);
        if (_SWUBotWdHelpsOpponent(strval($wm[1] ?? ''))) return $c === 'NO' ? 0.1 : 0.0;
    }
    // Feature 'dedra' (p42): SEC_010 Dedra Meero — "Choose an enemy unit. Its controller may deal N damage to it. If they don't, draw a
    // card." Her pick is an N-damage hostile target (a unit the N would defeat first: then both answers cost them); as the opponent, the
    // N is taken only when the unit survives it, or is a token (cost 0) worth less than her card. Was first-listed / always YES.
    if (SWUBotFeatureOn('dedra') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#(\d+)$/', $head, $dd)
        && preg_match("/Its controller may deal (\\d+) damage to it\\. If they don't, draw a card/i", strval(CardText($dd[1])), $dn)) {
        if ($type === 'YESNO') {
            $uid = intval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[2] ?? 0);
            $mz = $uid > 0 ? SWUFindMzByUID($uid) : null;
            $v = $mz !== null ? SWUBotUnitView(GetZoneObject($mz), str_contains($mz, 'Space') ? 'Space' : 'Ground') : null;
            $take = $v === null || intval($v['shields']) > 0 || intval($v['remaining']) > intval($dn[1]) || intval($v['cost']) <= 0;
            return $c === ($take ? 'YES' : 'NO') ? 0.1 : 0.0;
        }
        if (str_starts_with($c, 'their') || preg_match('/^p\d+/', $c)) return (_SWUBotTargetScore($seat, $c, true, intval($dn[1]), $head, $W) ?? 0.0) - $index * 1e-6;
    }
    // Feature 'chewieattack' (p42): LAW_013 Chewbacca deployed — "On Attack: You may defeat a friendly resource. If you do, deal 2 damage to a
    // unit …": a resource only when the 2 is worth it (_SWUBotChewieOnAttackWorth), otherwise below PASS. Was first-listed: 98.6% accepted.
    if (SWUBotFeatureOn('chewieattack') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#\d+$/', $head, $cw) && preg_match('/^my(Resources)-\d+$/', $c)
        && preg_match('/On Attack: You may defeat a friendly resource\. If you do, deal (\d+) damage to a unit/i', strval(CardDeployText($cw[1])), $cn)) {
        return (_SWUBotChewieOnAttackWorth($seat, intval($cn[1])) ? 1.0 : -1.0) - $index * 1e-6;
    }
    // Feature 'lciwplan' (p42): SEC_180 Let's Call It War — "Deal 3 damage to a unit. Then, if you have the initiative, you may deal 2 damage to
    // another unit in the same arena." While I hold the initiative the first target is worth itself plus the best 2 on another unit in its
    // arena (owner, Mando: "clear two low-health space units"). Scored alone, the 3 went on a 1-HP TIE and the 2 only chipped a 3-HP Y-Wing.
    if (SWUBotFeatureOn('lciwplan') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#0$/', $head, $lw) && SWUBotIsEnemyMz($seat, $c)
        && preg_match('/Deal (\d+) damage to a unit\. Then, if you have the initiative, you may deal (\d+) damage to another unit in the same arena/i', strval(CardText($lw[1])), $ln)
        && function_exists('PlayerHasIniative') && PlayerHasIniative($seat) && ($first = _SWUBotTargetScore($seat, $c, true, intval($ln[1]), 'DEAL_UNIT_DAMAGE', $W)) !== null) {
        $arena = str_contains($c, 'Space') ? 'Space' : 'Ground'; $follow = 0.0;
        foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits($o) as $u) {
            if ($u['arena'] !== $arena) continue;
            $mz = SWUFindMzByUID($u['uid']);
            if ($mz === null || $mz === $c) continue;
            if (SWUBotProposalOn('lciwkillonly') && $u['remaining'] > intval($ln[2])) continue;   // lever: only a follow-up KILL counts
            $follow = max($follow, _SWUBotTargetScore($seat, $mz, true, intval($ln[2]), 'DEAL_UNIT_DAMAGE', $W) ?? 0.0);
        }
        return $first + $follow - $index * 1e-6;
    }
    // Feature 'pitchtarget' (p42): ASH_163 Reckless Sacrifice — "Discard a unit from your hand. Deal 5 damage to a unit that costs more than
    // the discarded card." The discard is the unit that keeps the best 5-damage enemy target in reach, then the one worth least to keep. Every
    // unit scored the same, so the first listed went: a 2-cost Karis put a 2-cost Gungi out of reach and the 5 fizzled (owner, Mando: "the
    // best Turn 2 play is Reckless Sacrifice when you have any of the Villainy 1-drops in hand").
    if (SWUBotFeatureOn('pitchtarget') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#0$/', $head, $pt) && preg_match('/^myHand-(\d+)$/', $c, $ph)
        && preg_match('/Discard a unit from your hand\. Deal (\d+) damage to a unit that costs more than the discarded card/i', strval(CardText($pt[1])), $pn)
        && ($po = GetHand($seat)[intval($ph[1])] ?? null) !== null) {
        $gain = 0.0;
        foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits($o) as $u) {
            if (intval(CardCost($u['cardID'])) <= intval(CardCost(strval($po->CardID ?? '')))) continue;
            $mz = SWUFindMzByUID($u['uid']);
            if ($mz !== null) $gain = max($gain, _SWUBotTargetScore($seat, $mz, true, intval($pn[1]), 'DEAL_UNIT_DAMAGE', $W) ?? 0.0);
        }
        return $gain + 0.5 - 0.01 * SWUBotHandKeepValue($seat, $po, $W) - $index * 1e-6;
    }
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
        if ($o !== null) return 0.5 + 0.01 * _SWUBotPlayValue($seat, strval($o->CardID ?? ''), $W, 'hand', _SWUBotPlayPromptRoute($tip));
    }
    // Feature 'discardpick' (p29): a card discarded from MY hand — a cost (LAW_011 Darth Vader's ping) or an effect — is the
    // one worth least to keep. Before this every such prompt fell to the enumeration tiebreak below: hand index 0, which
    // was Chimaera 95 times in 200 traced Vader games — and SEC_197 Furtive Handmaiden's loot ("Choose_a_card_to_discard")
    // threw away the same Chimaera. Matched on the word, not a prefix: the prompts say "Discard_a_card_from_your_hand",
    // "Choose_a_card_to_discard", "An opponent discards…" (the bot choosing from its own hand). 0.5 − 0.01 × keep keeps
    // every hand card ABOVE an optional prompt's PASS, exactly as the near-zero tiebreak did: WHETHER to discard is
    // unchanged; only WHICH card is. Not a MULTI-select ("Discard any number of cards…", LAW_011 deployed): that is a
    // choice of how MANY, and lifting its single-card candidates above '-' made deployed Vader dump one card every attack.
    if (SWUBotFeatureOn('discardpick') && $type !== 'MZMULTICHOOSE' && stripos($tip, 'discard') !== false && preg_match('/^myHand-(\d+)$/', $c, $hm)) {
        $o = GetHand($seat)[intval($hm[1])] ?? null;
        if ($o !== null) return 0.5 - 0.01 * SWUBotHandKeepValue($seat, $o, $W) - $index * 1e-6;
    }
    // Feature 'anvader' (p29): the hand-size attack event's attacker is the unit its hand pays most (the event has left
    // the hand, so the bonus is the hand as it is now).
    if (SWUBotFeatureOn('anvader') && $onBoard && str_starts_with($c, 'my') && preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $am)
        && _SWUBotIsHandSizeAttackEvent($am[1]) && ($av = SWUBotViewForMz($seat, $c)) !== null) {
        return _SWUBotHandSwingValue($ctx, $av, count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed))), $W) - $index * 1e-6;
    }
    if (SWUBotFeatureOn('dumpdamage') && $type === 'MZMULTICHOOSE' && $tip === 'Discard_any_number_of_cards_from_your_hand'
        && ($ds = _SWUBotDumpScore($seat, $c, $W)) !== null) {
        return $ds - $index * 1e-6;
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
        if (str_contains($c, 'Base-')) {
            $bv = SWUBotTargetValue($att, null, $W) + (SeatCountForGame() > 2 ? 1e-4 * SWUBaseRemainingHp(SWUMzOwner($c, $seat)) : 0.0);
            // Feature 'cleanup' (p42): hard control vs an aggro board still reaching my base — a unit it defeats goes first.
            if (SWUBotFeatureOn('cleanup') && ($cap = _SWUBotCleanupCap($ctx, $att, $W)) !== null) $bv = min($bv, $cap);
            return $bv;
        }
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
    // The decision's 6th segment is the verb: "Play" — the picks are played for FREE (DoTopDeckPlay) — or "Take" (drawn).
    if ($type === 'TOPDECKSEARCH' && $tip === 'Search_top_cards' && SWUBotFeatureOn('picks'))
        return _SWUBotSearchScore($seat, $c, $W, _SWUBotSearchRoute(strval($ctx['param'] ?? '')));
    // Part 20 'buffspread': a Support leader's flip turn — who makes the Support attack, and where its phase buffs go.
    if ($onBoard && SWUBotFeatureOn('buffspread')) {
        if ($head === 'SWUSupportChooseAttacker') {
            $v = SWUBotViewForMz($seat, $c);
            return $v === null ? -$index * 1e-6 : _SWUBotSupportAttackerScore($seat, $v, strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? ''));
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
    // Feature 'traskreturn' (p39): ASH_133 Trask Walker takes back the best ANSWER and returns it to hand. Owner (Krennic Blue, 2026-10-06):
    // Trask takes back "Chimaera". Traced: "Bottom + heal 3" 225 of 225 — the discard pick was the first-legal tiebreak (the oldest
    // card) and the mode lookahead priced the heal as a gain and a card in hand as nothing.
    if (SWUBotFeatureOn('traskreturn') && $head === 'ASH_133#0' && preg_match('/^myDiscard-(\d+)$/', $c, $dm)) {
        $cid = strval((GetDiscard($seat)[intval($dm[1])] ?? null)->CardID ?? '');
        return 1.0 + (_SWUBotIsAnswer($cid) ? 10.0 : 0.0) + 0.1 * intval(CardCost($cid)) - $index * 1e-6;
    }
    if (SWUBotFeatureOn('traskreturn') && $type === 'OPTIONCHOOSE' && $head === 'ASH_133#1') {
        $mz = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? '');
        $cid = preg_match('/^myDiscard-(\d+)$/', $mz, $dm) ? strval((GetDiscard($seat)[intval($dm[1])] ?? null)->CardID ?? '') : '';
        if (_SWUBotIsAnswer($cid)) return $c === 'Return' ? 1.0 : 0.0;
    }
    // A card's own modal choice (its continuation "CARD#n"): judged by what each option does (feature 'modes').
    if ($type === 'OPTIONCHOOSE' && SWUBotFeatureOn('modes') && strval($ctx['param'] ?? '') !== 'Unit&Pilot'
        && $tip !== 'Choose_a_player_to_deal_indirect_damage' && preg_match('/^[A-Z0-9]+_[A-Z0-9]+#/', $head)) {
        $s = _SWUBotOptionDelta($ctx, $action, $W);
        if ($s !== null) return $s - $index * 1e-6;
    }
    // Feature 'pilotchoice' (p42): a pilot leader's deploy — Pilot whenever a host it can use exists, and the best such host.
    if (SWUBotFeatureOn('pilotchoice') && ($pc = _SWUBotPilotChoiceScore($ctx, $seat, $c, $type, $tip)) !== null) return $pc - $index * 1e-6;
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
        // 'budgetsetup' (p41): what the token adds to my budget wipe's kill set — on top, since a softened body is softened either way.
        $budget = (SWUBotFeatureOn('budgetsetup') && !$v['isLeader']) ? _SWUBotBudgetSetupValue($seat, $v, $loss, $W) : 0.0;
        if (SWUBotFeatureOn('setup') && !$v['isLeader'] && $v['remaining'] - $loss <= _SWUBotFinisherHPFor($seat, $v)) return max($soften, 0.8 * $kill) + $budget;
        return $soften + $budget;
    }
    if ($loss >= $v['remaining']) return SWUBotFeatureOn('fodder') ? -SWUBotSacrificeCost($v) : -SWUBotUnitValue($v);
    return -$soften;
}

// ── Feature 'budgetsetup' (p41) — Weakness as setup for a budget wipe ────────────────────────────────────────────────
// Ninin vs Wicket Green R5-R7: three Weakness tokens and 3 damage took Luminara (7/7) to 1 HP, and Pre Vizsla's "total of 6 or
// less remaining HP" then defeated her, Teebo and Crix Madine. The token's share of that: SWU_BOT_BUDGET_SETUP of the kill
// weight times what it adds to the wipe's best kill set (a future, answerable kill, and the wipe may never come).
const SWU_BOT_BUDGET_SETUP = 0.5;
const SWU_BOT_BUDGET_SETUP_ROUNDS = 2;   // the R5 token came two rounds before the R7 Pre Vizsla: a resource a round until then

// The biggest budget N among the budget wipes in my hand that I can cast within SWU_BOT_BUDGET_SETUP_ROUNDS rounds (one more
// resource each regroup), else 0.
function _SWUBotBudgetWipeCap(int $seat): int {
    $res = count(array_filter(GetResources($seat), fn($o) => $o !== null && empty($o->removed)));
    $cap = 0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!preg_match('/Defeat any number of non-leader units with a total of (\d+) or less remaining HP/i', strval(CardText($cid)), $m)) continue;
        if (intval(CardCost($cid)) > $res + SWU_BOT_BUDGET_SETUP_ROUNDS) continue;
        $cap = max($cap, intval($m[1]));
    }
    return $cap;
}

// The best total value of units (['remaining' => HP, 'value' => worth]) whose remaining HP sums to <= $cap: an exact 0/1 knapsack
// over HP (budgets are single digits). A unit at 0 HP or less is dead already and is not counted.
function _SWUBotBudgetKillValue(array $units, int $cap): float {
    $best = array_fill(0, max(0, $cap) + 1, 0.0);
    foreach ($units as $u) {
        $hp = intval($u['remaining']);
        if ($hp <= 0 || $hp > $cap) continue;
        for ($h = $cap; $h >= $hp; $h--) $best[$h] = max($best[$h], $best[$h - $hp] + floatval($u['value']));
    }
    return max($best);
}

// What a Weakness give of $loss on enemy unit $v adds to my budget wipe: SWU_BOT_BUDGET_SETUP x kill weight x (the best kill set's
// value after the token - before it). The enemy non-leader units of every opponent, priced by SWUBotUnitValue.
function _SWUBotBudgetSetupValue(int $seat, array $v, int $loss, array $W): float {
    $cap = _SWUBotBudgetWipeCap($seat);
    if ($cap <= 0) return 0.0;
    $before = []; $after = [];
    foreach (OpponentsOf($seat) as $o) {
        foreach (SWUBotUnits(intval($o)) as $u) {
            if ($u['isLeader']) continue;
            $row = ['uid' => $u['uid'], 'remaining' => $u['remaining'], 'value' => SWUBotUnitValue($u)];
            $before[] = $row;
            if ($u['uid'] === $v['uid']) $row['remaining'] -= $loss;
            $after[] = $row;
        }
    }
    return SWU_BOT_BUDGET_SETUP * $W['kill'] * max(0.0, _SWUBotBudgetKillValue($after, $cap) - _SWUBotBudgetKillValue($before, $cap));
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
    // Part 25 'defeatpick': "…choose a friendly unit and an enemy non-leader unit. If you do, defeat THOSE UNITS" (ASH_052
    // Chimaera) — the enemy half read as neutral, so every enemy scored the flat 0.01 and the FIRST listed was taken:
    // a shielded 1-cost Han Solo over an 8-cost Pre Vizsla (owner report 2026-10-03, game 1438045). Reached only for a
    // prompt the friendly-defeat check above has not already classed as a sacrifice.
    if (SWUBotFeatureOn('defeatpick') && preg_match('/\bdefeat (those units|that unit|them)\b/i', $text)) return 'hostile';
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
    // 'defeatimmune' (p36): my defeat effect on an enemy that can't be defeated by enemy card abilities does nothing.
    if ($hostile && $enemy && $amount === 0 && _SWUBotHeadDefeats($head) && SWUBotDefeatFizzles($v)) return 0.0;
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
        // A Shield stops the hit — and popping it is worth what the Shield was worth ('splitpop', p32). At 0, IG-2000's
        // "1 damage to each of up to 3 units" chipped a 6/6 Pre Vizsla over popping a third Mandalorian token's Shield
        // (owner, 2026-10-04: "it would actually be better to pop 3 shields than to only pop 2 and ping 1 on Pre Vizsla").
        if ($blocked) $score = SWUBotFeatureOn('splitpop') ? _SWUBotShieldPopValue($seat, $v, $W, true) : 0.0;
        elseif ($amount >= $v['remaining']) $score = SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0;
        elseif (SWUBotFeatureOn('setup') && !$v['isLeader'] && $v['remaining'] - $amount <= _SWUBotFinisherHPFor($seat, $v))
            $score = 0.8 * (SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0);   // my finisher defeats it next
        else $score = SWUBotUnitValue($v) * $W['chip'] * $amount / max(1, $v['hp']);
    }
    // Exhaust a READY enemy; buffs go on units that attack this round (a ready friendly unit).
    if ($v['ready'] && (($hostile && $enemy && $head === 'EXHAUST_UNIT') || (!$hostile && !$enemy))) $score += $W['ready'];
    return $score;
}

// Is $head a DEFEAT of the chosen unit — the DEFEAT_UNIT continuation, or a card continuation ("LAW_133#…") whose text defeats
// without taking control first (No Glory, Only Results defeats a unit its caster then controls: not an enemy ability)?
function _SWUBotHeadDefeats(string $head): bool {
    if ($head === 'DEFEAT_UNIT') return true;
    if (!preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', $head, $m)) return false;
    $t = strval(CardText($m[1]));
    return (bool)preg_match('/\bdefeat\b/i', $t) && !preg_match('/take control/i', $t);
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

// [$isLandoLeader, $postFlip] for this seat's undeployed Lando, in whichever slot he sits (a Twin Suns seat has two
// leaders). $leaders narrows it to the leader whose Action is being priced (_SWUBotActionLeader).
function _SWUBotLandoPhase(int $seat, ?array $leaders = null): array {
    foreach ($leaders ?? GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $deployed = !empty($l->Deployed) && strval($l->Deployed) !== 'false';
        if ($deployed || !preg_match(SWU_BOT_LANDO_ACTION, strval(CardText(strval($l->CardID ?? ''))))) continue;
        $flipped = !empty($l->EpicActionUsed) && strval($l->EpicActionUsed) !== 'false';
        return [true, $flipped];
    }
    return [false, false];
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

// A seat's leader COLOR aspects — every aspect on its leader(s) but Heroism / Villainy (public: the leader is face up).
function _SWUBotLeaderColorAspects(int $seat): array {
    $out = [];
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        foreach (explode(',', strval(CardAspect(strval($l->CardID ?? '')) ?? '')) as $a) {
            $a = trim($a);
            if ($a !== '' && $a !== 'Heroism' && $a !== 'Villainy') $out[] = $a;
        }
    }
    return array_values(array_unique($out));
}

// 'landomill' — SHIPPED 2026-10-08 as a p42 feature (owner: "turn it on"); '@try-landomill' still names the proposal.
function _SWUBotLandomillOn(): bool { return SWUBotProposalOn('landomill') || SWUBotFeatureOn('landomill'); }

// Lando mills THEIR deck after the flip (2026-09-23) or once he has 10+ resources (owner 2026-10-08: "Lando aims for 9 for Bo-Katan and
// off-aspect Chimaera … no guaranteed Credits are needed when Lando has 10+ resources. 9 + 1 for the mill ability"). Credit tokens are
// not resources (CR 3.13; SWUResourceCount skips them).
const SWU_BOT_LANDO_MILL_THEIRS_AT = 10;
function _SWUBotLandoMillsTheirs(int $seat, bool $postFlip): bool {
    return $postFlip || SWUResourceCount($seat) >= SWUBotLeverNum('LANDO_MILL_THEIRS_AT', SWU_BOT_LANDO_MILL_THEIRS_AT);
}

// The two prompts the Action raises. Returns null when this is not a landomill decision.
function _SWUBotLandoDecisionScore(array $ctx, string $c, int $index): ?float {
    if (!_SWUBotLandomillOn()) return null;
    $seat = intval($ctx['seat']);
    [$isLando, $post] = _SWUBotLandoPhase($seat);
    if (!$isLando) return null;
    $post = _SWUBotLandoMillsTheirs($seat, $post);   // after the flip, or at 10+ resources (owner 2026-10-08)
    $tip = strval($ctx['tooltip'] ?? '');
    if ($tip === 'Choose_an_aspect') {
        $counts = _SWUBotLandoAspectCounts($seat, $post);
        // OWNER RULING 2026-10-08: milling THEIR deck, name one of THEIR aspects — the leader's COLOR, never Heroism / Villainy: "against a
        // Lake Country deck, it is safe to pick the aspect that is not Hero or Villain … Boba Fett JTL Lake Country, you would mill Red
        // off them (Aggression)" (their cards share the leader's color, not its alignment). Feature 'aspectpick'.
        if ($post && SWUBotFeatureOn('aspectpick') && in_array($c, _SWUBotLeaderColorAspects(SWUBotOpponent($seat)), true)) {
            return 2.0 + 0.01 * floatval($counts[$c] ?? 0) - $index * 1e-6;
        }
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
function _SWUBotLandoAbilityScore(array $ctx, array $action, array $W): ?float {
    if (!_SWUBotLandomillOn()) return null;
    $seat = intval($ctx['seat']);
    $l = _SWUBotActionLeader($seat, $action);
    if ($l === null) return null;
    [$isLando, $post] = _SWUBotLandoPhase($seat, [$l]);
    if (!$isLando || !_SWUBotLandoMillsTheirs($seat, $post)) return null;
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
        if (!empty($v['isLeader']) || SWUBotDefeatFizzles($v)) continue;   // 'defeatimmune' (p36)
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

// Feature 'sentinelsac' (p42), owner 2026-10-08: "generally, do not sac active Sentinels. so Gideon or Koska when a token unit is present".
// An ACTIVE Sentinel (printed or gained — the view reads the live keyword) is priced SWU_BOT_SENTINEL_SAC_PREMIUM above its own cost, so
// every other body goes first; a doomed one (it dies anyway) is not protected. ASH_097 Moff Gideon's When Defeated payback priced him as
// fodder: Krennic's Credit Action took him over a bare Krennic unit and over a 3/3 Battlefield Marine.
const SWU_BOT_SENTINEL_SAC_PREMIUM = 10.0;
function SWUBotSacrificeCost(array $v): float {
    $cost = _SWUBotSacrificeCostBase($v);
    if (SWUBotFeatureOn('sentinelsac') && !empty($v['sentinel']) && (SWUBotProposalOn('sentsacdoomed') || !SWUBotUnitIsDoomed($v))) $cost += SWUBotLeverNum('SENTINEL_SAC_PREMIUM', SWU_BOT_SENTINEL_SAC_PREMIUM);
    return $cost;
}
function _SWUBotSacrificeCostBase(array $v): float {
    // 'shieldtrader': a printed-Shielded cheap unit is kept while its Shield is up (priced at its full value); once it is gone, fodder (owner: "then sac it") —
    // but after a 0-power token ("I'd sac the Spy since it is weaker on defense"). JTL_032's "When Defeated" is only words in its text,
    // so only a real "When Defeated:" ability earns that discount here.
    if (SWUBotFeatureOn('shieldtrader') && empty($v['isLeader']) && intval($v['cost']) <= 2 && preg_match('/\bShielded\b/', $text = strval(CardText($v['cardID'])))) {
        $own = preg_match('/When Defeated:/i', $text) ? _SWUBotSacrificeCostByValue($v) : SWUBotUnitValue($v);
        if (intval($v['shields']) > 0) return $own;   // its value counts the Shield: above a same-cost body
        $bare = max(0.0, $own - SWUBotLeverNum('BARE_FODDER_DISCOUNT', SWU_BOT_BARE_FODDER_DISCOUNT));
        return SWUBotFeatureOn('doomedsac') && SWUBotUnitIsDoomed($v) ? min($bare, SWU_BOT_DOOMED_SAC_COST + 0.01 * intval($v['power'])) : $bare;
    }
    if (SWUBotFeatureOn('doomedsac')) {
        if (!empty($v['isLeader'])) {
            // 2a: Condemned ("loses all abilities") — sending it back restores the leader's front side.
            $condemned = isset($v['obj']) && function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($v['obj'], 'SEC_038');
            if ($condemned || SWUBotUnitIsDoomed($v)) return min(SWU_BOT_DOOMED_LEADER_SAC_COST, SWUBotUnitValue($v));
        } elseif (SWUBotUnitIsDoomed($v)) {
            // 2b: dying anyway — unless the When Defeated pricing below already makes it cheaper still.
            $byValue = _SWUBotSacrificeCostByValue($v);
            return min(SWU_BOT_DOOMED_SAC_COST, $byValue);
        }
    }
    return _SWUBotSacrificeCostByValue($v);
}

// Feature 'spentetb' (p31): a unit whose ONLY text is a When Played — already used, it is in play — is its body: what its printed
// cost paid for the effect is spent (Ninin's Chimaera took her Solar Sailer, not a unit with an ongoing ability). Any other
// ability or keyword (On Attack, When Defeated, an Action, a "While …", Sentinel, Raid…) keeps the printed-cost pricing.
function _SWUBotOnlySpentWhenPlayed(string $cid): bool {
    $t = trim(strval(CardText($cid)));
    return (bool)preg_match('/^When Played:/i', $t)
        && !preg_match('/When Defeated|On Attack|Action \[|\bWhile\b|Sentinel|Raid|Restore|Overwhelm|Grit|Saboteur|Ambush|Shielded|Hidden|Bounty|Smuggle|Exploit|Piloting|Coordinate/i', $t);
}

function _SWUBotSacrificeCostByValue(array $v): float {
    $value = SWUBotUnitValue($v);
    if (SWUBotFeatureOn('spentetb') && _SWUBotOnlySpentWhenPlayed($v['cardID'])) $value = min($value, _SWUBotUnitStatsValue($v));
    $text = strval(CardText($v['cardID']));
    $hasWd = stripos($text, 'When Defeated') !== false;
    if (!$hasWd) return $value;
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
    // Feature 'thrawnwd' (p42): JTL_002 Thrawn's reuse available — the When Defeated pays back TWICE (one that helps an opponent is not
    // reused, so it is not doubled).
    $times = (SWUBotFeatureOn('thrawnwd') && function_exists('_SWUThrawnReuseMode') && _SWUThrawnReuseMode(intval($v['controller'])) !== null
              && !_SWUBotWdHelpsOpponent($wd)) ? 2 : 1;
    // It pays something back: heal, draw, or a replacement body.
    if (preg_match('/\bheal \d+|\bdraw (a card|\d+)|\bcreate \d+/i', $wd)) return max(0.0, $value - 2.5 * $times);
    return max(0.0, $value - 1.5 * $times);
}

// A When Defeated text that hands something to an OPPONENT: SEC_215 "Each opponent may ready a resource", JTL_221 "Choose an opponent.
// For this phase, they may play this unit …" (feature 'thrawnwd').
function _SWUBotWdHelpsOpponent(string $wd): bool {
    return (bool)preg_match('/\bopponents?\b[^.]*\bmay\b|Choose an opponent\.[^.]*\bthey may\b/i', $wd);
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

function _SWUBotPlayValue(int $seat, string $cid, array $W, string $fromZone = 'hand', string $route = 'paid'): float {
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
        if ($t === 'search-top-deck' && SWUBotFeatureOn('searchvalue')) $v += _SWUBotSearchValue($seat, $cid, $W);
    }
    // FEATURE 'curveplay' (p38; spec 2026-10-05-swusim-curve-value-design.md §4.1): the card's curve SURPLUS — what it is worth
    // over what it costs, in resources, from the owner's prices — is ADDED on top of the cost floor and the tag sum (never
    // replacing them: 'unitvalue' measured −50 doing that). An unpriced card adds 0.
    // $route: 'paid' | 'waived' | 'nopenalty' | 'free' (BotCurveValue.php) | 'nocurve' — a comparison that must not use curve value.
    if (function_exists('SWUBotFeatureOn') && SWUBotFeatureOn('curveplay') && $route !== 'nocurve') {   // feature p38
        $v += floatval($W['curve'] ?? 0.0) * (SWUBotCurveSurplus($seat, $cid, intval(round($W['horizon'] ?? SWU_CURVE_STATIC_HORIZON)), true, $route) ?? 0.0);
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
    // Feature 'ctxpower' (p28): this function prices a unit by its COST, so a card whose power depends on the
    // board is worth the same here whether it is a 3/3 or a 9/9. Add the board-dependent SURPLUS, priced
    // per point like any other power (W['base'] — the same currency SWUBotTargetValue uses for a swing).
    // Zero for every card without an arm, and zero on an empty board, so the default-OFF proposal changes
    // nothing until it is switched on.
    $v += $W['base'] * SWUBotContextSurplus($seat, $cid);
    // Part 23 'bigcredit': a credit-RAMP deck spends a banked Credit only on a big play (owner 2026-10-03, game 1438045:
    // "krennic didn't bank credits. wasted them right away on Onyx Squad Brute").
    // Feature 'earlycredits' (p40): R1-3 vs an aggro leader the Credits pay for board — owner 2026-10-06, "Spend by R3, then bank".
    if (SWUBotFeatureOn('bigcredit') && SWUBotCreditSpendFor($seat, $cid) > 0 && SWUBotBanksCredits($seat) && !SWUBotCreditWorthy($cid)
        && !(SWUBotFeatureOn('earlycredits') && intval(GetTurnNumber()) <= SWU_BOT_EARLY_CREDIT_ROUNDS && SWUBotOpponentIsAggroLeader($seat))) {
        $v = min($v, -0.5);
    }
    // '@try-wipecredit' (2026-10-07 gap screen): a play that spends a Credit the wipe needs NEXT round is held — Lando (any credit deck) began
    // R4 with Hyperspace Disaster, 5 resources and 1 Credit, spent it on Anakin, and was one short in R5 (7 losses; diagnosis .claude/tmp/diag_vader).
    if (SWUBotProposalOn('wipecredit') && ($spend = SWUBotCreditSpendFor($seat, $cid)) > 0 && !in_array('wipe', SWUBotCardTags($cid), true)
        && ($w = _SWUBotWipeNextRound($seat)) !== null && _SWUBotCapNextRound($seat) - $spend < _SWUBotSeatCost($seat, $w['cid'])) {
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
// Feature 'earlycredits' (p40) lifts the hold below in rounds 1-3 against an aggro leader. Autopsy 2026-10-06: the bot held 1.0-1.3
// Credits every round while behind on board (R2: 0.9 units vs the owner's 2.2). Owner: "Spend by R3, then bank". The 2026-10-03 report
// that started 'bigcredit' was vs HMW_008, not an aggro leader, and stays held.
const SWU_BOT_EARLY_CREDIT_ROUNDS = 3;
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
// Feature 'keepbody' (p40). Rounds 1-3 vs an aggro leader: Krennic's Credit Action ("Action [Exhaust, defeat a friendly unit]: Create a
// Credit token") does not sacrifice my ONLY unit unless its When Defeated draws. Autopsy 2026-10-06: the bot ended R1 with 0.2 units (a
// 1-drop, sacrificed at once), the owner with 1.2. Owner ruling: "Sac it if it draws" (Ant Droid, Nightsister); a body without a draw stays.
const SWU_BOT_KEEP_BODY_ROUNDS = 3;
function _SWUBotKeepBodyHolds(int $seat): bool {
    if (!SWUBotFeatureOn('keepbody') || intval(GetTurnNumber()) > SWU_BOT_KEEP_BODY_ROUNDS || !SWUBotOpponentIsAggroLeader($seat)) return false;
    $leader = GetLeader($seat)[0] ?? null;
    if ($leader === null || !preg_match('/defeat a friendly unit\]:\s*Create a Credit token/i', strval(CardText(strval($leader->CardID ?? ''))))) return false;
    $units = array_values(array_filter(SWUBotUnits($seat), fn($v) => empty($v['isLeader'])));
    return count($units) === 1 && !preg_match('/When Defeated:[^.]*\bdraw\b/i', strval(CardText($units[0]['cardID'])));
}

// Feature 'discountfirst' (p40). A unit whose STATIC text makes another card cheaper — JTL_032 Director Krennic: "The first unit you
// play each round that has a 'When Defeated' ability costs 1 less" — is played FIRST when that is the only order in which both fit this
// round. Autopsy 2026-10-06: holding the Krennic unit + Ant Droid on round 1 (the owner's opener), the bot played only the Ant Droid in
// 28 of 28 games — Ant Droid first costs 1, and the 2-cost unit no longer fits. 'enablerfirst' reads only a When Played "next unit you
// play this phase" grant. Judged by the lookahead (the hand's play costs after this play), so the discount's own conditions — which
// unit, once a round — are the engine's, not a reading of the text.
const SWU_BOT_DISCOUNT_ORDER_BONUS = 1.0;   // the whole second play this round
function _SWUBotDiscountOrderBonus(int $seat, array $action, string $cid): float {
    if (!str_contains(strval(CardType($cid)), 'Unit') || !preg_match('/\bcosts? \d+ (resources? )?less\b/i', strval(CardText($cid)))
        || preg_match('/next unit you play this phase/i', strval(CardText($cid))) || !function_exists('SWUBotLookahead')) return 0.0;
    $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
    $self = GetHand($seat)[$i] ?? null;
    if ($self === null) return 0.0;
    $cap = SWUTotalPaymentCapacity($seat);
    $selfCost = intval(SWUComputePlayCost($seat, $self));
    $left = $cap - $selfCost;
    $costs = function () use ($seat) {
        $out = [];
        foreach (GetHand($seat) as $j => $o) { if ($o !== null && empty($o->removed)) $out[strval($o->CardID ?? '') . '#' . $j] = intval(SWUComputePlayCost($seat, $o)); }
        return $out;
    };
    $before = $costs();
    $after = SWUBotLookahead($seat, $action, fn() => ['c' => array_values(array_filter(array_map(fn($o) => $o === null || !empty($o->removed) ? null : [strval($o->CardID ?? ''), intval(SWUComputePlayCost($seat, $o))], GetHand($seat))))]);
    if ($after === null) return 0.0;
    foreach (GetHand($seat) as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed)) continue;
        $pid = strval($o->CardID ?? '');
        $was = $before[$pid . '#' . $j] ?? PHP_INT_MAX;
        $now = PHP_INT_MAX;
        foreach ($after['c'] as [$c2, $k2]) if ($c2 === $pid) $now = min($now, $k2);
        // It fits ONLY after this play, and playing it first would leave too little for this one: the order decides a whole play.
        if ($now < $was && $now <= $left && $cap - $was < $selfCost) return SWU_BOT_DISCOUNT_ORDER_BONUS;
    }
    return 0.0;
}

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
// Feature 'deploystrike' (p39): a leader whose deploy is "When Deployed: Another friendly unit deals damage equal to its power to an
// enemy unit" (LAW_008 Director Krennic) waits until that strike KILLS. Owner 2026-10-06 — Krennic Blue: "Deploy when you have another
// body that can deal big damage to the enemy"; Krennic Splash: "when the When Deployed ping kills". Traced: R6 (7 resources) in 305 of
// 366 deploys. A Shield absorbs the strike, so a shielded enemy is not a kill.
const SWU_BOT_DEPLOY_STRIKE_LEADERS = ['LAW_008'];
function _SWUBotDeployStrikeWaits(int $seat): bool {
    if (!SWUBotFeatureOn('deploystrike') || !in_array(strval((GetLeader($seat)[0] ?? null)->CardID ?? ''), SWU_BOT_DEPLOY_STRIKE_LEADERS, true)) return false;
    $power = 0;
    foreach (SWUBotUnits($seat) as $v) if (!$v['isLeader']) $power = max($power, intval($v['power']));
    foreach (SWUBotEnemyUnits($seat) as $e) if (intval($e['shields']) === 0 && $power > 0 && $power >= intval($e['remaining'])) return false;
    return true;
}

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
            // Feature 'epicwipe' (p37): against an aggro leader that has NOT flipped, an unlocked wipe is not worth the waiver yet — owner
            // follow-up 2026-10-06, "wipe on HER flip turn only" (rule 5 opens it once she has deployed).
            if (SWUBotFeatureOn('epicwipe') && in_array('wipe', SWUBotCardTags(strval($o->CardID)), true) && SWUBotOpponentIsAggroLeader($seat)
                && empty(array_filter(SWUBotUnits(SWUBotOpponent($seat)), fn($v) => $v['isLeader']))) continue;
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
        // ⚠ An EVENT is played too: it resolves and goes to the discard, so it is never "in play". Requiring that made
        // a waiver unlocking an event worth 0 — LAW_044 Single Reactor Ignition, the very card the owner's 6R + 2 Credit
        // line uses the waiver for (found 2026-10-03 while adding 'waiverhold', which would otherwise have held it).
        if (!in_array($cid, $inPlay, true) && !(SWUBotFeatureOn('waiverhold') && stripos(strval(CardType($cid)), 'Event') !== false)) continue;
        if (in_array($cid, $alreadyCastable, true)) continue;
        $v += _SWUBotPlayValue($seat, $cid, $W, 'hand', 'waived');   // played through the waiver: no aspect penalty
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

// The Raid the Support SOURCE has (e.g. SEC_099 Naboo Royal Starship's Raid 2 on a deployed Ahsoka): Support LENDS it to the
// Supported unit (owner ruling, "Support lends gained abilities"), so a Support attacker breaches with it. The source is
// the unit at $sourceMz (the Support-attacker prompt carries it: "SWUSupportChooseAttacker|<mz>"), else the unit of mine
// whose card a live Support trigger names — never "my leader with the most Raid": a Twin Suns seat has two leaders.
// 0 when there is no source.
function _SWUBotSupportLentRaid(int $seat, ?string $sourceMz = null): int {
    $raid = fn(array $v) => max(0, intval($v['attackPower']) - intval($v['power']));
    if ($sourceMz !== null && $sourceMz !== '') { $v = SWUBotViewForMz($seat, $sourceMz); return $v === null ? 0 : $raid($v); }
    $cards = [];
    foreach (GetEffectStack() as $e) {
        if (empty($e->removed) && strval($e->TriggerType ?? '') === 'Support' && intval($e->Controller ?? 0) === $seat) $cards[strval($e->CardID ?? '')] = true;
    }
    $best = 0;
    foreach (SWUBotUnits($seat) as $v) if (isset($cards[$v['cardID']])) $best = max($best, $raid($v));
    return $best;
}

// The unit that should make the Support attack: a ready non-leader unit ("another unit"), in an arena that reaches the
// base if any does, the strongest attacker there (Raid is lent to whoever it is, so it never changes the pick).
function _SWUBotSupportPlanUid(int $seat, int $bonus = 0): int {
    $blocked = _SWUBotBlockedArenas($seat);
    $lent = _SWUBotSupportLentRaid($seat);
    // 'stacklethal' (p36): the Plot buffs still to come and the base they must finish.
    $stack = SWUBotFeatureOn('stacklethal');
    $plotLeft = $stack ? _SWUBotPlotBuffsLeft($seat) : 0;
    $hp = $stack ? SWUBaseRemainingHp(SWUBotOpponent($seat)) : PHP_INT_MAX;
    $best = null; $bestKey = null;
    foreach (SWUBotUnits($seat) as $v) {
        if (!$v['ready'] || $v['isLeader']) continue;
        // 'breach' (p26): a Sentinel this unit can defeat with the buff on offer is not a write-off.
        $clear = !$blocked[$v['arena']] || (SWUBotFeatureOn('breach') && _SWUBotCanBreach($v, $bonus + $lent));
        // 'stacklethal' (p36): this unit's ONE attack with every buff on it (and its own On Attack boost) finishes the base.
        $lethal = $stack && !$blocked[$v['arena']] && $v['attackPower'] + $bonus + $plotLeft + _SWUBotSelfAttackBoost($seat, $v) + $lent >= $hp;
        $key = [$lethal ? 1 : 0, $clear ? 1 : 0, $v['attackPower'], $v['remaining'], -$v['uid']];
        if ($bestKey === null || $key > $bestKey) { $best = $v; $bestKey = $key; }
    }
    return $best === null ? 0 : $best['uid'];
}

// 'stacklethal' (p36): the "+N/+N to another friendly unit for this phase" still to come from Plot cards in my resources, greedily
// as far as my ready resources pay (SEC_111 Jar Jar Binks: +2 for 2). Only meaningful in a deploy's Plot window.
function _SWUBotPlotBuffsLeft(int $seat): int {
    $cap = SWUTotalPaymentCapacity($seat); $sum = 0;
    foreach (GetResources($seat) as $r) {
        if ($r === null || !empty($r->removed)) continue;
        $cid = strval($r->CardID ?? '');
        if (!function_exists('HasKeyword_Plot') || !HasKeyword_Plot($r)) continue;
        if (!preg_match('/give another friendly unit \+(\d+)\/\+\d+ for this phase/i', strval(CardText($cid)), $m)) continue;
        $c = intval(CardCost($cid));
        if ($c > $cap) continue;
        $cap -= $c; $sum += intval($m[1]);
    }
    return $sum;
}

// 'stacklethal' (p36): the boost $v gives ITSELF on attack — "On Attack: … this unit gets +N/+0 for this attack" (ASH_203 Mando's
// N-1 Starfighter: "You may exhaust a friendly (non-upgrade) leader. If you do, …" — needs a ready leader of mine).
function _SWUBotSelfAttackBoost(int $seat, array $v): int {
    $t = strval(CardText($v['cardID']));
    if (!preg_match('/On Attack:[^"]*?this unit gets \+(\d+)\/\+0 for this attack/i', $t, $m)) return 0;
    if (stripos($t, 'exhaust a friendly (non-upgrade) leader') !== false) {
        $ready = false;
        foreach (SWUBotUnits($seat) as $u) if ($u['isLeader'] && $u['ready']) $ready = true;
        foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && _SWULeaderReadyUndeployed($seat, strval($l->CardID ?? ''))) $ready = true;
        if (!$ready) return 0;
    }
    return intval($m[1]);
}

// The Support attacker: the strongest in an arena that reaches the base. After the Plot buffs that is the planned unit —
// the same order _SWUBotSupportPlanUid uses — so no separate plan check is needed (a mutation of one proved it inert).
function _SWUBotSupportAttackerScore(int $seat, array $v, string $sourceMz = ''): float {
    $blocked = _SWUBotBlockedArenas($seat)[$v['arena']] && !(SWUBotFeatureOn('breach') && _SWUBotCanBreach($v, _SWUBotSupportLentRaid($seat, $sourceMz)));
    return ($blocked ? 0.1 : 1.0) * (1.0 + 0.1 * $v['attackPower'] + 0.001 * $v['remaining']);
}

// A friendly "+N for this phase" during the flip turn, or null when this is not one (the ordinary scoring decides).
//  · the Support attack is still pending (Jar Jar's Plot): the planned Support attacker. The leader only when there is
//    none — a +2/+2 on her still beats passing (an explicit "avoid the leader" was proved inert by mutation and removed);
//  · the Supported unit's borrowed On Attack (the source is a leader card, the attacker is not a leader): the leader;
//  · the leader's own On Attack: any ready unit that still attacks.
// Everywhere: a buff on a unit in a Sentinel-blocked arena is wasted for the base race, so it scores near the bottom; one on
// an exhausted unit (other than the one attacking now) does nothing at all, so it scores below that — a blocked unit still
// attacks a unit. Tied, the just-played (exhausted) Jar Jar or Royal Starship took the +2 over a ready ship whenever a
// space Sentinel stood (2026-10-03 canary traces, Ahsoka vs Luke ASH DV).
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
    $amount = intval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? 0);   // APPLY_PHASE_BUFF|<amount>|…
    // While the Support attack is pending (Jar Jar's prompt), the unit that MAKES it — the plan, and only the plan — also
    // borrows the Support source's Raid. Any other candidate attacks without it.
    $lent = ($pending && !$fromLeader && !$v['isLeader'] && $v['uid'] === _SWUBotSupportPlanUid($seat, $amount)) ? _SWUBotSupportLentRaid($seat) : 0;
    $blocked = _SWUBotBlockedArenas($seat)[$v['arena']] && !(SWUBotFeatureOn('breach') && _SWUBotCanBreach($v, $amount + $lent));
    $attackingNow = $att !== null && $att['uid'] === $v['uid'];
    if (!$v['ready'] && !$attackingNow) return 0.001 + 0.0001 * SWUBotUnitValue($v);   // expires unused, but no worse than PASS
    if ($blocked) return 0.01 + 0.0001 * SWUBotUnitValue($v);                        // still attacks — a unit, not the base
    $score = 1.0 + 0.01 * SWUBotUnitValue($v);
    if ($pending && !$fromLeader) {
        if ($v['uid'] === _SWUBotSupportPlanUid($seat, $amount)) $score += 10.0;   // the leader gets hers from the Supported attack
    } elseif (!$att['isLeader'] && $v['isLeader']) {
        $score += 10.0;                                                      // the Supported unit buffs the leader: she attacks next
    }
    return $score;
}

// A search's pick ("CardID,CardID"; "" = none): the play value of what it takes, a little more per card.
// Curve-value route of a "Play_a_…" hand prompt (final review, 2026-10-06): "ignore 1 of its … aspect penalties" (a LAW
// base) is 'waived' — one battlefield pip; ignoring the whole penalty is 'nopenalty'; "…for free" is 'free'; a discount
// ("costs 1 less") still pays its penalty — 'paid'.
function _SWUBotPlayPromptRoute(string $tip): string {
    if (preg_match('/ignor\w*_1_of_its/i', $tip)) return 'waived';                       // a LAW base: one battlefield pip
    if (stripos($tip, 'ignor') !== false && stripos($tip, 'aspect') !== false) return 'nopenalty';   // the whole penalty
    if (stripos($tip, 'for_free') !== false || stripos($tip, 'for free') !== false) return 'free';
    return 'paid';
}

// Curve-value route of a TOPDECKSEARCH (final review, 2026-10-06): FREE only when the picks are PLAYED within a combined-cost
// budget — DoTopDeckPlay's "cost:N" constraint with the verb "Play" (Ackbar, U-Wing). A discounted "Play" search (Kelleran
// Beq, "count:1", 3 less) and a "Take" search are 'paid'. Param segments: all|match|constraint|costs|label|verb|scope.
function _SWUBotSearchRoute(string $param): string {
    $p = explode('|', $param);
    return (stripos(strval($p[5] ?? ''), 'play') !== false && str_starts_with(strval($p[2] ?? ''), 'cost:')) ? 'free' : 'paid';
}

function _SWUBotSearchScore(int $seat, string $candidate, array $W, string $route = 'paid'): float {
    // Feature 'searchpick' (p36): a UNIQUE card whose name is already in play under me, or earlier in this pick, is defeated by
    // the uniqueness rule the moment it lands — worth -1, not its play value (17 of 125 traced Ackbar searches did this).
    $uniq = SWUBotFeatureOn('searchpick');
    $names = [];
    $name = fn(string $c) => strval(CardTitle($c)) . '|' . strval(CardSubtitle($c));
    if ($uniq) foreach (SWUBotUnits($seat) as $v) if (CardUnique($v['cardID'])) $names[$name($v['cardID'])] = true;
    $s = 0.0;
    foreach (array_filter(explode(',', $candidate)) as $cid) {
        $cid = trim($cid);
        if ($uniq && CardUnique($cid)) {
            if (isset($names[$name($cid)])) { $s -= 1.0; continue; }
            $names[$name($cid)] = true;
        }
        $s += _SWUBotPlayValue($seat, $cid, $W, 'hand', $route) + 0.01;
    }
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

// Feature 'uniquereplay' (p31): a second copy of a CHEAP unique unit with a When Played, over a SPENT copy (exhausted or damaged)
// — the uniqueness rule defeats the old copy and the new one's When Played resolves again, a ready body for a used one. Ninin vs
// Ackbar Data Vault R3 (a second Nuvo Vindi, another Weakness token); owner 2026-10-04: "this is usually true for cheap units with
// When Played abilities." Cheap = printed cost 3 or less.
const SWU_BOT_UNIQUE_REPLAY_MAX_COST = 3;
function _SWUBotUniqueReplayWorthIt(int $seat, string $cid): bool {
    if (intval(CardCost($cid)) > SWU_BOT_UNIQUE_REPLAY_MAX_COST || !preg_match('/When Played/i', strval(CardText($cid)))) return false;
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && strval(CardSubtitle($v['cardID'])) === strval(CardSubtitle($cid)))
            return !$v['ready'] || $v['remaining'] < $v['hp'];
    }
    return false;
}

// The copy of unique $cid I already control is at full HP (nothing to refresh). Feature 'unique'.
// 'uniquerefresh' (p41): a copy carrying a downgrade (a Weakness token, a Bounty) is not healthy either — its current HP already
// includes the -1, so remaining >= hp read it as untouched (Ninin vs Wicket Green R7).
function _SWUBotUniqueCopyHealthy(int $seat, string $cid): bool {
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && strval(CardSubtitle($v['cardID'])) === strval(CardSubtitle($cid)))
            return $v['remaining'] >= $v['hp'] && !(SWUBotFeatureOn('uniquerefresh') && $v['downgrades'] > 0);
    }
    return false;
}

// Feature 'uniquerefresh' (p41): how worn my copy of unique $cid is — (damage + Weakness tokens) / printed HP, capped at 1. A fresh
// copy arrives without either.
function _SWUBotUniqueWear(int $seat, string $cid): float {
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) !== CardTitle($cid) || strval(CardSubtitle($v['cardID'])) !== strval(CardSubtitle($cid))) continue;
        $weak = 0;
        foreach (GetUpgradesOnUnit($v['obj']) as $s) if (strval($s->CardID ?? '') === 'HMW_T02') $weak++;
        return min(1.0, (intval($v['obj']->Damage ?? 0) + $weak) / max(1, intval(CardHp($v['cardID']))));
    }
    return 0.0;
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
    return ['theirs' => $theirs, 'mine' => _SWUBotSideValue($seat), 'theirBase' => $theirBase, 'myBase' => SWUBaseRemainingHp($seat),
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
    // 'dudheal' (p41): the HP the event heals on my base counts, a base point each (Lost and Forgotten's "heal 3 damage from
    // your base"; Ninin vs Wicket Green R8). Only the dud gate reads it — the other lookahead callers keep their delta.
    $heal = SWUBotFeatureOn('dudheal');
    $line = SWUBotLookaheadBest($seat, $action, fn() => _SWUBotBoardRead($seat),
        fn(array $r) => _SWUBotBoardDelta($before, $r, $W) + ($heal ? $W['base'] * max(0, $r['myBase'] - $before['myBase']) : 0.0),
        SWU_BOT_LOOKAHEAD_DEPTH, 12);
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
// The leader whose Action this is: 'myLeader-1!CustomInput!LeaderAbility' → GetLeader($seat)[1]. Null for any other
// action. A Twin Suns seat has two leaders, and reading slot 0 priced one leader's Action with the other's text (#1127).
function _SWUBotActionLeader(int $seat, array $action): ?object {
    if (SWUBotActionKind($action) !== 'leader-ability') return null;
    $i = preg_match('/^myLeader-(\d+)$/', SWUBotActionMz($action), $m) ? intval($m[1]) : 0;
    $l = GetLeader($seat)[$i] ?? null;
    return is_object($l) && empty($l->removed) ? $l : null;
}

function _SWUBotActionSourceText(int $seat, array $action): string {
    $k = SWUBotActionKind($action);
    if ($k === 'leader-ability') { $l = _SWUBotActionLeader($seat, $action); return $l !== null ? strval(CardText(strval($l->CardID ?? ''))) : ''; }
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

// The finisher threshold against THIS target $v: _SWUBotFinisherHP, raised to 1 when my leader can finish a 1-HP unit this round.
//   'ndsetup' (p36)        — a ready UNDEPLOYED leader's Action: Doctor Hemlock ("Action [1 resource, Exhaust]: Give a Weakness token
//                            to a unit without a Weakness token on it" — a ready resource, a target with none) or Mother Talzin
//                            ("Action [Exhaust, use the Force (lose your Force token)]: Give a unit -1/-1" — needs the Force). Owner
//                            2026-10-04: No Disintegrations sets these up.
//   'onattackfinish' (p36) — a READY deployed leader whose On Attack gives a Weakness token / -1/-1 to a unit (Hemlock, Talzin):
//                            Reprint_Cad split Ninth Sister 1/1/1, then Hemlock's attack token finished the Interceptor.
function _SWUBotFinisherHPFor(int $seat, array $v): int {
    $n = _SWUBotFinisherHP($seat);
    $nd = SWUBotFeatureOn('ndsetup'); $oa = SWUBotFeatureOn('onattackfinish');
    if (!$nd && !$oa) return $n;
    foreach (GetLeader($seat) as $l) {
        if (!$nd || $l === null || !empty($l->removed)) continue;
        $cid = strval($l->CardID ?? '');
        if (!_SWULeaderReadyUndeployed($seat, $cid)) continue;
        $t = strval(CardText($cid));
        if (preg_match('/Action \[1 resource, Exhaust\]: Give a Weakness token to a unit without a Weakness token on it/i', $t)
            && SWUTotalPaymentCapacity($seat) >= 1 && intval($v['downgrades'] ?? 0) === 0) return max($n, 1);
        if (preg_match('/Action \[Exhaust, use the Force[^\]]*\]: Give a unit -1\/-1/i', $t)
            && function_exists('PlayerHasTheForce') && PlayerHasTheForce($seat)) return max($n, 1);
    }
    if ($oa) {
        foreach (SWUBotUnits($seat) as $u) {
            if (!$u['isLeader'] || !$u['ready']) continue;
            if (preg_match('/On Attack: You may give (a Weakness token to a unit|a unit -1\/-1)/i', strval(CardDeployText($u['cardID'])))) return max($n, 1);
        }
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
            // The waiver removes ONE battlefield pip's penalty — never Heroism/Villainy (owner, 2026-10-06; it was min(2, penalty)).
            $waived  = $actual - ($waiver ? _SWUBotWaiverDiscount($seat, $cid) : 0);
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

// ── Part 29: a card discarded from MY hand is a real cost (BotFeatures.php, group p29) ─────────────────────────────────────
// Does $cid need more Heroism icons than this seat provides — off-aspect BECAUSE of Heroism (a Villainy deck's splash)?
function _SWUBotOffAspectHeroism(int $seat, string $cid): bool {
    $need = count(array_filter(array_map('trim', explode(',', strval(CardAspect($cid) ?? ''))), fn($a) => $a === 'Heroism'));
    if ($need === 0 || !function_exists('PlayerAspects')) return false;
    return $need > count(array_filter((array)PlayerAspects($seat), fn($a) => $a === 'Heroism'));
}

// Feature 'heropitch': a card still to be played wants "a Heroism card in your discard pile" (LOF_070 Anakin Skywalker's
// second -3/-3) and the discard has none yet. Hand and deck both count — the deck's copy is the one the pitch is for.
function _SWUBotWantsHeroismInDiscard(int $seat): bool {
    foreach (GetDiscard($seat) as $o) {
        if ($o !== null && empty($o->removed) && str_contains(strval(CardAspect(strval($o->CardID ?? '')) ?? ''), 'Heroism')) return false;
    }
    foreach ([GetHand($seat), GetDeck($seat)] as $zone) {
        foreach ($zone as $o) {
            if ($o !== null && empty($o->removed) && preg_match('/Heroism card in your discard pile/i', strval(CardText(strval($o->CardID ?? ''))))) return true;
        }
    }
    return false;
}

// ── Feature 'pilotchoice' (p42) — the pilot leaders' deploy (JTL_006 Vader, JTL_009 Boba, JTL_012 Luke) ────────────────────────
// "Deploy_as_Unit_or_Pilot?" was a 0.2 guide (Pilot only with a READY Vehicle) and "Choose_a_Vehicle_to_deploy_onto" was never scored
// (the first listed Vehicle took the pilot: a token, a 1-HP ship). Pilot whenever a usable host exists — any host for a "When deployed as
// an upgrade" pilot (Boba's split, Vader's TIEs), a READY one otherwise (Vonreg's / Luke's value is the host's attack, and a leader unit
// deploys ready). A pilot that names a trait ("If it's a Fighter, it gains …", Luke) uses only a host with that trait. Else: Unit.
// NULL for any other prompt.
// The deploying leader is read off the continuation ("LEADER_DEPLOY_CHOICE|<cid>|<i>" / "LEADER_DEPLOY_PILOT|<cid>|<i>").
function _SWUBotPilotNeedsTrait(string $leaderCid): string {
    return preg_match("/If it's an? ([\w ]+?), it gains/i", strval(CardDeployText($leaderCid)), $m) ? $m[1] : '';
}
function _SWUBotPilotHostScore(int $seat, string $mz, string $trait): ?float {
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null) return null;
    if ($trait !== '' && !TraitContains($v['obj'], $trait)) return -1.0;
    // Ready first (the pilot's power swings this round), then the sturdiest (the pilot dies with its host), then power. A token host
    // is no worse in itself — a 1/1 TIE just loses on HP.
    return 1.0 + ($v['ready'] ? 2.0 : 0.0) + 0.3 * $v['remaining'] + 0.1 * $v['power'];
}
function _SWUBotPilotChoiceScore(array $ctx, int $seat, string $c, string $type, string $tip): ?float {
    $next = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
    if (!in_array($next[0] ?? '', ['LEADER_DEPLOY_CHOICE', 'LEADER_DEPLOY_PILOT'], true)) return null;
    $trait = _SWUBotPilotNeedsTrait(strval($next[1] ?? ''));
    if ($type === 'OPTIONCHOOSE' && strval($ctx['param'] ?? '') === 'Unit&Pilot') {
        global $playerID;
        $saved = $playerID; $playerID = $seat;
        $hosts = function_exists('SWUGetLeaderPilotVehicles') ? (array)SWUGetLeaderPilotVehicles($seat) : [];
        $playerID = $saved;
        // A "When deployed as an upgrade" pilot (Boba's split, Vader's TIEs) pays off on any host; any other pilot's value is the host's
        // attack (Vonreg's, Luke's On Attack), and a deployed leader UNIT enters ready and swings now — so it wants a READY host.
        $anyHost = (bool)preg_match('/When deployed as an upgrade:/i', strval(CardDeployText(strval($next[1] ?? ''))));
        $usable = array_filter($hosts, fn($mz) => ($s = _SWUBotPilotHostScore($seat, strval($mz), $trait)) !== null && $s > 0.0
                                                  && ($anyHost || (SWUBotViewForMz($seat, strval($mz))['ready'] ?? false)));
        return $c === (empty($usable) ? 'Unit' : 'Pilot') ? 1.0 : 0.0;
    }
    if ($tip === 'Choose_a_Vehicle_to_deploy_onto') return _SWUBotPilotHostScore($seat, $c, $trait);
    return null;
}

// ── Features 'shipdiscount' + 'deployfirst' (p42) — JTL_005 Admiral Piett ──────────────────────────────────────────────────
// Front: "Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1 resource less." The bot priced it by what it UNLOCKS, so
// an affordable ship read the discount as nothing and was hard-cast while the Action was ready (367/300-game traces, Blue). Its value:
// the ship's own play score, plus the best OTHER hand card the saved resources pay for (it fits only because of the discount). With no
// such card it is the same outcome as the direct play, so it sits just under it. NULL when no $trait unit can be played through it.
function _SWUBotDiscountPlayActionValue(array $ctx, int $seat, string $trait, int $discount): ?float {
    $cap = SWUTotalPaymentCapacity($seat);
    $hand = GetHand($seat);
    $best = null;
    foreach ($hand as $i => $o) {
        $cid = strval($o->CardID ?? '');
        if ($o === null || !empty($o->removed) || !str_contains(strval(CardType($cid)), 'Unit') || !HasTrait($cid, $trait)) continue;
        $full = intval(SWUComputePlayCost($seat, $o));
        $pay = max(0, $full - $discount);
        if ($pay > $cap) continue;
        $extra = null;
        foreach ($hand as $j => $q) {
            if ($j === $i || $q === null || !empty($q->removed)) continue;
            $c = intval(SWUComputePlayCost($seat, $q));
            if ($c > $cap - $pay || $c <= $cap - $full) continue;   // fits only thanks to the discount
            $s = _SWUBotHandPlayScore($ctx, intval($j));
            if ($s > 0.0) $extra = max($extra ?? $s, $s);
        }
        $v = _SWUBotHandPlayScore($ctx, intval($i)) + ($extra ?? ($full <= $cap ? -0.01 : 0.0));
        $best = max($best ?? $v, $v);
    }
    return $best;
}

// Deployed: "Each Capital Ship unit you play costs 2 resources less." Playing such a unit while a deploy that would discount it is on
// offer RIGHT NOW wastes the discount — 'blockerfirst' hard-cast a 5-cost ship just before deploying Piett in ~25% of traced games. True
// when $cid is such a unit and that leader can deploy now (the play then waits; the deploy goes first). Read off the LEADER, not the
// action list: 'blockerfirst' re-scores its plays against a list cut down to the plays alone.
function _SWUBotDeployWouldDiscount(int $seat, string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Unit')) return false;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true) || SWUResourceCount($seat) < _SWUBotLeaderThreshold($seat, $l)) continue;
        if (preg_match('/Each ([\w ]+?) unit you play costs \d+ resources? less/i', strval(CardDeployText(strval($l->CardID ?? ''))), $m)
            && HasTrait($cid, $m[1])) return true;
    }
    return false;
}

// Feature 'deploybuff' (p42): a leader that can deploy NOW whose deployed side reads "When you play or create a unit: …" (ASH_017 Greef:
// "Give an Advantage token to that unit") — a unit played before that deploy misses it (41% of Greef's deploys came after 2+ plays).
function _SWUBotDeployBuffsPlay(int $seat, string $cid): bool {
    if (!str_contains(strval(CardType($cid)), 'Unit')) return false;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true) || SWUResourceCount($seat) < _SWUBotLeaderThreshold($seat, $l)) continue;
        if (preg_match('/When you play (or create )?a unit:/i', strval(CardDeployText(strval($l->CardID ?? ''))))) return true;
    }
    return false;
}

// Feature 'creditdeploy' (p42): one more Credit brings an undeployed leader's "Epic Action [N resources]: Deploy this leader" within reach
// — this round (the payment capacity now) or next (one more resource), where it is short without it.
function _SWUBotCreditAdvancesDeploy(int $seat): bool {
    $credits = function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($seat)) : 0;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true)) continue;
        if (!preg_match('/Epic Action \[(\d+) resources?\]: Deploy this leader/i', strval(CardText(strval($l->CardID ?? ''))), $m)) continue;
        $n = intval($m[1]);
        foreach (SWUBotProposalOn('creditdeploynow') ? [SWUTotalPaymentCapacity($seat)] : [SWUTotalPaymentCapacity($seat), SWUResourceCount($seat) + 1 + $credits] as $cap) if ($cap < $n && $cap + 1 >= $n) return true;
    }
    return false;
}

// Feature 'deployswing' (p42): a deploy puts a READY leader unit on the board (CR 4.329) — in a race (audit: "in a race, price a non-pilot
// deploy as the leader unit's attack this turn"), worth its best attack this turn, priced as any attack is (SWUBotTargetValue). HMW_007 Vader's free deploy scored the flat 1.5, below most plays and attacks: in 24 of 47 probe games
// that reached 6 resources he never deployed before the game ended. 0 when no ready leader unit appears (a pilot deploy stops at its
// Unit/Pilot prompt) or it has nothing to attack.
// A race is on: either side's clock on the other's base (SWUBotClock, in rounds) is SWU_BOT_DEPLOYSWING_RACE or less.
const SWU_BOT_DEPLOYSWING_RACE = 3;
function _SWUBotRaceIsOn(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $opp) {
        $race = SWUBotLeverNum('DEPLOYSWING_RACE', SWU_BOT_DEPLOYSWING_RACE);
        if (SWUBotClock($seat, $opp) <= $race || SWUBotClock($opp, $seat) <= $race) return true;
    }
    return false;
}

function _SWUBotDeploySwingValue(array $ctx, array $deploy, array $W): float {
    $seat = intval($ctx['seat']);
    $leader = _SWUBotLeaderOfAction($seat, $deploy);
    if ($leader === null || !function_exists('SWUBotLookahead')) return 0.0;
    $cid = strval($leader->CardID ?? '');
    $r = SWUBotLookahead($seat, $deploy, function () use ($ctx, $seat, $cid, $W) {
        $best = 0.0;
        foreach (SWUBotUnits($seat) as $v) {
            if (!$v['isLeader'] || $v['cardID'] !== $cid || !$v['ready']) continue;
            foreach (SWUBotAllowedTargets($ctx, $v) as [$k, $u]) $best = max($best, SWUBotTargetValue($v, $k === 'base' ? null : $u, $W));
        }
        return ['v' => $best];
    });
    return floatval($r['v'] ?? 0.0);
}

// Feature 'supportfirst' (p42): a SUPPORT leader's deploy ("When you deploy this leader, you may attack with another unit. It gains this
// unit's other abilities for this attack") makes the planned Support attacker's attack in the same action — so the deploy is worth that
// attack too, plus the lent "On Attack: If you have the initiative, you may draw a card" (ASH_014 The Mandalorian) while I hold it. Owner,
// Mando: "6R deploy and use Mando's support on any unit that stuck"; at the flat 1.5 the stuck unit attacked first and the Support was lost.
// 0 when no ready unit is there to make the attack.
function _SWUBotSupportDeployValue(array $ctx, array $deploy, array $W): float {
    $seat = intval($ctx['seat']);
    $leader = _SWUBotLeaderOfAction($seat, $deploy);
    if ($leader === null) return 0.0;
    $dtext = strval(CardDeployText(strval($leader->CardID ?? '')));
    if (!preg_match('/^Support \(/', $dtext) || ($uid = _SWUBotSupportPlanUid($seat)) === 0) return 0.0;
    $best = null;
    foreach (SWUBotUnits($seat) as $v) {
        if ($v['uid'] !== $uid) continue;
        foreach (SWUBotAllowedTargets($ctx, $v) as [$k, $u]) {
            $tv = SWUBotTargetValue($v, $k === 'base' ? null : $u, $W);
            $best = $best === null ? $tv : max($best, $tv);
        }
    }
    if ($best === null) return 0.0;
    $draw = preg_match('/On Attack: If you have the initiative, you may draw a card/i', $dtext) && function_exists('PlayerHasIniative') && PlayerHasIniative($seat)
        ? floatval($W['draw'] ?? 0.0) * SWUBotDrawMultiplier($seat) : 0.0;
    return SWUBotLeverNum('SUPPORT_ATTACK_SHARE', 1.0) * max(0.0, $best) + SWUBotLeverNum('SUPPORT_DRAW_SHARE', 1.0) * $draw;
}

// Feature 'actionfirst' (p42): a leader deploys whether ready or exhausted, and its unit enters ready (CR 4.329) — so in the round it
// deploys, its front Action, while worth using, comes first and costs the deploy nothing. Audit 2026-10-08: Lando 0 of 1,092 deploys
// after his Action (47% deployed with no Credit for the When Deployed), Obi-Wan 0/1,011, Talzin 35/1,965. Left alone:
//   - a deploy that discounts what that Action plays (JTL_005 Piett, "Each Capital Ship unit you play costs 2 resources less": deploy
//     first — 'deployfirst' also gets there, but bot_enablers_test runs without it and pins "deploy Piett first");
//   - a SUPPORT leader (ASH_009 Ahsoka …): its flip turn is planned by 'buffspread' / 'stacklethal' (bot_stacklethal_test);
//   - an Action whose COST defeats a friendly unit (LAW_008 Krennic): the deploy may need that body ("another friendly unit deals
//     damage…", 'deploystrike' — bot_deploystrike_test);
//   - a When Deployed replay of a unit defeated this phase (HMW_016 Maul): 'deployreplay' owns that hold.
// Returns the score the deploy must stay under, or NULL.
function _SWUBotDeployWaitsForAction(array $ctx, array $deploy): ?float {
    $seat = intval($ctx['seat']);
    $leader = _SWUBotLeaderOfAction($seat, $deploy);
    if ($leader === null) return null;
    $dtext = strval(CardDeployText(strval($leader->CardID ?? '')));
    if (preg_match('/Each [\w ]+? unit you play costs \d+ resources? less|\bSupport\b/i', $dtext) || preg_match(SWU_BOT_DEPLOYREPLAY_RE, $dtext)) return null;
    if (preg_match('/Action \[[^\]]*defeat a friendly unit/i', strval(CardText(strval($leader->CardID ?? ''))))) return null;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        if (SWUBotActionKind($a) !== 'leader-ability' || _SWUBotLeaderOfAction($seat, $a) !== $leader) continue;
        $av = SWUBotScoreAction($ctx, $a, intval($i));
        return $av > 0.0 ? $av - 0.01 : null;
    }
    return null;
}

const SWU_BOT_HEAL_BASE_PRESSURE = 15;   // 'healamount': a base this close to defeat counts its HP for more than a unit's

// ── Feature 'landoflip' (p42) — LAW_018 Lando's flip: "When Deployed: You may defeat a friendly Credit token. If you do, create 3 Credit
// tokens." Owner 2026-10-08: "before Lando flips, make sure you have 1 Credit to triple it. do not deploy Lando with 0 Credits"; with one
// banked from 5R the 6R Action is skipped (6R + 3C = 9 for Bo-Katan / Chimaera; using it on 6R leaves 5R + 3C = 8).
function _SWUBotCreditFlipLeader(object $l): bool {
    return (bool)preg_match('/defeat a friendly Credit token\. If you do, create \d+ Credit tokens/i', strval(CardDeployText(strval($l->CardID ?? ''))));
}
function _SWUBotUsableCredits(int $seat): int {
    return function_exists('SWUUsableCreditTokenMzIDs') ? count(SWUUsableCreditTokenMzIDs($seat)) : 0;
}
function _SWUBotActionLeaderOfDeploy(int $seat, array $action): ?object { return _SWUBotLeaderOfAction($seat, $action); }
function _SWUBotLeaderCanDeployNow(int $seat, object $l): bool {
    return !in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true) && !in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true)
        && SWUResourceCount($seat) >= _SWUBotLeaderThreshold($seat, $l);
}
// A "triple a Credit" leader still to flip (undeployed, Epic Action unspent): its last banked Credit is kept for the flip.
function _SWUBotKeepsFlipCredit(int $seat): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || !_SWUBotCreditFlipLeader($l)) continue;
        if (!in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true) && !in_array(strval($l->EpicActionUsed ?? 'false'), ['true', '1'], true)) return true;
    }
    return false;
}

// ── Feature 'mandoclaim' (p42) — ASH_014 The Mandalorian: "When you take the initiative: You may pay 1 resource. If you do, draw a card." ─
// Owner plan 2026-10-08: "2R/T1 immediately claim and draw · 3R play a 2-drop then claim and draw · 4R play a 3-drop then claim and draw".
// Taking the initiative ENDS my actions this round, so the plays that still leave the resource go first (attacks already do: the attack-first
// guide and rule 8), then the claim. The claim is worth the draw (W['draw'] × the deck-out multiplier), kept just under those plays; a play
// that spends that last resource is charged the draw.
// Audit 2026-10-08: the claim was worth 0.05 — taken only when nothing else was left, 43% of the time with no resource for the draw.
// The claim's resource cost N while the draw is on offer (an undeployed leader with the text, the initiative unclaimed) — else null.
function _SWUBotClaimDrawCost(int $seat): ?int {
    if (!str_contains(strval(GetInitiativeCounter() ?? ''), 'UNCLAIMED')) return null;
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) continue;
        if (preg_match('/When you take the initiative: You may pay (\d+) resources?\. If you do, draw a card/i', strval(CardText(strval($l->CardID ?? ''))), $m)) return intval($m[1]);
    }
    return null;
}
function _SWUBotClaimDrawInitiative(array $ctx, int $seat, array $W): float {
    $n = _SWUBotClaimDrawCost($seat);
    $cap = SWUTotalPaymentCapacity($seat);
    if ($n === null || $cap < $n) return $W['initiative'];
    $v = $W['initiative'] + floatval($W['draw'] ?? 0.0) * SWUBotDrawMultiplier($seat) * SWUBotLeverNum('CLAIM_DRAW_MULT', 1.0);
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        // (Attacks need no cap here: the 'attack-first' guide and rule 8 "no unused attacks" already put them before the claim — test D.)
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = GetHand($seat)[intval(substr(SWUBotActionMz($a), strlen('myHand-')))] ?? null;
        if ($o === null || $cap - intval(SWUComputePlayCost($seat, $o)) < $n) continue;   // it spends the draw's resource: the claim may go first
        $s = SWUBotScoreAction($ctx, $a, intval($i));
        if ($s > 0.0 && !SWUBotProposalOn('mandoclaimnocap')) $v = min($v, $s - 0.01);   // lever 'mandoclaimnocap': the claim is not capped under the plays
    }
    return $v;
}

// ── Feature 'attackreserve' (p42) — deployed HMW_010 Tarfful ───────────────────────────────────────────────────────────────
// "On Attack: You may pay 1 resource. If you do, create a Beast token." Owner, 2026-10-08: "on 7R, you can play Anakin plus Tarfful swing
// + 1R for beast token … evaluate whether spending resources or saving 1 for a Beast token On Attack is worth more." While a READY unit
// of mine (it can still attack this phase) reads that, playing $obj so that fewer than N resources are left costs the token — priced as
// the play of a unit its size (develop x (power + HP) / 2 + unitPlay; a 3/3 Beast = a 3-drop). The largest such token is charged once.
// A token UNIT's CardID by its printed title ("Beast" -> HMW_T03), or null. Token IDs are SET_T##.
function _SWUBotTokenUnitByTitle(string $title): ?string {
    static $cache = [];
    if (array_key_exists($title, $cache)) return $cache[$title];
    foreach (($GLOBALS['titleData'] ?? []) as $id => $t) {
        if (preg_match('/_T\d+$/', strval($id)) && strcasecmp(strval($t), $title) === 0 && strval(CardType($id)) === 'Token Unit') return $cache[$title] = strval($id);
    }
    return $cache[$title] = null;
}

function _SWUBotAttackReserveCost(int $seat, $obj, array $W): float {
    $cap = SWUTotalPaymentCapacity($seat);
    $left = $cap - intval(SWUComputePlayCost($seat, $obj));
    $cost = 0.0;
    foreach (SWUBotUnits($seat) as $v) {
        if (!$v['ready']) continue;
        $text = strval(CardText($v['cardID'])) . "\n" . ($v['isLeader'] ? strval(CardDeployText($v['cardID'])) : '');
        if (!preg_match('/On Attack: You may pay (\d+) resources?\. If you do, create an? ([\w\- ]+?) token/i', $text, $m)) continue;
        $n = intval($m[1]);
        if ($cap < $n || $left >= $n) continue;
        $tok = _SWUBotTokenUnitByTitle($m[2]);
        if ($tok === null) continue;
        $cost = max($cost, $W['develop'] * (intval(CardPower($tok)) + intval(CardHp($tok))) / 2 + $W['unitPlay']);
    }
    return $cost;
}

// ── Feature 'villainpitch' (p42) — HMW_010 Tarfful, the 'heropitch' mirror ─────────────────────────────────────────────────
// An off-aspect $aspect card ($seat cannot pay its $aspect icon) and "a card still to be played wants an $aspect card in my discard,
// and there is none yet" — LOF_070 Anakin's two halves ("If there is a Heroism / Villainy card in your discard pile …").
function _SWUBotOffAspectOf(int $seat, string $cid, string $aspect): bool {
    $need = count(array_filter(array_map('trim', explode(',', strval(CardAspect($cid) ?? ''))), fn($a) => $a === $aspect));
    if ($need === 0 || !function_exists('PlayerAspects')) return false;
    return $need > count(array_filter((array)PlayerAspects($seat), fn($a) => $a === $aspect));
}
function _SWUBotWantsAspectInDiscard(int $seat, string $aspect): bool {
    foreach (GetDiscard($seat) as $o) {
        if ($o !== null && empty($o->removed) && str_contains(strval(CardAspect(strval($o->CardID ?? '')) ?? ''), $aspect)) return false;
    }
    foreach ([GetHand($seat), GetDeck($seat)] as $zone) {
        foreach ($zone as $o) {
            if ($o !== null && empty($o->removed) && preg_match("/$aspect card in your discard pile/i", strval(CardText(strval($o->CardID ?? ''))))) return true;
        }
    }
    return false;
}

// Feature 'anakinpitch' (p42), owner 2026-10-08 (Mando Colossus): "Reckless Sacrifice + the villainy card is also a great way to instantly
// activate Anakin … before the 6R turn … especially against aggro". A "Discard a unit from your hand" play (ASH_163) puts the event AND the
// pitched unit in my discard: each Anakin half ("If there is a Heroism / Villainy card in your discard pile …") it switches on is worth
// W['removal'] (Anakin's -3/-3, as 'heropitch' / 'villainpitch' price the pitched card). The pitch is the unit that switches on most —
// never an Anakin (with only Anakin to pitch, the play throws away its own payoff: 0).
const SWU_BOT_ANAKIN_ASPECTS = ['Heroism', 'Villainy'];
function _SWUBotAnakinPitchValue(int $seat, int $i, string $cid, array $W): float {
    if (!preg_match('/Discard a unit from your hand/i', strval(CardText($cid)))) return 0.0;
    $want = array_values(array_filter(SWU_BOT_ANAKIN_ASPECTS, fn($a) => _SWUBotWantsAspectInDiscard($seat, $a)));
    if (empty($want)) return 0.0;
    $aspects = fn(string $c) => array_map('trim', explode(',', strval(CardAspect($c) ?? '')));
    $own = $aspects($cid);
    $best = 0;
    foreach (GetHand($seat) as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed) || !str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) continue;
        if (_SWUBotIsAnakinPayoff(strval($o->CardID ?? ''))) continue;   // never pitch the Anakin it is for
        $best = max($best, count(array_intersect($want, array_merge($own, $aspects(strval($o->CardID ?? ''))))));
    }
    return SWUBotLeverNum('ANAKIN_PITCH_MULT', 1.0) * $W['removal'] * $best;
}
function _SWUBotIsAnakinPayoff(string $cid): bool {
    return (bool)preg_match('/If there is a (Heroism|Villainy) card in your discard pile/i', strval(CardText($cid)));
}
// An Anakin in hand to cash the pitch: then a pitch that hits nothing else is still the play (the dud gate does not hold it).
function _SWUBotAnakinInHand(int $seat): bool {
    foreach (GetHand($seat) as $o) {
        if ($o !== null && empty($o->removed) && _SWUBotIsAnakinPayoff(strval($o->CardID ?? ''))) return true;
    }
    return false;
}

// Tarfful's "Action [N resources, Exhaust, discard a card from your hand]: Create a Beast token" is the PITCH LINE (owner ruling
// 2026-10-08) and nothing else: an off-aspect Villainy card to pitch, the discard still wanting one, fewer than 6 resources (the flip
// turn) — then it is taken with the last N resources, after the unit plays ("a 2-drop and then [the pitch] on the 4R or 5R turn"; in
// round 1 the 2 resources ARE the last). NULL when $action is not such an Action.
const SWU_BOT_PITCH_LINE = 3.0;
const SWU_BOT_PITCH_BEFORE_RESOURCES = 6;
function _SWUBotPitchActionValue(array $ctx, int $seat, array $action): ?float {
    if (!preg_match('/Action \[(\d+) resources?, Exhaust, discard a card from your hand\]: Create a/i', _SWUBotActionSourceText($seat, $action), $m)) return null;
    $pitch = false;
    foreach (GetHand($seat) as $o) if ($o !== null && empty($o->removed) && _SWUBotOffAspectOf($seat, strval($o->CardID ?? ''), 'Villainy')) $pitch = true;
    if (!$pitch || !_SWUBotWantsAspectInDiscard($seat, 'Villainy')) return -0.5;
    if (count(array_filter(GetResources($seat), fn($o) => $o !== null && empty($o->removed))) >= SWUBotLeverNum('PITCH_BEFORE_RESOURCES', SWU_BOT_PITCH_BEFORE_RESOURCES)) return -0.5;
    if (SWUTotalPaymentCapacity($seat) > intval($m[1])) {
        foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
            if (SWUBotActionKind($a) === 'play' && SWUBotScoreAction($ctx, $a, intval($i)) > 0.0) return 0.02;   // the plays first
        }
    }
    return SWUBotLeverNum('PITCH_LINE', SWU_BOT_PITCH_LINE);
}

// What a hand card is worth to KEEP — what discarding it gives up (feature 'discardpick'): its play value, less the aspect
// penalty THIS seat pays for it (an off-aspect card is a worse play than its printed cost says), never below 0.
// Feature 'heropitch' (owner 2026-10-03): an off-aspect Heroism card is "worth more in the discard to activate Anakin fully"
// — below every ordinary card, by Anakin's extra -3/-3 (priced as a removal).
function SWUBotHandKeepValue(int $seat, $obj, array $W): float {
    $cid = strval($obj->CardID ?? '');
    if (SWUBotFeatureOn('heropitch') && _SWUBotOffAspectHeroism($seat, $cid) && _SWUBotWantsHeroismInDiscard($seat)) return -$W['removal'];
    if (SWUBotFeatureOn('villainpitch') && _SWUBotOffAspectOf($seat, $cid, 'Villainy') && _SWUBotWantsAspectInDiscard($seat, 'Villainy')) return -$W['removal'];
    $penalty = function_exists('SWUComputePlayCost') ? max(0, intval(SWUComputePlayCost($seat, $obj)) - intval(CardCost($cid))) : 0;
    // Fix 2 (owner, 2026-10-06): an UNPRICED card adds no curve term, so next to a priced under-curve card it looks better
    // than it is. Keep/discard comparisons use curve value only when every card in hand is priced.
    $allPriced = true;
    foreach (GetHand($seat) as $h) if ($h !== null && empty($h->removed) && SWUBotCurveValue($seat, strval($h->CardID ?? '')) === null) { $allPriced = false; break; }
    // 'nopenalty' when priced: this line already charges the aspect penalty itself (− develop × penalty); a 'paid' curve
    // surplus would charge it a second time.
    return max(0.0, _SWUBotPlayValue($seat, $cid, $W, 'hand', $allPriced ? 'nopenalty' : 'nocurve') - $W['develop'] * $penalty);
}

// The card a discard cost would take: the lowest keep value in hand. NULL for an empty hand.
function _SWUBotCheapestDiscard(int $seat, array $W): ?float {
    $low = null;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $k = SWUBotHandKeepValue($seat, $o, $W);
        $low = $low === null ? $k : min($low, $k);
    }
    return $low;
}

// What N damage to "a unit or base" is worth (feature 'pingvalue'): REMOVAL — the best enemy unit it defeats (a Shield
// absorbs it) — or LETHAL on a base. 1 chip on a base otherwise buys nothing a card is worth: the human pilot aimed 80
// of 82 pings at units. (Lethal "together with this round's attacks" needs no rule of its own: the attacks go first, and
// the ping is then lethal by itself.)
// $chip (feature 'dumpdamage'): base damage short of lethal counts too, at the per-point rate of any base hit — deployed
// Vader's dump is spent late, where the human pilot aimed 18 of 38 uses at a base.
const SWU_BOT_PING_LETHAL = 100.0;
function _SWUBotPingEffect(int $seat, int $n, array $W, bool $chip = false): float {
    $best = $chip ? $W['base'] * $n : 0.0;
    foreach (SWUBotOpponents($seat) as $opp) {
        if (SWUBaseRemainingHp($opp) <= $n) return SWU_BOT_PING_LETHAL;
        foreach (SWUBotUnits($opp) as $v) {
            if ($v['shields'] === 0 && $v['remaining'] <= $n) $best = max($best, $W['kill'] * SWUBotUnitValue($v));
        }
    }
    return $best;
}

// A PING Action whose COST discards a card from my hand — "Action [Exhaust, discard a card from your hand]: Deal N damage
// to a unit or base" (LAW_011 Darth Vader's front side): its effect less the card it costs. NULL otherwise (or with an
// empty hand) — the ordinary pricing stands. ⚠ The other discard-cost Actions (SHD_011 Kylo Ren's +2/+0, ASH_217 Mayor's
// Majordomo's exhaust, HMW_010 Tarfful's Beast token) are left alone on purpose: their effect has no price here, and
// "a flat W['ability'] less a card" would stop Tarfful ever making a Beast.
function _SWUBotDiscardCostActionValue(int $seat, array $action, array $W): ?float {
    if (!preg_match('/Action \[[^\]]*discard a card from your hand[^\]]*\]:\s*Deal (\d+) damage to a unit or base/i', _SWUBotActionSourceText($seat, $action), $m)) return null;
    $cost = _SWUBotCheapestDiscard($seat, $W);
    if ($cost === null) return null;
    return _SWUBotPingEffect($seat, intval($m[1]), $W) - $cost;
}

// Feature 'dumpdamage' (p29): "Discard any number of cards from your hand. Deal damage to a unit or base equal to the
// number of cards discarded this way" (LAW_011 Darth Vader, deployed On Attack). A candidate set of k cards is worth what
// k damage does, less what those cards are worth to keep. Owner 2026-10-03: "hold cards until you can use Aggressive
// Negotiations for a double buffed attack" — SEC_179 gives +1/+0 per card in hand, so while it is in hand every card is
// ALSO a point of that future swing: its keep rises by the same per-point rate.
function _SWUBotDumpScore(int $seat, string $c, array $W): ?float {
    $parts = $c === '-' || $c === '' ? [] : explode('&', $c);
    $hand = GetHand($seat); $cost = 0.0; $chosen = [];
    foreach ($parts as $p) {
        if (!preg_match('/^myHand-(\d+)$/', $p, $m) || !isset($hand[intval($m[1])])) return null;
        $chosen[intval($m[1])] = true;
        $cost += SWUBotHandKeepValue($seat, $hand[intval($m[1])], $W);
    }
    if (empty($parts)) return 0.0;
    foreach ($hand as $i => $o) {
        // A hand-size swing still to come (not one of the cards being dumped): each dumped card is a point of it lost.
        if ($o !== null && empty($o->removed) && !isset($chosen[$i]) && preg_match('/\+1\/\+0 for each card in your hand/i', strval(CardText(strval($o->CardID ?? ''))))) {
            $cost += $W['base'] * count($parts);
            break;
        }
    }
    return _SWUBotPingEffect($seat, count($parts), $W, true) - $cost;
}

// Feature 'anvader' (p29): a HAND-SIZE ATTACK EVENT — SEC_179 Aggressive Negotiations, "Attack with a unit. For this attack,
// it gets +1/+0 for each card in your hand." Its play value was develop × cost, the attack it makes unpriced, so the bot cast
// it 0.04 times a game and never with Vader. It is worth the attack it makes: $att's best allowed target with $h more power,
// and $h again when the attacker's own On Attack cashes the hand as damage (LAW_011 deployed — the owner's "double buffed
// attack", 2026-10-03). The engine fixes the +$h when the attack begins, so the On Attack discards do not shrink it.
function _SWUBotIsHandSizeAttackEvent(string $cid): bool {
    return (bool)preg_match('/Attack with a unit\. For this attack, it gets \+1\/\+0 for each card in your hand/i', strval(CardText($cid)));
}
function _SWUBotHandSwingValue(array $ctx, array $att, int $h, array $W): float {
    $buffed = $att; $buffed['attackPower'] += $h;   // attack-only, like Raid — the unit itself is no bigger
    $best = null;
    foreach (SWUBotAllowedTargets($ctx, $buffed) as [$k, $u]) {
        $tv = SWUBotTargetValue($buffed, $k === 'base' ? null : $u, $W);
        $best = $best === null ? $tv : max($best, $tv);
    }
    $text = $att['isLeader'] ? strval(CardDeployText($att['cardID']) ?? '') : strval(CardText($att['cardID']));
    $cashes = (bool)preg_match('/On Attack: Discard any number of cards from your hand\. Deal damage/i', $text);
    return ($best ?? 0.0) + ($cashes ? $W['base'] * $h : 0.0);
}
// The best ready attacker for a hand-size attack event, or NULL when no unit can attack. $h = the cards left in hand
// once the event itself has gone. The event's attack IS that unit's attack, so it carries the 'attackFirst' guide when
// the guide favours that unit — without it the plain attack (3.6 + the flat 6.00 guide) always beat the 9-power swing.
function _SWUBotHandSwingBest(array $ctx, int $seat, int $h, array $W): ?float {
    global $playerID;
    $guides = $ctx['_guides'] ?? _SWUBotGuides($ctx);
    $best = null;
    foreach (SWUBotUnits($seat) as $v) {
        if (!$v['ready']) continue;
        $saved = $playerID; $playerID = $seat; $mz = SWUFindMzByUID($v['uid']); $playerID = $saved;
        $s = _SWUBotHandSwingValue($ctx, $v, $h, $W)
           + ($mz !== null && in_array($mz . '!FSM!', $guides['attackFirst'], true) ? $W['attackFirst'] : 0.0);
        $best = $best === null ? $s : max($best, $s);
    }
    return $best;
}

// Feature 'fodderfirst' (p30): a PAIRED-DEFEAT card ("choose a friendly unit and an enemy non-leader unit. If you do,
// defeat those units" — ASH_052 Chimaera) cast with no friendly unit in play can only give ITSELF. True when that is the
// board, an enemy non-leader unit is there to take, and a cheaper UNIT in hand is castable now with the paired card still
// affordable after it — the cheap unit is then played first, to be the price (owner's Hemlock games, 2026-10-03).
function _SWUBotPairedDefeatWantsFodder(int $seat, int $i, string $cid): bool {
    if (!preg_match('/choose a friendly unit and an enemy non-leader unit\. If you do, defeat those units/i', strval(CardText($cid)))) return false;
    // WIDENED 2026-10-04 (Ninin vs Maul Blue, R16): not only an empty board — the hand unit must be a CHEAPER price than the
    // cheapest one already in play (an empty board leaves only the paired card itself, so anything is cheaper).
    $price = PHP_FLOAT_MAX;
    foreach (SWUBotUnits($seat) as $v) $price = min($price, SWUBotSacrificeCost($v));
    $target = false;
    foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits($o) as $v) if (!$v['isLeader']) { $target = true; break 2; }
    if (!$target) return false;
    $hand = GetHand($seat); $self = $hand[$i] ?? null;
    if ($self === null) return false;
    $cap = SWUTotalPaymentCapacity($seat); $own = intval(SWUComputePlayCost($seat, $self));
    foreach ($hand as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed) || !str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) continue;
        if (floatval(CardCost(strval($o->CardID ?? ''))) >= $price) continue;   // not a cheaper price than what is in play
        $c = intval(SWUComputePlayCost($seat, $o));
        if ($c <= $cap && $cap - $c >= $own) return true;
    }
    return false;
}

// Feature 'observerfirst' (p31): a unit in hand that pays off ENEMY DEFEATS — "When an enemy unit is defeated: Deal N damage to
// its controller's base" (LOF_130 HK-47) — goes down BEFORE this round's kill, when none is in play yet and both fit this round
// (Ninin vs Maul Blue, R16: HK-47 first, then Chimaera's kill pinged the base). True for the KILL play $i that should wait.
function _SWUBotKillWaitsForObserver(int $seat, int $i, string $cid): bool {
    $re = "/When an enemy unit is defeated: Deal \\d+ damage to its controller's base/i";
    if (SWUBotPlayEnemyKills($seat, $cid) <= 0) return false;
    foreach (SWUBotUnits($seat) as $v) if (preg_match($re, strval(CardText($v['cardID'])))) return false;   // already observing
    $hand = GetHand($seat); $self = $hand[$i] ?? null;
    if ($self === null) return false;
    $cap = SWUTotalPaymentCapacity($seat); $own = intval(SWUComputePlayCost($seat, $self));
    foreach ($hand as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed)) continue;
        $pid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($pid)), 'Unit') || !preg_match($re, strval(CardText($pid)))) continue;
        $c = intval(SWUComputePlayCost($seat, $o));
        if ($c <= $cap && $cap - $c >= $own) return true;
    }
    return false;
}

// Feature 'wipeaware' (p30): a "Defeat all units. For each enemy unit defeated this way, deal N damage to its controller's base"
// wipe (LAW_044 Single Reactor Ignition) that an opponent has SHOWN — it is in their discard — and can cast again (they
// control at least its printed cost in resources). Returns N, or 0. Public information only: no peeking at their hand.
function _SWUBotShownPerUnitWipe(int $seat): int {
    $per = 0;
    foreach (SWUBotOpponents($seat) as $o) {
        foreach (GetDiscard($o) as $c) {
            if ($c === null || !empty($c->removed)) continue;
            $cid = strval($c->CardID ?? '');
            if (preg_match("/Defeat all units\\. For each enemy unit defeated this way, deal (\\d+) damage to its controller's base/i", strval(CardText($cid)), $m)
                && SWUResourceCount($o) >= intval(CardCost($cid))) $per = max($per, intval($m[1]));
        }
    }
    return $per;
}
// Does this play leave me with enough units — tokens included, so it is read off the lookahead — for that wipe to finish my
// base? Only a play that ADDS units counts: one that does not was never the overextension.
function _SWUBotPlayEntersWipeRange(int $seat, array $action, int $per): bool {
    $hp = SWUBaseRemainingHp($seat); $now = count(SWUBotUnits($seat));
    if (!function_exists('SWUBotLookaheadBest')) return false;
    $o = SWUBotActionKind($action) === 'play' ? _SWUBotHandObject($seat, $action) : null;
    $text = $o !== null ? strval(CardText(strval($o->CardID ?? ''))) : '';
    // "Defeat any number of non-leader units with a total of N or less remaining HP. Create a … token for each unit defeated"
    // (ASH_053 Pre Vizsla): the lookahead's branch cap explores only the first few of its many answers — which include my own
    // units — and undercounted the tokens. Count directly: the most ENEMY non-leader units that fit N (smallest first), + itself.
    if (preg_match('/Defeat any number of non-leader units with a total of (\d+) or less remaining HP\. Create an? .+? token for each unit defeated/i', $text, $m)) {
        $hps = [];
        foreach (SWUBotOpponents($seat) as $opp) foreach (SWUBotUnits($opp) as $v) if (!$v['isLeader']) $hps[] = max(0, $v['remaining']);
        sort($hps); $left = intval($m[1]); $tokens = 0;
        foreach ($hps as $h) { if ($h > $left) break; $left -= $h; $tokens++; }
        $after = $now + 1 + $tokens;
    } else {
        // Otherwise the worst case for me the lookahead finds: the line that leaves the most units.
        $line = SWUBotLookaheadBest($seat, $action, fn() => ['n' => count(SWUBotUnits($seat))], fn(array $r) => $r['n'], SWU_BOT_LOOKAHEAD_DEPTH, 12);
        $after = $line === null ? $now : intval($line['n']);
    }
    return $after > $now && $after * $per >= $hp;
}

// Feature 'etbsetup' (p31): a When Played gated on "If you control a unit that costs N or less" (HMW_154 Dooku's Solar Sailer)
// with the condition OFF, while a unit in hand of printed cost N or less is castable now and the gated card stays affordable
// after it — the cheap unit goes first and turns the When Played on (Ninin vs Lando: IDT, then the Sailer took Chimaera).
function _SWUBotEtbWantsSetup(int $seat, int $i, string $cid): bool {
    if (!preg_match('/When Played: If you control a unit that costs (\d+) or less,/i', strval(CardText($cid)), $m)) return false;
    $n = intval($m[1]);
    foreach (SWUBotUnits($seat) as $v) if (intval(CardCost($v['cardID'])) <= $n) return false;   // already on (tokens cost 0)
    $hand = GetHand($seat); $self = $hand[$i] ?? null;
    if ($self === null) return false;
    $cap = SWUTotalPaymentCapacity($seat); $own = intval(SWUComputePlayCost($seat, $self));
    foreach ($hand as $j => $o) {
        if ($j === $i || $o === null || !empty($o->removed)) continue;
        $pid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($pid)), 'Unit') || intval(CardCost($pid)) > $n) continue;
        $c = intval(SWUComputePlayCost($seat, $o));
        if ($c <= $cap && $cap - $c >= $own) return true;
    }
    return false;
}

// Feature 'lockpiece' (p35): $cid in my hand is blanked by an enemy Galen (SWU_GALEN — it would come down with no abilities),
// and a ready friendly unit can kill that Galen by attack this round (kill-survive or trade). Then the kill goes first and the
// bomb follows whole — Ninin vs Luke (ASH) Data Vault: Galen named Chimaera; Galen died, then Chimaera.
function _SWUBotBlankedBombWaits(int $seat, string $cid): bool {
    $title = str_replace(' ', '_', strval(CardTitle($cid)));
    if ($title === '' || !function_exists('_SWUGalenNames') || !_SWUGalenNames($seat, $title)) return false;
    foreach (SWUBotEnemyUnits($seat) as $g) {
        if (!in_array($title, _SWUBotLockTitles($g), true) || strval($g['cardID']) !== 'SEC_046') continue;
        foreach (SWUBotUnits($seat) as $att) {
            if (!$att['ready']) continue;
            $reach = false;
            foreach (SWUBotAttackTargets($seat, $att)['units'] as $u) if ($u['uid'] === $g['uid']) { $reach = true; break; }
            if ($reach && in_array(SWUBotCombatOutcome($att, $g), ['kill-survive', 'trade'], true)) return true;
        }
    }
    return false;
}

// The arena a unit card enters ('Ground' | 'Space'), from its printed arena. Feature 'wipeinit' (p36).
function _SWUBotPlayArena(string $cid): string {
    return stripos(strval(CardArena($cid)), 'Space') !== false ? 'Space' : 'Ground';
}

// ── Feature 'disclosereserve' (p36) — keep the hand that powers my disclose defence ──────────────────────────────
// Reprint_Cad (Hemlock Red) vs Ninin (Ahsoka Yellow), 2026-10-04: SEC_038 Condemn on Ahsoka ("On Attack: the defending player
// may disclose Vigilance Villainy. If they do, this unit gets -6/-0"); after three blanked attacks Reprint_Cad PLAYED Marrok, the
// last card that could disclose. Owner: hold it. My upgrade on an ENEMY unit => [the aspects my hand must show].
const SWU_BOT_DISCLOSE_DEFENCES = ['SEC_038' => ['Vigilance', 'Villainy']];

// Each enemy unit carrying my disclose defence: ['req' => aspects, 'blanked' => attack power the -6/-0 removes].
function _SWUBotDiscloseReserves(int $seat): array {
    $out = [];
    foreach (SWUBotEnemyUnits($seat) as $u) {
        if (!isset($u['obj'])) continue;
        foreach (GetUpgradesOnUnit($u['obj']) as $s) {
            $req = SWU_BOT_DISCLOSE_DEFENCES[strval($s->CardID ?? '')] ?? null;
            if ($req !== null && intval($s->Controller ?? ($s->Owner ?? 0)) === $seat)
                $out[] = ['req' => $req, 'blanked' => min(6, max(0, intval($u['rawAttackPower'] ?? $u['attackPower'])))];
        }
    }
    return $out;
}

// Can my hand, leaving out index $skip, still disclose $req? The engine's own icon reader and cover test (PlayerCanDisclose).
function _SWUBotDiscloseCovers(int $seat, ?int $skip, array $req): bool {
    if (!function_exists('SWUCardAspectIcons') || !function_exists('_SWUAspectsCover')) return false;
    $have = [];
    foreach (GetHand($seat) as $j => $o) {
        if ($j === $skip || $o === null || !empty($o->removed)) continue;
        $have = array_merge($have, SWUCardAspectIcons(strval($o->CardID ?? '')));
    }
    return _SWUAspectsCover($have, $req);
}

// The attack power I stop blanking if hand card $i leaves (played or resourced): summed over the defences it breaks.
function _SWUBotDiscloseReserveCost(int $seat, int $i): int {
    $pts = 0;
    foreach (_SWUBotDiscloseReserves($seat) as $r) {
        if (_SWUBotDiscloseCovers($seat, null, $r['req']) && !_SWUBotDiscloseCovers($seat, $i, $r['req'])) $pts += $r['blanked'];
    }
    return $pts;
}
function _SWUBotBreaksDiscloseReserve(int $seat, int $i): bool {
    foreach (_SWUBotDiscloseReserves($seat) as $r) {
        if (_SWUBotDiscloseCovers($seat, null, $r['req']) && !_SWUBotDiscloseCovers($seat, $i, $r['req'])) return true;
    }
    return false;
}

// Feature 'weaknessaction' (p31): "Action [N resource(s), Exhaust]: Give a Weakness token to a unit…" (HMW_003 Doctor Hemlock's
// front side) is worth its BEST target on the Weakness scale (_SWUBotWeaknessScore: a kill, else a soften) less the resources
// it costs. NULL for any other Action. "…without a Weakness token on it" excludes units that already carry one.
function _SWUBotWeaknessActionValue(int $seat, array $action, array $W): ?float {
    if (!preg_match('/Action \[([^\]]*)\]: Give a Weakness token to a unit( without a Weakness token on it)?\./i', _SWUBotActionSourceText($seat, $action), $m)) return null;
    $res = preg_match('/(\d+) resources?/i', $m[1], $r) ? intval($r[1]) : 0;
    $best = null;
    foreach (SWUBotOpponents($seat) as $o) {
        foreach (SWUBotUnits($o) as $v) {
            if (!empty($m[2]) && isset($v['obj']) && function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($v['obj'], 'HMW_T02')) continue;
            $s = _SWUBotWeaknessScore($seat, $v, true, 0, 'GIVE_WEAKNESS', 1, $W);
            $best = $best === null ? $s : max($best, $s);
        }
    }
    return $best === null ? -0.5 : $best - $W['develop'] * $res;
}

// A leader / unit / base Action, judged by applying it with the lookahead (BotLookahead.php):
//   - it would change nothing and raise no decision (an Epic Action with nothing to play) → never use it;
//   - it costs friendly units, either directly or through a "defeat a friendly unit" choice → the flat
//     ability value, less whatever sacrifice goes beyond 1 point of value;
//   - otherwise → the flat ability value.
// If the lookahead cannot apply the action, the flat value stands (the pre-2026-09-13 behaviour).
// ── Feature 'tuck' (p42) — LOF_016 Qui-Gon Jinn ─────────────────────────────────────────────────────────────────────────
// "Return a friendly non-leader unit to its owner's hand. Play a non-Villainy unit that costs less than the returned unit from your
// hand for free." (front Action; deployed: after a completed attack, "You may …"). The owner's loops: Yoda -> Kelleran Beq,
// Kelleran -> Depa Billaba, Depa -> Queen Amidala, Amidala -> Captain Typho — play Yoda, tuck him, play him again for his heal.
// What one pair is worth, in RESOURCES (the scale SWUBotUnitValue uses, ~1 per cost):
//   + the returned unit's When Played re-bought (it is cast again later) — half its cost, when it has one (Ambush / Shielded too);
//   + the free unit's cost (it reaches the board without paying);
//   - the returned unit's body as it stands now (scaled by its HP left — the card comes back whole; upgrades make it dearer) and its
//     unused attack this round.
// Before this the bot priced the Action at the flat W['ability'] and, at the return prompt, read "Return … to hand" as HOSTILE: it
// returned its CHEAPEST unit (24 of 120 traced uses were the 1-cost Luke, which nothing can undercut) and always declined the
// deployed "you may". And the Action's lookahead charged the returned body as a SACRIFICE (Depa -> Amidala scored -2.7).
const SWU_BOT_TUCK_REBUY_SHARE = 0.5;
const SWU_BOT_FORCE_HEAL_RE = '/When Played: You may use the Force\. If you do, heal/i';   // LOF_101 Yoda ('yodaloop')

// Feature 'yodaloop' (p42, owner 2026-10-08: "Yoda's heal first"): a hand unit castable NOW whose When Played spends the Force on a heal —
// the tuck, which spends the Force too, waits for it.
function _SWUBotCastableForceHealInHand(int $seat): bool {
    // Owner: "no need to heal when there's nothing on base" — and "don't waste on a heal of 4 or less": only a heal that lands in FULL.
    $dmg = intval((GetBase($seat)[0] ?? null)->Damage ?? 0);
    $cap = SWUTotalPaymentCapacity($seat);
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed) || !preg_match(SWU_BOT_FORCE_HEAL_RE, strval(CardText(strval($o->CardID ?? ''))))) continue;
        if (!preg_match('/use the Force\. If you do, heal (\d+)/i', strval(CardText(strval($o->CardID ?? ''))), $hm) || $dmg < intval($hm[1])) continue;
        if (intval(SWUComputePlayCost($seat, $o)) <= $cap) return true;
    }
    return false;
}

// Feature 'forceregen' (p42) — FORCE SEQUENCING (leader audit 2026-10-08, the Qui-Gon deck). The Force is one token; a refill while I hold
// it does nothing. LOF_023 Jedi Temple refills it "When a friendly Force unit attacks". So: holding the Force, a spender worth using now goes
// BEFORE a refilling attack (attacking first wasted the refill, and the tuck then ended the round with no Force); without it, a "When
// Played: You may use the Force" play (LOF_101 Yoda's heal 5) goes AFTER one (cast first, the heal was lost).
const SWU_BOT_FORCE_WP_RE = '/When Played: You may use the Force/i';
function _SWUBotAttackRefillsForce(int $seat, array $att): bool {
    return str_contains(strval(CardTrait($att['cardID']) ?? ''), 'Force')
        && (bool)preg_match('/When a friendly Force unit attacks: The Force is with you/i', strval(CardText(strval((GetBase($seat)[0] ?? null)->CardID ?? ''))));
}
// The best score among the refilling attacks on offer, or NULL when none is worth making.
function _SWUBotForceRefillAttackScore(array $ctx, int $seat): ?float {
    $best = null;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        if (SWUBotActionKind($a) !== 'attack' || ($v = SWUBotViewForMz($seat, SWUBotActionMz($a))) === null || !_SWUBotAttackRefillsForce($seat, $v)) continue;
        $s = SWUBotScoreAction($ctx, $a, intval($i));
        if ($s > 0.0) $best = $best === null ? $s : max($best, $s);
    }
    return $best;
}
// The best score among the Force spenders on offer — an Action whose cost uses the Force (LOF_016's tuck), a "When Played: You may use
// the Force" play — or NULL when none is worth using.
function _SWUBotForceSpenderScore(array $ctx): ?float {
    $seat = intval($ctx['seat']); $best = null;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        $kind = SWUBotActionKind($a);
        if (in_array($kind, ['leader-ability', 'unit-action'], true)) $spends = (bool)preg_match('/Action \[[^\]]*use the Force/i', _SWUBotActionSourceText($seat, $a));
        elseif ($kind === 'play') $spends = preg_match('/^myHand-(\d+)/', SWUBotActionMz($a), $hm) && preg_match(SWU_BOT_FORCE_WP_RE, strval(CardText(strval((GetHand($seat)[intval($hm[1])] ?? null)->CardID ?? ''))));
        else continue;
        if (!$spends) continue;
        $s = SWUBotScoreAction($ctx, $a, intval($i));
        if ($s > 0.0) $best = $best === null ? $s : max($best, $s);
    }
    return $best;
}

// Feature 'yodaloop': a TUCK deck (its leader returns a unit and plays a cheaper one free — LOF_016) and a chain piece of it: a unit that
// is not of the excluded aspect, costs 5+, and has something to re-buy (When Played / Ambush / Shielded) — Kelleran, Depa, Amidala, Yoda.
// Owner: "only don't resource" them.
function _SWUBotTuckChainPiece(int $seat, string $cid): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || ($ex = _SWUBotTuckExcluded(strval($l->CardID ?? ''))) === null) continue;
        return str_contains(strval(CardType($cid)), 'Unit') && intval(CardCost($cid)) >= 5
            && ($ex === '' || !str_contains(strval(CardAspect($cid) ?? ''), $ex))
            && (bool)preg_match('/When Played|\bAmbush\b|\bShielded\b/i', strval(CardText($cid)));
    }
    return false;
}

// The aspect a tuck card's free play excludes ('Villainy'), '' for none, or NULL when $cid does not tuck (front or deployed text).
function _SWUBotTuckExcluded(string $cid): ?string {
    $re = '/return a friendly non-leader unit to its owner\'s hand\.\s*Play an? (?:non-(\w+) )?unit that costs less than the returned unit from your hand for free/i';
    foreach ([strval(CardText($cid)), strval(CardDeployText($cid))] as $t) if (preg_match($re, $t, $m)) return strval($m[1] ?? '');
    return null;
}

// The gain of returning the unit $v: the best cheaper unit in hand it lets me play free. NULL when there is none.
function _SWUBotTuckGainFor(int $seat, array $v, string $excluded, bool $canWait = false): ?float {
    if ($v['isLeader']) return null;
    // Feature 'yodaloop' (owner 2026-10-08: "if possible to swing to base and get the 5 damage in, then take that line"): where the tuck can
    // WAIT (the front Action), a ready unit that can hit the enemy base swings first and is returned after.
    if ($canWait && SWUBotFeatureOn('yodaloop') && $v['ready'] && (SWUBotAttackTargets($seat, $v)['base'] ?? false)) return null;
    $free = null;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit') || intval(CardCost($cid)) >= intval($v['cost'])) continue;
        if ($excluded !== '' && str_contains(strval(CardAspect($cid) ?? ''), $excluded)) continue;
        $free = max($free ?? 0, intval(CardCost($cid)));
    }
    if ($free === null) return null;
    $rebuy = preg_match('/When Played|\bAmbush\b|\bShielded\b/i', strval(CardText($v['cardID']))) ? SWUBotLeverNum('TUCK_REBUY_SHARE', SWU_BOT_TUCK_REBUY_SHARE) * intval($v['cost']) : 0.0;
    // Feature 'yodaloop' (p42, owner 2026-10-08: "tuck Yoda every round once he's used"): a Force-heal When Played (LOF_101 Yoda) is re-bought
    // at its whole cost, so it is the first unit the tuck returns.
    if (SWUBotFeatureOn('yodaloop') && preg_match(SWU_BOT_FORCE_HEAL_RE, strval(CardText($v['cardID'])))) $rebuy = 1.0 * intval($v['cost']);
    // SWUBotUnitValue ignores damage; the returned card comes back whole, so the body that leaves is the HP it has left.
    $body = SWUBotUnitValue($v) * max(0, intval($v['remaining'])) / max(1, intval($v['hp']));
    return $rebuy + $free - $body - SWUBotUnusedSacPremium($v);
}

// The best pair on my board, or NULL when no unit can be returned for a cheaper free play.
function _SWUBotTuckBest(int $seat, string $excluded, bool $canWait = false): ?float {
    $best = null;
    foreach (SWUBotUnits($seat) as $v) {
        $g = _SWUBotTuckGainFor($seat, $v, $excluded, $canWait);
        if ($g !== null) $best = max($best ?? $g, $g);
    }
    return $best;
}

// ── Feature 'doubleplay' (p42) — HMW_008 General Grievous ───────────────────────────────────────────────────────────────
// "Action [Exhaust]: Play 2 units from your hand (one at a time, paying their costs)." Two plays for one action. Each hand unit is
// scored EXACTLY as the free-play stack scores playing it (SWUBotScoreAction — every hold included: a duplicate unique, fodder
// first, no gift…), so the Action and its pick can never disagree with what the bot would do with the card directly.
const SWU_BOT_DOUBLEPLAY_RE = '/Action \[[^\]]*\]: Play 2 units from your hand \(one at a time, paying their costs\)/i';

function _SWUBotHandPlayScore(array $ctx, int $i): float {
    $c = $ctx; $c['kind'] = 'free-play';
    return SWUBotScoreAction($c, ['playerID' => intval($ctx['seat']), 'mode' => 10002, 'cardID' => "myHand-$i!FSM!"], 0);
}

// The best two units payable together, each worth what playing it directly scores. With only one worth playing the Action is that
// play and nothing more, so it sits just under the direct play of the same card. -0.5 when no unit is worth playing.
function _SWUBotDoublePlayValue(array $ctx, int $seat): float {
    $cap = SWUTotalPaymentCapacity($seat);
    $plays = [];
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed) || !str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) continue;
        $cost = intval(SWUComputePlayCost($seat, $o));
        if ($cost > $cap) continue;
        $s = _SWUBotHandPlayScore($ctx, intval($i));
        if ($s > 0.0) $plays[] = [$s, $cost];
    }
    if (empty($plays)) return -0.5;
    $best = max(array_column($plays, 0)) - 0.01;
    foreach ($plays as $a => [$s1, $c1]) foreach ($plays as $b => [$s2, $c2]) if ($a < $b && $c1 + $c2 <= $cap) $best = max($best, $s1 + $s2);
    return $best;
}

// Feature 'healwaste' (p42): the Restore points $att's attack would waste (Restore N past my base's damage), when an enemy unit is ready to
// hit my base before it — 0 otherwise.
function _SWUBotRestoreWasted(int $seat, array $att): int {
    if (!isset($att['obj']) || !function_exists('HasKeyword_Restore') || !HasKeyword_Restore($att['obj'])) return 0;
    $n = intval(GetKeyword_Restore_Value($att['obj']));
    $wasted = max(0, $n - intval((GetBase($seat)[0] ?? null)->Damage ?? 0));
    if ($wasted <= 0) return 0;
    foreach (SWUBotOpponents($seat) as $opp) {
        foreach (SWUBotUnits($opp) as $u) if ($u['ready'] && (SWUBotAttackTargets($opp, $u)['base'] ?? false)) return $wasted;
    }
    return 0;
}

// ── Feature 'unitedge' (p42) — HMW_008 General Grievous, deployed ─────────────────────────────────────────────────────────
// "While you control more units than an opponent, this unit gets +3/+0." When $att reads that, has not got the edge yet, and a unit
// play on offer would give it (one more unit beats the opponent with the fewest), the best such play's score — the attack waits for
// it. NULL otherwise. Audit 2026-10-08: 7 attacks at an even count with that unit in hand.
function _SWUBotUnitEdgePlay(array $ctx, array $att): ?float {
    $seat = intval($ctx['seat']);
    $text = strval(CardText($att['cardID'])) . "\n" . ($att['isLeader'] ? strval(CardDeployText($att['cardID'])) : '');
    if (!preg_match('/While you control more units than an opponent, this unit gets \+\d+\/\+0/i', $text)) return null;
    $mine = count(SWUBotUnits($seat));
    $fewest = min(array_map(fn($o) => count(SWUBotUnits($o)), SWUBotOpponents($seat) ?: [0]));
    if ($mine > $fewest || $mine + 1 <= $fewest) return null;
    $best = null;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        if (SWUBotActionKind($a) !== 'play') continue;
        $o = GetHand($seat)[intval(substr(SWUBotActionMz($a), strlen('myHand-')))] ?? null;
        if ($o === null || !str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) continue;
        $s = SWUBotScoreAction($ctx, $a, intval($i));
        if ($s > 0.0) $best = max($best ?? $s, $s);
    }
    return $best;
}

// ── Features 'playdefeat' + 'deployreplay' (p42) — HMW_016 Maul, Old Master ──────────────────────────────────────────────
// Front: "Play a unit from your hand. It costs 1 resource less. Then, defeat it." The body dies at once, so the play is worth the
// unit's EFFECTS — its When Played and When Defeated (both still resolve) — never its body; a unit with neither is worth nothing.
// Deployed: "When Deployed: You may play a unit that was defeated this phase from your discard pile. It costs 5 resources less." —
// Maul's line: the Action first (the effects now), then the deploy brings the body back cheap. Audit 2026-10-08: 25 of 128 uses
// played an effect-less unit (Mae x12), the pick took the biggest body, and 21 of 31 deploys replayed nothing.
const SWU_BOT_PLAYDEFEAT_RE = '/Play a unit from your hand\. It costs (\d+) resources? less\. Then, defeat it/i';
const SWU_BOT_DEPLOYREPLAY_RE = '/When Deployed: You may play a unit that was defeated this phase from your discard pile\. It costs (\d+) resources? less/i';
const SWU_BOT_BODY_TAGS = ['ambush', 'shielded', 'grit', 'sentinel', 'saboteur', 'raid', 'restore', 'overwhelm', 'hidden'];

// What $cid's When Played / When Defeated effects are worth (its tags, keywords excluded). NULL when it has neither.
function _SWUBotEffectOnlyValue(int $seat, string $cid, array $W): ?float {
    if (!preg_match('/When Played|When Defeated/i', strval(CardText($cid)))) return null;
    $v = 0.0;
    foreach (SWUBotCardTags($cid) as $t) {
        if (!in_array($t, SWU_BOT_BODY_TAGS, true)) $v += floatval($W[$t] ?? 0.0) * ($t === 'draw' ? SWUBotDrawMultiplier($seat) : 1.0);
        if ($t === 'search-top-deck' && SWUBotFeatureOn('searchvalue')) $v += _SWUBotSearchValue($seat, $cid, $W);
    }
    return $v;
}

// The leader object behind "myLeader-N!…" (any verb), or null.
function _SWUBotLeaderOfAction(int $seat, array $action): ?object {
    if (!preg_match('/^myLeader-(\d+)!/', strval($action['cardID'] ?? ''), $m)) return null;
    $l = GetLeader($seat)[intval($m[1])] ?? null;
    return is_object($l) && empty($l->removed) ? $l : null;
}

// Hand unit $i through a "play it, then defeat it" leader ($discount less): its effects, plus — when that leader's own deploy (offered
// right now) replays a unit defeated this phase and the resources left cover it — the replay, which is that unit's ordinary play value.
// NULL when the unit is not affordable or has no effect.
function _SWUBotPlayDefeatValue(array $ctx, object $leader, int $i, int $discount, array $W): ?float {
    $seat = intval($ctx['seat']);
    $o = GetHand($seat)[$i] ?? null;
    if ($o === null || !empty($o->removed) || !str_contains(strval(CardType(strval($o->CardID ?? ''))), 'Unit')) return null;
    $cid = strval($o->CardID);
    $cap = SWUTotalPaymentCapacity($seat);
    $pay = max(0, intval(SWUComputePlayCost($seat, $o)) - $discount);
    if ($pay > $cap) return null;
    $eff = _SWUBotEffectOnlyValue($seat, $cid, $W);
    if ($eff === null) return null;
    if (SWUBotFeatureOn('deployreplay') && preg_match(SWU_BOT_DEPLOYREPLAY_RE, strval(CardDeployText(strval($leader->CardID))), $rm)) {
        $deployOffered = false;
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            if (str_ends_with(strval($a['cardID'] ?? ''), '!CustomInput!DeployLeader:Unit') && _SWUBotLeaderOfAction($seat, $a) === $leader) $deployOffered = true;
        }
        if ($deployOffered && $cap - $pay >= max(0, intval(CardCost($cid)) - intval($rm[1]))) $eff += _SWUBotPlayValue($seat, $cid, $W, 'discard');
    }
    return $eff;
}

// The deploy of a leader whose When Deployed replays a unit defeated this phase: + the best such replay the resources cover. With
// nothing to replay yet, it waits behind that leader's own "play, then defeat" Action when that Action is worth using (it fills the
// discard for the replay). Any other deploy: $dv unchanged.
function _SWUBotDeployReplayScore(array $ctx, array $action, float $dv, array $W): float {
    $seat = intval($ctx['seat']);
    $leader = _SWUBotLeaderOfAction($seat, $action);
    if ($leader === null || !preg_match(SWU_BOT_DEPLOYREPLAY_RE, strval(CardDeployText(strval($leader->CardID))), $rm)) return $dv;
    $cap = SWUTotalPaymentCapacity($seat);
    $best = 0.0;
    foreach (GetDiscard($seat) as $o) {
        $cid = strval($o->CardID ?? '');
        if ($o === null || !empty($o->removed) || !str_contains(strval(CardType($cid)), 'Unit')) continue;
        if (GlobalEffectCount($seat, 'SWU_DEFEATED_CARD_' . $cid) <= 0 || max(0, intval(CardCost($cid)) - intval($rm[1])) > $cap) continue;
        $best = max($best, _SWUBotPlayValue($seat, $cid, $W, 'discard'));
    }
    if ($best > 0.0) return $dv + $best;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        if (SWUBotActionKind($a) !== 'leader-ability' || _SWUBotLeaderOfAction($seat, $a) !== $leader) continue;
        $av = _SWUBotAbilityValue($ctx, $a, $W);
        if ($av > 0.0) return min($dv, $av - 0.01);
    }
    return $dv;
}

// ── Feature 'searchvalue' (p42) — "Search the top N cards of your deck for …" ────────────────────────────────────────────────────────
// The 'search-top-deck' tag had no weight: every search was worth 0 (LOF_057 Owen Lars read as effect-less). OWNER RULINGS 2026-10-08:
//   to hand ("draw it/them", "into your hand") — a draw per card it can find; into play ("play it/them") — a draw per card played PLUS the
//   resources saved (W['develop'] each); anything else (discard it, put it on top, resource it) — half a draw. A draw = W['draw'] × the
//   deck-out multiplier. "up to N" / "N" / "a" counts as written; "any number of" counts as 2.
function _SWUBotSearchValue(int $seat, string $cid, array $W): float {
    $text = str_replace("\n", ' ', strval(CardText($cid)));
    if (!preg_match('/Search the top \d+ cards of your deck for (.*?)(?:\(|$)/i', $text, $m)) return 0.0;
    $clause = strtolower($m[1]);
    $words = ['a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];
    $n = 1;
    if (preg_match('/^(?:up to )?(\d+|a|an|one|two|three|four)\b/', $clause, $c)) $n = ctype_digit($c[1]) ? intval($c[1]) : ($words[$c[1]] ?? 1);
    elseif (str_starts_with($clause, 'any number of')) $n = 2;
    $draw = floatval($W['draw'] ?? 0.0) * SWUBotDrawMultiplier($seat) * SWUBotLeverNum('SEARCH_MULT', 1.0);
    if (preg_match('/\bdraw (it|them)\b|into your hand/', $clause)) return $n * $draw;
    if (preg_match('/\bplay (it|them|each of them)\b/', $clause)) {
        $saved = 0;
        if (preg_match('/costs? (\d+) resources? less/', $clause, $s)) $saved = intval($s[1]);
        elseif (preg_match('/combined cost (\d+) or less/', $clause, $s)) $saved = intval($s[1]);
        elseif (str_contains($clause, 'for free') && preg_match('/costs? (\d+) or less/', $clause, $s)) $saved = $n * intval($s[1]);
        return $n * $draw + floatval($W['develop'] ?? 0.0) * $saved;
    }
    return SWUBotLeverNum('SEARCH_OTHER_SHARE', 0.5) * $draw;
}

// ── Feature 'twoping' (p42) — LOF_009 Darth Maul, Sith Revealed ─────────────────────────────────────────────────────────────
// "Deal 1 damage to a unit and 1 damage to a different unit" — BOTH mandatory. The best first target plus the best DIFFERENT second
// one, each priced as the targeting pick prices it (_SWUBotTargetScore: an enemy hit is a gain, my own unit a loss). With one enemy
// unit the second ping lands on mine. Returns [value, kills] (kills: a ping defeats an enemy unit), or null for any other text.
function _SWUBotTwoPingValue(int $seat, string $text, array $W): ?array {
    if (!preg_match('/Deal (\d+) damage to a unit and (\d+) damage to a different unit/i', $text, $m)) return null;
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    // The whole table: 'team*' (my units, and a teammate's in Team Suns — 'my' outside one) + 'their*'.
    $mzs = [];
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $mzs[] = strval($mz); }
    }
    $playerID = $saved;
    $score = function (string $mz, int $n) use ($seat, $W) { return _SWUBotTargetScore($seat, $mz, true, $n, '', $W) ?? 0.0; };
    $best = null; $kills = false;
    foreach ($mzs as $a) foreach ($mzs as $b) {
        if ($a === $b) continue;
        $v = $score($a, intval($m[1])) + $score($b, intval($m[2]));
        if ($best === null || $v > $best) $best = $v;
    }
    foreach ($mzs as $mz) {
        if (!str_starts_with($mz, 'their')) continue;
        $u = SWUBotViewForMz($seat, $mz);
        if ($u !== null && intval($u['shields']) === 0 && intval($u['remaining']) <= max(intval($m[1]), intval($m[2]))) $kills = true;
    }
    return $best === null ? null : [$best, $kills];
}

// ── Feature 'freekill' (p42) — LAW_004 Aurra Sing ──────────────────────────────────────────────────────────────────────────
// "Action [Exhaust]: Defeat a non-leader unit with N or less remaining HP." Free, and it never costs an attack — every attack is still
// there afterwards, and the one that would have spent itself on that unit can hit something else. So with a target it is the best kill's
// value, and it goes before any attack on offer. NULL for any other text; -0.5 with nothing to kill. Audit 2026-10-08: flat 0.40, used in
// 60-68% of the rounds with a target, 39 attacks into a unit it could have taken for free.
function _SWUBotFreeKillValue(array $ctx, int $seat, string $text, array $W): ?float {
    if (!preg_match('/Action \[Exhaust\]: Defeat a non-leader unit with (\d+) or less remaining HP/i', $text, $m)) return null;
    global $playerID;
    $saved = $playerID; $playerID = $seat;
    $best = null;
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (GetZone($z) as $i => $o) {
            if ($o === null || !empty($o->removed)) continue;
            $v = SWUBotViewForMz($seat, "$z-$i");
            if ($v === null || $v['isLeader'] || intval($v['remaining']) > intval($m[1])) continue;
            $s = SWUBotUnitValue($v) * (1.0 + $W['kill']) + 1.0;   // the targeting pick's price of a kill
            $best = max($best ?? $s, $s);
        }
    }
    $playerID = $saved;
    if ($best === null) return -0.5;
    foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
        if (SWUBotActionKind($a) === 'attack') $best = max($best, SWUBotScoreAction($ctx, $a, intval($i)) + 0.01);
    }
    return $best;
}

function _SWUBotAbilityValue(array $ctx, array $action, array $W): float {
    $seat = intval($ctx['seat']);
    // Feature 'landoflip' (p42): a Credit is already banked and the flip is available now — the Action is not needed (owner: "the ability
    // may not be necessary if you already had one banked from the 5R turn. this way you can play a 9-drop").
    if (SWUBotFeatureOn('landoflip') && ($fl = _SWUBotActionLeader($seat, $action)) !== null && _SWUBotCreditFlipLeader($fl)
        && !SWUBotProposalOn('landoflipaction') && _SWUBotUsableCredits($seat) >= SWUBotLeverNum('LANDO_FLIP_CREDITS', 1.0) && _SWUBotLeaderCanDeployNow($seat, $fl)) return -0.5;
    if (SWUBotFeatureOn('freekill') && SWUBotActionKind($action) === 'leader-ability'
        && ($fk = _SWUBotFreeKillValue($ctx, $seat, _SWUBotActionSourceText($seat, $action), $W)) !== null) return $fk;
    // Feature 'twoping' (p42): Maul's two mandatory pings, priced as a pair; no gain and it waits. A ping that kills is a kill for the
    // Force: without one, the Force stays for another card that needs it (the 'force' gate's rule, which read kills off "-N/-N" only).
    if (SWUBotFeatureOn('twoping') && ($tp = _SWUBotTwoPingValue($seat, _SWUBotActionSourceText($seat, $action), $W)) !== null) {
        [$v, $kills] = $tp;
        if ($v <= 0.0) return -0.5;
        if (!$kills && preg_match('/use the Force/i', _SWUBotActionSourceText($seat, $action)) && _SWUBotHasOtherForceUse($seat)) return min($v, 0.02);
        return $W['ability'] + $v;
    }
    // Feature 'playdefeat' (p42): the best unit's effects (+ the deploy replay it sets up). None worth it: the Action waits.
    if (SWUBotFeatureOn('playdefeat') && ($l = _SWUBotActionLeader($seat, $action)) !== null && preg_match(SWU_BOT_PLAYDEFEAT_RE, strval(CardText(strval($l->CardID ?? ''))), $pm)) {
        $best = null;
        foreach (GetHand($seat) as $i => $o) {
            $v = _SWUBotPlayDefeatValue($ctx, $l, intval($i), intval($pm[1]), $W);
            if ($v !== null) $best = max($best ?? $v, $v);
        }
        return ($best === null || $best <= 0.0) ? -0.5 : $best;
    }
    if (SWUBotFeatureOn('doubleplay') && preg_match(SWU_BOT_DOUBLEPLAY_RE, _SWUBotActionSourceText($seat, $action))) return _SWUBotDoublePlayValue($ctx, $seat);
    if (SWUBotFeatureOn('villainpitch') && ($pv = _SWUBotPitchActionValue($ctx, $seat, $action)) !== null) return $pv;
    // Feature 'tuck' (p42): priced by its best pair; no pair, or a losing one, and the Action waits. Ahead of the lookahead, which
    // charges the returned body as a sacrifice.
    if (SWUBotFeatureOn('tuck') && ($l = _SWUBotActionLeader($seat, $action)) !== null && ($ex = _SWUBotTuckExcluded(strval($l->CardID ?? ''))) !== null) {
        if (SWUBotFeatureOn('yodaloop') && !SWUBotProposalOn('tuckfirst') && preg_match('/use the Force/i', _SWUBotActionSourceText($seat, $action)) && _SWUBotCastableForceHealInHand($seat)) return -0.5;
        $g = _SWUBotTuckBest($seat, $ex, true);   // the front Action can wait for a swing
        return ($g === null || $g <= 0.0) ? -0.5 : $W['ability'] + $W['develop'] * $g;
    }
    // PROPOSAL 'landomill' (default OFF): once Lando has flipped and come back, the Action is a spare-resource play —
    // or a skip, or the win condition when their deck is nearly out. Pre-flip it is left exactly as it was.
    if (SWUBotActionKind($action) === 'leader-ability' && ($l = _SWUBotLandoAbilityScore($ctx, $action, $W)) !== null) return $l;
    // PROPOSAL 'piettcheat' (default OFF). Owner, 2026-09-22: Piett "should cheat out capital ships to be able to trade
    // and stall against Vader". The lookahead stops at the ship prompt, so the discounted play scored as a bare
    // ability (1.5) — no more than playing the same ship at full price. Value it as the best ship it can play, plus
    // the resource it saves.
    if (SWUBotProposalOn('piettcheat') && SWUBotActionKind($action) === 'leader-ability') {
        $txt = _SWUBotActionSourceText($seat, $action);
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
    // Feature 'shipdiscount' (p42): Piett's "Play a Capital Ship unit from your hand. It costs 1 resource less" — the ship, plus the
    // card the saved resource pays for. (Maul's "…Then, defeat it" is 'playdefeat', above.)
    if (SWUBotFeatureOn('shipdiscount') && SWUBotActionKind($action) === 'leader-ability'
        && preg_match('/Play an? ([A-Z][\w ]*?) unit from your hand\. It costs (\d+) resources? less\.(?!\s*Then, defeat it)/', _SWUBotActionSourceText($seat, $action), $sm)
        && ($sv = _SWUBotDiscountPlayActionValue($ctx, $seat, $sm[1], intval($sm[2]))) !== null) return $sv;
    // Feature 'pingvalue' (p29): the lookahead stops at the discard prompt, so a discard-cost Action was a flat
    // W['ability'] with its cost unpriced — the bot pinged a base for 1 with its Chimaera.
    if (SWUBotFeatureOn('pingvalue') && ($pv = _SWUBotDiscardCostActionValue($seat, $action, $W)) !== null) return $pv;
    // Feature 'weaknessaction' (p31): the same blind spot for a Weakness Action — flat W['ability'] whether or not it kills.
    if (SWUBotFeatureOn('weaknessaction') && ($wv = _SWUBotWeaknessActionValue($seat, $action, $W)) !== null) return $wv;
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
    // …and the hostile mirror: it resolved on its own and all it did was HURT my units. Owner report 2026-10-07:
    // Tarkintown's Epic Action ("Deal 3 damage to a damaged non-leader unit") spent on the bot's own unit — the only
    // damaged one on the board, so the lone target skipped the MZCHOOSE prompt the all-mine refusal below reads, and
    // the Action kept the flat W['ability'] whether the unit survived or died. Refused only when that harm (lost HP,
    // a popped Shield, a defeat) is the WHOLE change: no friendly unit gained, nothing else in the signature moved
    // (their units, bases, hands, resources, Credits, decks, their discard — my discard is where my dead unit goes).
    // A printed "friendly" target is a designed cost whose gain may not show in the signature (the Pershing
    // carve-out below), so it is left to the pricing that follows.
    if ($d === null && SWUBotFeatureOn('buffs') && !preg_match('/\bfriendly\b/i', _SWUBotActionSourceText($seat, $action))) {
        $s1 = $after['sig'];
        $harmed = false; $helped = false;
        foreach ($before['mine'] as $uid => $row) {
            $now = $s1['mine'][$uid] ?? null;   // [cardID, power, remaining, ready, shields, upgrades]
            if ($now === null || $now[2] < $row[2] || $now[4] < $row[4]) $harmed = true;
            if ($now !== null && ($now[1] > $row[1] || $now[2] > $row[2] || $now[4] > $row[4] || $now[5] > $row[5])) $helped = true;
        }
        if (count(array_diff_key($s1['mine'], $before['mine'])) > 0) $helped = true;
        $restSame = true;
        foreach (['theirs', 'bases', 'hands', 'resources', 'credits', 'decks'] as $k) if ($s1[$k] != $before[$k]) $restSame = false;
        if (array_slice($s1['discards'], 1) != array_slice($before['discards'], 1)) $restSame = false;
        if ($harmed && !$helped && $restSame) return -0.5;
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
            // Part 24 'waiverhold': unlocking NOTHING is a LOSS, not a tie. $enabled is exactly 0 then, which tied PASS
            // (0) and the waiver went by enumeration order — and its "play a card" prompt is MANDATORY, so it then
            // forced a play the bot itself scored below PASS (owner report 2026-10-03, game 1438045: with the
            // initiative gone, Coaxium Mine's waiver was spent on an ON-aspect Director Krennic, paid with the banked
            // Credit 'bigcredit' had just held). Below PASS and below taking the initiative (0.05).
            if (SWUBotFeatureOn('waiverhold') && $enabled <= 0.0) return SWU_BOT_WAIVER_UNLOCKS_NOTHING;
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
    // Feature 'forcebuff' (p42): only a HOSTILE Force Action is judged so — LOF_008 Obi-Wan's Experience needs no enemy and kills nothing
    // by design (0 uses on 2,065 empty enemy boards).
    $forceHostile = !SWUBotFeatureOn('forcebuff') || preg_match('/\]:.*(-\d+\/-\d+|deal \d+ damage|defeat)/is', $text);
    if (SWUBotFeatureOn('force') && $forceHostile && preg_match('/Action \[[^\]]*use the Force/i', $text)) {
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
    // Feature 'mgbuff' (p28, shipped 2026-10-03; @no-mgbuff) — owner ruling 2026-09-23. The branch below only reads the generic
    // APPLY_PHASE_BUFF continuation, so a card that applies its buff inside its OWN handler is invisible and its
    // Action keeps the flat W['ability'] — the T-6 Shuttle (ASH_109) was offered 16 times in 40 traced games and
    // used 0. Six cards share that shape. Read the amount off the ACTION'S PRINTED TEXT instead, then price it with
    // the same machinery, so mgbuff and buffattack agree on what a buff is worth.
    if ($gain <= 0.0 && $d !== null && $action !== null && SWUBotFeatureOn('mgbuff')
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
    // FEATURE 'curvemull' (p38; spec 2026-10-05-swusim-curve-value-design.md §4.3) — the curve-value keep (BotCurveValue.php).
    // LAST, so an explicit '@try-<mulligan proposal>' above still decides when it is switched on.
    if (SWUBotFeatureOn('curvemull')) return _SWUBotCurveMulligan($seat, $style);
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
