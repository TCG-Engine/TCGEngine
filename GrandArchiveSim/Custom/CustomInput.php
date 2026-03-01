<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myHealth"://Actually pass ... TODO: Clean this up?
        global $gCurrentPhase;
        $gCurrentPhase = "MAIN";
        AdvanceAndExecute("PASS");
        AutoAdvanceAndExecute();
        break;
      case "myField":
        $zoneArr = &GetZone($zone, $playerID);
        // Parse ability index from action (e.g., "Activate:0", "Activate:1")
        $abilityIndex = 0;
        if (strpos($action, ':') !== false) {
            $actionParts = explode(':', $action);
            $abilityIndex = intval($actionParts[1]);
        }
        ActivateAbility($playerID, $zone . "-" . (count($zoneArr) - 1), $abilityIndex);
        break;
      default: break;
    }
}

?>