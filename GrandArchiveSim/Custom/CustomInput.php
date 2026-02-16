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
      default: break;
    }
}

?>