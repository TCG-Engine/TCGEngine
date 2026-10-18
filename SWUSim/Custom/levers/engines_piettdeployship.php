<?php
// LEVER 'piettdeployship' (theme 'engines', ASH lever hunt G4-18). Off by default: '@try-piettdeployship' / '@try-engines'.
// JTL_005 Admiral Piett's deploy round (R4, 5 resources): No Glory, Only Results was cast BEFORE the deploy 40x (Blue) / 7x (Colossus),
// spending all 5, with Chimaera in hand in 13 / 3 of those; Colossus plays its first capital ship at median R6. 'deployfirst' (p42)
// holds the SHIP until the deploy, not the OTHER plays that eat the pool. While Piett can deploy now, a non-Capital-Ship play that would
// leave the best Capital Ship in hand unaffordable at the deployed side's discount (−2) is charged that ship's play value — so "deploy,
// then the ship" is compared against "spend first". Stands down while the general 'fliproundplan' lever is on (it covers this case).

SWUBotLeverRegister('engines', 'piettdeployship', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (SWUBotProposalOn('fliproundplan') || HasTrait($cid, 'Capital Ship')) return null;
        $l = _SWUBotLeaderFront($seat, 'JTL_005');
        if ($l === null || !_SWUBotLeaderCanDeployNow($seat, $l) || ($o = _SWUBotHandObject($seat, $action)) === null) return null;
        $cap = SWUTotalPaymentCapacity($seat);
        $left = $cap - intval(SWUComputePlayCost($seat, $o));
        $charge = 0.0;
        foreach (GetHand($seat) as $q) {
            if ($q === null || !empty($q->removed) || $q === $o) continue;
            $sc = strval($q->CardID ?? '');
            if (!str_contains(strval(CardType($sc)), 'Unit') || !HasTrait($sc, 'Capital Ship')) continue;
            $post = max(0, intval(SWUComputePlayCost($seat, $q)) - 2);
            if ($post <= $cap && $post > $left) $charge = max($charge, _SWUBotPlayValue($seat, $sc, $W));
        }
        return $charge > 0.0 ? $v - $charge : null;
    },
]);
