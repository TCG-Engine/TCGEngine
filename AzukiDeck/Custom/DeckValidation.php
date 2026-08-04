<?php

include_once __DIR__ . '/../../AppCore/Azuki/CardCanonicalization.php';

function ValidateDeckCardAddition($cardID) {
  $canonicalID = AzukiCanonicalCardID($cardID);
  if (strtolower((string)CardCategory($canonicalID)) === 'ikz') return false;

  $count = 0;
  foreach (GetMainDeck(1) as $card) {
    if (AzukiCanonicalCardID($card->CardID) === $canonicalID && !$card->Removed()) ++$count;
  }
  foreach (GetSideboard(1) as $card) {
    if (AzukiCanonicalCardID($card->CardID) === $canonicalID && !$card->Removed()) ++$count;
  }
  return $count < 4;
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
