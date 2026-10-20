<?php
// LEVER 'readykill' (theme 'pricing'). Owner, 2026-10-19 (Bug Report game 1647080): "against aggro, you should be killing ready units with
// higher power if your unit survives. it should price current power and ready higher than exhausted units of the same power." There, the
// Chimaera (6/6) took the exhausted Chewbacca-piloted N-1 (4/6, unit value 7) over the READY Emissary's Sheathipede (4/4 with Ahsoka's
// +2 for this phase, unit value 2): 9.1 vs 2.6 — the body's value outweighed the attack the ready unit still has this round.
// decision — against an aggro leader (SWUBotOpponentIsAggroLeader), an attack target that is a READY enemy unit the attack kills while the
//            attacker survives is lifted just above the best EXHAUSTED enemy unit on offer that the attack also kills cleanly and whose
//            attack power is no higher than its CURRENT attack power (phase buffs included). It is lifted, not the exhausted one capped:
//            a cap dropped both under the base hit and the Chimaera went face (probe 2026-10-19). Bases and other outcomes are unchanged.

SWUBotLeverRegister('pricing', 'readykill', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($tip !== 'Choose_an_attack_target' || str_contains($c, 'Base-') || !SWUBotIsEnemyMz($seat, $c) || !SWUBotOpponentIsAggroLeader($seat)) return null;
        $att = _SWUBotDecisionAttacker($ctx);
        $def = SWUBotViewForMz($seat, $c);
        if ($att === null || $def === null || empty($def['ready']) || intval($def['attackPower']) <= 0 || SWUBotCombatOutcome($att, $def) !== 'kill-survive') return null;
        $best = null;
        foreach ((array)($ctx['actions'] ?? []) as $a) {
            $mz = strval($a['cardID'] ?? '');
            if ($mz === $c || str_contains($mz, 'Base-') || !SWUBotIsEnemyMz($seat, $mz)) continue;
            $x = SWUBotViewForMz($seat, $mz);
            if ($x === null || !empty($x['ready']) || intval($x['attackPower']) > intval($def['attackPower']) || SWUBotCombatOutcome($att, $x) !== 'kill-survive') continue;
            $xv = SWUBotTargetValue($att, $x, $W);
            $best = $best === null ? $xv : max($best, $xv);
        }
        if ($best === null) return null;
        $tv = SWUBotTargetValue($att, $def, $W);
        return $tv > $best ? null : $best + 0.01;
    },
]);
