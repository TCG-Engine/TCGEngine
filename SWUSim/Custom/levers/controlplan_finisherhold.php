<?php
// LEVER 'finisherhold' (theme 'controlplan', report OA2). A burst attack event is the finisher: 7a held Aggressive Negotiations SEC_179 from R3
// to R11 and won on its +7 for exact lethal; 4b0f spent it at +3 on R2 and conceded; f36f fired it for 5 on R3 with nothing behind it. 'anvader'
// (p29) prices AN by the attack it makes, with no hold. Here an aggro or midrange seat (rank <= 2) holds a burst attack event (value capped at
// -0.2) unless: it makes lethal now (my ready potential + the burst >= their base), or their clock on me is <= 1 (use it before I die),
// or it is round 10+.
function _SWUBotLvBurstBonus(int $seat, string $cid): ?int {
    if (!str_contains(strval(CardType($cid)), 'Event')) return null;   // a unit with an attack mode (HMW_232 Mon Cal Cruiser) is not held (final review I7)
    $t = strval(CardText($cid));
    $hand = count(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed)));
    if (preg_match('/Attack with a unit\. For this attack, it gets \+1\/\+0 for each card in your hand/i', $t)) return max(0, $hand - 1);
    if (preg_match('/^\s*Attack with a unit\. (?:For this attack, )?[Ii]t gets \+(\d+)\/\+0/m', $t, $m)) return intval($m[1]);
    return null;
}
SWUBotLeverRegister('controlplan', 'finisherhold', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (intval($W['archetypeRank'] ?? 2) > 2 || ($bonus = _SWUBotLvBurstBonus($seat, $cid)) === null) return null;
        $opp = SWUBotOpponent($seat);
        if (SWUBotBasePotential($seat, $opp, true) + $bonus >= SWUBaseRemainingHp($opp)) return null;          // lethal: fire
        if (function_exists('_SWUBotLvTheirClock') && _SWUBotLvTheirClock($seat)['clock'] <= 1) return null;   // dying: fire
        if (intval(GetTurnNumber()) >= 10) return null;
        return min($v, -0.2);
    },
]);
