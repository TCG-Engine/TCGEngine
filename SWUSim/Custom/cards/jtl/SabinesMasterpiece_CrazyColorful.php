<?php
// JTL_250
// Cost 3 - Sabine's Masterpiece - Crazy Colorful - [Heroism] - Power 3 - HP 3
// Text: On Attack: If you control a: / <bullet>Vigilance unit, heal 2 damage from a base. / Command unit, give an Experience token to a unit. / Aggression unit, deal 1 damage to a unit or a base. / Cunning unit, exhaust or ready a resource.</bullet> /

// ── JTL_250 Sabine's Masterpiece — On Attack: for EACH aspect you control among your units, run its
// effect (all applicable, in printed order): Vigilance→heal 2 from a base; Command→give an Experience
// token to a unit; Aggression→deal 1 to a unit or base; Cunning→exhaust or ready a resource.
// Each effect sits at a higher block so its sub-decision fully resolves before the next one is offered.
$onAttackAbilities["JTL_250:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $p = intval($player);
    $allUnits = SWUAllUnits();
    $block = 1;
    // Vigilance → heal 2 from a base. Healing the enemy base is never beneficial, so this auto-targets
    // the player's own base (a base-only MZCHOOSE auto-resolves to nothing — MZCountChoices doesn't count
    // base zones, so it can't be presented in-combat anyway).
    if (_SWUControlsUnitWithAspect($p, 'Vigilance')) {
        OnHealBase($p, $p, 2);
    }
    // Command/Aggression use MZMAYCHOOSE (the proven in-combat OnAttack choose — a mandatory multi-target
    // MZCHOOSE auto-resolves to nothing here because OnAttackTrigger restores $playerID before
    // MZCountChoices runs). The player would never decline a beneficial effect.
    if (_SWUControlsUnitWithAspect($p, 'Command') && !empty($allUnits)) {
        GiveTokenUpgrade($p, $mzID, [
            'traits' => [], 'friendlyOnly' => false, 'may' => true, 'block' => $block++,
            'question' => "Give_an_Experience_token_to_a_unit",
            'prompt'   => "Give_an_Experience_token_to_a_unit",
        ]);
    }
    if (_SWUControlsUnitWithAspect($p, 'Aggression')) {
        $targets = array_merge($allUnits, SWUAllBaseMzIDs(intval($p), 'any'));
        SWUQueueMayChooseTarget($p, $targets, "Deal_1_damage_to_a_unit_or_a_base",
            "Deal_1_damage_to_a_unit_or_a_base", "DEAL_TARGET|1", $block++);
    }
    if (_SWUControlsUnitWithAspect($p, 'Cunning')) {
        DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", "Exhaust&Ready", $block, tooltip:"Exhaust_or_ready_a_resource");
        DecisionQueueController::AddDecision($p, "CUSTOM", "JTL_250#0", $block);
        $block++;
    }
};

// JTL_250 Cunning branch: after the Exhaust/Ready choice, the controller chooses WHICH player's resource
// ("exhaust or ready a resource" — no "your", so either player is a legal target). Queue a You/Opponent
// pick carrying the chosen verb, resolved in JTL_250#1.
$customDQHandlers["JTL_250#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'Exhaust' && $lastDecision !== 'Ready') return;
    $p = intval($player);
    DecisionQueueController::AddDecision($p, "OPTIONCHOOSE", SWUPlayerPickerLabels($p), 1,
        tooltip: ($lastDecision === 'Exhaust' ? "Choose_a_player_to_exhaust_a_resource" : "Choose_a_player_to_ready_a_resource"));
    DecisionQueueController::AddDecision($p, "CUSTOM", "JTL_250#1|{$lastDecision}", 1);
};

$customDQHandlers["JTL_250#1"] = function($player, $parts, $lastDecision) {
    $verb   = $parts[0] ?? '';
    $target = SWUDecodePlayerPick($lastDecision, intval($player)); // "You"→caster, "Opponent"→the other player
    if ($verb === 'Exhaust')    SWUExhaustResources($target, 1);
    elseif ($verb === 'Ready')  SWUReadyResources($target, 1);
};
