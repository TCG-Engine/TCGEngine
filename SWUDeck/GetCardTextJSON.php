<?php
require_once "../Core/HTTPLibraries.php";
require_once "../AccountFiles/AccountSessionAPI.php";
require_once "../Database/ConnectionManager.php";

$modError = CheckLoggedInUserMod();
if ($modError !== "") {
    header('Content-Type: application/json');
    echo json_encode(["error" => $modError]);
    exit();
}

$deckID = TryGet("deckID", default: "");

if ($deckID === "" || !ctype_digit((string)$deckID)) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Invalid or missing deckID"]);
    exit();
}

require_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
require_once "../SWUDeck/GamestateParser.php";
require_once "../SWUDeck/ZoneClasses.php";
require_once "../SWUDeck/ZoneAccessors.php";

$gameName = $deckID;
ParseGamestate("../SWUDeck/");

$result = [];
$seen = [];

$leader    = &GetLeader(1);
$base      = &GetBase(1);
$mainDeck  = &GetMainDeck(1);

$allCards = array_merge((array)$leader, (array)$base, (array)$mainDeck);

foreach ($allCards as $card) {
    $cardID = $card->CardID;
    if (isset($seen[$cardID])) continue;
    $seen[$cardID] = true;

    $setCode = CardIDLookup($cardID);
    if ($setCode === null) continue;

    $title    = CardTitle($cardID);
    $subtitle = CardSubtitle($cardID);
    $fullName = $title ?? $setCode;
    if ($subtitle !== null && $subtitle !== '') {
        $fullName .= ' | ' . $subtitle;
    }

    $type   = CardType($cardID);
    $entry  = [
        "card"     => $setCode,
        "fullName" => $fullName,
        "type"     => $type,
        "cardText" => CardText($cardID),
    ];

    if ($type === "Unit") {
        $entry["power"] = CardPower($cardID);
        $entry["hp"]    = CardHp($cardID);
    } elseif ($type === "Upgrade") {
        $entry["upgradePower"] = CardUpgradePower($cardID);
        $entry["upgradeHp"]    = CardUpgradeHp($cardID);
    }

    $result[] = $entry;
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
