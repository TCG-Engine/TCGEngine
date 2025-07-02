<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $cardArr = explode("-", $actionCard);
    $zone = $cardArr[0];
    $index = $cardArr[1];
    switch ($zone) {
      case "myCards":
        switch ($action) {
          case ">":
            $card = GetZoneObject($actionCard);
            $destination = "myMainDeck";
            MZAddZone($playerID, $destination, $card->CardID);
            break;
          case ">>>":
            for ($i = 0; $i < 3; ++$i) {
              $card = GetZoneObject($actionCard);
              $destination = "myMainDeck";
              MZAddZone($playerID, $destination, $card->CardID);
            }
            break;
          default:
            break;
        }
        break;
    case "myMainDeck":
        switch ($action) {
          case "<":
            $card = GetZoneObject($actionCard);
            $card->Remove();
            break;
          case "<<<":
            $card = GetZoneObject($actionCard);
            $cardID = $card->CardID;
            for($i = 0; $i < 3; ++$i) {
              $card = SearchZoneForCard($actionCard, $cardID, 1);
              if($card != null) $card->Remove();
            }
            break;
          case "+":
            $card = GetZoneObject($actionCard);
            $destination = "myMainDeck";
            MZAddZone($playerID, $destination, $card->CardID);
            break;
          default: break;
        }
        break;
      default: break;
    }
}

?>