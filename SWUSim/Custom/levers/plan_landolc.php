<?php
// LEVER 'landolc' (theme 'tempoplans', shipped in p59). Spec 2026-10-20-swusim-tempo-deck-plans-design.md §4, Lando (LAW_018) on Lake Country
// (JTL_031). Owner, 2026-10-20: "they want 8R into flip (5R +3C) to play the 8-cost bombs like The Mandalorian or Liberty in yellow". The
// 1-resource Action goes BEFORE the 6-resource deploy (the deploy turns one banked Credit into three): 5 ready + 3 Credits = 8. Lando Blue's
// 9 (6R + 3C, shipped 'landoflip' / 'landoflipbomb') is unchanged. The other half lives in _SWUBotLandoBombFlip (BotFallback.php): the bomb
// bar is 8 here, and the deploy does not jump ahead of the Action.
// ability — on the flip turn (deploy on offer, 6+ ready resources, a banked Credit, an 8-drop castable after Action + flip), the Action first.

SWUBotLeverRegister('tempoplans', 'landolc', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!_SWUBotLandoLC($seat) || _SWUBotLeaderFront($seat, 'LAW_018') === null || SWUBotActionKind($action) !== 'leader-ability') return null;
        if (strval((_SWUBotLeaderOfAction($seat, $action))->CardID ?? '') !== 'LAW_018') return null;
        if (_SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'deploy') === null) return null;
        $ready = SWUResourceCount($seat, true);
        $credits = _SWUBotUsableCredits($seat);
        if ($credits < 1 || $ready < 6) return null;
        foreach (_SWUBotHandCards($seat) as $cid) {
            if (intval(CardCost($cid)) >= 8 && _SWUBotSeatCost($seat, $cid) <= $ready - 1 + $credits + 2) return SWUBotLeverNum('lando.lcaction', 3.1);
        }
        return null;
    },
]);
