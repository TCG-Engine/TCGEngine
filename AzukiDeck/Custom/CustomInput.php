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
        for ($i = 0; $i < 4; ++$i) MZAddZone($playerID, 'myMainDeck', $card->CardID);
      } elseif ($action === 'V') MZAddZone($playerID, 'mySideboard', $card->CardID);
      break;

    case 'myMainDeck':
    case 'mySideboard':
      $card = GetZoneObject($actionCard);
      if ($card === null) break;
      if ($action === '<') $card->Remove();
      elseif ($action === '<<<') {
        $cardID = $card->CardID;
        for ($i = 0; $i < 4; ++$i) {
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

require_once __DIR__ . '/../AutoVersioning.php';

function AutomaticAssetVersioningEnabled() {
  return AzukiAutoVersioningEnabled();
}

function LoadAutomaticAssetVersion($playerID, $versionID) {
  global $gameName;
  $config = AzukiAutoVersioningGetConfig($gameName, $versionID);
  if(!is_array($config)) return false;

  $leaderID = trim((string)($config['identities']['leader'] ?? ''));
  $gateID = trim((string)($config['identities']['gate'] ?? ''));
  $mainCounts = (array)($config['zones']['mainDeck'] ?? []);
  if($leaderID === '' || $gateID === '' || empty($mainCounts)) return false;

  $leader = &GetLeader($playerID);
  $gate = &GetGate($playerID);
  $mainDeck = &GetMainDeck($playerID);
  $leader = [new Leader($leaderID, 'Leader', $playerID, 0)];
  $gate = [new Gate($gateID, 'Gate', $playerID, 0)];
  $mainDeck = [];
  foreach($mainCounts as $cardID => $quantity) {
    for($i = 0; $i < intval($quantity); ++$i) {
      $mainDeck[] = new MainDeck($cardID, 'MainDeck', $playerID, count($mainDeck));
    }
  }
  return true;
}

?>
