<?php
// LEVER 'bounceetb' (theme 'attrition'). R4: the Ahsoka player's The Axe Forgets returned Nuvo Vindi, whose When Played Weakness was spent —
// Ninin got to cast it again. The replay bot made the same pick (Nuvo 3.0 over HK-47 2.0: the bigger card). A bounce costs the owner the
// replay, and hands them the When Played again. Among enemy non-token bounce targets the pick is now
//   1 + 0.1 x (replay cost + upgrades + power − the When Played value it gives back) + 1 for a Sentinel (the existing Sentinel-first guide),
// the When Played value from the card's curve terms ("when played: …"), 1 resource for a When Played the curve model cannot parse.
// Only when some candidate has a When Played; a prompt offering a token is left alone (a bounced token is gone for good).
function _SWUBotLvBounceGift(string $cid): float {
    $t = strval(CardText($cid));
    if (!preg_match('/When Played/i', $t)) return 0.0;
    $r = function_exists('SWUBotCurveValue') ? SWUBotCurveValue(0, $cid, false) : null;
    if ($r === null) return 1.0;
    $g = 0.0;
    foreach ($r['parts'] as $label => $x) if (stripos(strval($label), 'when played') === 0) $g += floatval($x);
    return $g;
}

SWUBotLeverRegister('attrition', 'bounceetb', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'BOUNCE_UNIT' && !preg_match('/^Return_(?:a|an|another|up_to)_.*unit/i', $tip)) return null;
        if (stripos($tip, 'friendly') !== false || !SWUBotIsEnemyMz($seat, $c)) return null;
        $any = false;
        foreach ($ctx['actions'] ?? [] as $a) {
            $mz = strval($a['cardID'] ?? '');
            if (!SWUBotIsEnemyMz($seat, $mz) || ($u = SWUBotViewForMz($seat, $mz)) === null) continue;
            if (str_contains(strval($u['cardID']), '_T')) return null;
            if (_SWUBotLvBounceGift(strval($u['cardID'])) > 0.0) $any = true;
        }
        if (!$any || ($v = SWUBotViewForMz($seat, $c)) === null || !empty($v['isLeader'])) return null;
        $cid = strval($v['cardID']);
        return 1.0 + 0.1 * (intval(CardCost($cid)) + intval($v['upgrades']) + intval($v['power']) - _SWUBotLvBounceGift($cid)) + (!empty($v['sentinel']) ? 1.0 : 0.0);
    },
]);
