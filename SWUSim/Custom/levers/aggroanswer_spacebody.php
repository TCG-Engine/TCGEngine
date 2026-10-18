<?php
// LEVER 'spacebody' (theme 'aggroanswer', G1-35 space-body-vs-space-swarm). Traced mando-vader-1 R4: Mando held Green Leader (2-cost space
// body, "When Defeated: deal 2 damage to a unit") and claimed for a draw; Rebellious Hammerhead was resourced in R3. Mando vs Vader resourced
// Green Leader 12 of 35 and Hammerhead 11 of 42, and opponents made 0.1-1.1 space attacks a game vs Vader.
// play — a midrange / control seat (archetype rank >= 2) facing a space-flavoured opponent with ships out: my SPACE unit's play adds the enemy
// space attack it absorbs or trades into next round — W['loss'] (a hit my base or another unit does not take), in full when its power
// defeats an unshielded enemy ship, half when it only blocks — plus what its damage When Defeated kills on the enemy board now.

function _SWUBotLvSpacebodySpaceOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) if (in_array('space', SWUBotDeckFlavours($o), true)) return true;
    $sp = 0; $gr = 0;
    foreach (SWUBotEnemyUnits($seat) as $u) { if ($u['arena'] === 'Space') $sp++; else $gr++; }
    return $sp >= 2 && $sp >= $gr;
}

SWUBotLeverRegister('aggroanswer', 'spacebody', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || intval($W['archetypeRank'] ?? 0) < 2 || !str_contains(strval(CardType($cid)), 'Unit') || _SWUBotPlayArena($cid) !== 'Space') return null;
        $ships = array_values(array_filter(SWUBotEnemyUnits($seat), fn($u) => $u['arena'] === 'Space'));
        if (empty($ships) || !_SWUBotLvSpacebodySpaceOpp($seat)) return null;
        $p = max(0, intval(CardPower($cid)));
        $trades = false;
        foreach ($ships as $u) if (intval($u['shields']) === 0 && $p >= intval($u['remaining'])) { $trades = true; break; }
        $add = floatval($W['loss']) * ($trades ? 1.0 : 0.5);
        if (_SWUBotWdDamage($cid) !== null) $add += _SWUBotWdBoardValue($seat, $cid, SWUBotEnemyUnits($seat), $W)[0];
        return $v + $add;
    },
]);
