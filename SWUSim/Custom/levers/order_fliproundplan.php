<?php
// LEVER 'fliproundplan' (theme 'order', ASH lever hunt G4-35). Off by default: '@try-fliproundplan' / '@try-order'.
// One root cause behind G4-16 (Bo-Katan cast by R5 in 28 of 129 Lando Colossus games), G4-18 (No Glory before Piett's deploy 40x)
// and G4-22 (Plots offered 6/30 Sloane Splash): the deploy round is scored action by action, so a cheap play that spends the pool
// goes first and the deploy's same-round payoff becomes unaffordable.
// The deploy round's line: deploy, then spend the whole pool on what the deploy makes cheaper or affordable — hand cards at the
// deployed side's discount ("Each <Trait> unit you play costs N resources less"), the Credits its When Deployed creates (net), and
// Plot cards in my resources (left to 'plotdeployfirst' when that lever is on). A non-deploy play that leaves that line less
// affordable is charged the difference (the line's value with the whole pool minus with what the play leaves), cards the
// deploy does not help excluded. Greedy by value per resource; read off the leader, so re-scoring sub-lists sees it too.

// What the deploy of $l does to costs and the pool: [trait => discount] and the net Credits it adds.
function _SWUBotLvFliproundplanDeployEffect(int $seat, object $l): array {
    $dt = strval(CardDeployText(strval($l->CardID ?? '')));
    $disc = [];
    if (preg_match_all('/Each ([\w ]+?) unit you play costs (\d+) resources? less/i', $dt, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) $disc[$m[1]] = intval($m[2]);
    }
    $credits = 0;
    if (preg_match('/When Deployed: You may defeat a friendly Credit token\. If you do, create (\d+) Credit tokens/i', $dt, $m)) {
        $credits = _SWUBotUsableCredits($seat) >= 1 ? intval($m[1]) - 1 : 0;
    } elseif (preg_match('/When Deployed:[^\n]*?create (\d+|a) Credit tokens?/i', $dt, $m)) {
        $credits = $m[1] === 'a' ? 1 : intval($m[1]);
    }
    return [$disc, $credits];
}

// The line's value with $budget: the deploy-helped cards (hand index $skip excluded), greedily by value per resource.
function _SWUBotLvFliproundplanLine(int $seat, object $l, int $budget, ?int $skip, array $W): float {
    [$disc, $credits] = _SWUBotLvFliproundplanDeployEffect($seat, $l);
    $capNow = SWUTotalPaymentCapacity($seat);
    $budget += $credits;
    $items = [];   // [cost, value]
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed) || $i === $skip) continue;
        $cid = strval($o->CardID ?? '');
        $full = intval(SWUComputePlayCost($seat, $o));
        $post = $full;
        if (str_contains(strval(CardType($cid)), 'Unit')) foreach ($disc as $trait => $n) if (HasTrait($cid, $trait)) $post = max(0, $post - $n);
        // Only the cards the deploy HELPS: cheaper after it, or affordable this round only with its Credits.
        if ($post >= $full && !($credits > 0 && $full > $capNow && $full <= $capNow + $credits)) continue;
        $v = _SWUBotPlayValue($seat, $cid, $W);
        if ($v > 0.0) $items[] = [$post, $v];
    }
    if (!SWUBotProposalOn('plotdeployfirst')) foreach (_SWUBotAffordablePlots($seat) as [$cost, $pc]) {
        $v = _SWUBotPlayValue($seat, $pc, $W);
        if ($v > 0.0) $items[] = [intval($cost), $v];
    }
    usort($items, fn($a, $b) => ($b[1] / max(1, $b[0])) <=> ($a[1] / max(1, $a[0])));
    $total = 0.0;
    foreach ($items as [$c, $v]) { if ($c <= $budget) { $budget -= $c; $total += $v; } }
    return $total;
}

SWUBotLeverRegister('order', 'fliproundplan', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (($o = _SWUBotHandObject($seat, $action)) === null) return null;
        $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        $cap = SWUTotalPaymentCapacity($seat);
        $cost = intval(SWUComputePlayCost($seat, $o));
        $charge = 0.0;
        foreach (GetLeader($seat) as $l) {
            if ($l === null || !empty($l->removed) || _SWUBotLeaderThreshold($seat, $l) <= 0 || !_SWUBotLeaderCanDeployNow($seat, $l)) continue;
            $charge = max($charge, _SWUBotLvFliproundplanLine($seat, $l, $cap, $i, $W) - _SWUBotLvFliproundplanLine($seat, $l, $cap - $cost, $i, $W));
        }
        return $charge > 0.0 ? $v - $charge : null;
    },
]);
