<?php
// LEVER 'arenastrike' (theme 'pricing', G2-21 arena-strike-is-a-one-sided-wipe). JTL_131 Turbolaser Salvo ("Choose an arena. A friendly space
// unit deals damage equal to its power to each enemy unit in that arena") is in 65% of Piett lists: played 0%, resourced 50 of 60 sightings.
// Its only tag is 'power-strike', priced as ONE kill (W['kill'] when any striker can kill any enemy unit).
// play — priced as a one-sided wipe: my strongest SPACE unit's power against each enemy unit of the best arena — W['kill'] per unshielded
//        unit it defeats (the 'debuff-all-enemy-units' per-kill convention) + W['chip'] per point on the unshielded survivors — replacing
//        the single power-strike kill.

const SWU_BOT_LV_ARENASTRIKE_RE = '/friendly (space )?unit deals damage equal to its power to each enemy unit in that arena/i';

function _SWUBotLvArenastrikePower(int $seat, string $cid): int {
    if (!preg_match(SWU_BOT_LV_ARENASTRIKE_RE, strval(CardText($cid)), $m)) return -1;
    $space = trim($m[1] ?? '') !== '';
    $p = 0;
    foreach (SWUBotUnits($seat) as $u) if (!$space || $u['arena'] === 'Space') $p = max($p, intval($u['power']));
    return $p;
}

// The best arena's one-sided strike value for power $p: [value, kills].
function _SWUBotLvArenastrikeBest(int $seat, int $p, array $W): array {
    $best = [0.0, 0];
    foreach (['Ground', 'Space'] as $arena) {
        $val = 0.0; $kills = 0;
        foreach (SWUBotEnemyUnits($seat) as $u) {
            if ($u['arena'] !== $arena || intval($u['shields']) > 0) continue;
            if ($p >= intval($u['remaining'])) { $kills++; $val += floatval($W['kill']); }
            else $val += floatval($W['chip']) * $p;
        }
        if ($val > $best[0]) $best = [$val, $kills];
    }
    return $best;
}

SWUBotLeverRegister('pricing', 'arenastrike', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.4 || ($p = _SWUBotLvArenastrikePower($seat, $cid)) <= 0) return null;
        $old = (in_array('power-strike', SWUBotCardTags($cid), true) && SWUBotPowerStrikeCanKill($seat, SWUBotPowerStrikePower($seat, $cid))) ? floatval($W['kill']) : 0.0;
        [$val] = _SWUBotLvArenastrikeBest($seat, $p, $W);
        return $v - $old + $val;
    },
]);
