<?php
// LOF_079
// Cost 4 - Shatterpoint - [Vigilance]
// Text: Choose one: / Defeat a non-leader unit with 3 or less remaining HP. / Use the Force (lose your Force token). If you do, defeat a non-leader unit. /

// LOF_079 Shatterpoint — modal: defeat a ≤3-HP non-leader, OR use the Force to defeat any non-leader.
$customDQHandlers["LOF_079#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'ForceDefeat') {
        if (!PlayerHasTheForce(intval($player))) return; // can't use the Force you don't have → fizzle
        UseTheForce(intval($player));
        $targets = [];
        foreach (SWUAllUnits() as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
        }
        if (empty($targets)) return;
        SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit", "DEFEAT_UNIT");
        return;
    }
    // 'DefeatWeak' — defeat a non-leader unit with 3 or less remaining HP.
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_non-leader_unit_with_3_or_less_HP", "DEFEAT_UNIT");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_079:0"] = function($player, $mzID = '') {
// Shatterpoint — "Choose one: Defeat a non-leader unit with 3 or less remaining
                          // HP. / Use the Force → defeat a non-leader unit."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "DefeatWeak&ForceDefeat", 1,
                tooltip: "Choose:_defeat_a_3-or-less-HP_unit,_or_use_the_Force_to_defeat_any_non-leader");
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_079#0", 1);
            return;
};
