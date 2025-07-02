<?php


  include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
  include_once './Database/ConnectionManager.php';

  $p1Cards = [];
  $allCards = GetAllCardIds();
  foreach ($allCards as $cardId) {
    array_push($p1Cards, new Cards($cardId));
  }

  WriteGamestate("./SWUCardList/");

?>