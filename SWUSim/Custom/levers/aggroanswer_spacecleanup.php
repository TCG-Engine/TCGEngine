<?php
// LEVER 'spacecleanup' (theme 'aggroanswer', G4-33 fighter-trade-vs-space-aggro). In Luke JTL games the opponents' space units attacked the
// base 5,372 times and Luke's units 1,100 — yet those unit attacks killed 88% of the time (Krennic Blue 78% at the base / 94% kill, Dedra
// 82% / 100%). Luke's damage is spread over 2-3 cost fighters.
// target (base) — for a midrange / control space attacker (archetype rank >= 2) facing a space-flavoured or aggro deck whose space power is
// at least mine, the base is capped just under the best enemy space unit the attacker kills and SURVIVES ('cleanup''s cap, per arena,
// midrange included). No free kill: no cap.

// Space-flavoured: the leader's flavour says so, or the board is mostly ships (2+ enemy space units, no fewer than on the ground).
function _SWUBotLvSpacecleanupSpaceOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) if (in_array('space', SWUBotDeckFlavours($o), true)) return true;
    $sp = 0; $gr = 0;
    foreach (SWUBotEnemyUnits($seat) as $u) { if ($u['arena'] === 'Space') $sp++; else $gr++; }
    return $sp >= 2 && $sp >= $gr;
}

SWUBotLeverRegister('aggroanswer', 'spacecleanup', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def !== null || $att['arena'] !== 'Space' || intval($W['archetypeRank'] ?? 0) < 2) return null;
        $seat = intval($att['controller']);
        if (!SWUBotOpponentIsAggroLeader($seat) && !_SWUBotLvSpacecleanupSpaceOpp($seat)) return null;
        $pw = function (array $units): int { $n = 0; foreach ($units as $u) if ($u['arena'] === 'Space') $n += max(0, intval($u['attackPower'])); return $n; };
        if ($pw(SWUBotEnemyUnits($seat)) < $pw(SWUBotUnits($seat))) return null;
        $best = null;
        foreach (SWUBotAttackTargets($seat, $att)['units'] as $u) {
            if (SWUBotCombatOutcome($att, $u) !== 'kill-survive') continue;
            $v = SWUBotTargetValue($att, $u, $W);
            if ($v >= 0.0) $best = $best === null ? $v : max($best, $v);
        }
        if ($best === null || $best - 0.01 >= $tv) return null;
        return $best - 0.01;
    },
]);
