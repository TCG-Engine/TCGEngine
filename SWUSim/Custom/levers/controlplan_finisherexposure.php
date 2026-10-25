<?php
// LEVER 'finisherexposure' (theme 'controlplan', report OG1). About 23 units across the 23 games were left one Weakness / one ping from
// dead after an attack or Ambush and then finished (7a R5, 9264 R2/R4/R5, 6b1c R5, e4f7 R6, 1fea R5/R6, e026 R5, 3597 R5, 54ae R4-R6). The
// scorer's combat outcome treats "attacker survives at 1 HP" as a clean kill; _SWUBotUnitExposed reads only enemy combat. Here: an attack
// my unit SURVIVES, leaving it at or below the defender's controller's finisher reach (_SWUBotFinisherHPFor: Hemlock's front Action, a
// ready deployed Hemlock / Talzin, an Aurra-style Action), is priced as losing the attacker too.
SWUBotLeverRegister('controlplan', 'finisherexposure', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def === null) return null;
        $out = SWUBotCombatOutcome($att, $def);
        if ($out !== 'kill-survive' && $out !== 'bounce') return null;
        $hit = intval($att['shields']) > 0 ? 0 : max(0, intval($def['attackPower']));
        $after = $att; $after['remaining'] = intval($att['remaining']) - $hit;
        if ($after['remaining'] <= 0) return null;
        $reach = _SWUBotFinisherHPFor(intval($def['controller']), $after);
        if ($after['remaining'] > $reach) return null;
        return $tv - floatval($W['loss']) * SWUBotUnitValue($att) * _SWUBotLossFactor($att);
    },
]);
