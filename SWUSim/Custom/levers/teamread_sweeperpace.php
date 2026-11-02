<?php
// LEVER 'sweeperpace' (theme 'teamread', report D14; team games 2026-10-29). Against hard control that has shown or likely holds a sweeper,
// the team's Grievous added two or three threats a turn while already ahead — all 11 such turns came in losses to the wipe. 'wipecap' (p56)
// charges a unit into an expected wipe only at 3+ units or under a budget wipe, and exempts every When Played unit (B1, Lifetree,
// Grievous's whole pair). Here, while an opponent is expected to cast a wipe next round (the wipecap read with a lower bar: seen in hand,
// or the meta model's inHand >= SWU_BOT_TR_PACE_INHAND) and my board is worth more than theirs, a SECOND unit played this round (one is already
// SWUUnitPlayedThisPhase) waits (min(v, -0.4)): one new threat per turn. When Played units are held too, except a cheap one (cost <=
// SWU_BOT_TR_PACE_WPCOST) whose When Played draws or ramps — that pays for itself before any wipe.
// Released while their clock on me is <= 2 (I need bodies) and when they have just spent a shown wipe (proposal 'rebuildafterwipe').
const SWU_BOT_TR_PACE_WPCOST = 2;
const SWU_BOT_TR_PACE_INHAND = 0.35;   // the meta model's chance a wipe is in their hand (wipecap waits for 0.5)

// An opponent can cast a wipe next round and it is seen in hand, or likely there (model inHand >= SWU_BOT_TR_PACE_INHAND).
function _SWUBotTrPaceWipeLikely(int $seat): bool {
    if (!function_exists('SWUBotOpponentCardModel')) return false;
    $res = _SWUBotOpponentResources($seat) + 1;
    foreach (SWUBotOpponentCardModel($seat) as $row) {
        $cid = strval($row['cid'] ?? '');
        if ($cid === '' || !in_array('wipe', SWUBotCardTags($cid), true) || intval($row['cost'] ?? 99) > $res) continue;
        if (intval($row['known'] ?? 0) > 0 || floatval($row['inHand'] ?? 0) >= SWUBotLeverNum('pace.inhand', SWU_BOT_TR_PACE_INHAND)) return true;
    }
    return false;
}

function _SWUBotTrPacePlayedThisRound(int $seat): int {
    if (!function_exists('SWUUnitPlayedThisPhase')) return 0;
    $n = 0;
    foreach (SWUBotUnits($seat) as $u) if (empty($u['isLeader']) && SWUUnitPlayedThisPhase($u['obj'])) $n++;
    return $n;
}
function _SWUBotTrPaceSelfPays(string $cid): bool {
    if (intval(CardCost($cid)) > intval(SWUBotLeverNum('pace.wpcost', SWU_BOT_TR_PACE_WPCOST))) return false;
    if (!preg_match('/When Played/i', strval(CardText($cid)))) return false;
    return (bool)array_intersect(SWUBotCardTags($cid), ['draw', 'search-top-deck', 'resource-ramp', 'credit-ramp']);
}

SWUBotLeverRegister('teamread', 'sweeperpace', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || !str_contains(strval(CardType($cid)), 'Unit') || _SWUBotTrPaceSelfPays($cid)) return null;
        if (!_SWUBotTrPaceWipeLikely($seat)) return null;
        if (_SWUBotTrPacePlayedThisRound($seat) < 1) return null;
        $value = fn(array $us) => array_sum(array_map('SWUBotUnitValue', $us));
        if ($value(SWUBotUnits($seat)) <= $value(SWUBotEnemyUnits($seat))) return null;   // behind or even: build
        if (function_exists('_SWUBotLvTheirClock') && _SWUBotLvTheirClock($seat)['clock'] <= 2) return null;
        if (function_exists('_SWUBotRebuildAfterWipe') && _SWUBotRebuildAfterWipe($seat)) return null;
        return min($v, -0.4);
    },
]);
