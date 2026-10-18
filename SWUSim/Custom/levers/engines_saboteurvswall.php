<?php
// LEVER 'saboteurvswall' (theme 'engines', ASH lever hunt G3-25). Off by default: '@try-saboteurvswall' / '@try-engines'.
// Saboteur grants vs Sentinel/Shield boards: LOF_215 Ascension Cable ("Attached unit gains Saboteur") was played 11% / resourced 52%
// (Leia), 0/35 (Sabé, non-Krennic games), 1/36 (Obi-Wan); vs Krennic's Sentinel wall Leia played it 2/32 (won 13/54, real 64%). The
// grant is worth what it opens for a READY host this round: its best swing WITH Saboteur (the base past a Sentinel, an enemy unit's
// Shield popped) minus its best swing now, priced with the attack scorer — one attack, no lethal search (unlike the dropped
// 'saboteurgrant'). Added to the play value of the grant (the best host), and the Ascension Cable host pick takes that host.
// LOF_191 BD-1 ("the chosen unit gets +1/+0 and gains Saboteur") is priced the same way.

function _SWUBotLvSaboteurvswallBestNow(int $seat, array $att, array $W): float {
    $t = SWUBotAttackTargets($seat, $att);
    $best = $t['base'] ? SWUBotTargetValue($att, null, $W) : 0.0;
    foreach ($t['units'] as $u) $best = max($best, SWUBotTargetValue($att, $u, $W));
    return $best;
}
function _SWUBotLvSaboteurvswallBestSab(int $seat, array $att, array $W): float {
    $att['saboteur'] = true;
    $best = SWUBotTargetValue($att, null, $W);
    foreach (SWUBotEnemyUnits($seat) as $u) {
        if ($u['arena'] !== $att['arena']) continue;
        $u['shields'] = 0;   // Saboteur defeats the defender's Shields
        $best = max($best, SWUBotTargetValue($att, $u, $W));
    }
    return $best;
}
// The gain of giving host $u Saboteur (+$p/+$h), or 0.
function _SWUBotLvSaboteurvswallGain(int $seat, array $u, int $p, int $h, array $W): float {
    if (empty($u['ready']) || !empty($u['saboteur'])) return 0.0;
    $now = _SWUBotLvSaboteurvswallBestNow($seat, $u, $W);
    $g = $u; $g['power'] += $p; $g['attackPower'] += $p; $g['hp'] += $h; $g['remaining'] += $h;
    return max(0.0, _SWUBotLvSaboteurvswallBestSab($seat, $g, $W) - $now);
}
// [power, hp, needs a non-Vehicle host] for a Saboteur-granting card, or null.
function _SWUBotLvSaboteurvswallGrant(string $cid): ?array {
    $t = strval(CardText($cid));
    if (str_contains(strval(CardType($cid)), 'Upgrade') && preg_match('/Attached unit gains Saboteur/i', $t)) {
        return [intval(CardUpgradePower($cid) ?? 0), intval(CardUpgradeHp($cid) ?? 0), (bool)preg_match('/non-Vehicle/i', $t)];
    }
    if (preg_match('/When Played: Choose another friendly unit\. While this unit is in play, the chosen unit gets \+(\d+)\/\+0 and gains Saboteur/i', $t, $m)) return [intval($m[1]), 0, false];
    return null;
}

SWUBotLeverRegister('engines', 'saboteurvswall', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (($g = _SWUBotLvSaboteurvswallGrant($cid)) === null) return null;
        $best = 0.0;
        foreach (SWUBotUnits($seat) as $u) {
            if ($g[2] && isset($u['obj']) && TraitContains($u['obj'], 'Vehicle')) continue;
            $best = max($best, _SWUBotLvSaboteurvswallGain($seat, $u, $g[0], $g[1], $W));
        }
        return $best > 0.0 ? $v + $best : null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!preg_match('/^Choose_a_unit_to_attach_(.+)_to$/', $tip, $m) || !preg_match('/^(my|their|p\d+|team)\w*Arena-\d+$/', $c)) return null;
        $cid = null;
        foreach (_SWUBotHandCards($seat) as $h) if (str_replace(' ', '_', strval(CardTitle($h))) === $m[1]) { $cid = $h; break; }
        if ($cid === null && $m[1] === 'Ascension_Cable') $cid = 'LOF_215';   // the played card has left the hand by the host pick
        if ($cid === null || ($g = _SWUBotLvSaboteurvswallGrant($cid)) === null) return null;
        if (!str_starts_with($c, 'my')) return -1.0;
        $u = SWUBotViewForMz($seat, $c);
        if ($u === null) return null;
        $gain = _SWUBotLvSaboteurvswallGain($seat, $u, $g[0], $g[1], $W);
        return ($gain > 0.0 ? 10.0 + $gain : 0.5) + 0.01 * intval($u['power']) + (!empty($u['ready']) ? 0.1 : 0.0);
    },
]);
