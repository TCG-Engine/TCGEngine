<?php

  function Draw($player, $amount=1) {
    $zone = &GetDeck($player);
    $hand = &GetHand($player);
    for($i=0; $i<$amount; ++$i) {
      if(count($zone) == 0) {
        return;
      }
      $card = array_shift($zone);
      array_push($hand, $card);
    }
  }

  function SearchZoneForCard($zoneName, $cardID, $playerID = "") {
    $zoneName = explode("-", $zoneName)[0];//In case it's an mzid
    $zone = &GetZone($zoneName);
    for($i=0; $i<count($zone); ++$i) {
      $card = $zone[$i];
      if($card->CardID == $cardID && !$card->Removed()) {
        return $card;
      }
    }
    return null;
  }

?>