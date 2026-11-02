<?php
// LEVER 'maulemptydeploy' (theme 'teamread', team games 2026-10-29, report D3). Maul (HMW_016): "When Deployed: You may play a unit that was
// defeated this phase from your discard pile. It costs 5 resources less." 31 of 34 human deploys replayed a unit; the one empty deploy died
// that round. With no unit defeated this phase that the resources cover, the deploy waits for a later round — his front Action (play a
// unit, then defeat it) fills the discard first, and 'deployreplay' (p42) already orders that Action before the deploy when it is on offer.
// deploy — Maul with no affordable replay target waits (-0.4), a whole round if need be: unlike Talzin's flip there is no "forced" GO — the
//          empty body costs the front Action, which is what fills the discard. Goes anyway: lethal on board, or round >= 'maulempty.round'
//          (default 10). (Smoke 2026-10-29: an "opponent claimed" release made 1 of 7 remaining empty deploys; dropped.)

const SWU_BOT_TR_MAUL = 'HMW_016';

// A unit defeated this phase in my discard that the deploy's discount and my resources cover.
function _SWUBotTrMaulReplayTarget(int $seat): bool {
    if (!preg_match(SWU_BOT_DEPLOYREPLAY_RE, strval(CardDeployText(SWU_BOT_TR_MAUL)), $rm)) return true;   // text changed: never hold
    $cap = SWUTotalPaymentCapacity($seat);
    foreach (GetDiscard($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $cid = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($cid)), 'Unit') || GlobalEffectCount($seat, 'SWU_DEFEATED_CARD_' . $cid) <= 0) continue;
        if (max(0, intval(CardCost($cid)) - intval($rm[1])) <= $cap) return true;
    }
    return false;
}

SWUBotLeverRegister('teamread', 'maulemptydeploy', [
    'deploy' => function (float $dv, array $ctx, int $seat, array $action, array $W): ?float {
        if ($dv <= -0.4) return null;
        $leader = _SWUBotLeaderOfAction($seat, $action);
        if ($leader === null || strval($leader->CardID ?? '') !== SWU_BOT_TR_MAUL || _SWUBotTrMaulReplayTarget($seat)) return null;
        if (SWUBotLethalNow($seat, SWUBotOpponent($seat))) return null;
        if (intval(GetTurnNumber()) >= intval(SWUBotLeverNum('maulempty.round', 10))) return null;
        return min($dv, -0.4);
    },
]);
