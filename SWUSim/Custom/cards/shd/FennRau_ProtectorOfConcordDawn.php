<?php
// SHD_067
// Cost 6 - Fenn Rau - Protector of Concord Dawn - [Vigilance] - Power 5 - HP 6
// Text: When Played: You may play an upgrade from your hand. It costs 2 resources less. / When you play an upgrade on this unit: Give an enemy unit -2/-2 for this phase.

// ─── SHD_067 Fenn Rau ─────────────────────────────────────────────────────────
// When Played: You may play an upgrade from your hand. It costs 2 resources less. (The reactive half —
// "when you play an upgrade on this unit, give an enemy unit -2/-2" — is wired in
// CollectWhenPlayedAsUpgradeTriggers + DispatchTrigger case 'SHD_067'.)
$whenPlayedAbilities["SHD_067:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();  // Fenn Rau still lingers in hand (removed) — compact first
    $targets = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && strpos(CardType($o->CardID ?? '') ?? '', 'Upgrade') !== false) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Play_an_upgrade_from_your_hand_(costs_2_less)?", "Choose_an_upgrade", "SHD_067#play");
};

$customDQHandlers["SHD_067#play"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains((string)$lastDecision, '-')) return;
    $up = GetZoneObject($lastDecision);
    if (SWUObjGone($up)) return;
    $cardID = $up->CardID;
    $hosts  = SWUGetUpgradeValidTargets(intval($player), $cardID);
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_to_attach_the_upgrade_to", "SHD_067#attach|{$cardID}|{$lastDecision}");
};

$customDQHandlers["SHD_067#attach"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    if ($cardID === '' || $upMz === '' || $hostMz === '' || !str_contains((string)$hostMz, '-')) return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 2 /*prepaid: -2 cost*/, false, false, true);
};
