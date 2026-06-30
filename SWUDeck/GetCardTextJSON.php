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
require_once "../Core/StatsBaseRegistry.php";
require_once "../SWUDeck/GamestateParser.php";
require_once "../SWUDeck/ZoneClasses.php";
require_once "../SWUDeck/ZoneAccessors.php";

$gameName = $deckID;
ParseGamestate("../SWUDeck/");

function buildCardEntry($cardID) {
    $setCode = CardIDLookup($cardID);
    if ($setCode === null) return null;

    $title    = CardTitle($cardID);
    $subtitle = CardSubtitle($cardID);
    $fullName = $title ?? $setCode;
    if ($subtitle !== null && $subtitle !== '') {
        $fullName .= ' | ' . $subtitle;
    }

    $type  = CardType($cardID);
    $entry = [
        "card"     => $setCode,
        "fullName" => $fullName,
        "type"     => $type,
        "cost"     => CardCost($cardID),
        "cardText" => CardText($cardID),
    ];

    if ($type === "Unit") {
        $entry["power"] = CardPower($cardID);
        $entry["hp"]    = CardHp($cardID);
    } elseif ($type === "Upgrade") {
        $entry["upgradePower"] = CardUpgradePower($cardID);
        $entry["upgradeHp"]    = CardUpgradeHp($cardID);
    }

    return $entry;
}

$leader    = &GetLeader(1);
$base      = &GetBase(1);
$mainDeck  = &GetMainDeck(1);
$sideboard = &GetSideboard(1);

$leaderAndBase = [];
$mainBoard     = [];
$sideBoard     = [];

$counts = [];
foreach (array_merge((array)$leader, (array)$base) as $card) {
    $counts[$card->CardID] = ($counts[$card->CardID] ?? 0) + 1;
}
foreach ($counts as $cardID => $count) {
    $entry = buildCardEntry($cardID);
    if ($entry !== null) { $entry["copies"] = $count; $leaderAndBase[] = $entry; }
}

$counts = [];
foreach ((array)$mainDeck as $card) {
    $counts[$card->CardID] = ($counts[$card->CardID] ?? 0) + 1;
}
foreach ($counts as $cardID => $count) {
    $entry = buildCardEntry($cardID);
    if ($entry !== null) { $entry["copies"] = $count; $mainBoard[] = $entry; }
}

$counts = [];
foreach ((array)$sideboard as $card) {
    $counts[$card->CardID] = ($counts[$card->CardID] ?? 0) + 1;
}
foreach ($counts as $cardID => $count) {
    $entry = buildCardEntry($cardID);
    if ($entry !== null) { $entry["copies"] = $count; $sideBoard[] = $entry; }
}

