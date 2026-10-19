<?php
// LEVER 'wipecap' (theme 'controlplan', report S1 + OG4 + OA1). Ninin held his board to exactly 3 units before each of hard control's five
// sweepers (54ae) and played NOTHING with 9 up before a known SRI (54ae R8); opponents that committed into a wipe they had SEEN lost 3-5
// units at once (6e90 R9/R14, dbb7 R6, 6faf R7, 99cd R8/R11). Every sweeper in the logs landed at 8+ resources. 'wipeaware' (p30) only
// fires after a wipe is CAST. Here, a UNIT play waits (value - SWU_BOT_LV_WIPECAP) when an opponent is expected to hold a wipe it can cast
// next round (seen in hand: 'known' > 0; or the meta model: 'inHand' >= 0.5) AND either my board is already at the cap, or the wipe is a
// budget wipe ("total of N or less remaining HP") that this unit fits under. Release: never while their clock on me is <= 2 (I need bodies),
// and never for a unit whose When Played / Ambush pays at once.
const SWU_BOT_LV_WIPECAP = 0.6;
const SWU_BOT_LV_WIPECAP_UNITS = 3;

// The threatening wipe: ['budget' => N|null] for the most likely castable-next-round wipe in an opponent's hand, or null.
function _SWUBotLvExpectedWipe(int $seat): ?array {
    if (!function_exists('SWUBotOpponentCardModel')) return null;
    $res = _SWUBotOpponentResources($seat) + 1;
    $best = null; $bestP = 0.0;
    foreach (SWUBotOpponentCardModel($seat) as $row) {
        $cid = strval($row['cid'] ?? '');
        if ($cid === '' || !in_array('wipe', SWUBotCardTags($cid), true) || intval($row['cost'] ?? 99) > $res) continue;
        $p = intval($row['known'] ?? 0) > 0 ? 1.0 : floatval($row['inHand'] ?? 0);
        if ($p < 0.5 || $p <= $bestP) continue;
        $bestP = $p;
        $best = ['budget' => preg_match('/total of (\d+) or less remaining HP/i', strval(CardText($cid)), $m) ? intval($m[1]) : null];
    }
    return $best;
}

SWUBotLeverRegister('controlplan', 'wipecap', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!str_contains(strval(CardType($cid)), 'Unit')) return null;
        if (preg_match('/When Played:|\bAmbush\b/i', strval(CardText($cid)))) return null;              // pays at once
        if (function_exists('_SWUBotLvTheirClock') && _SWUBotLvTheirClock($seat)['clock'] <= 2) return null;
        if (($w = _SWUBotLvExpectedWipe($seat)) === null) return null;
        $atCap = count(SWUBotUnits($seat)) >= SWU_BOT_LV_WIPECAP_UNITS;
        $underBudget = $w['budget'] !== null && intval(CardHp($cid)) <= $w['budget'];
        return ($atCap || $underBudget) ? $v - SWU_BOT_LV_WIPECAP : null;
    },
]);
