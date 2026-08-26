<?php
// ASH_139
// Cost 4 - Hold Them Off - [Command]
// Text: Choose a friendly unit. That unit deals damage equal to its power divided as you choose among any number of units in its arena.

// ASH_139 Hold Them Off — the chosen friendly unit deals damage = its power, split among units in its
// arena (any number).
$customDQHandlers["ASH_139#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $caster = intval($parts[0] ?? $player);
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $pow = intval(ObjectCurrentPower($dealer));
    if ($pow <= 0) return;
    $arena = strpos($lastDecision, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
    $targets = [];
    foreach (["my{$arena}", "their{$arena}"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUOfferSplitDamage(intval($caster), intval($pow), $targets,
        "Split_{$pow}_damage_among_units_in_the_arena");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_139:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $friendly = SWUFriendlyUnits();
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_deal_its_power", "ASH_139#0|" . intval($player));
};
