<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myActions"://Actually draw ... TODO: Clean this up?
        Draw($playerID, amount: 1);
        UseActions(amount:1, player:$playerID);
        break;
      default: break;
    }
}

?>