<?php
// LEVER 'midcleanup' (theme 'aggroanswer', G2-30 midrange-cleans-up-when-losing-the-race). 'cleanup' / 'cleanupsoft' (p42/p44) cap base
// attacks under a kill for the control wing only. Piett trace R4-R6: base attacks while 4 indirect ships killed his base (19 dealt vs 27
// taken in 6 rounds); midrange decks lose to space aggro (Piett vs Luke JTL 18%, Leia 14-25%, Armorer 21% vs soft aggro).
// target (base) — a MIDRANGE attacker (archetype rank 2) whose side LOSES the action race (the 'actionclock' simulation: every unit swings
// once a round, largest first, the sides alternating) caps the base just under the best enemy unit it defeats (kill-survive, or a trade
// worth >= 0), as 'cleanup' does. Not 'mgtrade' (harmful: it paid more for ANY kill while behind on board power).

// Does $seat lose the action race to its opponent? False too when neither base falls in the simulated window.
function _SWUBotLvMidcleanupLosesRace(int $seat): bool {
    return SWUBotMemo("lv-midcleanup-lose|$seat", function () use ($seat) {
        $opp = SWUBotOpponent($seat);
        $won = _SWUBotActionRaceSim(_SWUBotRaceAttacks($seat, $opp), _SWUBotRaceAttacks($opp, $seat), SWUBaseRemainingHp($seat),
            SWUBaseRemainingHp($opp), str_starts_with(strval(GetInitiativeCounter() ?? ''), 'P' . $seat . '_'));
        return $won === false;
    });
}

SWUBotLeverRegister('aggroanswer', 'midcleanup', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def !== null || intval($W['archetypeRank'] ?? -1) !== 2) return null;
        $seat = intval($att['controller']);
        if (!_SWUBotLvMidcleanupLosesRace($seat)) return null;
        $best = null;
        foreach (SWUBotAttackTargets($seat, $att)['units'] as $u) {
            if (!in_array(SWUBotCombatOutcome($att, $u), ['kill-survive', 'trade'], true)) continue;
            $v = SWUBotTargetValue($att, $u, $W);
            if ($v >= 0.0) $best = $best === null ? $v : max($best, $v);
        }
        if ($best === null || $best - 0.01 >= $tv) return null;
        return $best - 0.01;
    },
]);
