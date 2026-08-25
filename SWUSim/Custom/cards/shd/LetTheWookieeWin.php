<?php
// SHD_205
// Cost 2 - Let the Wookiee Win - [Cunning,Heroism]
// Text: An opponent chooses one: / <bullet>You ready up to 6 resources. / You ready a friendly unit. If it's a Wookiee unit, attack with it. It gets +2/+0 for this attack.</bullet>

// ─── SHD_205 Let the Wookiee Win (opponent chooses one of two caster benefits) ─
// $player = the OPPONENT (chooser); parts[0] = the caster.
// Picked seat in $lastDecision; hand the two-way choice to THAT opponent, on their queue.
$customDQHandlers["SHD_205#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $opp;
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "Ready6Resources&ReadyUnit", 1,
        "Opponent_chooses:_they_ready_up_to_6_resources_OR_ready_a_friendly_unit");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "SHD_205#0|" . $caster, 1);
};

$customDQHandlers["SHD_205#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? OtherPlayer(intval($player)));
    if ($lastDecision === 'Ready6Resources') {
        SWUReadyResources($caster, 6);   // "you ready up to 6 resources"
        return;
    }
    // ReadyUnit: the caster readies a friendly unit; a Wookiee then attacks with +2/+0.
    $playerID = $caster;
    $units = array_values(array_filter(SWUAllUnits('my'), fn($mz) => ($o = GetZoneObject($mz)) !== null && empty($o->removed)));
    if (empty($units)) return;
    SWUQueueChooseTarget($caster, $units, "Ready_a_friendly_unit_(a_Wookiee_attacks_with_+2/+0)", "SHD_205#1");
};

$customDQHandlers["SHD_205#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnReadyCard(intval($player), $lastDecision);   // ready it
    if (HasTrait($o->CardID ?? '', 'Wookiee')) {
        SWUAddAttackPowerBonus($lastDecision, 2);   // +2/+0 for this attack
        BeginSWUAttack(intval($player), $lastDecision);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_205:0"] = function($player, $mzID = '') {
// Let the Wookiee Win — "An opponent chooses one: [You ready up to 6 resources] OR
                          // [You ready a friendly unit. If it's a Wookiee, attack with it. It gets +2/+0]."
            global $playerID; $playerID = intval($player);
            // "AN opponent chooses one" — the caster picks WHICH opponent does the choosing.
            // ⚠ NO $eligible filter: BOTH modes read and mutate only the CASTER's own resources and units,
            // so no property of a candidate opponent can make them unable to choose (taxonomy shape 3 —
            // the pool they act on is not theirs). Same shape as LOF_065 Watto.
            SWUQueueChooseOpponent(intval($player), 'SHD_205#2|' . intval($player),
                "Choose_an_opponent_to_make_the_choice");
            return;
};
