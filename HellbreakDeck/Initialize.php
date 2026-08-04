<?php

include_once './HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';

$p1Monsters = [];
$p1Locations = [];
$p1Cards = [];

foreach (GetAllCardIds() as $cardID) {
    if (function_exists('CardRevealed') && !CardRevealed($cardID)) continue;
    $deckImage = __DIR__ . '/../HellbreakSim/concat/' . $cardID . '.webp';
    if (!is_file($deckImage) || filesize($deckImage) < 8000) continue;
    $type = strtolower(trim((string)CardType($cardID)));
    if ($type === 'monster') $p1Monsters[] = new Monsters($cardID);
    elseif ($type === 'location') $p1Locations[] = new Locations($cardID);
    else $p1Cards[] = new Cards($cardID);
}

WriteGamestate('./HellbreakDeck/');

?>
