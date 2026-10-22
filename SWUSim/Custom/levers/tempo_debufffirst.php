<?php
// LEVER 'debufffirst' (theme 'tempo', shipped in p58). Spec 2026-10-20-swusim-tempo-flavour-design.md §2 — owner, 2026-09-22: "the tempo
// flavor … just changes what plays to take. so debuffs before trades". Gate: my deck is tempo (detected); every style.
// attack   — an attack waits (capped at -0.4) when a shrink I would cast right now (a "-X/-Y" or Weakness card in hand, or a leader / unit
//            Action whose text has one) turns this attacker's trade into a clean kill ('kill-survive') of an enemy unit it can reach, and
//            the attacker has no clean kill already.
// decision — that shrink's pick goes on the unit one of my READY units then kills cleanly. A shrink that kills by itself is left to the scorer.

// [power, hp] a shrink takes: "-X/-Y", or 1/1 per Weakness token ("Give N Weakness tokens", else 1). Null = no shrink.
function _SWUBotTempoShrink(string $text): ?array {
    if (str_contains($text, 'while attached unit is attacking')) return null;   // Condemn: only while IT attacks, not when my unit hits it
    if (preg_match('/-(\d+)\/-(\d+)/', $text, $m)) return [intval($m[1]), intval($m[2])];
    if (preg_match('/give (\d+) weakness tokens/i', $text, $m)) return [intval($m[1]), intval($m[1])];
    if (preg_match('/weakness token/i', $text)) return [1, 1];
    return null;
}
function _SWUBotTempoShrunk(array $v, array $sh): array {
    $v['power'] = max(0, intval($v['power']) - $sh[0]);
    $v['attackPower'] = max(0, intval($v['attackPower']) - $sh[0]);
    $v['remaining'] = intval($v['remaining']) - $sh[1];
    return $v;
}
// The enemy units $att may attack: its arena, and only the Sentinels there unless it has Saboteur.
function _SWUBotTempoReachable(int $seat, array $att): array {
    $in = array_values(array_filter(SWUBotEnemyUnits($seat), fn($e) => $e['arena'] === $att['arena']));
    $walls = array_values(array_filter($in, fn($e) => !empty($e['sentinel'])));
    return ($walls && empty($att['saboteur'])) ? $walls : $in;
}
// Does shrink $sh turn $att's attack on $e into a clean kill it is not already (and not a kill by the shrink alone)?
function _SWUBotTempoShrinkMakesKill(array $att, array $e, array $sh): bool {
    if ($sh[1] >= intval($e['remaining'])) return false;
    return SWUBotCombatOutcome($att, $e) !== 'kill-survive' && SWUBotCombatOutcome($att, _SWUBotTempoShrunk($e, $sh)) === 'kill-survive';
}

SWUBotLeverRegister('tempo', 'debufffirst', [
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (!_SWUBotTempoOn($seat)) return null;
        $reach = _SWUBotTempoReachable($seat, $att);
        foreach ($reach as $e) if (SWUBotCombatOutcome($att, $e) === 'kill-survive') return null;   // a clean kill is already on offer
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            $kind = SWUBotActionKind($a);
            if ($kind === 'play') { $o = _SWUBotHandObject($seat, $a); $text = $o !== null ? strval(CardText(strval($o->CardID ?? ''))) : ''; }
            elseif ($kind === 'leader-ability' || $kind === 'unit-action') {
                // Talzin's front -1/-1 while her flip is pending: 'talzinflip' orders it; two levers holding each other could stall the round
                // (final review #7).
                if (function_exists('_SWUBotTalzinFlipPending') && _SWUBotTalzinAction($seat, $a, 'leader-ability') && _SWUBotTalzinFlipPending($ctx, $seat)) continue;
                $text = _SWUBotActionSourceText($seat, $a);
            }
            else continue;
            $sh = _SWUBotTempoShrink(strtolower(strip_tags($text)));
            if ($sh === null || !_SWUBotLvScoresAbovePass($ctx, $a)) continue;   // only a shrink the bot would cast now
            foreach ($reach as $e) if (_SWUBotTempoShrinkMakesKill($att, $e, $sh)) return min($s, -0.4);
        }
        return null;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!SWUBotIsEnemyMz($seat, $c) || !_SWUBotTempoOn($seat)) return null;
        $sh = null;
        if (preg_match('/-(\d+)\/-(\d+)/', $tip, $m)) $sh = [intval($m[1]), intval($m[2])];
        elseif (($w = _SWUBotWeaknessCount($tip, strval(($ctx['following'] ?? [])[0] ?? ''))) > 0) $sh = [$w, $w];
        if ($sh === null || ($e = SWUBotViewForMz($seat, $c)) === null) return null;
        foreach (SWUBotUnits($seat) as $u) {
            if (empty($u['ready']) || $u['arena'] !== $e['arena'] || intval($u['attackPower']) <= 0) continue;
            if (_SWUBotTempoShrinkMakesKill($u, $e, $sh)) return SWUBotLeverNum('tempo.debufftarget', 6.0);
        }
        return null;
    },
]);
