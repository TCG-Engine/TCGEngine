<?php
// LAW_245
// Cost 1 - Salvaged Materials - [Cunning]
// Text: Play an Item upgrade from your discard pile. It costs 3 resources less. At the start of the next regroup phase, defeat it.

// LAW_245 Salvaged Materials — step 0: upgrade chosen from discard; pick its host.
$customDQHandlers["LAW_245#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $up = GetZoneObject($lastDecision);
    if (SWUObjGone($up)) return;
    $cardID = $up->CardID;
    $hosts  = SWUGetUpgradeValidTargets(intval($player), $cardID);
    if (empty($hosts)) return;
    SWUQueueChooseTarget(intval($player), $hosts, "Choose_a_unit_to_attach_to", "LAW_245#1|{$cardID}|{$lastDecision}");
};

// Step 1: attach from discard at −3; flag the upgrade to be defeated at the next regroup phase.
$customDQHandlers["LAW_245#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $upMz   = $parts[1] ?? '';
    $hostMz = $lastDecision ?? '';
    $host   = ($hostMz !== '' && str_contains($hostMz, '-')) ? GetZoneObject($hostMz) : null;
    if ($cardID === '' || SWUObjGone($host)) return;
    $hostUID = intval($host->UniqueID ?? 0);
    // prepaid=3 = "costs 3 resources less"; suppress After Action (the event's FINISH_PLAY_CARD owns it).
    _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upMz, $hostMz, 3, false, false, true);
    if ($hostUID > 0) AddGlobalEffects(intval($player), "SWU_LAW245_DEFEAT|{$hostUID}|{$cardID}");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_245:0"] = function($player, $mzID = '') {
// Salvaged Materials — "Play an Item upgrade from your discard pile. It costs 3
                          // resources less. At the start of the next regroup phase, defeat it."
            global $playerID; $playerID = intval($player);
            $ready    = SWUResourceCount(intval($player), readyOnly: true);
            $upgrades = [];
            foreach (ZoneSearch('myDiscard') as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (stripos(CardType($o->CardID) ?? '', 'Upgrade') === false) continue;
                if (!HasTrait($o->CardID, 'Item')) continue;
                $hosts = SWUGetUpgradeValidTargets(intval($player), $o->CardID);
                if (empty($hosts)) continue;
                $cost = max(0, SWUComputePlayCost(intval($player), $o, GetZoneObject($hosts[0])) - 3);
                if ($cost <= $ready) $upgrades[] = $mz;
            }
            if (empty($upgrades)) return;
            SWUQueueChooseTarget(intval($player), $upgrades, "Play_an_Item_upgrade_from_your_discard_(costs_3_less)", "LAW_245#0");
            return;
};
