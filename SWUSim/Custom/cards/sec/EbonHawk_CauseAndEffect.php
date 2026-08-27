<?php
// SEC_219
// Cost 3 - Ebon Hawk - Cause and Effect - [Cunning] - Power 3 - HP 3
// Text: On Attack: You may disclose Heroism and/or Villainy. If you disclosed Heroism, this unit gets +2/+0 for this attack. If you disclosed Villainy, give the defender -4/-0 for this attack.

// SEC_219 Ebon Hawk — On Attack: you may disclose Heroism AND/OR Villainy (a free reveal, not a fixed
// requirement). Disclosed Heroism → +2/+0 this attack; disclosed Villainy → defender −4/−0 this attack.
// Markers are set in the continuation, which fully resolves before the deferred SWUCombatDamage.
$onAttackAbilities["SEC_219:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();
    $hand = ZoneSearch("myHand");
    if (empty($hand)) return;
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
        "0|" . count($hand) . "|" . implode('&', $hand), 1, tooltip: "Disclose_Heroism_and-or_Villainy");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_219#0|{$selfUID}", 1, dontSkipOnPass: 1);
};

$customDQHandlers["SEC_219#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    if (SWUDecisionDeclined($lastDecision)) return;
    $icons = [];
    foreach (array_values(array_filter(explode('&', (string)$lastDecision))) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) {
            $icons = array_merge($icons, SWUCardAspectIcons($o->CardID ?? ''));
            AddGameLogEntry('DISCLOSE', 'P' . intval($player) . ' discloses ' . GameLogCardRef($o->CardID ?? '')); // log the revealed card
        }
    }
    $selfMz = SWUFindMzByUID($selfUID);
    if ($selfMz === null) return;
    if (in_array('Heroism', $icons, true))  SWUAddAttackPowerBonus($selfMz, 2);     // +2/+0 this attack
    if (in_array('Villainy', $icons, true)) AddTurnEffect($selfMz, 'SWU_DEF_DEBUFF_4'); // defender −4/−0
};
