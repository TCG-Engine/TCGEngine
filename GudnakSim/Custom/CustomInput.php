<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myActions":
        Draw($playerID, amount: 1);
        UseActions(amount:1, player:$playerID);
        break;
      case "BG1": case "BG2": case "BG3": case "BG4": case "BG5": case "BG6": case "BG7": case "BG8": case "BG9":
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