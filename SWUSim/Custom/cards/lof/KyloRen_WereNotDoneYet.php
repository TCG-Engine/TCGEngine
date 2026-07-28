<?php
// LOF_001
// Cost 7 - Kylo Ren - We're Not Done Yet - [Vigilance,Villainy] - Power 5 - HP 5
// Text: Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this way, draw a card.
// DeployText: Sentinel / When Deployed: Play any number of upgrades from your discard pile on this unit (one at a time, paying their costs).
// Epic Action: If you control 7 or more resources, deploy this leader.

// LOF_001 Kylo Ren — Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this
// way, draw a card.
$leaderAbilities["LOF_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = GetHand($player); $cards = [];
    for ($i = 0; $i < count($hand); $i++) {
        if ($hand[$i] !== null && empty($hand[$i]->removed)) $cards[] = "myHand-{$i}";
    }
    if (empty($cards)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $cards, "Discard_a_card_(draw_if_it's_an_upgrade)", "LOF_001#0");
};

$customDQHandlers["LOF_001#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            $cardID = $o->CardID; $isUpgrade = (CardType($cardID) === 'Upgrade');
            $o->removed = true; SWUAddToDiscard(intval($player), $cardID, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            if ($isUpgrade) DoDrawCard(intval($player), 1);
        }
    }
    SWUAfterAction(intval($player));
};

// LOF_001 Kylo Ren (deployed) — When Deployed: Play any number of upgrades from your discard pile
// on THIS unit (one at a time, paying their costs). Self-re-queuing loop: offer playable discard
// upgrades attachable to Kylo → on a pick, pay full cost + attach onto Kylo, then re-offer;
// decline or none-left stops. The deploy action owns the After Action (entry trigger).
$whenPlayedAbilities["LOF_001:0"] = function($player, $mzID) {
    $host = GetZoneObject($mzID);
    $uid  = SWUObjUID($host);
    if ($uid < 0) return;
    KyloRenWereNotDoneYetOffer(intval($player), $uid);
};

$customDQHandlers["LOF_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $upMz = $lastDecision ?? '';
    if ($upMz === '' || $upMz === '-' || $upMz === 'PASS') return;   // declined → stop the loop
    $up = str_contains($upMz, '-') ? GetZoneObject($upMz) : null;
    if (SWUObjGone($up)) return;
    $hostUID = intval($parts[0] ?? -1);
    $hostMz  = SWUFindMzByUID($hostUID);
    if ($hostMz === null) return;
    // pay full cost + attach onto Kylo; suppress the attach's After Action (the deploy owns it).
    _SWUFinalizeUpgradeAttach(intval($player), $up->CardID, $upMz, $hostMz, 0, false, false, true);
    KyloRenWereNotDoneYetOffer(intval($player), $hostUID);   // re-offer (any number)
};

function KyloRenWereNotDoneYetOffer(int $player, int $hostUID): void {
    global $playerID; $playerID = $player;
    $hostMz = SWUFindMzByUID($hostUID);
    if ($hostMz === null) return;
    $hostObj = GetZoneObject($hostMz);
    if (SWUObjGone($hostObj)) return;
    $ready = SWUResourceCount($player, readyOnly: true);
    $offer = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
        if (!in_array($hostMz, SWUGetUpgradeValidTargets($player, $o->CardID), true)) continue;   // attachable to Kylo
        if (SWUComputePlayCost($player, $o, $hostObj) <= $ready) $offer[] = $mz;                   // affordable at full cost
    }
    if (empty($offer)) return;
    SWUQueueMayChooseTarget($player, $offer, "Play_an_upgrade_from_discard_on_Kylo?",
        "Choose_an_upgrade_to_play_on_Kylo", "LOF_001#1|{$hostUID}");
}
