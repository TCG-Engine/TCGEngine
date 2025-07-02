<?php

  include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

  $allCards = GetAllCardIds();
  $cardArr = [];
  foreach ($allCards as $cardId) {
    $card = new stdClass();
    $card->id = $cardId;
    $card->text = CardText($cardId);
    $card->title = CardTitle($cardId);
    $card->subtitle = CardSubtitle($cardId);
    $card->cost = CardCost($cardId);
    $card->hp = CardHp($cardId);
    $card->power = CardPower($cardId);
    $card->unique = CardUnique($cardId);
    $card->upgradeHp = CardUpgradeHp($cardId);
    $card->upgradePower = CardUpgradePower($cardId);
    $card->aspect = CardAspect($cardId);
    $card->trait = CardTrait($cardId);
    $card->arena = CardArena($cardId);
    $card->type = CardType($cardId);
    $cardArr[] = $card;
  }
  $file = fopen('cards.json', 'w');
  foreach ($cardArr as $card) {
    fwrite($file, json_encode($card) . "\n");
  }
  fclose($file);

?>