<?php
// SOR_189
// Cost 2 - Leia Organa - Defiant Princess - [Cunning,Heroism] - Power 2 - HP 2
// Text: When Played: Either ready a resource or exhaust a unit.

// SOR_189 Leia Organa (Defiant Princess) — "When Played: Either ready a resource or exhaust a unit."
// Mandatory either/or → OPTIONCHOOSE with two labeled buttons (no decline).
// "Ready a resource" → auto-ready the first exhausted resource (no further player choice).
// "Exhaust a unit"   → exhaust a unit; auto-picks when only 1 other ready unit exists.
$whenPlayedAbilities["SOR_189:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ready a resource&Exhaust a unit", 1, "Ready_a_resource_or_exhaust_a_unit?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_189#0|{$mzID}", 1);
};

// Receives the OPTIONCHOOSE label. $parts[0] = Leia's own arena mzID (excluded from exhaust targets).
$customDQHandlers["SOR_189#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $leiaMzID  = $parts[0] ?? '';

    if ($lastDecision === "Ready a resource") {
        // Ready the first exhausted resource belonging to this player.
        $resources = GetResources($player);
        $target = null;
        for ($i = 0; $i < count($resources); $i++) {
            if (!empty($resources[$i]->removed)) continue;
            if (intval($resources[$i]->Status) === 0) {
                $target = "myResources-{$i}";
                break;
            }
        }
        if ($target === null) return;
        DecisionQueueController::AddDecision($player, "PASSPARAMETER", $target, 0);
        DecisionQueueController::AddDecision($player, "CUSTOM", "READY_RESOURCE", 0);
    } else {
        // Exhaust a unit — collect all ready units (Status=1) except Leia herself.
        $targets = [];
        foreach (SWUAllUnits() as $mz) {
            if ($mz === $leiaMzID) continue;
            $obj = GetZoneObject($mz);
            if (SWUObjGone($obj)) continue;
            if (intval($obj->Status) !== 1) continue;
            $targets[] = $mz;
        }
        if (empty($targets)) return;
        if (count($targets) === 1) {
            DecisionQueueController::AddDecision($player, "PASSPARAMETER", $targets[0], 0);
        } else {
            // Leave $playerID set — ExecuteStaticMethods calls MZCountChoices immediately after return.
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 0);
        }
        DecisionQueueController::AddDecision($player, "CUSTOM", "EXHAUST_UNIT", 0);
    }
};
