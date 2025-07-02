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
$arr = &GetLeader(1);
$leaderName = count($arr) > 0 ? CardTitle($arr[0]->CardID) . ", " . CardSubtitle($arr[0]->CardID) : "";
$arr = &GetBase(1);
$baseName = count($arr) > 0 ? CardTitle($arr[0]->CardID) : "";

$pdf = new \TCPDF();

// Set document information
$pdf->SetCreator('PHP Script');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Star Wars: Unlimited™ Deck Form');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Star Wars: Unlimited™ Deck Form', 0, 1, 'C');
$pdf->Ln(5);

// Player Information Table
$pdf->SetFont('helvetica', '', 10);
$html = <<<EOD
<table border="1" cellpadding="4">
    <tr>
      <td colspan="2"><b>Full Name:</b></td>
      <td><b>SWU ID:</b></td>
    </tr>
    <tr>
        <td><b>Pronouns:</b></td>
        <td colspan="2"><b>Event:</b></td>
    </tr>
</table>
<br><br><b>Leader:</b> $leaderName<br>
<b>Base:</b> $baseName
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
  $cardTitle = str_replace("\\", "", CardTitle($cardID));
  $cardType = CardType($cardID); // Assuming CardType function returns 'unit', 'event', or 'upgrade'
  $setID = str_replace("_", "", CardIDLookup($cardID));
  $cardRarity = CardRarity($cardID);
  $cardString = "{$cardTitle} ({$setID} - {$cardRarity}) x{$quantity}";
  switch ($cardType) {
    case 'Unit':
      $units[] = $cardString;
      $numUnits += $quantity;
      break;
    case 'Event':
      $events[] = $cardString;
      $numEvents += $quantity;
      break;
    case 'Upgrade':
      $upgrades[] = $cardString;
      $numUpgrades += $quantity;
      break;
  }
}

// Sort each section alphabetically
sort($units);
sort($events);
sort($upgrades);


$sideboard = [];
$numSideboard = 0;
$arr = &GetSideboard(1);
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
  $cardTitle = str_replace("\\", "", CardTitle($cardID));
  $cardType = CardType($cardID); // Assuming CardType function returns 'unit', 'event', or 'upgrade'
  $setID = str_replace("_", "", CardIDLookup($cardID));
  $cardRarity = CardRarity($cardID);
  $cardString = "{$cardTitle} ({$setID} - {$cardRarity}) x{$quantity}";
  $numSideboard += $quantity;
  $sideboard[] = $cardString;
}
// Sort sideboard alphabetically
sort($sideboard);

// Add main deck sections
addCardSection($pdf, 'UNITS', $numUnits, $units);
addCardSection($pdf, 'EVENTS', $numEvents, $events);
addCardSection($pdf, 'UPGRADES', $numUpgrades, $upgrades);

// Add sideboard in a separate column
$pdf->SetY(57); // Adjust Y position to start sideboard section
$pdf->SetX(120); // Adjust X position to create a new column
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, "SIDEBOARD (Count: " . $numSideboard . ")", 0, 1);
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
$pdf->MultiCell(0, 0, "Document prepared at swustats.net. SWU Stats is fan made and is in no way affiliated with Disney or Fantasy Flight Games. Star Wars characters, cards, logos, and art are property of Disney and/or Fantasy Flight Games.", 0, 'L');

// Output the PDF
$pdf->Output('Star_Wars_Deck_Form.pdf', 'I');
?>