<?php
// LEVER 'clockdefence' (theme 'controlplan', report G1 + H1 + OC1). 3 of Ninin's 4 losses ended at or near exact lethal where a defensive
// kill or shrink beat the base hit he made: 7a R11 (base swing into a revealed 2x Aggressive Negotiations hand), 8996 R8 (Hemlock to the
// base instead of killing Pre Vizsla, who then dealt 15), e4f7 R11 (Weakness on a Tyranus that had already attacked, not the Merrin about
// to). 'break-lethal' fires only at SWUBotClock == 1, and SWUBotClock reads the board only. Here:
//   their clock = my base HP / (their board potential + the biggest burst SEEN in their hand) — Aggressive Negotiations' +1 per card,
//                 "Attack with a unit. It gets +N/+0", "deal N damage to a base";
//   while that clock is <= 2 and mine is longer, an attack that KILLS one of their attackers is worth the base damage it buys back
//   (its power x the rounds it adds to their clock, max 3), and a Weakness soften on a READY enemy counts 1.5x (0.75x on an exhausted one).
const SWU_BOT_LV_CLOCKDEF_MAX_ROUNDS = 3;

function _SWUBotLvKnownBurst(int $seat, int $opp): int {
    if (!function_exists('SWUBotOpponentCardModel')) return 0;
    $hand = count(array_filter(GetHand($opp), fn($o) => $o !== null && empty($o->removed)));
    $best = 0;
    foreach (SWUBotOpponentCardModel($seat) as $row) {
        if (intval($row['known'] ?? 0) <= 0) continue;
        $t = strval(CardText(strval($row['cid'] ?? '')));
        if (preg_match('/gets \+1\/\+0 for each card in your hand/i', $t)) $best = max($best, max(0, $hand - 1));
        elseif (preg_match('/Attack with a unit\. (?:For this attack, )?[Ii]t gets \+(\d+)\/\+0/', $t, $m)) $best = max($best, intval($m[1]));
        elseif (preg_match("/deal (\\d+) damage to (?:a|each|an enemy|the defending player's|your opponent's) base/i", $t, $m)) $best = max($best, intval($m[1]));
    }
    return $best;
}

// Rounds for a board of $pot per round plus ONE burst of $burst (an event fires once — final review I4) to take $hp.
function _SWUBotLvClockWithBurst(int $hp, int $pot, int $burst): int {
    if ($hp <= $pot + $burst) return 1;
    if ($pot <= 0) return SWU_BOT_NO_CLOCK;
    return 1 + intdiv($hp - $pot - $burst + $pot - 1, $pot);
}

// ['clock' => rounds until $seat's base falls, 'pot' => their per-round board potential, 'burst' => the seen one-off burst,
//  'mine' => my clock on them, 'hp'].
function _SWUBotLvTheirClock(int $seat): array {
    $opp = function_exists('SWUBotMostDangerousOpponent') ? SWUBotMostDangerousOpponent($seat) : SWUBotOpponent($seat);
    $pot = SWUBotBasePotential($opp, $seat, false);
    $burst = _SWUBotLvKnownBurst($seat, $opp);
    $hp = max(1, SWUBaseRemainingHp($seat));
    return ['clock' => _SWUBotLvClockWithBurst($hp, $pot, $burst), 'pot' => $pot, 'burst' => $burst, 'mine' => SWUBotClock($seat, $opp), 'hp' => $hp];
}

function _SWUBotLvClockDefOn(int $seat): ?array {
    $c = _SWUBotLvTheirClock($seat);
    return ($c['clock'] <= 2 && $c['mine'] > $c['clock']) ? $c : null;
}

SWUBotLeverRegister('controlplan', 'clockdefence', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || intval($def['attackPower']) <= 0) return null;
        $seat = intval($att['controller']);
        if (($c = _SWUBotLvClockDefOn($seat)) === null) return null;
        $out = SWUBotCombatOutcome($att, $def);
        if ($out !== 'kill-survive' && $out !== 'trade') return null;
        $after = _SWUBotLvClockWithBurst($c['hp'], max(0, $c['pot'] - intval($def['attackPower'])), $c['burst']);
        $gain = min(SWU_BOT_LV_CLOCKDEF_MAX_ROUNDS, $after - $c['clock']);
        return $gain > 0 ? $tv + floatval($W['base']) * intval($def['attackPower']) * $gain : null;
    },
    'weakness' => function (float $score, int $seat, array $v, bool $enemy, int $weak, array $W, int $loss): ?float {
        if (!$enemy || $loss >= intval($v['remaining']) || $score <= 0.0 || _SWUBotLvClockDefOn($seat) === null) return null;
        return $score * (!empty($v['ready']) ? 1.5 : 0.75);
    },
]);
