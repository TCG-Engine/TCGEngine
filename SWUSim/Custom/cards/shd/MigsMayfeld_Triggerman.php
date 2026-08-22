<?php
// SHD_163
// Cost 2 - Migs Mayfeld - Triggerman - [Aggression] - Power 2 - HP 2
// Text: When a player discards a card from their hand: You may deal 2 damage to a unit or base. Use this ability only once each round.

// ─── SHD_163 Migs Mayfeld (reactive: any hand-discard → may deal 2 to a unit/base, once/round) ───
$customDQHandlers["SHD_163#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (strpos($lastDecision, 'Base') !== false) {
        // The chosen base's own mzID names its owner: "myBase-0" / "theirBase-0" at ≤2 seats,
        // "p{n}Base-0" above. This is the DEAL_TARGET / HEAL_TARGET base-routing family that was fixed
        // centrally on 2026-08-21 — Migs has a BESPOKE handler that the central SWUMzOwner fix never
        // reached, so it kept the old decoder. A "p{n}Base-0" mzID matches NEITHER branch of the old
        // my-prefix test, so above two seats picking seat 3's or seat 4's base dealt the 2 to a
        // different player entirely.
        SWUDealDamageToBase(2, SWUMzOwner($lastDecision, intval($player)));
    } else {
        $o = GetZoneObject($lastDecision);
        if (SWUObjGone($o)) return;
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    AddGlobalEffects(intval($player), 'SWU_SHD163_USED');   // once each round — consumed on actual use
};
