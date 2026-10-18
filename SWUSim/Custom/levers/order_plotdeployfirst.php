<?php
// LEVER 'plotdeployfirst' (theme 'order', ASH lever hunt G4-22). Off by default: '@try-plotdeployfirst' / '@try-order'.
// Deploys with a Plot card already in resources that were OFFERED the Plot play: Sloane Splash 6/30, Sloane Blue 17/46, Thrawn
// Yellow 53/108, Vane 5/9, Talzin G 4/7 — vs Ahsoka Splash 37/40, Luke 677/946. The 'plotdeploy' ordering lives in the max-units
// guide, whose weight is 0 for midrange/control, so their deploy (1.5 + plot value) lost to plays that spent the pool first.
// Any style: while a deploy is on offer and Plot cards in my resources are affordable, a play that would make some of them
// unaffordable (the Plot is paid from the same pool, after the deploy) is charged the play value of the Plots it prices out.

// The Plots (cheapest first, as _SWUBotAffordablePlots picks them) that $budget still pays for.
function _SWUBotLvPlotdeployfirstFit(array $plots, int $budget): array {
    $out = []; $spent = 0;
    foreach ($plots as [$cost, $cid]) {
        if ($spent + $cost > $budget) continue;
        $spent += $cost; $out[] = $cid;
    }
    return $out;
}

SWUBotLeverRegister('order', 'plotdeployfirst', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        // Read off the LEADER, not the list: 'blocker-first' / 'wall-first' re-score plays against a list cut to the plays alone.
        $canDeploy = false;
        foreach (GetLeader($seat) as $l) {
            if ($l !== null && empty($l->removed) && _SWUBotLeaderThreshold($seat, $l) > 0 && _SWUBotLeaderCanDeployNow($seat, $l)) $canDeploy = true;
        }
        if (!$canDeploy) return null;
        $plots = _SWUBotAffordablePlots($seat);
        if (empty($plots) || ($o = _SWUBotHandObject($seat, $action)) === null) return null;
        $cap = SWUTotalPaymentCapacity($seat);
        $after = _SWUBotLvPlotdeployfirstFit($plots, $cap - intval(SWUComputePlayCost($seat, $o)));
        $lost = 0.0;
        $kept = array_count_values($after);
        foreach ($plots as [$cost, $pc]) {
            if (!empty($kept[$pc])) { $kept[$pc]--; continue; }
            $lost += max(0.0, _SWUBotPlayValue($seat, $pc, $W));
        }
        return $lost > 0.0 ? $v - $lost : null;
    },
]);
