<?php
// SHD_009
// Cost 7 - Hunter - Outcast Sergeant - [Command,Heroism] - Power 5 - HP 8
// Text: Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly unique unit, return the resource to its owner's hand and put the top card of your deck into play as a resource.
// DeployText: Overwhelm / On Attack: You may reveal a resource you control. If it shares a name with a friendly unique unit, return the resource to its owner's hand and put the top card of your deck into play as a resource.
// Epic Action: If you control 7 or more resources, deploy this leader.

$leaderAbilities["SHD_009"] = function(int $player): void {
    global $playerID; $playerID = $player;
    // WHICH ready resource pays the [1 resource] is the payer's choice, and for Hunter it can decide the line: pay with the
    // resource you are about to reveal and return, and the others stay ready to replay it (bug report P-36). Asked only when it
    // can matter (HunterOutcastSergeantPayChoices); otherwise paid as every cost is, without a question. Owner ruling
    // 2026-11-22: Hunter only — every other cost still pays with the first ready resources.
    $choices = HunterOutcastSergeantPayChoices($player);
    if (!empty($choices)) {
        SWUQueueChooseTarget($player, $choices, "Choose_a_resource_to_exhaust_(cost)", "SHD_009#pay");
        return;
    }
    if (!SWUPayInlineAbilityCost($player, 1)) { SWUAfterAction($player); return; }
    HunterOutcastSergeantOfferReveal($player);
};

// The [1 resource] was paid by exhausting the chosen ready resource; the reveal follows as it always did.
$customDQHandlers["SHD_009#pay"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $res = SWUDecisionDeclined($lastDecision) ? null : GetZoneObject((string)$lastDecision);
    if (SWUObjGone($res) || intval($res->Status ?? 0) !== 1) {
        // Defensive: the chosen resource is gone or no longer ready — pay the ordinary way instead.
        if (!SWUPayInlineAbilityCost(intval($player), 1)) { SWUAfterAction(intval($player)); return; }
    } else {
        $res->Status = 0;
    }
    HunterOutcastSergeantOfferReveal(intval($player));
};

function HunterOutcastSergeantOfferReveal(int $player): void {
    $targets = HunterOutcastSergeantResourceTargets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Reveal_a_resource_you_control", "SHD_009#front");
    SWUQueueAfterAction($player);
}

// The ready resources the player may pick to pay Hunter's [1 resource] — or [] when the pick cannot matter, so no question is
// asked: fewer than two ready resources (nothing to choose), or no READY resource shares a name with a friendly unique unit
// (only such a resource can be revealed and returned, so only then does paying with it keep the others ready). Credit tokens
// are not resources and are never offered here (an unaffordable-by-resources cost still auto-spends one, as before).
function HunterOutcastSergeantPayChoices(int $player): array {
    $ready = []; $anyMatch = false;
    $res = GetResources($player);
    for ($i = 0; $i < count($res); $i++) {
        if (!empty($res[$i]->removed) || SWUIsCreditToken($res[$i]->CardID ?? '') || intval($res[$i]->Status ?? 0) !== 1) continue;
        $ready[] = "myResources-{$i}";
        if (!$anyMatch && HunterOutcastSergeantMatchesUnit($player, $res[$i])) $anyMatch = true;
    }
    return (count($ready) >= 2 && $anyMatch) ? $ready : [];
}

// Does this resource share a name with a friendly unique unit (the reveal's condition)?
function HunterOutcastSergeantMatchesUnit(int $player, $resObj): bool {
    $name = SWUObjectTitle($resObj);
    foreach (SWUFriendlyUnitObjects($player) as $u) {
        if (empty($u->removed) && CardUnique($u->CardID ?? '') && SWUObjectTitle($u) === $name) return true;
    }
    return false;
}

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
    if (!HunterOutcastSergeantMatchesUnit($player, $res)) return;   // reveal only; no name-match with a friendly unique unit → nothing happens
    if (!SWUReturnResourceToHand($player, $resMz)) return;
    DecisionQueueController::CleanupRemovedCards();
    SWUResourceTopOfDeck($player);   // the top card of the deck, exhausted (SSOT #3 funnel)
    DecisionQueueController::CleanupRemovedCards();
}
