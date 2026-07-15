<?php

function ValidateMainDeckAddition($cardID) {
  if (strtolower((string)CardCategory($cardID)) === 'ikz') return false;

  $count = 0;
  foreach (GetMainDeck(1) as $card) {
    if ($card->CardID == $cardID && !$card->Removed()) ++$count;
  }
  foreach (GetSideboard(1) as $card) {
    if ($card->CardID == $cardID && !$card->Removed()) ++$count;
  }
  return $count < 3;
}

function ValidateLeaderAddition($cardID) {
  global $gameName;
  SetAssetKeyIdentifier(1, $gameName, 1, $cardID);
  return true;
}

function ValidateGateAddition($cardID) {
  global $gameName;
  SetAssetKeyIdentifier(1, $gameName, 2, $cardID);
  return true;
}

?>
