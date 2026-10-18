<?php
// LEVER 'debufffollow' (theme 'engines', G4-08 phase-debuff-needs-a-follow-up). Two thirds of the non-lethal "-N/-N for this phase" uses
// (Talzin's Action, G4-04's counts) had no attack by or into the debuffed unit afterwards that phase: the effect expired unused.
// 'buffattack' / 'phaseexpiry' handle friendly buffs only.
// ability — a leader / unit Action whose effect is "Give a unit -N/-M for this phase" (or -N/-0), when no enemy unit dies to it, is worth
// (a) the damage it prevents from the target's attack still to come this phase (a READY enemy: W['base'] x min(N, its attack power)) plus
// (b) the kill it enables for my ready attackers this phase (the gain in that attack's value). Neither: it waits (-0.5). When the debuff
// kills something, no opinion (the shipped pricing judges a kill). ('karisforce''s reservation is charged when that lever is on.)
// Events with the same shape (Knowledge and Defense) are not covered: their value is the play scorer's tag sum.

SWUBotLeverRegister('engines', 'debufffollow', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!in_array(SWUBotActionKind($action), ['leader-ability', 'unit-action'], true)) return null;
        $text = _SWUBotActionSourceText($seat, $action);
        if (!preg_match('/Action \[[^\]]*\]:\s*Give (?:a|an enemy) unit -(\d+)\/-(\d+) for this phase/i', $text, $m)) return null;
        $p = intval($m[1]); $h = intval($m[2]);
        $enemies = SWUBotEnemyUnits($seat);
        if (empty($enemies)) return null;   // 'nohostiletarget' (p50) waits on an empty board
        foreach ($enemies as $u) if ($h > 0 && intval($u['remaining']) <= $h) return null;   // lethal: not this lever's
        $best = 0.0; $enables = 0.0;
        foreach ($enemies as $u) {
            $prevent = !empty($u['ready']) ? floatval($W['base']) * min($p, max(0, intval($u['attackPower']))) : 0.0;
            $enable = function_exists('_SWUBotLvForcefreeuseGain') ? _SWUBotLvForcefreeuseGain($ctx, $seat, $u, $p, $h, $W) : 0.0;
            $best = max($best, $prevent + $enable); $enables = max($enables, $enable);
        }
        if ($best <= 0.0) return -0.5;
        // A Force Action that enables no kill keeps the shipped 'talzinforce' wait while other Force cards need the token.
        if ($enables <= 0.0 && preg_match('/use the Force/i', $text) && SWUBotFeatureOn('talzinforce')
            && (_SWUBotHasOtherForceUse($seat) || _SWUBotForceCardsInDeck($seat) >= 2)) return null;
        $charge = (preg_match('/use the Force/i', $text) && SWUBotProposalOn('karisforce') && function_exists('_SWUBotLvKarisforceCharge')) ? _SWUBotLvKarisforceCharge($seat, $W) : 0.0;
        return $best - $charge;
    },
]);
