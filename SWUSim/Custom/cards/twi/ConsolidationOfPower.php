<?php
// TWI_089
// Cost 6 - Consolidation of Power - [Command,Villainy]
// Text: Choose any number of friendly units. You may play a unit from your hand if its cost is less than or equal to the combined power of the chosen units for free. Then, defeat the chosen units.

// TWI_089 Consolidation of Power — step 1: the chosen friendly units set the budget (combined power).
// Offer a free hand-unit play (cost ≤ budget), carrying the chosen units' UIDs to the defeat step.
$customDQHandlers["TWI_089#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();   // the just-played event lingers in hand until now
    $chosen = array_values(array_filter(explode('&', (string)$lastDecision),
        fn($p) => $p !== '' && $p !== '-' && $p !== 'PASS'));
    $chosenUIDs = []; $combined = 0;
    foreach ($chosen as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) { $chosenUIDs[] = intval($o->UniqueID ?? 0); $combined += intval(ObjectCurrentPower($o)); }
    }
    $uidCsv = implode(',', $chosenUIDs);
    $handUnits = [];
    if ($combined > 0) {
        foreach (ZoneSearch('myHand') as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (strpos(CardType($o->CardID ?? '') ?? '', 'Unit') === false) continue; // units only
            if (intval(CardCost($o->CardID ?? '')) <= $combined) $handUnits[] = $mz;
        }
    }
    if (empty($handUnits)) {           // nothing playable → just defeat the chosen units
        ConsolidationofPowerDefeatChosen(intval($player), $chosenUIDs);
        return;
    }
    SWUQueueMayChooseTarget($player, $handUnits, "Play_a_unit_from_hand_for_free?", "Choose_a_unit_to_play",
        "TWI_089#1|{$uidCsv}");
};

// TWI_089 — step 2: (optional) free play of the chosen hand unit, THEN defeat the chosen friendly units.
$customDQHandlers["TWI_089#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $chosenUIDs = (isset($parts[0]) && $parts[0] !== '') ? array_map('intval', explode(',', $parts[0])) : [];
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            // Free play from hand (mirror JTL_089#1: guard the nested play's turn/PASS so it doesn't
            // double-advance the outer event's action).
            $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
            ActivateCard(intval($player), $lastDecision, true);
            $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
            $playerID = intval($player);
        }
    }
    ConsolidationofPowerDefeatChosen(intval($player), $chosenUIDs);
};

function ConsolidationofPowerDefeatChosen(int $player, array $uids): void
{
  foreach ($uids as $uid) {
    if ($uid <= 0)
      continue;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null && $mz !== '')
      SWUDefeatUnit($player, $mz);
  }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_089:0"] = function($player, $mzID = '') {
// Consolidation of Power — "Choose any number of friendly units. You may play a
                          // unit from your hand if its cost is less than or equal to the combined power of
                          // the chosen units for free. Then, defeat the chosen units."
            global $playerID; $playerID = intval($player);
            $friendly = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
            if (empty($friendly)) return;
            $max = count($friendly);
            SWUQueueMultiChoose($player, 0, $max, $friendly, "Choose_any_number_of_friendly_units", "TWI_089#0");
            return;
};
