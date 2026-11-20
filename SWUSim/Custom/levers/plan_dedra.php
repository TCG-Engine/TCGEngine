<?php
// LEVERS 'dedraaction' + 'dedrahome' (theme 'dedraplan', default OFF). SEC_010 Dedra Meero on Colossus is the weakest HMW v3 deck (bot 38% /
// real 77%, n=13; weakest-first, owner 2026-10-28). Her 22 control games: deployed in round 3 every game, then her own Operation Cinder /
// Chimaera / Collateral Damage defeated her unit in 11 — and her front Action was used in 35 of 177 leader-side rounds.
// Owner plan 2026-10-28: "a real Dedra Colossus player may deploy her early against hyper aggro. but if they can control them enough no deploy
// is needed until late game as a finisher (with Aggressive Negotiations). this deck leans into card draw and finishing with Agg Neg."

// "Action [...]: Choose an enemy unit. Its controller may deal N damage to it. If they don't, draw a card." — N, else null.
function _SWUBotDedraPunishN(string $text): ?int {
    return preg_match('/Action \[[^\]]*\]:\s*Choose an enemy unit\. Its controller may deal (\d+) damage to it\. If they don\'t, draw a card/i', $text, $m)
        ? intval($m[1]) : null;
}

// dedraaction — the lookahead stops at the OPPONENT's "deal N damage to your X?" prompt, on their queue: my board signature has not moved
// (a spent resource and an exhausted leader are not in it), so _SWUBotAbilityValue read the Action as a no-op and scored it -0.5 (traced
// 2026-10-28, Dedra vs Thrawn R3, Fennec Shand the target). It is worth the opponent's LESSER answer on my best pick: the damage (a kill
// when N defeats an unshielded unit, else its share of one) or the card I draw. While the 1 resource it costs is the last one a castable
// card in hand needs, it only beats passing — the plays go first and it takes a resource left over.
const SWU_BOT_DEDRA_ACTION_TIGHT = 0.1;
function _SWUBotDedraActionValue(int $seat, int $n, array $W): ?float {
    $draw = floatval($W['draw'] ?? 1.0) * SWUBotDrawMultiplier($seat);
    $best = null;
    foreach (SWUBotEnemyUnits($seat) as $e) {
        $kill = floatval($W['kill']) * SWUBotUnitValue($e);
        $hp = max(1, intval($e['remaining']));
        $dmg = intval($e['shields']) > 0 ? 0.1 : ($hp <= $n ? $kill : 0.5 * $kill * $n / $hp);
        $v = min($dmg, $draw);
        $best = $best === null ? $v : max($best, $v);
    }
    if ($best === null) return null;
    $ready = SWUResourceCount($seat, true);
    foreach (_SWUBotHandCards($seat) as $cid) {
        $cost = _SWUBotSeatCost($seat, $cid);
        if ($cost <= $ready && $cost > $ready - 1) return min($best, SWU_BOT_DEDRA_ACTION_TIGHT);
    }
    return $best;
}

// dedrahome — she stays on the leader side (her Action each round) until one of:
//   hyper  the opponent's leader + base reads hyper aggro in the meta lists — deploy early, as a blocker ("early against hyper aggro");
//   clock  their clock on me is 2 rounds or less — the 2/5 body is needed now;
//   lethal my ready attackers + her attack (Raid 2 while I hold more cards) + a castable Aggressive Negotiations take their base;
//   finish round 7+ and a hand-size attack event is castable this round — she enters ready and carries it ("finisher with Agg Neg");
//   late   round 10+;
// and never into my own sweeper: while a castable-soon card in hand deals her HP or more to each unit, or defeats all units, and the enemy
// has units to sweep, the deploy waits (except 'clock'). Control seats only (racing rank >= 3).
const SWU_BOT_DEDRA_HYPER_BELOW = 0.75;   // a meta mean rank nearer hyper aggro (0) than soft aggro (1)
const SWU_BOT_DEDRA_FINISH_ROUND = 7;
const SWU_BOT_DEDRA_LATE_ROUND = 10;
function _SWUBotDedraSweeperHeld(int $seat, int $hp): bool {
    if (empty(SWUBotEnemyUnits($seat))) return false;
    $cap = SWUResourceCount($seat) + 1;
    foreach (_SWUBotHandCards($seat) as $cid) {
        if (_SWUBotSeatCost($seat, $cid) > $cap) continue;
        $t = strval(CardText($cid));
        if (preg_match('/deal (\d+) damage to each unit\b/i', $t, $m) && intval($m[1]) >= $hp) return true;
        if (preg_match('/^\s*Defeat all units\b/mi', $t)) return true;
    }
    return false;
}
function _SWUBotDedraRelease(int $seat, object $leader): ?string {
    $opp = SWUBotOpponent($seat);
    $rank = SWUBotLeaderBaseStyleRank(strval((GetLeader($opp)[0] ?? null)->CardID ?? ''), strval((GetBase($opp)[0] ?? null)->CardID ?? ''));
    if (function_exists('_SWUBotLvTheirClock') && _SWUBotLvTheirClock($seat)['clock'] <= 2) return 'clock';
    $cid = strval($leader->CardID ?? '');
    if (_SWUBotDedraSweeperHeld($seat, intval(CardHp($cid)))) return null;
    if ($rank !== null && $rank < SWU_BOT_DEDRA_HYPER_BELOW) return 'hyper';
    $hand = _SWUBotHandCards($seat);
    $ready = SWUResourceCount($seat, true);
    $burst = 0;
    foreach ($hand as $h) {
        if (_SWUBotIsHandSizeAttackEvent($h) && _SWUBotSeatCost($seat, $h) <= $ready) $burst = max($burst, count($hand) - 1);
    }
    $oppHand = count(array_filter(GetHand($opp), fn($o) => $o !== null && empty($o->removed)));
    $her = intval(CardPower($cid)) + (count($hand) > $oppHand ? 2 : 0);
    if (SWUBotBasePotential($seat, $opp, true) + $her + $burst >= SWUBaseRemainingHp($opp)) return 'lethal';
    $round = intval(GetTurnNumber());
    if ($burst > 0 && $round >= SWU_BOT_DEDRA_FINISH_ROUND) return 'finish';
    if ($round >= SWU_BOT_DEDRA_LATE_ROUND) return 'late';
    return null;
}

SWUBotLeverRegister('dedraplan', 'dedraaction', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability' || ($n = _SWUBotDedraPunishN(_SWUBotActionSourceText($seat, $action))) === null) return null;
        return _SWUBotDedraActionValue($seat, $n, $W);
    },
]);
SWUBotLeverRegister('dedraplan', 'dedrahome', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotRacingRank(strval($ctx['style'] ?? ''), $seat) < 3 || ($l = _SWUBotLeaderOfAction($seat, $action)) === null
            || _SWUBotDedraPunishN(strval(CardText(strval($l->CardID ?? '')))) === null) return null;
        if (($why = _SWUBotDedraRelease($seat, $l)) !== null) { _SWUBotFired("dedrahome:$why", $seat); return null; }
        return min($dv, -0.4);
    },
]);
