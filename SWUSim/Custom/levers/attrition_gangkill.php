<?php
// LEVER 'gangkill' (theme 'attrition'). R5: Anakin's -3/-3 left the deployed Ahsoka a 2/3; HK-47 hit her and died, then the Door Technician
// finished her and died — two 2-drops for a 6-cost leader that does not come back (owner 2026-10-19). The bot priced each half alone: a hit
// that dies without killing is a pure loss (-1.6 on the replay board), and midrange's target filter dropped it before it was even scored, so
// HK-47 went at the base. A non-killing hit on an enemy unit now earns the JOINT kill when another ready unit of mine can finish what is
// left this phase and could not kill it alone:
//   W[kill] x value + the kill's extras + the threat removed − what both attacks lose − the finisher's own base swing (it does not go face).
// target      — that credit, when it beats the hit's own value;
// allowtarget — the style filter keeps the hit while the credit is positive.
// The order falls out of 'observerping': the observer attacking FIRST dies before the kill and pings nothing, so the other unit sets up
// and HK-47 trades into the kill (Ninin attacked with HK-47 first and lost that point).
// Not through a Shield (the hit only pops it) and not past another Sentinel the finisher cannot ignore. 2 players only.
function _SWUBotLvGangKillCredit(array $att, array $def, array $W): ?float {
    $seat = intval($att['controller'] ?? 0);
    if ($seat <= 0 || !in_array(intval($def['controller'] ?? 0), array_map('intval', SWUBotOpponents($seat)), true)) return null;
    if (function_exists('SeatCountForGame') && SeatCountForGame() > 2) return null;
    $out = SWUBotCombatOutcome($att, $def);
    if ($out !== 'die' && $out !== 'bounce') return null;
    if (intval($def['shields']) > 0 && empty($att['saboteur'])) return null;
    $hit = intval($att['attackPower']);
    $left = intval($def['remaining']) - $hit;
    if ($hit <= 0 || $left <= 0 || !empty($def['hpImmune'])) return null;
    $damaged = $def; $damaged['remaining'] = $left;
    $walled = false;
    foreach (SWUBotEnemyUnits($seat) as $e) if ($e['arena'] === $def['arena'] && !empty($e['sentinel']) && intval($e['uid']) !== intval($def['uid'])) $walled = true;
    $attDies = $out === 'die';
    $best = null;
    foreach (SWUBotUnits($seat) as $f) {
        if (intval($f['uid']) === intval($att['uid']) || empty($f['ready']) || $f['arena'] !== $def['arena'] || intval($f['attackPower']) <= 0) continue;
        if ($walled && empty($f['saboteur'])) continue;
        if (in_array(SWUBotCombatOutcome($f, $def), ['kill-survive', 'trade'], true)) continue;   // it kills alone: no setup needed
        $fo = SWUBotCombatOutcome($f, $damaged);
        if ($fo !== 'kill-survive' && $fo !== 'trade') continue;
        $GLOBALS['SWUBotLvDeadObserverUid'] = $attDies ? intval($att['uid']) : 0;
        try {
            $kill = floatval($W['kill']) * SWUBotUnitValue($def) + _SWUBotKillExtras($def, $W, $seat) + _SWUBotThreatRemoved($f, $def, $W);
        } finally { $GLOBALS['SWUBotLvDeadObserverUid'] = 0; }
        $loss = ($attDies ? _SWUBotLossFactor($att) * floatval($W['loss']) * SWUBotUnitValue($att) + _SWUBotObserverTax($att, $W) : 0.0)
              + ($fo === 'trade' ? _SWUBotLossFactor($f) * floatval($W['loss']) * SWUBotUnitValue($f) + _SWUBotObserverTax($f, $W) : 0.0);
        $credit = $kill - $loss - floatval($W['base']) * intval($f['attackPower']);
        if ($best === null || $credit > $best) $best = $credit;
    }
    return $best;
}

SWUBotLeverRegister('attrition', 'gangkill', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null || ($cr = _SWUBotLvGangKillCredit($att, $def, $W)) === null) return null;
        return $cr > $tv ? $cr : null;
    },
    'allowtarget' => function (array $ctx, array $att, array $def): ?bool {
        $cr = _SWUBotLvGangKillCredit($att, $def, SWUBotWeights(strval($ctx['style'] ?? ''), intval($ctx['seat'] ?? 0)));
        return ($cr !== null && $cr > 0.0) ? true : null;
    },
]);
