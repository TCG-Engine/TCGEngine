<?php
// LEVER 'handfinisher' (theme 'controlplan', report H4). Ninin's front Weakness was usually the FIRST half of a two-card kill: weaken, then
// Nuvo Vindi's When Played token kills and its chain hits a second unit (~14 lines: 1fea R1->R2, 5536 R3, 8996 R10, ae0a R4, 6e90 R22,
// e4f7 R4, 54ae R4->R5); weaken, then Latts Razzi's 3 (e4f7 R1->R2, e026 R1); weaken, then Craving Power's exact hit (dbb7 R5, 8996 R7).
// _SWUBotFinisherHP knew only "Action: Defeat … N or less", Hemlock / Talzin themselves and a pending Ambush. Here the finisher threshold
// against $v also counts the largest hit a card in my hand that I can still cast this round deals to it (arena-checked).
function _SWUBotLvHandFinish(int $seat, array $v): int {
    $cap = function_exists('SWUTotalPaymentCapacity') ? intval(SWUTotalPaymentCapacity($seat)) : 0;
    $myPower = 0;
    foreach (SWUBotUnits($seat) as $u) $myPower = max($myPower, intval($u['power']));
    $best = 0;
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (intval(CardCost($cid)) > $cap) continue;
        $t = strval(CardText($cid));
        if (preg_match('/When Played:[^.]*?\bgive a Weakness token to a unit/i', $t)) $best = max($best, 1);
        if (preg_match('/deals damage equal to (?:her|his|its) power to an enemy (ground|space) unit/i', $t, $m)
            && strcasecmp($m[1], strval($v['arena'])) === 0) {
            $best = max($best, intval(CardPower($cid)) + (preg_match('/Experience token/i', $t) ? 1 : 0));
        }
        if (preg_match('/When Played: Deal damage to an enemy unit equal to attached unit\'s power/i', $t)) $best = max($best, $myPower + max(0, intval(CardPower($cid))));
        if (preg_match('/When Played: Deal (\d+) damage to an? (?:enemy )?(?:(ground|space) )?unit/i', $t, $m)
            && (empty($m[2]) || strcasecmp($m[2], strval($v['arena'])) === 0)) $best = max($best, intval($m[1]));
    }
    return $best;
}

SWUBotLeverRegister('controlplan', 'handfinisher', [
    'finisherhp' => function (int $n, int $seat, array $v): ?int {
        if (!empty($v['isLeader'])) return null;   // as 'setup': leaders are not set up by the finisher threshold
        if (!_SWUBotLvIsViewer($seat)) return null;   // only MY hand is known — never read an opponent's hidden hand (final review I5)
        $h = _SWUBotLvHandFinish($seat, $v);
        return $h > $n ? $h : null;
    },
]);
