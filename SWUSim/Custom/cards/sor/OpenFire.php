<?php
// ⚠ Target pools use AnyUnitFilter (Unit + TOKEN Unit + Leader Unit): this card's text is
// unqualified, and a hand-built ["Unit","Leader Unit"] filter silently excluded token units
// (the Open Fire bug report, 2026-08-13 — a whole family of six files had the same miss).
// SOR_172
// Open Fire
// Text: Deal 4 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["IC27_136:0"] =
$whenPlayedAbilities["TWI_174:0"] =
$whenPlayedAbilities["SOR_172:0"] = function($player, $mzID = '') {
    // Open Fire — "Deal 4 damage to a unit."
    //
    // ⚠ Routed through SWUOfferUnitTarget, NOT a hand-rolled ZoneSearch. The helper sets the global
    // $playerID to the caster before collecting, which is load-bearing here: my*/their* zone names are
    // resolved relative to that frame, so an event played by the OPPONENT collected mzIDs in one frame
    // and handed them to DEAL_UNIT_DAMAGE in another — the damage silently landed nowhere.
    // (Caught when the TWI_174 and SOR_172 copies were merged: the TWI copy used this helper and was
    // correct, the SOR copy hand-rolled it and was not. jtl/PhantomIi_ModifiedToDock.md GritSharedTo-
    // HostFunctional is the guard — P2 plays this at P1's unit.)
    // The helper collects via SWUAllUnits/AnyUnitFilter, which preserves the token-unit inclusion the
    // header note above is about.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4,
        'prompt' => "Deal_4_damage_to_a_unit",
    ]);
};
