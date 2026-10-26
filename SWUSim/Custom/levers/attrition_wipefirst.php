<?php
// LEVER 'wipefirst' (theme 'attrition'). R7: Ninin held Single Reactor Ignition and Devastator with 8 resources against Queen Amidala, two Spy
// tokens and Naboo Royal Starship, his own Anakin and deployed Hemlock in play, and an opponent down to one card. He wiped on his first action
// (6 units, 4 to their base) and dropped Devastator onto the empty board the round after. The replay bot cast Devastator (7.47 vs the wipe's
// 2.21), so its own wipe would have taken its bomb next round; 'widewipe' (p56) stays off there because my two units are worth more than their
// four. A castable unbounded wipe is GOOD here when it hits 3+ enemy units, at least 2 more than mine (a deployed leader of mine counts half:
// it flips back and keeps its Action), and either the opponent holds at most 1 card (they cannot rebuild, I can) or their units are worth
// at least mine. While it is, the wipe goes first:
//   play / deploy — a unit play or a leader deploy that the wipe would defeat waits (below PASS);
//   attack        — while the opponent has a ready unit, an attack waits below the wipe (wiping first takes their whole round of attacks:
//                   owner 2026-10-04, "claiming and wiping first can stop the opponent … getting another 5+ damage in").
// Only while the wipe itself is worth casting (_SWUBotPlayValue > 0), so a hold never leaves the bot passing.
const SWU_BOT_LV_WIPEFIRST_OPP_HAND = 1;

// [wipe CardID, its play value] for the good, castable-now wipe in $seat's hand, or null.
function _SWUBotLvWipeFirst(int $seat, array $W): ?array {
    return SWUBotMemo("lvwipefirst|$seat", function () use ($seat, $W) {
        if (!function_exists('_SWUBotLvWideWipeShape') || (function_exists('SeatCountForGame') && SeatCountForGame() > 2)) return null;
        $cap = intval(SWUTotalPaymentCapacity($seat));
        $oppHand = 0;
        foreach (SWUBotOpponents($seat) as $o) $oppHand = max($oppHand, _SWUBotLvHandCount(intval($o)));
        foreach (GetHand($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $cid = strval($o->CardID ?? '');
            if (!in_array('wipe', SWUBotCardTags($cid), true) || ($sh = _SWUBotLvWideWipeShape($cid)) === null) continue;
            if (intval(SWUComputePlayCost($seat, $o)) > $cap) continue;
            $hit = fn(array $u) => ($sh['arena'] === 'all' || $u['arena'] === $sh['arena']) && ($sh['leaders'] || empty($u['isLeader'])) && !SWUBotDefeatFizzles($u);
            $theirs = array_values(array_filter(SWUBotEnemyUnits($seat), $hit));
            $mine = array_values(array_filter(SWUBotUnits($seat), $hit));
            $mineN = 0.0;
            foreach ($mine as $u) $mineN += empty($u['isLeader']) ? 1.0 : 0.5;
            if (count($theirs) < 3 || count($theirs) - $mineN < 2.0) continue;
            $value = fn(array $us) => array_sum(array_map('SWUBotUnitValue', $us));
            if ($oppHand > SWU_BOT_LV_WIPEFIRST_OPP_HAND && $value($theirs) < $value($mine)) continue;
            $pv = _SWUBotPlayValue($seat, $cid, $W);
            if ($pv > 0.0) return [$cid, $pv];
        }
        return null;
    });
}

SWUBotLeverRegister('attrition', 'wipefirst', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!str_contains(strval(CardType($cid)), 'Unit') || ($wf = _SWUBotLvWipeFirst($seat, $W)) === null || $wf[0] === $cid) return null;
        return min($v, -0.4);
    },
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if (_SWUBotLvWipeFirst($seat, $W) === null) return null;
        return min($dv, -0.4);
    },
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (($wf = _SWUBotLvWipeFirst($seat, $W)) === null) return null;
        $readyFoe = false;
        foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready'])) $readyFoe = true;
        return $readyFoe ? min($s, $wf[1] - 0.01) : null;
    },
]);
