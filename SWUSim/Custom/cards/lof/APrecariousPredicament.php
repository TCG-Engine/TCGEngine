<?php
// LOF_222
// Cost 2 - A Precarious Predicament - [Cunning]
// Text: Return an enemy non-leader unit to its owner's hand unless its controller says, "It could be worse." If they do, you may play a card named It's Worse from your hand or resources for free.

// LOF_222 A Precarious Predicament — the caster chose an enemy non-leader unit; its CONTROLLER (opponent)
// is offered a cross-player YESNO to "say It could be worse" (keep it). On keep, the caster may play It's
// Worse free from hand; otherwise the unit is bounced.
$customDQHandlers["LOF_222#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? -1);
    $opp = OtherPlayer(intval($player));
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1,
        tooltip: "Say_'It_could_be_worse'_to_keep_this_unit?_(opponent_may_then_play_It's_Worse_for_free)");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_222#1|" . intval($player) . "|{$uid}", 1);
};

$customDQHandlers["LOF_222#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? -1);
    if ($lastDecision === 'YES') {
        // Opponent kept the unit; the caster MAY play "It's Worse" (LOF_264) free from hand.
        $playerID = $caster;
        $hand = GetHand($caster); $found = -1;
        for ($i = 0; $i < count($hand); $i++) {
            if ($hand[$i] !== null && empty($hand[$i]->removed) && CardTitle($hand[$i]->CardID ?? '') === "It's Worse") { $found = $i; break; }
        }
        if ($found < 0) return; // none in hand (the "or resources" path is deferred)
        DecisionQueueController::AddDecision($caster, "YESNO", "-", 1, tooltip: "Play_It's_Worse_from_your_hand_for_free?");
        DecisionQueueController::AddDecision($caster, "CUSTOM", "LOF_222#2|myHand-{$found}", 1);
    } else {
        // Opponent declined → return the unit to its owner's hand.
        $playerID = $caster;
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUBounceUnit($caster, $mz);
    }
};

$customDQHandlers["LOF_222#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $mz = $parts[0] ?? '';
    if ($mz === '') return;
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, true, 0); // free play
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_264:0"] = function($player, $mzID = '') {
    // It's Worse — "Defeat a non-leader unit."
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'prompt' => "Defeat_a_non-leader_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_222:0"] = function($player, $mzID = '') {
// A Precarious Predicament — "Return an enemy non-leader unit to its owner's hand
                          // unless its controller says 'It could be worse.' If they do, you may play a card
                          // named It's Worse from your hand … for free."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
                $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Return_an_enemy_non-leader_unit_(unless_its_controller_objects)", "LOF_222#0");
            return;
};
