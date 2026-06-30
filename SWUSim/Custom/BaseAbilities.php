<?php
global $baseAbilities;
$baseAbilities = [];

// Repeatable base Actions (NOT once-per-game Epic Actions). Maps base CardID → per-GAME use budget,
// tracked in the base's NumUses field. SWUBaseAction gates/consumes via NumUses (instead of the
// once-per-game EpicActionUsed flag), and SWUResetAllNumUses EXEMPTS these bases from the per-round
// refill so the budget spans the whole game.
global $baseActionNumUses;
$baseActionNumUses = [];

// LOF_022 Mystic Monastery — "Action: The Force is with you (create your Force token). Use this ability
// no more than 3 times each game." Repeatable base Action (no Epic Action / EpicActionUsed).
$baseAbilities["LOF_022"] = function($player) {
    TheForceIsWithYou($player);
    SWUAfterAction($player);
};
$baseActionNumUses["LOF_022"] = 3;

// Repeatable base Actions whose only limit is paying a card-cost (not a per-game NumUses budget and not
// the once-per-game Epic Action). SWUBaseAction runs these every time without touching EpicActionUsed;
// the ability closure enforces its own cost and availability is gated in SWUComputeActionsData.
global $baseActionRepeatable;
$baseActionRepeatable = [];

// LOF_028 Tomb of Eilram — "Action [exhaust a friendly unit]: The Force is with you (create your Force
// token)." Repeatable; the cost is exhausting one ready friendly unit (any arena, incl. a deployed
// leader unit). With no ready friendly unit the action is unavailable.
$baseActionRepeatable["LOF_028"] = true;
$baseAbilities["LOF_028"] = function($player) {
    $targets = [];
    foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Status ?? 0) === 1) $targets[] = $mz; // ready only — can't exhaust an exhausted unit
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Exhaust_a_friendly_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_028#0", 1);
};

// SOR_022 Energy Conversion Lab — Epic Action: Play a unit costing ≤6 from hand; give it AMBUSH.
// Eligibility uses printed cost (no modifiers per official ruling). Payment is normal (printed + aspect penalty).
// AMBUSH is injected via $gPendingEntryEffects keyed by UniqueID before ActivateCard checks keywords.
$baseAbilities["SOR_022"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $handUnits = ZoneSearch("myHand", ["Unit"]);
    $eligible  = array_values(array_filter($handUnits, function($mzID) {
        $obj = GetZoneObject($mzID);
        return $obj !== null && intval(CardCost($obj->CardID)) <= 6;
    }));
    $playerID = $savedPID;
    if (empty($eligible)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $eligible);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_unit_costing_6_or_less");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_022#0", 1);
};

// SOR_019 Security Complex — Epic Action: Give a Shield token to a non-leader unit.
// ZoneSearch with type ["Unit"] already excludes deployed leaders (CardType="Leader").
// SOR_028 Jedha City — Epic Action: Give a non-leader unit -4/-0 for this phase.
$baseAbilities["SOR_028"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_non-leader_unit_-4/-0_for_this_phase", "APPLY_PHASE_DEBUFF|4|0|SOR_028");
    SWUQueueAfterAction($player);
};

// SOR_025 Tarkintown — Epic Action: Deal 3 damage to a damaged non-leader unit.
// ["Unit","Token Unit"] excludes deployed leaders; filter to Damage > 0.
$baseAbilities["SOR_025"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    $targets = [];
    foreach (array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (intval($o->Damage ?? 0) > 0) $targets[] = $mz;
    }
    $playerID = $savedPID;
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_3_to_a_damaged_non-leader_unit", "DEAL_UNIT_DAMAGE|3");
    SWUQueueAfterAction($player);
};

$baseAbilities["SOR_019"] = function($player) {
    $targets = array_merge(
        ZoneSearch("myGroundArena", ["Unit"]),
        ZoneSearch("theirGroundArena", ["Unit"]),
        ZoneSearch("mySpaceArena", ["Unit"]),
        ZoneSearch("theirSpaceArena", ["Unit"])
    );
    if (empty($targets)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Choose_a_non-leader_unit_to_shield");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_019#0", 1);
};

