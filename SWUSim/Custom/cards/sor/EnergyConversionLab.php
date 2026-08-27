<?php
// SOR_022
// Energy Conversion Lab - [Command] - HP 25
// Text: 
// Epic Action: Play a unit that costs 6 resources or less from your hand. Give it AMBUSH for this phase.

// ── SOR_022 Energy Conversion Lab — Base Epic Action ────────────────────────
// Resolves the unit-selection MZCHOOSE queued by BaseAbilities.php.
// Injects AMBUSH into $gPendingEntryEffects keyed by the next UniqueID so that
// ActivateCard picks it up before running keyword checks (Ambush, Shielded ordering).
$customDQHandlers["SOR_022#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gUniqueIDCounter, $gPendingEntryEffects;
    $savedPID = $playerID;
    $playerID = intval($player);
    $chosen   = $lastDecision; // mzID from MZCHOOSE
    $obj      = GetZoneObject($chosen);
    if ($obj === null) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }
    // Tag the next UniqueID with the SOR_022 grant token (registry: Ambush, this phase) so
    // ActivateCard applies it on entry. The CardID token gives the Active Effects UI its provenance.
    $nextUID = intval($gUniqueIDCounter) + 1;
    $gPendingEntryEffects[$nextUID] = ['SOR_022'];
    // ActivateCard pays normal cost (printed + aspect penalty) and handles all
    // keyword checks, Shielded/Ambush ordering, WhenPlayed triggers, and AfterAction.
    ActivateCard($player, $chosen, false);
    $playerID = $savedPID;
};

// SOR_022 Energy Conversion Lab — Epic Action: Play a unit costing ≤6 from hand; give it AMBUSH.
// Eligibility uses printed cost (no modifiers per official ruling). Payment is normal (printed + aspect penalty).
// AMBUSH is injected via $gPendingEntryEffects keyed by UniqueID before ActivateCard checks keywords.
$baseAbilities["SOR_022"] = function($player) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = $player;
    // Eligibility: printed cost ≤6 (official ruling) AND affordable at the EFFECTIVE cost with full
    // payment capacity (SWUHandPlayablesAtDiscount → ready resources + Credits + Vuutun Droids).
    // Without the affordability half, picking an unpayable unit burned the once-per-game Epic Action
    // on a silent no-op (the epic-preservation family; candidate #5 fix 2026-08-14).
    $eligible  = array_values(array_filter(SWUHandPlayablesAtDiscount($player, ["Unit"], 0), function($mzID) {
        $obj = GetZoneObject($mzID);
        return $obj !== null && intval(CardCost($obj->CardID)) <= 6;
    }));
    $playerID = $savedPID;
    if (empty($eligible)) { SWUAfterAction($player); return; }
    $targetStr = implode("&", $eligible);
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, "Choose_a_unit_costing_6_or_less");
    // dontSkipOnPass:1 — the decline path calls SWUAfterAction(), i.e. it is what CLOSES the epic action.
    // A "PASS" decline (every real client decline is a "PASS") skipped it, so the Epic Action was spent
    // and the player still held the turn. Same shape as JTL_003 Lando, measured 2026-08-27.
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_022#0", 1, dontSkipOnPass: 1);
};
