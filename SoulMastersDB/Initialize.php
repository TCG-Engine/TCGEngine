<?php


  include_once './SoulMastersDB/GeneratedCode/GeneratedCardDictionaries.php';

  //Set up the list of cards to choose from
  $p1Commanders = [];
  $p1Reserves = [];
  $p1Cards = [];
  $allCards = GetAllCardIds();
  foreach ($allCards as $cardId) {
    $cardType = CardType($cardId);
    $cardSubtype = CardSubtype($cardId);
    if($cardType == "Commander") {
      if(strpos($cardSubtype, "Base") !== false) array_push($p1Commanders, new Commanders($cardId));
    } else if(CardType($cardId) == "Reserve") {
      array_push($p1Reserves, new Reserves($cardId));
    } else {
      array_push($p1Cards, new Cards($cardId));
    }
  }

  WriteGamestate("./SoulMastersDB/");

?>