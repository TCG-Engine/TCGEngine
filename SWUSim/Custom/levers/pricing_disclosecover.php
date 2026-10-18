<?php
// LEVER 'disclosecover' (theme 'pricing', G1-03 part b; the answering half shipped as 'discloseanswer', p50). SEC_182 Charged with Treason was
// cast 73 times and dealt its 5 damage 11 times; Mando Colossus vs Vader drew it in 30 games, cast 0, resourced 9. A card whose effect sits
// behind "You may disclose <icons>. If you do, …" is worth that effect only while the hand LEFT after the play still shows the icons.
// play — not covered: an event whose whole text is the disclose clause is held; a unit whose When Played / On Attack is the disclose clause
//        loses its effect-tag value (the body stays). An event with an ungated first half (Bog Down) is unchanged.
// keep — the last hand card that still covers a held disclose payoff is kept (+100, the 'disclosereserve' magnitude).

const SWU_BOT_LV_DISCLOSECOVER_KEYWORD_TAGS = ['raid', 'sentinel', 'shielded', 'ambush', 'overwhelm', 'saboteur', 'restore', 'grit',
    'hidden', 'plot', 'smuggle', 'piloting', 'bounty', 'unique', 'coordinate', 'exploit'];

// ['kind' => 'event'|'unit', 'req' => icons] for a disclose-gated card, else null.
function _SWUBotLvDisclosecoverGate(string $cid): ?array {
    static $memo = [];
    if (array_key_exists($cid, $memo)) return $memo[$cid];
    $t = strval(CardText($cid));
    $type = strval(CardType($cid));
    $out = null;
    if (preg_match('/(?:^|\n)\s*(When Played(?:\/[^:\n]*)?:\s*|On Attack:\s*)?You may disclose ((?:Vigilance|Command|Aggression|Cunning|Heroism|Villainy)+)\b[^\n]*?If you do,/i', $t, $m, PREG_OFFSET_CAPTURE)) {
        preg_match_all('/Vigilance|Command|Aggression|Cunning|Heroism|Villainy/', $m[2][0], $mm);
        $req = $mm[0];
        $atStart = trim(substr($t, 0, intval($m[0][1]))) === '';
        if (str_contains($type, 'Event') && $atStart && $m[1][0] === '') $out = ['kind' => 'event', 'req' => $req];
        elseif (str_contains($type, 'Unit') && $m[1][0] !== '') $out = ['kind' => 'unit', 'req' => $req];
    }
    return $memo[$cid] = $out;
}

// Does my hand, leaving out the indices in $skip, show $req? The engine's own icon reader and cover test.
function _SWUBotLvDisclosecoverCovers(int $seat, array $skip, array $req): bool {
    if (!function_exists('SWUCardAspectIcons') || !function_exists('_SWUAspectsCover')) return true;
    $have = [];
    foreach (GetHand($seat) as $j => $o) {
        if (in_array($j, $skip, true) || $o === null || !empty($o->removed)) continue;
        $have = array_merge($have, SWUCardAspectIcons(strval($o->CardID ?? '')));
    }
    return _SWUAspectsCover($have, $req);
}

SWUBotLeverRegister('pricing', 'disclosecover', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $g = _SWUBotLvDisclosecoverGate($cid);
        if ($g === null) return null;
        $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        if (_SWUBotLvDisclosecoverCovers($seat, [$i], $g['req'])) return null;
        if ($g['kind'] === 'event') return min($v, -0.5);
        $off = 0.0;
        foreach (SWUBotCardTags($cid) as $t) {
            if (in_array($t, SWU_BOT_LV_DISCLOSECOVER_KEYWORD_TAGS, true)) continue;
            $off += max(0.0, floatval($W[$t] ?? 0.0));
        }
        return $off > 0.0 ? $v - $off : null;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        foreach (GetHand($seat) as $j => $o) {
            if ($j === $i || $o === null || !empty($o->removed)) continue;
            $g = _SWUBotLvDisclosecoverGate(strval($o->CardID ?? ''));
            if ($g === null) continue;
            if (_SWUBotLvDisclosecoverCovers($seat, [$j], $g['req']) && !_SWUBotLvDisclosecoverCovers($seat, [$j, $i], $g['req'])) return $keep + 100.0;
        }
        return null;
    },
]);
