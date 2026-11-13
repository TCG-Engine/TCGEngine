<?php
// LEVER 'indirectbase' (theme 'postban'; Force Fam post-ban team read 2026-11-12, lever G1; owner: "usually only against Topple opponents.
// which i think Boba Fett (JTL) is the only indirect damage deck anyway"). Split / indirect damage I assign onto MY OWN units costs only
// W[chip] a point (0.15-0.45 below hard control) against SWU_BOT_OWN_BASE_DAMAGE (0.5) on my base (_SWUBotSplitScore), so every style below
// hard control soaked it onto units — the opposite of the humans: opponents sent 56 of 93 Boba pings to base, and indirect put onto
// undamaged units was then cashed by Topple the Summit SEC_183 ("deal 3 damage to each damaged unit": 6ef1f771, d346cf57). Against a
// Topple opponent (their leader is Boba Fett JTL_009, or a Topple is in their discard) a share that leaves my unit alive but within
// Topple's 3 costs more than the same points on my base; a share that only marks a fresh unit as "damaged" costs a hair more. Not while my
// base is low (<= 8 left), not on a doomed unit ('doomedsoak' prices those), never on a share that kills (the loss pricing stands).
// Hook: 'splitown'.

const SWU_BOT_LV_INDIRECTBASE_SAFE_BASE = 8;
const SWU_BOT_LV_INDIRECTBASE_TOPPLE = 3;
const SWU_BOT_LV_INDIRECTBASE_EXPOSED = 0.3;   // over the base price: the unit is now a Topple kill
const SWU_BOT_LV_INDIRECTBASE_MARKED = 0.05;  // over the base price: a fresh unit becomes a Topple target

function _SWUBotLvIndirectBaseToppleOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) {
        if (strval((GetLeader($o)[0] ?? null)->CardID ?? '') === 'JTL_009') return true;
        foreach (GetDiscard($o) as $c) if (strval($c->CardID ?? '') === 'SEC_183') return true;
    }
    return false;
}

SWUBotLeverRegister('postban', 'indirectbase', [
    'splitown' => function (float $part, int $seat, array $v, int $amt, array $W): ?float {
        if ($amt <= 0 || SWUBotDamageKills($v, $amt) || SWUBotUnitIsDoomed($v)) return null;
        if (SWUBaseRemainingHp($seat) <= SWU_BOT_LV_INDIRECTBASE_SAFE_BASE || !_SWUBotLvIndirectBaseToppleOpp($seat)) return null;
        $after = intval($v['remaining']) - $amt;
        $fresh = intval($v['obj']->Damage ?? 0) === 0;
        if ($after > SWU_BOT_LV_INDIRECTBASE_TOPPLE && !$fresh) return null;
        _SWUBotFired('indirectbase', $seat);
        $extra = $after <= SWU_BOT_LV_INDIRECTBASE_TOPPLE ? SWU_BOT_LV_INDIRECTBASE_EXPOSED : SWU_BOT_LV_INDIRECTBASE_MARKED;
        return min($part, -SWU_BOT_OWN_BASE_DAMAGE * $amt - $extra);
    },
]);
