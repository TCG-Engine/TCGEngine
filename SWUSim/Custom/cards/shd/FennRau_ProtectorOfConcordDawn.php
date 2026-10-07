<?php
// SHD_067
// Cost 6 - Fenn Rau - Protector of Concord Dawn - [Vigilance] - Power 5 - HP 6
// Text: When Played: You may play an upgrade from your hand. It costs 2 resources less. / When you play an upgrade on this unit: Give an enemy unit -2/-2 for this phase.

// ─── SHD_067 Fenn Rau ─────────────────────────────────────────────────────────
// When Played: You may play an upgrade from your hand. It costs 2 resources less. (The reactive half —
// "when you play an upgrade on this unit, give an enemy unit -2/-2" — is wired in
// CollectWhenPlayedAsUpgradeTriggers + DispatchTrigger case 'SHD_067'.)
// "Play an upgrade" includes PILOTS: a Piloting unit played through a "play an upgrade" ability can only be played as
// an upgrade (Piloting ruling, 03/06/2025; The Armorer's "play an upgrade" ruling names Pilots outright). The offer
// used to filter the hand by printed type 'Upgrade', and a Pilot is printed as a Unit, so it was never offered (found
// 2026-10-06 with the ASH_001 fix). A Pilot is offered only when it has a friendly Vehicle with room that it can pay
// for at its Piloting cost, 2 less — the same check that picks its hosts below.
function _SWUShd067PilotHosts(int $player, string $cid): array {
    return SWUGetPilotValidTargets($player, $cid, false, 2);
}

$whenPlayedAbilities["SHD_067:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();  // Fenn Rau still lingers in hand (removed) — compact first
    $targets = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        $cid = (string)($o->CardID ?? '');
        if (CardPilotingCost($cid) !== null) {
            if (!empty(_SWUShd067PilotHosts(intval($player), $cid))) $targets[] = $mz;
        } elseif (strpos(CardType($cid) ?? '', 'Upgrade') !== false) {
            $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Play_an_upgrade_from_your_hand_(costs_2_less)?", "Choose_an_upgrade", "SHD_067#play");
};

$customDQHandlers["SHD_067#play"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains((string)$lastDecision, '-')) return;
    $up = GetZoneObject($lastDecision);
    if (SWUObjGone($up)) return;
    $cardID  = $up->CardID;
    $isPilot = CardPilotingCost($cardID) !== null;   // played as an upgrade = as a Pilot, priced at its Piloting cost
    $hosts   = $isPilot ? _SWUShd067PilotHosts(intval($player), $cardID) : SWUGetUpgradeValidTargets(intval($player), $cardID);
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_to_attach_the_upgrade_to", "SHD_067#attach|{$cardID}|{$lastDecision}|" . ($isPilot ? '1' : '0'));
};

$customDQHandlers["SHD_067#attach"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID  = $parts[0] ?? '';
    $upMz    = $parts[1] ?? '';
    $isPilot = ($parts[2] ?? '0') === '1';
    $hostMz  = $lastDecision ?? '';
    if ($cardID === '' || $upMz === '' || $hostMz === '' || !str_contains((string)$hostMz, '-')) return;
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 2 /*prepaid: -2 cost*/, false, $isPilot, true);
};
