<?php
// LEVER 'losingshift' (theme 'aggroanswer', G4-32 losing-race-defensive-shift). Soft aggro under-performs vs hyper aggro: Greef -13 (vs
// Vader -25), Ahsoka AO -12; Greef vs Vader made 1.3 unit attacks in R1-5 while taking 10.5 base hits. SWUBotRacingRank only shifts TOWARD
// aggro while winning; a losing aggro seat keeps aggro weights and keeps racing.
// target — an aggro-wing attacker (acting rank 0-1) whose side LOSES the action race and whose round clock is at least a round slower than
// the opponent's prices unit targets with the next rank's weights (kill up, loss/chip as midrange-ward): the mirror of SWU_BOT_RACING_SHIFT
// for attack targeting only. The base weight is flat across ranks, so a base hit is unchanged.

function _SWUBotLvLosingshiftLosing(int $seat): bool {
    return SWUBotMemo("lv-losingshift|$seat", function () use ($seat) {
        $opp = SWUBotOpponent($seat);
        $a = _SWUBotRaceAttacks($seat, $opp); $b = _SWUBotRaceAttacks($opp, $seat);
        $hm = SWUBaseRemainingHp($seat); $ht = SWUBaseRemainingHp($opp);
        $won = _SWUBotActionRaceSim($a, $b, $hm, $ht, str_starts_with(strval(GetInitiativeCounter() ?? ''), 'P' . $seat . '_'));
        if ($won !== false) return false;
        $clock = fn(array $att, int $hp) => array_sum($att) <= 0 ? SWU_BOT_NO_CLOCK : max(1, intdiv($hp + array_sum($att) - 1, array_sum($att)));
        return $clock($a, $ht) >= $clock($b, $hm) + 1;
    });
}
// $W re-read at one rank toward control (racing rank + 1), keeping the probe-free table values.
function _SWUBotLvLosingshiftWeights(array $W): array {
    $col = min(count(SWU_BOT_ARCHETYPES) - 1, intval($W['racingRank'] ?? 0) + 1);
    $W1 = $W;
    foreach (SWUBotWeightTable() as $k => $row) $W1[$k] = $row[$col];
    $W1['racingRank'] = $col;
    return $W1;
}

SWUBotLeverRegister('aggroanswer', 'losingshift', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || intval($W['racingRank'] ?? 9) > 1 || !empty($GLOBALS['SWUBotLvLosingshiftDepth'])) return null;
        if (!_SWUBotLvLosingshiftLosing(intval($att['controller']))) return null;
        $GLOBALS['SWUBotLvLosingshiftDepth'] = 1;
        try {
            $delta = _SWUBotUnitTargetValue($att, $def, _SWUBotLvLosingshiftWeights($W)) - _SWUBotUnitTargetValue($att, $def, $W);
        } finally { $GLOBALS['SWUBotLvLosingshiftDepth'] = 0; }
        return abs($delta) < 1e-9 ? null : $tv + $delta;
    },
]);
