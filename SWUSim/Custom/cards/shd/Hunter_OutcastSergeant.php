<?php
// SHD_009
// Cost 7 - Hunter - Outcast Sergeant - [Command,Heroism] - Power 5 - HP 8
// Text: Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly unique unit, return the resource to its owner's hand and put the top card of your deck into play as a resource.
// DeployText: Overwhelm / On Attack: You may reveal a resource you control. If it shares a name with a friendly unique unit, return the resource to its owner's hand and put the top card of your deck into play as a resource.
// Epic Action: If you control 7 or more resources, deploy this leader.

$leaderAbilities["SHD_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUPayInlineAbilityCost($player, 1)) { SWUAfterAction($player); return; }
    $targets = HunterOutcastSergeantResourceTargets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Reveal_a_resource_you_control", "SHD_009#front");
    SWUQueueAfterAction($player);
};

$customDQHandlers["SHD_009#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    HunterOutcastSergeantResolve(intval($player), $lastDecision);
};

$onAttackAbilities["SHD_009:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = HunterOutcastSergeantResourceTargets(intval($player));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Reveal_a_resource_you_control?", "Choose_a_resource", "SHD_009#front");
};

// ── SHD_009 Hunter ─────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly
// unique unit, return the resource to its owner's hand and put the top card of your deck into play as a
// resource. Deployed: Overwhelm (keyword, auto) + On Attack: same, but "You may".
function HunterOutcastSergeantResourceTargets(int $player): array {
    $res = GetResources($player);
    $t = [];
    for ($i = 0, $pos = 0; $i < count($res); $i++) {
        if (!empty($res[$i]->removed)) continue;
        $t[] = "myResources-{$pos}"; $pos++;
    }
    return $t;
}

function HunterOutcastSergeantResolve(int $player, string $resMz): void {
    global $playerID; $playerID = intval($player);
    $res = GetZoneObject($resMz);
    if (SWUObjGone($res)) return;
    $name = SWUObjectTitle($res);
    $match = false;
    foreach (SWUFriendlyUnitObjects($player) as $u) {
        if (empty($u->removed) && CardUnique($u->CardID ?? '') && SWUObjectTitle($u) === $name) { $match = true; break; }
    }
    if (!$match) return;   // reveal only; no name-match with a friendly unique unit → nothing happens
    if (!SWUReturnResourceToHand($player, $resMz)) return;
    DecisionQueueController::CleanupRemovedCards();
    $deck = &GetDeck($player);
    for ($i = 0; $i < count($deck); $i++) {
        if (!empty($deck[$i]->removed)) continue;
        $top = $deck[$i]->CardID; $deck[$i]->Remove();
        AddResources($player, $top, 0, $player, $player);   // enters exhausted
        AddGameLogEntry('RESOURCE', 'P' . $player . ' put a card into play as a resource');
        break;
    }
    DecisionQueueController::CleanupRemovedCards();
}
