<?php
// LEVER 'plotfirst' (theme 'controlplan', report OA4 — Plan 2 check). A deploy's Plot plays share the round's resources with the hand: Ahsoka
// ASH_009 deployed with 6 open plotted Jar Jar + Naboo Royal Starship for a 13-damage turn (cdfd R5); with 1 open after Queen Amidala she
// plotted nothing, hit for 4 and died (e026 R5). 'plotdeploy' adds the Plots to the deploy's score, but no hand-play term asks whether paying
// it shrinks that Plot set, and the max-units guide decides the turn (3 traced Ahsoka deploys spent first and plotted nothing). Here, while a
// deploy is on offer that the bot would TAKE (scores above pass — a held deploy must not freeze the hand; final review I1) and Plot plays are
// affordable (_SWUBotAffordablePlots), hand plays that would leave less than those Plots cost leave the action list — the deploy goes first.
// Never while the opponent threatens lethal next round (break-lethal territory: every play must stay visible to the rules).
SWUBotLeverRegister('controlplan', 'plotfirst', [
    'filter' => function (array $actions, array $ctx): ?array {
        $seat = intval($ctx['seat'] ?? 0);
        if ($seat <= 0) return null;
        $deploy = _SWUBotFind($ctx, fn($a) => SWUBotActionKind($a) === 'deploy');
        if ($deploy === null) return null;
        $plots = _SWUBotAffordablePlots($seat);
        if (empty($plots)) return null;
        $opp = function_exists('SWUBotMostDangerousOpponent') ? SWUBotMostDangerousOpponent($seat) : SWUBotOpponent($seat);
        if (SWUBotClock($opp, $seat) <= 1) return null;
        if (!_SWUBotLvScoresAbovePass($ctx, $deploy)) return null;
        $room = intval(SWUTotalPaymentCapacity($seat)) - array_sum(array_column($plots, 0));
        $kept = array_values(array_filter($actions, function ($a) use ($seat, $room) {
            if (SWUBotActionKind($a) !== 'play' || ($o = _SWUBotHandObject($seat, $a)) === null) return true;
            return intval(CardCost(strval($o->CardID ?? ''))) <= $room;
        }));
        return count($kept) < count($actions) ? $kept : null;
    },
]);
