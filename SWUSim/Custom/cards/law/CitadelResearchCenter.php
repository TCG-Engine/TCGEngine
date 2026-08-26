<?php
// LAW_029
// Citadel Research Center - [Cunning] - HP 26
// Text: Epic Action [1 resource]: Return a friendly resource to its owner's hand. If you do, resource the top card of your deck.

// LAW_029 Citadel Research Center — return the chosen resource to hand, then resource the top of deck.
$customDQHandlers["LAW_029#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || !str_contains($mz, '-')) return;
    if (!SWUReturnResourceToHand(intval($player), $mz)) return;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < count($deck); $i++) {
        if (isset($deck[$i]->removed) && $deck[$i]->removed) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources(intval($player), $top, 0, intval($player), intval($player));   // enters exhausted
        AddGameLogEntry('RESOURCE', 'P' . intval($player) . ' resourced the top card of their deck');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
};

// LAW_029 Citadel Research Center — Epic Action [1 resource]: Return a friendly resource to its owner's
// hand. If you do, resource the top card of your deck.
// The "[1 resource]" cost is paid centrally by SWUBaseAction through the Credit/Droid alt-pay funnel
// (CR 3.13), which also gates on a real resource existing to RETURN — a Credit token sits in the
// resource zone but is not a resource, so it is never a legal choice below.
$baseAbilities["LAW_029"] = function($player) {
    global $playerID; $playerID = intval($player);
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $targets = SWUFriendlyResourceMzIDs(intval($player), fn($o) => !SWUIsCreditToken($o->CardID ?? ''));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Return_a_friendly_resource_to_its_owner's_hand", "LAW_029#0");
    SWUQueueAfterAction($player);
};
