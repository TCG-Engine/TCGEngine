<?php
// LEVER 'spaceleadkill' (theme 'pricing', G2-35 air-superiority-is-live-removal-in-space-decks). JTL_125 Air Superiority ("If you control more
// space units than an opponent, deal 4 damage to a ground unit that opponent controls") — Luke JTL 100% of lists, Poe 80% — was resourced 29 of
// 39 sightings (Luke JTL), 12 (Poe). Nothing reads its condition: tagged damage-enemy-unit only, it is never a key card.
// play — condition unmet: held. Met, with an unshielded enemy ground unit its damage defeats: + W['kill'] (the removal it is).
// keep — condition met: a key answer (+150; +50 for the control wing, the 'keep' feature's split).

const SWU_BOT_LV_SPACELEADKILL_RE = '/If you control more space units than an opponent, deal (\d+) damage to a ground unit that opponent controls/i';

// The opponents I out-number in space.
function _SWUBotLvSpaceleadkillOpps(int $seat): array {
    $mine = count(array_filter(SWUBotUnits($seat), fn($u) => $u['arena'] === 'Space'));
    $out = [];
    foreach (SWUBotOpponents($seat) as $o) {
        if ($mine > count(array_filter(SWUBotUnits($o), fn($u) => $u['arena'] === 'Space'))) $out[] = $o;
    }
    return $out;
}

SWUBotLeverRegister('pricing', 'spaceleadkill', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_LV_SPACELEADKILL_RE, strval(CardText($cid)), $m)) return null;
        $opps = _SWUBotLvSpaceleadkillOpps($seat);
        if (empty($opps)) return min($v, -0.5);
        if ($v <= -0.4) return null;
        foreach ($opps as $o) foreach (SWUBotUnits($o) as $u) {
            if ($u['arena'] === 'Ground' && intval($u['shields']) === 0 && intval($m[1]) >= intval($u['remaining'])) return $v + floatval($W['kill']);
        }
        return null;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!preg_match(SWU_BOT_LV_SPACELEADKILL_RE, strval(CardText($cid))) || empty(_SWUBotLvSpaceleadkillOpps($seat))) return null;
        return $keep + (SWUBotStyleRank(strval($ctx['style'] ?? '')) >= 3 ? 50.0 : 150.0);
    },
]);
