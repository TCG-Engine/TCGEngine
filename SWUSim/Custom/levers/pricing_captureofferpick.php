<?php
// LEVER 'captureofferpick' (theme 'pricing', G2-32 capture-offer-gives-the-cheapest-or-passes). SEC_193 Grand Admiral Thrawn ("When Played: An
// opponent may choose a non-leader unit they control. If they do, this unit captures that unit. If they don't, ready this unit") — Thrawn Yellow
// 86% of lists: "Choose_a_unit_to_be_captured_by_Thrawn_(or_pass)" was answered with the first listed unit 204/204 — never a token, never PASS.
// decision (MZMAYCHOOSE, continuation SEC_193#0|thrawnUid|caster — the OFFERED seat's pick) — a unit scores (what a ready Thrawn costs me
//        this round: max of W['base'] x his power at my base when it is reachable, W['loss'] x the value of my best unit he would kill) −
//        W['loss'] x that unit's value; PASS 0. So the cheapest unit (a token, a spent 1-drop) goes when it costs less than his swing.
// ⚠ Analyst: this makes Thrawn's OPPONENTS play better (Thrawn is under real) — check which side it lands on before shipping.

SWUBotLeverRegister('pricing', 'captureofferpick', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($head !== 'SEC_193#0') return null;
        if ($c === '-' || $c === '' || $c === 'PASS') return 0.0;
        $parts = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
        $tuid = intval($parts[1] ?? 0);
        $thrawn = null;
        foreach (SWUBotEnemyUnits($seat) as $e) if ($e['uid'] === $tuid) { $thrawn = $e; break; }
        $v = SWUBotViewForMz($seat, $c);
        if ($thrawn === null || $v === null) return null;
        $ready = $thrawn; $ready['ready'] = true;
        $t = SWUBotAttackTargets(intval($thrawn['controller']), $ready);
        $swing = $t['base'] ? floatval($W['base']) * intval($thrawn['attackPower']) : 0.0;
        foreach ($t['units'] as $u) {
            if (intval($u['controller']) !== $seat || $u['uid'] === $v['uid']) continue;
            $out = SWUBotCombatOutcome($ready, $u);
            if ($out === 'kill-survive' || $out === 'trade') $swing = max($swing, floatval($W['loss']) * SWUBotUnitValue($u));
        }
        return $swing - floatval($W['loss']) * SWUBotUnitValue($v);
    },
]);
