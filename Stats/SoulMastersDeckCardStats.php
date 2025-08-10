<?php

include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';

include_once '../SoulMastersDB/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';

$conn = GetLocalMySQLConnection();

// Get all decks and group by commander
$sql = "SELECT * FROM ownership WHERE assetType=1 AND keyIndicator1 IS NOT NULL";
$result = mysqli_query($conn, $sql);

$commanders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $commander = $row['keyIndicator1'];
    if ($commander == "") continue;
    if (!isset($commanders[$commander])) {
        $commanders[$commander] = [
            'decks' => [],
        ];
    }
    $commanders[$commander]['decks'][] = $row['assetIdentifier'];
}


// Use robust deck parsing for each deck (ParseGamestate, GetMainDeck, GetReserveDeck)
require_once '../vendor/autoload.php';
include_once '../SoulMastersDB/GamestateParser.php';
include_once '../SoulMastersDB/ZoneAccessors.php';
include_once '../SoulMastersDB/ZoneClasses.php';
include_once '../SoulMastersDB/GeneratedCode/GeneratedCardDictionaries.php';

echo "<div style='padding: 40px; max-width: 900px; margin: 0 auto;'>";
foreach ($commanders as $commander => $data) {
    $cardTotals = [];
    $deckCount = 0;
    foreach ($data['decks'] as $assetIdentifier) {
        // Parse the gamestate for this deck
        $gameName = $assetIdentifier;
        ParseGamestate("../SoulMastersDB/");
        $mainDeckArr = &GetMainDeck(1);
        foreach ($mainDeckArr as $card) {
            $cardID = $card->CardID;
            $qty = isset($cardTotals[$cardID]) ? $cardTotals[$cardID] : 0;
            $cardTotals[$cardID] = $qty + 1;
        }
        $deckCount++;
    }
    $commanderName = CardName($commander);
    echo "<div style='margin-bottom: 40px; border: 2px solid #333; border-radius: 12px; background: #181830; box-shadow: 0 2px 12px #0008; padding: 24px;'>";
    echo "<h2 style='margin-top:0; color: #6cf;'><img style='height:60px;vertical-align:middle;margin-right:12px;' src='../SoulMastersDB/concat/{$commander}.webp' title='{$commanderName}' /> {$commanderName} <span style='font-size:0.7em; color:#aaa;'>({$deckCount} decks)</span></h2>";
    if (count($cardTotals) === 0) {
        echo "<div style='color:#aaa;'>No cards found for this commander.</div>";
    } else {
        echo "<table border='0' cellpadding='6' cellspacing='0' style='width:100%; background:#222244; border-radius:8px;'>";
        echo "<tr style='background:#333366; color:#fff;'><th style='text-align:left;'>Card Name</th><th style='text-align:right;'>Total Copies</th></tr>";
        arsort($cardTotals);
        foreach ($cardTotals as $cardId => $total) {
            $cardName = CardName($cardId);
            if ($cardName == "") $cardName = $cardId;
            echo "<tr><td style='color:#fff;'>{$cardName}</td><td style='text-align:right;color:#6cf;font-weight:bold;'>{$total}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
}
echo "</div>";

$conn->close();

include_once '../SharedUI/Disclaimer.php';

?>