// ── LAW common bases (Vigilance 020/021, Command 022/024, Aggression 025/027, Cunning 028/030) ──
// Epic Action: "Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or
// Cunning aspect penalties." Offer every hand card affordable after waiving one battlefield-aspect
// pip (-2); LAW_COMMONBASE_PLAY then plays the chosen card with that discount. The framework already
// set EpicActionUsed; the handler calls SWUAfterAction.
$lawCommonBaseEpic = function($player) {
    global $playerID; $playerID = intval($player);
    $ready   = SWUResourceCount($player, readyOnly: true);
    $hand    = GetHand($player);
    $targets = [];
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i];
        if ($c === null || !empty($c->removed)) continue;
        $cid = $c->CardID;
        if (_SWUCantPlayFromHand($cid)) continue;            // SEC_053-style "can't be played from hand"
        $discount = min(_SWUCommonBaseWaivePenalty($player, $cid), SWUAspectPenalty($player, $cid));
        $eff      = max(0, SWUComputePlayCost($player, $c) - $discount);
        if ($ready >= $eff) $targets[] = "myHand-{$i}";
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets,
        "Play_a_card_(ignore_1_Vigilance/Command/Aggression/Cunning_aspect_penalty)", "LAW_COMMONBASE_PLAY");
};
foreach (['LAW_020','LAW_021','LAW_022','LAW_024','LAW_025','LAW_027','LAW_028','LAW_030'] as $_lawBase) {
    $baseAbilities[$_lawBase] = $lawCommonBaseEpic;
}

// ── LAW non-common Epic Action bases (Phase 7) ────────────────────────────────────────────────────
// The framework consumes EpicActionUsed before the closure; each closure pays its own card-cost and
// ends via SWUAfterAction (or a trailing SWU_AFTER_ACTION / #N continuation that does).

// LAW_019 Alliance Outpost — Epic Action [defeat a friendly token]: Give an Experience or Shield token
// to a unit, or create a Credit token.
$baseAbilities["LAW_019"] = function($player) {
    global $playerID; $playerID = intval($player);
    $tokens = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, ["Token Unit"]) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) { SWUAfterAction($player); return; }   // can't pay the cost
    SWUQueueChooseTarget(intval($player), $tokens, "Defeat_a_friendly_token_(cost)", "LAW_019#0");
};

// LAW_023 Great Pit of Carkoon — Epic Action [discard a unit from your hand]: Search your deck for a
// card named The Sarlacc of Carkoon (LAW_163), reveal it, and draw it.
$baseAbilities["LAW_023"] = function($player) {
    global $playerID; $playerID = intval($player);
    $handUnits = array_values(ZoneSearch("myHand", ["Unit"]));
    if (empty($handUnits)) { SWUAfterAction($player); return; }   // can't pay the cost
    SWUQueueChooseTarget(intval($player), $handUnits, "Discard_a_unit_from_your_hand_(cost)", "LAW_023#0");
};

// LAW_026 Shipbreaking Yard — Epic Action: Discard 3 cards from your deck. You may return a card
// discarded this way to the top of your deck.
$baseAbilities["LAW_026"] = function($player) {
    global $playerID; $playerID = intval($player);
    $milledMz = [];
    for ($i = 0; $i < 3; $i++) {
        $c = SWUMillTopCard(intval($player));
        if ($c === null) break;
        $disc = array_values(ZoneSearch("myDiscard"));
        if (!empty($disc)) $milledMz[] = end($disc);  // the just-milled card is the newest discard entry
    }
    if (empty($milledMz)) { SWUAfterAction($player); return; }
    SWUQueueMayChooseTarget(intval($player), $milledMz, "Return_a_discarded_card_to_the_top_of_your_deck?", "Choose_a_card", "LAW_026#0");
    SWUQueueAfterAction($player);
};

// LAW_029 Citadel Research Center — Epic Action [1 resource]: Return a friendly resource to its owner's
// hand. If you do, resource the top card of your deck.
$baseAbilities["LAW_029"] = function($player) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), readyOnly: true) < 1) { SWUAfterAction($player); return; }
    if (!SWUExhaustResources(intval($player), 1)) { SWUAfterAction($player); return; }   // pay [1 resource]
    $res = &GetResources(intval($player));
    $targets = [];
    for ($i = 0, $idx = 0; $i < count($res); $i++) {
        if (isset($res[$i]->removed) && $res[$i]->removed) continue;
        $targets[] = "myResources-{$idx}"; $idx++;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Return_a_friendly_resource_to_its_owner's_hand", "LAW_029#0");
    SWUQueueAfterAction($player);
};
