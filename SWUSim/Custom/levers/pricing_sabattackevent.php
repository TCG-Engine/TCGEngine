<?php
// LEVER 'sabattackevent' (theme 'pricing', G4-29 commence-is-an-attack). LAW_202 Commence the Festivities ("Attack with a unit. It gains Saboteur
// for this attack. If you control fewer resources than an opponent, it gets +2/+0 for this attack") — resourced 28/28 (Vane), 11/45 (Chewbacca);
// played Vane 0%, Sloane Splash 0%. It is the cheapest answer to a Sentinel / Shield wall, priced at develop x 1.
// play — valued like 'anvader' values Aggressive Negotiations: the best attack it makes (a ready unit with Saboteur, + the deficit bonus), when
//        that attack beats the same unit's plain attack (otherwise the card adds nothing and the ordinary price stands);
// keep — +50 while an enemy has a Sentinel or a Shielded unit (the wall it answers).
// ⚠ Analyst: may widen the over-performing aggro decks (Luke, Ahsoka Red); listed for correctness.

const SWU_BOT_LV_SABATTACKEVENT_RE = '/Attack with a unit\. It gains Saboteur for this attack\./i';

function _SWUBotLvSabattackeventBonus(int $seat, string $cid): int {
    if (!preg_match('/If you control fewer resources than an opponent, it gets \+(\d+)\/\+0 for this attack/i', strval(CardText($cid)), $m)) return 0;
    $mine = SWUResourceCount($seat);
    foreach (SWUBotOpponents($seat) as $o) if (SWUResourceCount($o) > $mine) return intval($m[1]);
    return 0;
}
function _SWUBotLvSabattackeventSwing(array $ctx, array $att, array $W): ?float {
    $best = null;
    foreach (SWUBotAllowedTargets($ctx, $att) as [$k, $u]) {
        $tv = SWUBotTargetValue($att, $k === 'base' ? null : $u, $W);
        $best = $best === null ? $tv : max($best, $tv);
    }
    return $best;
}

SWUBotLeverRegister('pricing', 'sabattackevent', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_LV_SABATTACKEVENT_RE, strval(CardText($cid)))) return null;
        $bonus = _SWUBotLvSabattackeventBonus($seat, $cid);
        $best = null; $gain = 0.0;
        foreach (SWUBotUnits($seat) as $u) {
            if (!$u['ready']) continue;
            $plain = _SWUBotLvSabattackeventSwing($ctx, $u, $W) ?? 0.0;
            $b = $u; $b['saboteur'] = true; $b['attackPower'] = intval($b['attackPower']) + $bonus;
            $with = _SWUBotLvSabattackeventSwing($ctx, $b, $W);
            if ($with === null) continue;
            $gain = max($gain, $with - $plain);
            $best = $best === null ? $with : max($best, $with);
        }
        if ($best === null || $gain <= 0.05 || $best <= $v) return null;
        return $best;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!preg_match(SWU_BOT_LV_SABATTACKEVENT_RE, strval(CardText($cid)))) return null;
        foreach (SWUBotEnemyUnits($seat) as $u) if (!empty($u['sentinel']) || intval($u['shields']) > 0) return $keep + 50.0;
        return null;
    },
]);
