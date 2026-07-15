<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
  $cardArr = explode('-', $actionCard);
  $zone = $cardArr[0];

  switch ($zone) {
    case 'myCards':
      $card = GetZoneObject($actionCard);
      if ($card === null) break;
      if ($action === '>') MZAddZone($playerID, 'myMainDeck', $card->CardID);
      elseif ($action === '>>>') {
        for ($i = 0; $i < 3; ++$i) MZAddZone($playerID, 'myMainDeck', $card->CardID);
      } elseif ($action === 'V') MZAddZone($playerID, 'mySideboard', $card->CardID);
      break;

    case 'myMainDeck':
    case 'mySideboard':
      $card = GetZoneObject($actionCard);
      if ($card === null) break;
      if ($action === '<') $card->Remove();
      elseif ($action === '<<<') {
        $cardID = $card->CardID;
        for ($i = 0; $i < 3; ++$i) {
          $match = SearchZoneForCard($actionCard, $cardID, 1);
          if ($match !== null) $match->Remove();
        }
      } elseif ($action === '+') {
        MZAddZone($playerID, $zone === 'myMainDeck' ? 'myMainDeck' : 'mySideboard', $card->CardID);
      } elseif ($action === 'V' && $zone === 'myMainDeck') {
        $card->Remove();
        MZAddZone($playerID, 'mySideboard', $card->CardID);
      } elseif ($action === '^' && $zone === 'mySideboard') {
        $card->Remove();
        MZAddZone($playerID, 'myMainDeck', $card->CardID);
      }
      break;
  }
}

?>
