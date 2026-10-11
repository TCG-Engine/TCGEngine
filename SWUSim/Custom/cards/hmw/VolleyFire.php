<?php
// HMW_192 Volley Fire — Event, cost 1, [Aggression], Tactic.
// "A friendly unit deals damage equal to its Raid to an enemy unit."
// Raid is read at resolution (printed + every grant, stacked). A unit with no Raid deals 0. The damage is
// dealt BY the chosen unit (it is the source). "friendly"/"enemy" are team-aware.

$whenPlayedAbilities["HMW_192:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (empty(SWUAllUnits('their'))) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'HMW_192#0', 'side' => 'friendly',
        'prompt' => 'Choose_a_friendly_unit_to_deal_damage_equal_to_its_Raid',
    ]);
};

$customDQHandlers["HMW_192#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $src = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($src)) return;
    $uid = intval($src->UniqueID ?? 0);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => "HMW_192#1|{$uid}", 'side' => 'their',
        'prompt' => 'Choose_an_enemy_unit',
    ]);
};

$customDQHandlers["HMW_192#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $srcMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($srcMz === null) return;
    $src = GetZoneObject($srcMz);
    $raid = intval(GetKeyword_Raid_Value($src));
    if ($raid <= 0) return;
    SWUDealDamageToUnit((string)$lastDecision, $raid, intval($player), $srcMz);
};
