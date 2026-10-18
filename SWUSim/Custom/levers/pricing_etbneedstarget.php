<?php
// LEVER 'etbneedstarget' (theme 'pricing', G2-20 etb-damage-needs-a-target). Trace piett_lukejtl R5/R6: Fulminatrix ("When Played/On Attack:
// You may deal 4 damage to a ground unit") was chosen over two cheaper bodies with only myGroundArena-0 / PASS on offer — its damage tags
// priced in full against a space-only enemy board.
// play — a UNIT whose When Played deals damage to / defeats a unit, with no legal ENEMY target in the arena it names (non-leader when it says
//        so), loses its damage/removal tag value; half of it when the same clause is also an On Attack (the attacks still come later).
// Skipped while 'condetbvalue' already zeroed the card's tags (an unmet "if you control …" gate).

const SWU_BOT_LV_ETBNEEDSTARGET_TAGS = ['damage-enemy-unit', 'removal', 'damage-enemy-unit-spread'];

// ['arena' => ''|'Ground'|'Space', 'nonLeader' => bool, 'onAttack' => bool] for a When Played unit hit, else null.
function _SWUBotLvEtbneedstargetClause(string $cid): ?array {
    static $memo = [];
    if (array_key_exists($cid, $memo)) return $memo[$cid];
    $out = null;
    if (str_contains(strval(CardType($cid)), 'Unit')
        && preg_match('/When Played(\/On Attack)?:\s*(?:If [^,.]+, )?(?:you may )?(?:deal \d+ damage to|defeat) (?:an? |another )?(?:enemy )?(non-leader )?(ground |space )?unit/i',
                      strval(CardText($cid)), $m)) {
        $out = ['arena' => ucfirst(strtolower(trim($m[3] ?? ''))), 'nonLeader' => trim($m[2] ?? '') !== '', 'onAttack' => ($m[1] ?? '') !== ''];
    }
    return $memo[$cid] = $out;
}

SWUBotLeverRegister('pricing', 'etbneedstarget', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $cl = _SWUBotLvEtbneedstargetClause($cid);
        if ($cl === null) return null;
        if (SWUBotFeatureOn('condetbvalue') && function_exists('_SWUBotCondEtbShortfall') && _SWUBotCondEtbShortfall($seat, $cid) > 0) return null;
        foreach (SWUBotEnemyUnits($seat) as $u) {
            if ($cl['arena'] !== '' && $u['arena'] !== $cl['arena']) continue;
            if ($cl['nonLeader'] && !empty($u['isLeader'])) continue;
            return null;   // a legal enemy target exists
        }
        $off = 0.0;
        $tags = SWUBotCardTags($cid);
        foreach (SWU_BOT_LV_ETBNEEDSTARGET_TAGS as $t) if (in_array($t, $tags, true)) $off += max(0.0, floatval($W[$t] ?? 0.0));
        if ($cl['onAttack']) $off *= 0.5;
        return $off > 0.0 ? $v - $off : null;
    },
]);
