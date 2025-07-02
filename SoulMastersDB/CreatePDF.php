<?php

require_once '../vendor/autoload.php';
include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once './GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';


$gameName = TryGet("gameName", "");

if($gameName == "") {
  echo("You must provide a game name to generate this pdf.");
  exit;
}

if(!IsUserLoggedIn()) {
  echo("You must be logged in to generate this pdf.");
  exit;
}
$loggedInUser = LoggedInUser();
$assetData = LoadAssetData(1, $gameName);
if($assetData == null) {
  echo("This game asset does not exist.");
  exit;
}
$assetOwner = $assetData["assetOwner"];
if($loggedInUser != $assetOwner) {
  if($assetData["assetVisibility"] > 10000) {
    if(!IsPatron($assetData["assetVisibility"])){
      echo("You must be a patron to generate this pdf.");
      exit;
    }
  } else if($assetData["assetVisibility"] == 0) {
    echo("You must own this asset to generate this pdf.");
    exit;
  }
}

ParseGamestate();
$arr = &GetCommander(1);
$commanderName = count($arr) > 0 ? CardName($arr[0]->CardID) : "";

$pdf = new \TCPDF();

// Set document information
$pdf->SetCreator('PHP Script');
$pdf->SetAuthor('Soul Masters DB');
$pdf->SetTitle('Soul Masters Deck Form');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Soul Masters Deck Form', 0, 1, 'C');
$pdf->Ln(5);

// Player Information Table
$pdf->SetFont('helvetica', '', 10);
$html = <<<EOD
<table border="1" cellpadding="4">
    <tr>
      <td colspan="2"><b>Full Name:</b></td>
      <td><b>Soul Masters ID:</b></td>
    </tr>
    <tr>
        <td colspan="3"><b>Event:</b></td>
    </tr>
</table>
<br><br><b>Commander:</b> $commanderName<br>
EOD;

$pdf->writeHTML($html);
$pdf->Ln(5);

// Function to format sections
function addCardSection($pdf, $title, $count, $cards) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, "$title (Count: $count)", 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    foreach ($cards as $card) {
        $pdf->Cell(0, 6, $card, 0, 1);
    }
    $pdf->Ln(3);
}
$quantityIndex = [];
$arr = &GetMainDeck(1);
foreach ($arr as $card) {
  $cardID = $card->CardID;
  if (isset($quantityIndex[$cardID])) {
    $quantityIndex[$cardID]++;
  } else {
    $quantityIndex[$cardID] = 1;
  }
}

$numUnits = 0;
$numEvents = 0;
$numUpgrades = 0;
$units = [];
$events = [];
$upgrades = [];
foreach ($quantityIndex as $cardID => $quantity) {
  $cardID = strval($cardID);

  $cardString = CardName($cardID) . " x{$quantity} (Rarity: " . CardRarity($cardID)[0] . ", Type: " . CardSubType($cardID) . ")";
  if (strpos($cardFaction, "Mercenary") !== false) {
    $cardString .= ", Faction: Mercenary";
  }
  
  $units[] = $cardString;
  $numUnits += $quantity;

}

// Sort each section alphabetically
sort($units);


$sideboard = [];
$numSideboard = 0;
$arr = &GetReserveDeck(1);
$sideboardQuantityIndex = [];
foreach ($arr as $card) {
  $cardID = $card->CardID;
  if (isset($sideboardQuantityIndex[$cardID])) {
    $sideboardQuantityIndex[$cardID]++;
  } else {
    $sideboardQuantityIndex[$cardID] = 1;
  }
}
foreach ($sideboardQuantityIndex as $cardID => $quantity) {
  $cardID = strval($cardID);
  $cardString = CardName($cardID) . " x{$quantity} (Rarity: " . CardRarity($cardID)[0] . ", Type: " . CardSubType($cardID)[0] . ")";
  $numSideboard += $quantity;
  $sideboard[] = $cardString;
}
// Sort sideboard alphabetically
sort($sideboard);

// Add main deck sections
addCardSection($pdf, 'Deck', $numUnits, $units);

// Add sideboard in a separate column
$pdf->SetY(57); // Adjust Y position to start sideboard section
$pdf->SetX(120); // Adjust X position to create a new column
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, "Reserves (Count: " . count($sideboard) . ")", 0, 1);
$pdf->SetFont('helvetica', '', 10);
foreach ($sideboard as $card) {
    $pdf->SetX(120); // Ensure each sideboard item starts in the new column
    $pdf->Cell(0, 6, $card, 0, 1);
}
//$pdf->Ln(3);
// Deck Link
$pdf->SetY(-38); // Move to 30 mm from bottom
$pdf->SetFont('helvetica', 'I', 10);
$assetName = isset($assetData["assetName"]) ? $assetData["assetName"] : "Deck #" . $gameName;
$ownerData = LoadUserDataFromId($assetData["assetOwner"]);
$ownerName = isset($ownerData["usersUid"]) ? $ownerData["usersUid"] : "Unknown";
$pdf->Write(0, 'Deck based on ' . $assetName . ' by ' . $ownerName, '', 0, 'L', true);

// Disclaimer
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 8);
$pdf->MultiCell(0, 0, "Document prepared at soulmastersdb.net. Soul Masters DB is in no way affiliated with Soul Master Games LLC. Soul Masters characters, cards, logos, and art are property of Soul Master Games LLC. All Soul Masters assets are used with permission from the owners.", 0, 'L');

// Output the PDF
$pdf->Output('Soul_Masters_Deck_Form.pdf', 'I');
?>