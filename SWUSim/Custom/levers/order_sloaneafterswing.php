<?php
// LEVER 'sloaneafterswing' (theme 'order', ASH lever hunt G4-21). Off by default: '@try-sloaneafterswing' / '@try-order'.
// ASH_007 Grand Admiral Sloane's front Action gives EVERY unit in an arena (theirs too) Sentinel and Overwhelm: 526 of 1,228
// uses came before my first attack of the round, so enemy units were Sentinels before I swung; after the Action my attacks went
// 121 at the base vs 209 into units (Blue). While a ready unit of mine can still attack the base, the Action waits just below the
// best such attack: my swings first, the Action last before a pass/claim (still ahead of the opponent's attacks).

SWUBotLeverRegister('order', 'sloaneafterswing', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvSloaneafterswingDepth']) || SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'ASH_007' || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) return null;
        $GLOBALS['SWUBotLvSloaneafterswingDepth'] = 1;
        try {
            $bestAtt = null;
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'attack') continue;
                $att = SWUBotViewForMz($seat, SWUBotActionMz($a));
                if ($att === null || empty($att['ready']) || !SWUBotAttackTargets($seat, $att)['base']) continue;
                $s = SWUBotScoreAction($ctx, $a, intval($i));
                if ($s > 0.0) $bestAtt = max($bestAtt ?? $s, $s);
            }
            if ($bestAtt === null) return null;
            $base = _SWUBotAbilityValue($ctx, $action, $W);
        } finally { $GLOBALS['SWUBotLvSloaneafterswingDepth'] = 0; }
        if ($base <= 0.0 || $base < $bestAtt - 0.01) return null;
        return $bestAtt - 0.01;
    },
]);
