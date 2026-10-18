<?php
// LEVER 'avarforcestart' (theme 'engines', ASH lever hunt G3-20). Off by default: '@try-avarforcestart' / '@try-engines'.
// LOF_007 Avar Kriss: front "Action [Exhaust]: The Force is with you", deploy at "resources + times you used the Force this phase >= 9".
// The Action ran in 11% of pre-deploy rounds (0.69/game) and from R4 on as the round's LAST action (trace g3x002), so the Force it made
// was never spent the same phase — and each spend is a step toward the deploy (median R8; 56%/35% of games). With no Force held and
// a Force spender usable THIS round (a castable hand card or a unit Action that uses the Force — Jedi Consular, Impossible Escape,
// Trust), the Action goes first in the round (3.0, above an ordinary play): make the Force, spend it, re-make it via Crystal Caves.

function _SWUBotLvAvarforcestartSpender(array $ctx, int $seat): bool {
    $cap = SWUTotalPaymentCapacity($seat);
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (intval(SWUComputePlayCost($seat, $o)) > $cap) continue;
        if (in_array('uses-the-force', SWUBotCardTags($cid), true) || preg_match('/use the Force/i', strval(CardText($cid)))) return true;
    }
    foreach (SWUBotUnits($seat) as $u) {
        if (empty($u['ready']) || !empty($u['isLeader'])) continue;
        if (preg_match('/Action \[[^\]]*use the Force/i', strval(CardText($u['cardID'])))) return true;
    }
    return false;
}

SWUBotLeverRegister('engines', 'avarforcestart', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || strval($l->CardID ?? '') !== 'LOF_007' || _SWUBotLeaderFront($seat, 'LOF_007') === null) return null;
        if (PlayerHasTheForce($seat) || !_SWUBotLvAvarforcestartSpender($ctx, $seat)) return null;
        return 3.0;
    },
]);
