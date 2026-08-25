<?php
// LOF_128
// Cost 4 - Protect the Pod - [Command]
// Text: A friendly non-Vehicle unit deals damage equal to its remaining HP to an enemy unit.

// LOF_128 Protect the Pod — the chosen friendly non-Vehicle unit deals damage equal to its remaining HP
// to an enemy unit (the player then chooses the enemy; the universal DEAL_UNIT_DAMAGE handler applies it).
$customDQHandlers["LOF_128#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $remHP = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
    if ($remHP <= 0) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $remHP, 'side' => 'their',
        'prompt' => "Deal_{$remHP}_damage_to_an_enemy_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_128:0"] = function($player, $mzID = '') {
// Protect the Pod — "A friendly non-Vehicle unit deals damage equal to its
                          // remaining HP to an enemy unit."
            global $playerID; $playerID = intval($player);
            $friendly = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (!HasTrait($o->CardID ?? '', 'Vehicle')) $friendly[] = $mz;
            }
            if (empty($friendly)) return;
            if (empty(array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)))) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-Vehicle_unit_(deals_its_remaining_HP)", "LOF_128#0");
            return;
};
