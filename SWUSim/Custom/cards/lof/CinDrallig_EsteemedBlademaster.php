<?php
// LOF_150
// Cost 8 - Cin Drallig - Esteemed Blademaster - [Aggression,Heroism] - Power 5 - HP 6
// Text: When Played: You may play a Lightsaber upgrade from your hand for free on this unit. If you do, ready him.

// LOF_150 Cin Drallig — When Played: You may play a Lightsaber upgrade from your hand for free on this
// unit. If you do, ready him. Offers only Lightsabers that could legally attach to Cin Drallig (host-
// restriction-checked via SWUGetUpgradeValidTargets); attaches free onto him, then readies him.
$whenPlayedAbilities["LOF_150:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $cin = GetZoneObject($mzID);
    if (SWUObjGone($cin)) return;
    $cinUID = intval($cin->UniqueID ?? -1);
    // Compact the hand first: Cin Drallig itself is still in the hand array (removed flag) at this point,
    // and it gets cleaned up before the player answers — which would shift the offered myHand-N indices.
    DecisionQueueController::CleanupRemovedCards();
    $sabers = [];
    $hand = GetHand($player);
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i];
        if (SWUObjGone($c)) continue;
        $cid = $c->CardID ?? '';
        if (CardType($cid) !== 'Upgrade' || !HasTrait($cid, 'Lightsaber')) continue;
        if (in_array($mzID, SWUGetUpgradeValidTargets(intval($player), $cid), true)) $sabers[] = "myHand-{$i}";
    }
    if (empty($sabers)) return;
    SWUQueueMayChooseTarget(intval($player), $sabers, "Play_a_Lightsaber_upgrade_free_on_Cin_Drallig_(then_ready_him)?", "Choose_a_Lightsaber", "LOF_150#0|{$cinUID}");
};

$customDQHandlers["LOF_150#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $cinUID = intval($parts[0] ?? -1);
    $hostMz = SWUFindMzByUID($cinUID);
    if ($hostMz === null || $hostMz === '') return;
    $up = GetZoneObject($lastDecision);
    if (SWUObjGone($up)) return;
    _SWUFinalizeUpgradeAttach(intval($player), $up->CardID ?? '', $lastDecision, $hostMz, 0, true, false); // free
    // "If you do, ready him."
    $hostMz = SWUFindMzByUID($cinUID);
    if ($hostMz !== null && $hostMz !== '') OnReadyCard(intval($player), $hostMz);
};
