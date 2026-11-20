<?php
// IC27_122
// Cost 7 - The Ghost - Whale Watching - [Aggression,Heroism] - Unit (Space) 7/7 (unique)
//   Traits: Rebel, Vehicle, Transport, Spectre
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.)
//       When a friendly unit deals damage to an enemy base: If you have 7 or fewer cards in your hand, draw a card.
//
// Overwhelm is in $Overwhelm_Cards. The reaction reads "deals DAMAGE" (not "combat damage", JTL_177 Stay on Target's
// reading), so every way a unit damages a base counts, once per instance: a direct attack on a base, Overwhelm excess
// (both the single-defender spill and TWI_135 Maul's two-defender spill), a unit's ABILITY damage (the SWU_DMG_SRC
// source of SWUDealDamageToBase — an event or an undeployed leader is not a unit and records no source), and indirect
// damage dealt by a unit's ability. Each site calls _SWUIc27122OnUnitDamagedBase right after its SWUDealDamageToBase,
// which reads the amount that call ACTUALLY dealt — prevented damage (Close the Shield Gate) deals none and draws
// nothing. "friendly unit" = the source's controller is on The Ghost controller's TEAM (The Ghost itself included);
// "enemy base" = the base's owner is an enemy of that seat. The hand check reads at resolution, per Ghost.
function _SWUIc27122OnUnitDamagedBase(int $sourceController, int $baseOwner): void {
    if (intval($GLOBALS['gSWULastBaseDamageDealt'] ?? 0) <= 0 || $sourceController <= 0 || $baseOwner <= 0) return;
    if (function_exists('SWUGetGameWinner') && SWUGetGameWinner() !== 0) return;
    foreach (GetLiveSeatsArray() ?: [1, 2] as $seat) {
        $seat = intval($seat);
        if (SWUTeamOf($seat) !== SWUTeamOf($sourceController)) continue;   // the dealer is not friendly to this seat
        if (!SWUIsEnemySeat($seat, $baseOwner)) continue;                   // not an enemy base for this seat
        for ($k = _SWUCountActiveUnitsWithCardID($seat, 'IC27_122'); $k > 0; $k--) {
            $hand = count(array_filter(GetHand($seat), fn($c) => empty($c->removed)));
            if ($hand > 7) break;
            DoDrawCard($seat, 1);
        }
    }
}
