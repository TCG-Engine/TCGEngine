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
    // ⚠ The cross-player YESNO goes to the CHOSEN UNIT'S controller — a DETERMINED seat we already hold
    // a reference to. OtherPlayer() named seat 2, so above two seats the wrong player was asked whether
    // to keep a unit that is not theirs (Twin Suns sweep Pass 2, §1b family).
    $opp = intval($o->Controller ?? 0);
    if ($opp <= 0) $opp = SWUMzOwner((string)$lastDecision, intval($player));
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
        // "…play a card named It's Worse from your HAND OR RESOURCES for free." Hand is searched first
        // (the common case); the RESOURCE zone is the second source and used to be unimplemented, so a
        // caster whose only copy was a resource silently got no offer at all.
        // Playing straight out of the resource zone needs no special machinery: ActivateCard takes the
        // zone-relative mzID, exactly as the Smuggle event path does with "myResources-N". This is NOT a
        // Smuggle play, so there is deliberately no CR 8.22 top-of-deck replacement — the card simply
        // leaves the resource zone, which is why the guard asserts the resource count DROPS.
        $playerID = $caster;
        $srcMz = '';
        $hand = GetHand($caster);
        for ($i = 0; $i < count($hand); $i++) {
            if ($hand[$i] !== null && empty($hand[$i]->removed) && SWUObjectTitle($hand[$i]) === "It's Worse") { $srcMz = "myHand-{$i}"; break; }
        }
        if ($srcMz === '') {
            $res = GetResources($caster);
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i] !== null && empty($res[$i]->removed) && SWUObjectTitle($res[$i]) === "It's Worse") { $srcMz = "myResources-{$i}"; break; }
            }
        }
        if ($srcMz === '') return;                       // no copy in either zone
        $fromRes = (strpos($srcMz, 'myResources-') === 0);
        DecisionQueueController::AddDecision($caster, "YESNO", "-", 1,
            tooltip: $fromRes ? "Play_It's_Worse_from_your_resources_for_free?" : "Play_It's_Worse_from_your_hand_for_free?");
        DecisionQueueController::AddDecision($caster, "CUSTOM", "LOF_222#2|{$srcMz}", 1);
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
