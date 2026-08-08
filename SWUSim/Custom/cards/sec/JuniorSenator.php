<?php
// SEC_200
// Cost 2 - Junior Senator - [Cunning,Heroism] - Power 3 - HP 2
// Text: When Played: You may return an upgrade that costs 3 or less to its owner's hand.

// SEC_200 Junior Senator — When Played: you may return an upgrade that costs 3 or less to its owner's
// hand. Offer hosts carrying a non-token upgrade of cost ≤3; return the first such upgrade on the pick.
$whenPlayedAbilities["SEC_200:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (GetUpgradesOnUnit($o) as $up) {
            // Any upgrade costing 3 or less is a legal target — INCLUDING a token upgrade (Experience, cost
            // 0). A token can't move to a non-play zone, so "returning" it DEFEATS it instead (handled in #0).
            $ucid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
            if ($ucid !== '' && intval(CardCost($ucid)) <= 3) { $hosts[] = $mz; break; }
        }
    }
    if (empty($hosts)) return;
    SWUQueueMayChooseTarget(intval($player), $hosts, "Return_an_upgrade_(cost_3_or_less)_to_owner's_hand?", "Choose_a_unit", "SEC_200#0");
};

$customDQHandlers["SEC_200#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o) || !is_array($o->Subcards ?? null)) return;
    foreach ($o->Subcards as $i => $sub) {
        $ucid  = is_array($sub) ? ($sub['CardID'] ?? '')  : ($sub->CardID ?? '');
        $isRem = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
        if ($isRem || $ucid === '' || intval(CardCost($ucid)) > 3) continue;
        if (stripos((string)CardType($ucid), 'token') !== false) {   // token upgrade (Experience/Shield)
            // CR: a token that would leave play (move to hand) is defeated and ceases to exist instead.
            // ⚠ This bespoke removal must still run the protections the canonical return path
            // (SWUReturnUpgradeToHand) applies — SEC_061 Willrow Hood blocks an ENEMY ability from
            // defeating OR returning his lone friendly upgrade, and a token upgrade is no exception.
            // Splicing directly skipped that check, so a Shield on Willrow was removable by an enemy.
            $tOwner = is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0);
            if ($tOwner <= 0) $tOwner = intval($o->Controller ?? $o->Owner ?? 0);
            $tCtrl  = is_array($sub) ? intval($sub['Controller'] ?? $tOwner) : intval($sub->Controller ?? $tOwner);
            if (_SWUWillrowProtectsUpgrade($o, $tCtrl, intval($player))) break;   // SEC_061 protection
            array_splice($o->Subcards, $i, 1);
        } else {
            SWUReturnUpgradeToHand($lastDecision, $ucid, intval($player));
        }
        break;
    }
};