// --- Card deck stats ---
$conn = GetLocalMySQLConnection();
$stmt = $conn->prepare("SELECT cardID,
    SUM(timesIncluded) as timesIncluded,
    SUM(timesIncludedInWins) as timesIncludedInWins,
    SUM(timesDrawn) as timesDrawn,
    SUM(timesDrawnInWins) as timesDrawnInWins,
    SUM(timesPlayed) as timesPlayed,
    SUM(timesPlayedInWins) as timesPlayedInWins,
    SUM(timesResourced) as timesResourced,
    SUM(timesResourcedInWins) as timesResourcedInWins,
    SUM(timesDiscarded) as timesDiscarded,
    SUM(timesDiscardedInWins) as timesDiscardedInWins
    FROM carddeckstats WHERE deckID = ? GROUP BY cardID");
$stmt->bind_param("i", $deckID);
$stmt->execute();
$cardStatsResult = $stmt->get_result();
$deckStats = [];
while ($row = $cardStatsResult->fetch_assoc()) {
    $setCode = CardIDLookup($row["cardID"]);
    if ($setCode === null) continue;
    $deckStats[] = [
        "card"                  => $setCode,
        "timesIncluded"         => (int)$row["timesIncluded"],
        "timesIncludedInWins"   => (int)$row["timesIncludedInWins"],
        "timesDrawn"            => (int)$row["timesDrawn"],
        "timesDrawnInWins"      => (int)$row["timesDrawnInWins"],
        "timesPlayed"           => (int)$row["timesPlayed"],
        "timesPlayedInWins"     => (int)$row["timesPlayedInWins"],
        "timesResourced"        => (int)$row["timesResourced"],
        "timesResourcedInWins"  => (int)$row["timesResourcedInWins"],
        "timesDiscarded"        => (int)$row["timesDiscarded"],
        "timesDiscardedInWins"  => (int)$row["timesDiscardedInWins"],
    ];
}
$stmt->close();

// --- Matchup stats ---
// (1) Common bases: color x type wide columns (Legacy / Standard / Force / Splash).
$colors = StatsBaseColors();
$typeSuffixes = StatsBaseTypeSuffixes(); // ['Legacy'=>'','Standard'=>'Standard','Force'=>'Force','Splash'=>'Splash']
$unionParts = [];
$ids = [];
foreach ($colors as $color) {
    foreach ($typeSuffixes as $typeName => $suffix) {
        $winCol = "winsVs$color$suffix";
        $totCol = "totalVs$color$suffix";
        $unionParts[] = "SELECT leaderID, '$color' AS baseColor, '$typeName' AS baseType,
            SUM($winCol) AS wins, SUM($totCol) AS total
            FROM opponentdeckstats WHERE deckID = ? GROUP BY leaderID HAVING total > 0";
        $ids[] = $deckID;
    }
}
$unionSql = implode(" UNION ALL ", $unionParts) . " ORDER BY total DESC";
$stmt = $conn->prepare($unionSql);
$stmt->bind_param(str_repeat("i", count($ids)), ...$ids);
$stmt->execute();
$matchupResult = $stmt->get_result();
$matchupStats = [];
while ($row = $matchupResult->fetch_assoc()) {
    $wins  = (int)$row["wins"];
    $total = (int)$row["total"];
    $matchupStats[] = [
        "leaderID"  => $row["leaderID"],
        "leaderName"=> trim(CardTitle($row["leaderID"]) . ", " . CardSubtitle($row["leaderID"]), ", "),
        "baseColor" => $row["baseColor"],
        "baseType"  => $row["baseType"],
        "baseName"  => null,
        "wins"      => $wins,
        "losses"    => $total - $wins,
        "games"     => $total,
        "winRate"   => $total > 0 ? round($wins / $total * 100, 2) : 0,
    ];
}
$stmt->close();

// (2) Rare/Special bases: tracked individually by baseID, shown by card name.
$stmt = $conn->prepare("SELECT leaderID, baseID, SUM(wins) AS wins, SUM(total) AS total
    FROM opponentnamedbasestats WHERE deckID = ? GROUP BY leaderID, baseID HAVING total > 0 ORDER BY total DESC");
$stmt->bind_param("i", $deckID);
$stmt->execute();
$namedResult = $stmt->get_result();
while ($row = $namedResult->fetch_assoc()) {
    $wins  = (int)$row["wins"];
    $total = (int)$row["total"];
    $matchupStats[] = [
        "leaderID"  => $row["leaderID"],
        "leaderName"=> trim(CardTitle($row["leaderID"]) . ", " . CardSubtitle($row["leaderID"]), ", "),
        "baseColor" => AspectToColor(CardAspect($row["baseID"]) ?? ''),
        "baseType"  => "Named",
        "baseName"  => CardTitle($row["baseID"]),
        "wins"      => $wins,
        "losses"    => $total - $wins,
        "games"     => $total,
        "winRate"   => $total > 0 ? round($wins / $total * 100, 2) : 0,
    ];
}
$stmt->close();
$conn->close();

$result = [
    "leaderAndBase" => $leaderAndBase,
    "mainBoard"     => $mainBoard,
    "sideBoard"     => $sideBoard,
    "deckStats"     => $deckStats,
    "matchupStats"  => $matchupStats,
];

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
