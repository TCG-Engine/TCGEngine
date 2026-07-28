<?php
// LOF_240
// Cost 2 - Flight of the Inquisitor - [Villainy]
// Text: You may return a Force unit and a Lightsaber upgrade from your discard pile to your hand.

$customDQHandlers["LOF_240#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $forceUnits = []; $myD = GetDiscard($player);
    for ($i = 0; $i < count($myD); $i++) {
        $c = $myD[$i];
        if (SWUObjGone($c)) continue;
        if (CardType($c->CardID ?? '') === 'Unit' && TraitContains($c, 'Force')) $forceUnits[] = "myDiscard-{$i}";
    }
    if (!empty($forceUnits)) {
        SWUQueueMayChooseTarget(intval($player), $forceUnits, "Return_a_Force_unit_from_discard_to_hand?", "Choose_a_Force_unit", "LOF_240#1");
    } else {
        FlightoftheInquisitorQueueSaberStep(intval($player)); // no Force units → straight to the saber step
    }
};

$customDQHandlers["LOF_240#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            $cardID = $o->CardID; $o->removed = true;
            AddHand(intval($player), CardID: $cardID);
            DecisionQueueController::CleanupRemovedCards();
        }
    }
    FlightoftheInquisitorQueueSaberStep(intval($player));
};

$customDQHandlers["LOF_240#3"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID; $o->removed = true;
    AddHand(intval($player), CardID: $cardID);
    DecisionQueueController::CleanupRemovedCards();
};

// LOF_240 Flight of the Inquisitor — optional return of a Force unit, then a Lightsaber upgrade, from the
// discard pile to hand. Four-step chain so each "may" resolves independently.
function FlightoftheInquisitorQueueSaberStep(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $sabers = [];
  $myD = GetDiscard($player);
  for ($i = 0; $i < count($myD); $i++) {
    $c = $myD[$i];
    if (SWUObjGone($c))
      continue;
    if (CardType($c->CardID ?? '') === 'Upgrade' && HasTrait($c->CardID ?? '', 'Lightsaber'))
      $sabers[] = "myDiscard-{$i}";
  }
  if (empty($sabers))
    return;
  SWUQueueMayChooseTarget($player, $sabers, "Return_a_Lightsaber_upgrade_from_discard_to_hand?", "Choose_a_Lightsaber", "LOF_240#3");
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_240:0"] = function($player, $mzID = '') {
// Flight of the Inquisitor — "You may return a Force unit and a Lightsaber upgrade
                        // from your discard pile to your hand." (Two independent optional returns.)
            DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_240#0", 1);
            return;
};
