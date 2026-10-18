<?php
// LEVER 'repeatattackhost' (theme 'pricing', G1-36 condemn-host-repeat-attacker). SEC_038 Condemn ("While attached unit is attacking, it gains:
// On Attack: The defending player may disclose Vigilance Villainy. If they do, this unit gets -6/-0 for this attack") was played in 30% of Aurra
// Red and 22% of Kylo games, priced at its cost floor ('debuff' weighs ~0). With 'discloseanswer' (p50) the defence works.
// play — + W['base'] x min(6, attack power) per attack the best enemy host makes before it dies (1 round when one of my units can kill it,
//        else 2), x1.5 for a leader unit or a Saboteur / Overwhelm attacker (walls do not stop them) — only while the hand left after the
//        play still shows the disclose (otherwise the blank never happens and the price stands).
// decision (ATTACH_UPGRADE of it) — the host is that same score: an enemy unit not already carrying one; my own units far below.

function _SWUBotLvRepeatattackhostReq(string $cid): ?array {
    if (defined('SWU_BOT_DISCLOSE_DEFENCES') && isset(SWU_BOT_DISCLOSE_DEFENCES[$cid])) return SWU_BOT_DISCLOSE_DEFENCES[$cid];
    return null;
}
// The blank's worth on enemy host $e, in base points (before W['base']).
function _SWUBotLvRepeatattackhostPoints(int $seat, array $e): float {
    $p = min(6, max(0, intval($e['rawAttackPower'] ?? $e['attackPower'])));
    if ($p <= 0) return 0.0;
    $rounds = 2;
    foreach (SWUBotUnits($seat) as $u) {
        if ($u['arena'] !== $e['arena']) continue;
        $out = SWUBotCombatOutcome($u, $e);
        if ($out === 'kill-survive' || $out === 'trade') { $rounds = 1; break; }
    }
    $mult = (!empty($e['isLeader']) || !empty($e['saboteur']) || !empty($e['overwhelm'])) ? 1.5 : 1.0;
    return $p * $rounds * $mult;
}
function _SWUBotLvRepeatattackhostHasOne(array $e, string $cid): bool {
    foreach (GetUpgradesOnUnit($e['obj']) as $s) if (strval($s->CardID ?? '') === $cid) return true;
    return false;
}

SWUBotLeverRegister('pricing', 'repeatattackhost', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || ($req = _SWUBotLvRepeatattackhostReq($cid)) === null) return null;
        $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        if (!_SWUBotDiscloseCovers($seat, $i, $req)) return null;
        $best = 0.0;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if (isset($e['obj']) && _SWUBotLvRepeatattackhostHasOne($e, $cid)) continue;
            $best = max($best, _SWUBotLvRepeatattackhostPoints($seat, $e));
        }
        return $best > 0.0 ? $v + floatval($W['base']) * $best : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'ATTACH_UPGRADE') return null;
        $up = strval(explode('|', strval(($ctx['following'] ?? [])[0] ?? ''))[1] ?? '');
        if ($up === '' || _SWUBotLvRepeatattackhostReq($up) === null) return null;
        if ($c === '-' || $c === '' || $c === 'PASS') return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null) return null;
        if (!SWUBotIsEnemyMz($seat, $c)) return -100.0;
        if (isset($v['obj']) && _SWUBotLvRepeatattackhostHasOne($v, $up)) return -50.0;
        return _SWUBotLvRepeatattackhostPoints($seat, $v) + 0.01 * SWUBotUnitValue($v);
    },
]);
