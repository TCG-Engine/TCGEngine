<?php

include_once './AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';

$p1Leaders = [];
$p1Gates = [];
$p1Cards = [];

foreach (GetAllCardIds() as $cardID) {
  $category = strtolower((string)CardCategory($cardID));
  if ($category === 'leader') {
    $p1Leaders[] = new Leaders($cardID);
  } elseif ($category === 'gate') {
    $p1Gates[] = new Gates($cardID);
  } elseif ($category === 'ikz') {
    continue;
  } else {
    $p1Cards[] = new Cards($cardID);
  }
}

WriteGamestate('./AzukiDeck/');

?>
