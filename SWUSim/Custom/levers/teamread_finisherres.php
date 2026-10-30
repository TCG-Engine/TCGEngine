<?php
// LEVER 'finisherres' (theme 'teamread', team games 2026-10-29, report D10). A hand-size finisher (SEC_179 Aggressive Negotiations, "+1/+0
// for each card in your hand") turns every card kept in hand into a point of base damage, so an aggro or midrange seat holding one stops
// resourcing once it has the resources it needs: 8 of 9 game-ending Aggressive Negotiations by the Wicket pilots came after a skipped
// regroup resource, and Wicket Endor stopped at 6 resources in 22 games. Pair with 'anfix' (D0), which lets 'finisherhold' time the cast.
// filter — at the regroup's "Resource up to 1 card", once the resourcing floor is met (the leader can deploy) and I have
//          'finisherres.stop' (6) resources, only PASS is left on the list.

function _SWUBotTrFinisherresHolds(int $seat): bool {
    foreach (GetHand($seat) as $o) {
        if ($o !== null && empty($o->removed) && _SWUBotIsHandSizeAttackEvent(strval($o->CardID ?? ''))) return true;
    }
    return false;
}

SWUBotLeverRegister('teamread', 'finisherres', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['tooltip'] ?? '') !== 'Resource_up_to_1_card') return null;
        $seat = intval($ctx['seat'] ?? 0);
        if ($seat <= 0 || SWUBotStyleRank(strval($ctx['style'] ?? '')) > 2 || SWUBotResourceFloorApplies($seat)) return null;
        if (SWUResourceCount($seat) < intval(SWUBotLeverNum('finisherres.stop', 6)) || !_SWUBotTrFinisherresHolds($seat)) return null;
        $pass = array_values(array_filter($actions, fn($a) => strval($a['cardID'] ?? '') === 'PASS'));
        return (empty($pass) || count($pass) === count($actions)) ? null : $pass;
    },
]);
