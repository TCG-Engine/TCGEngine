<?php
// SEC_008
// Cost 4 - Bail Organa - Doing Everything He Can - [Command,Heroism] - Power 3 - HP 3
// Text: Action [1 resource, Exhaust]: If a friendly unit was defeated this phase, return a friendly resource to its owner's hand. If you do, put the top card of your deck into play as a resource. / Action [Exhaust, discard 2 cards from your hand]: If you control 4 or more resources, deploy this leader.
// DeployText: When you play a card from your resources: Heal 1 damage from your base.

// ── SEC_008 Bail Organa ───────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If a friendly unit was defeated this phase, return a friendly resource to
// its owner's hand. If you do, put the top card of your deck into play as a resource.
$leaderAbilities["SEC_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_FRIENDLY_DEFEATED') <= 0) { SWUAfterAction($player); return; } // condition false
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $targets = SWUFriendlyResourceMzIDs(intval($player));
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_resource_to_its_owner's_hand", "SEC_008#0");
};

$customDQHandlers["SEC_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || !str_contains($mz, '-')) { SWUAfterAction(intval($player)); return; }
    if (!SWUReturnResourceToHand(intval($player), $mz)) { SWUAfterAction(intval($player)); return; }
    DecisionQueueController::CleanupRemovedCards();
    // "If you do, put the top card of your deck into play as a resource." (enters exhausted)
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < count($deck); $i++) {
        if (isset($deck[$i]->removed) && $deck[$i]->removed) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources(intval($player), $top, 0, intval($player), intval($player)); // Status 0 = exhausted
        AddGameLogEntry('RESOURCE', 'P' . intval($player) . ' put a card into play as a resource');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
    SWUAfterAction(intval($player));
};

// SEC_008 Bail Organa deploy cost — discard the 2 chosen hand cards, then commit the deploy (re-enter
// SWUDeployLeader with the SWU_SEC008_DEPLOY_PAID flag set so it skips the discard-cost and commits).
$customDQHandlers["SEC_008#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $leaderIndex = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision)) return; // cancelled → no deploy, no discard
    $mzs = array_values(array_filter(explode('&', $lastDecision), fn($m) => $m !== '' && $m !== '-' && $m !== 'PASS'));
    if (count($mzs) < 2) return; // must discard exactly 2 to pay the cost
    // discard highest hand index first so earlier indices don't shift out from under later picks
    usort($mzs, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
    foreach ($mzs as $mz) DoDiscardCard(intval($player), $mz);
    DecisionQueueController::CleanupRemovedCards();
    AddGlobalEffects(intval($player), 'SWU_SEC008_DEPLOY_PAID');
    SWUDeployLeader(intval($player), 'Unit', '', $leaderIndex);
};
