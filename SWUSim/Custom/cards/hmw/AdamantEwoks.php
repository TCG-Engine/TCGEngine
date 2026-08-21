<?php
// HMW_177
// Cost 2 - Adamant Ewoks - [Aggression] - Unit (Ground) 3/2 - Trait: Ewok
// Text: When Played: If you control another Ewok unit or an Endor base, you may deal 1 damage to a base
//       and 1 damage to an enemy unit.
//
// The base MZMAYCHOOSE is the "may" entry point (decline = do neither half). Accepting deals 1 to the
// chosen base (either base — no friendly/enemy qualifier), then 1 to a chosen enemy unit; the enemy-unit
// half fizzles cleanly if the opponent controls no units.
$whenPlayedAbilities["HMW_177:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjGone($self) ? -1 : intval($self->UniqueID ?? -1);

    // Gate: control ANOTHER Ewok unit, OR control an Endor base.
    $gate = _SWUControlsBaseWithTrait(intval($player), 'Endor');
    if (!$gate) {
        foreach (GetUnitsInPlay(intval($player)) as $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && TraitContains($u, 'Ewok')) {
                $gate = true; break;
            }
        }
    }
    if (!$gate) return;

    SWUQueueMayChooseTarget(intval($player), SWUAllBaseMzIDs(intval($player), 'any'),
        "Deal_1_to_a_base_and_1_to_an_enemy_unit?", "Deal_1_damage_to_a_base", 'HMW_177#0');
};

$customDQHandlers["HMW_177#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // declined the whole "may"

    SWUDealDamageToBase(1, SWUMzOwner((string)$lastDecision, intval($player)));

    // "…and 1 damage to an enemy unit." Mandatory within the accepted effect; fizzles with no enemy unit.
    $enemies = array_merge(
        ZoneSearch("theirGroundArena", AnyUnitFilter),
        ZoneSearch("theirSpaceArena",  AnyUnitFilter)
    );
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies, "Deal_1_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|1");
};
