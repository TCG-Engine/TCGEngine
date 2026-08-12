<?php
global $baseAbilities;
$baseAbilities = [];

// Base Epic Actions that carry a printed [N resource] cost. SWUBaseAction checks this BEFORE consuming the
// once-per-game EpicActionUsed flag: an unaffordable Epic is "not selectable" (CR), so it must be a true
// no-op that leaves the Epic AVAILABLE — not consumed by a failed activation. Maps base CardID → resources.
global $baseEpicResourceCosts;
$baseEpicResourceCosts = [
    'LAW_029' => 1, // Citadel Research Center — Epic Action [1 resource].
];

// Repeatable base Actions (NOT once-per-game Epic Actions). Maps base CardID → per-GAME use budget,
// tracked in the base's NumUses field. SWUBaseAction gates/consumes via NumUses (instead of the
// once-per-game EpicActionUsed flag), and SWUResetAllNumUses EXEMPTS these bases from the per-round
// refill so the budget spans the whole game.
global $baseActionNumUses;
$baseActionNumUses = [];




// Repeatable base Actions whose only limit is paying a card-cost (not a per-game NumUses budget and not
// the once-per-game Epic Action). SWUBaseAction runs these every time without touching EpicActionUsed;
// the ability closure enforces its own cost and availability is gated in SWUComputeActionsData.
global $baseActionRepeatable;
$baseActionRepeatable = [];












// ── LAW common bases (Vigilance 020/021, Command 022/024, Aggression 025/027, Cunning 028/030) ──
// Epic Action: "Play a card from your hand, ignoring 1 of its Vigilance, Command, Aggression, or
// Cunning aspect penalties." Offer every hand card affordable after waiving one battlefield-aspect
// pip (-2); LAW_COMMONBASE_PLAY then plays the chosen card with that discount. The framework already
// set EpicActionUsed; the handler calls SWUAfterAction.
$lawCommonBaseEpic = function($player) {
    global $playerID; $playerID = intval($player);
    $ready   = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a play cost (CR 3.13)
    $hand    = GetHand($player);
    $targets = [];
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i];
        if (SWUObjGone($c)) continue;
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
    $tokens = _SWULaw019FriendlyTokens(intval($player));
    // Unreachable in normal play: _SWUBaseOwnAction now gates on the same predicate, so an unpayable
    // Epic is never offered and never reaches here. Kept as a belt-and-braces bail-out.
    if (empty($tokens)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $tokens, "Defeat_a_friendly_token_(cost)", "LAW_019#0");
};







// ── TS26 bases — Epic Actions that scale with friendly leader units ──────────────
global $customDQHandlers;














