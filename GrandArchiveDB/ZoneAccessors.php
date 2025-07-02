<?php
function &GetCommander($player) {
  global $p1Commander, $p2Commander;
  if ($player == 1) return $p1Commander;
  else return $p2Commander;
}

function AddCommander($player, $CardID="-") {
  if(!ValidateCommanderAddition($CardID)) return;
  $zoneObj = new Commander($CardID);
  $zone = &GetCommander($player);
  array_push($zone, $zoneObj);
}

function &GetReserveDeck($player) {
  global $p1ReserveDeck, $p2ReserveDeck;
  if ($player == 1) return $p1ReserveDeck;
  else return $p2ReserveDeck;
}

function AddReserveDeck($player, $CardID="-") {
  if(!ValidateDeckAddition($CardID)) return;
  $zoneObj = new ReserveDeck($CardID);
  $zone = &GetReserveDeck($player);
  array_push($zone, $zoneObj);
}

function &GetMainDeck($player) {
  global $p1MainDeck, $p2MainDeck;
  if ($player == 1) return $p1MainDeck;
  else return $p2MainDeck;
}

function AddMainDeck($player, $CardID="-") {
  if(!ValidateDeckAddition($CardID)) return;
  $zoneObj = new MainDeck($CardID);
  $zone = &GetMainDeck($player);
  array_push($zone, $zoneObj);
}

function &GetCardPane($player) {
  global $p1CardPane, $p2CardPane;
  if ($player == 1) return $p1CardPane;
  else return $p2CardPane;
}

function AddCardPane($player, $Value="-") {
  $zoneObj = new CardPane($Value);
  $zone = &GetCardPane($player);
  array_push($zone, $zoneObj);
}

function &GetCommanders($player) {
  global $p1Commanders, $p2Commanders;
  if ($player == 1) return $p1Commanders;
  else return $p2Commanders;
}

function AddCommanders($player, $CardID="-") {
  $zoneObj = new Commanders($CardID);
  $zone = &GetCommanders($player);
  array_push($zone, $zoneObj);
}

function &GetReserves($player) {
  global $p1Reserves, $p2Reserves;
  if ($player == 1) return $p1Reserves;
  else return $p2Reserves;
}

function AddReserves($player, $CardID="-") {
  $zoneObj = new Reserves($CardID);
  $zone = &GetReserves($player);
  array_push($zone, $zoneObj);
}

function &GetCards($player) {
  global $p1Cards, $p2Cards;
  if ($player == 1) return $p1Cards;
  else return $p2Cards;
}

function AddCards($player, $CardID="-") {
  $zoneObj = new Cards($CardID);
  $zone = &GetCards($player);
  array_push($zone, $zoneObj);
}

function &GetNumReserve($player) {
  global $p1NumReserve, $p2NumReserve;
  if ($player == 1) return $p1NumReserve;
  else return $p2NumReserve;
}

function AddNumReserve($player, $CardID="-") {
  $zoneObj = new NumReserve($CardID);
  $zone = &GetNumReserve($player);
  array_push($zone, $zoneObj);
}

function &GetCountsDisplay($player) {
  global $p1CountsDisplay, $p2CountsDisplay;
  if ($player == 1) return $p1CountsDisplay;
  else return $p2CountsDisplay;
}

function AddCountsDisplay($player, $CardID="-") {
  $zoneObj = new CountsDisplay($CardID);
  $zone = &GetCountsDisplay($player);
  array_push($zone, $zoneObj);
}

function &GetSort($player) {
  global $p1Sort, $p2Sort;
  if ($player == 1) return $p1Sort;
  else return $p2Sort;
}

function AddSort($player, $Value="-") {
  $zoneObj = new Sort($Value);
  $zone = &GetSort($player);
  array_push($zone, $zoneObj);
}

function &GetCardNotes($player) {
  global $p1CardNotes, $p2CardNotes;
  if ($player == 1) return $p1CardNotes;
  else return $p2CardNotes;
}

function AddCardNotes($player, $CardID="-", $Notes="-") {
  $zoneObj = new CardNotes($CardID . ' ' . $Notes);
  $zone = &GetCardNotes($player);
  array_push($zone, $zoneObj);
}

function &GetVersions($player) {
  global $p1Versions, $p2Versions;
  if ($player == 1) return $p1Versions;
  else return $p2Versions;
}

function AddVersions($player, $Version="-") {
  $zoneObj = new Versions($Version);
  $zone = &GetVersions($player);
  array_push($zone, $zoneObj);
}

function &GetZoneObject($mzID) {
  global $playerID;
  $mzArr = explode("-",$mzID);
  switch($mzArr[0]) {
    case "myCommander": $zoneArr = &GetCommander($playerID); return $zoneArr[$mzArr[1]];
    case "theirCommander": $zoneArr = &GetCommander($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myReserveDeck": $zoneArr = &GetReserveDeck($playerID); return $zoneArr[$mzArr[1]];
    case "theirReserveDeck": $zoneArr = &GetReserveDeck($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myMainDeck": $zoneArr = &GetMainDeck($playerID); return $zoneArr[$mzArr[1]];
    case "theirMainDeck": $zoneArr = &GetMainDeck($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myCardPane": $zoneArr = &GetCardPane($playerID); return $zoneArr[$mzArr[1]];
    case "theirCardPane": $zoneArr = &GetCardPane($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myCommanders": $zoneArr = &GetCommanders($playerID); return $zoneArr[$mzArr[1]];
    case "theirCommanders": $zoneArr = &GetCommanders($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myReserves": $zoneArr = &GetReserves($playerID); return $zoneArr[$mzArr[1]];
    case "theirReserves": $zoneArr = &GetReserves($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myCards": $zoneArr = &GetCards($playerID); return $zoneArr[$mzArr[1]];
    case "theirCards": $zoneArr = &GetCards($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myNumReserve": $zoneArr = &GetNumReserve($playerID); return $zoneArr[$mzArr[1]];
    case "theirNumReserve": $zoneArr = &GetNumReserve($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myCountsDisplay": $zoneArr = &GetCountsDisplay($playerID); return $zoneArr[$mzArr[1]];
    case "theirCountsDisplay": $zoneArr = &GetCountsDisplay($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "mySort": $zoneArr = &GetSort($playerID); return $zoneArr[$mzArr[1]];
    case "theirSort": $zoneArr = &GetSort($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myCardNotes": $zoneArr = &GetCardNotes($playerID); return $zoneArr[$mzArr[1]];
    case "theirCardNotes": $zoneArr = &GetCardNotes($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    case "myVersions": $zoneArr = &GetVersions($playerID); return $zoneArr[$mzArr[1]];
    case "theirVersions": $zoneArr = &GetVersions($playerID == 1 ? 2 : 1); return $zoneArr[$mzArr[1]];
    default: return null;
  }
}

function &GetZone($mzID) {
  global $playerID;
  $mzArr = explode("-",$mzID);
  switch($mzArr[0]) {
    case "myCommander": $zoneArr = &GetCommander($playerID); return $zoneArr;
    case "theirCommander": $zoneArr = &GetCommander($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myReserveDeck": $zoneArr = &GetReserveDeck($playerID); return $zoneArr;
    case "theirReserveDeck": $zoneArr = &GetReserveDeck($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myMainDeck": $zoneArr = &GetMainDeck($playerID); return $zoneArr;
    case "theirMainDeck": $zoneArr = &GetMainDeck($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myCardPane": $zoneArr = &GetCardPane($playerID); return $zoneArr;
    case "theirCardPane": $zoneArr = &GetCardPane($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myCommanders": $zoneArr = &GetCommanders($playerID); return $zoneArr;
    case "theirCommanders": $zoneArr = &GetCommanders($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myReserves": $zoneArr = &GetReserves($playerID); return $zoneArr;
    case "theirReserves": $zoneArr = &GetReserves($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myCards": $zoneArr = &GetCards($playerID); return $zoneArr;
    case "theirCards": $zoneArr = &GetCards($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myNumReserve": $zoneArr = &GetNumReserve($playerID); return $zoneArr;
    case "theirNumReserve": $zoneArr = &GetNumReserve($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myCountsDisplay": $zoneArr = &GetCountsDisplay($playerID); return $zoneArr;
    case "theirCountsDisplay": $zoneArr = &GetCountsDisplay($playerID == 1 ? 2 : 1); return $zoneArr;
    case "mySort": $zoneArr = &GetSort($playerID); return $zoneArr;
    case "theirSort": $zoneArr = &GetSort($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myCardNotes": $zoneArr = &GetCardNotes($playerID); return $zoneArr;
    case "theirCardNotes": $zoneArr = &GetCardNotes($playerID == 1 ? 2 : 1); return $zoneArr;
    case "myVersions": $zoneArr = &GetVersions($playerID); return $zoneArr;
    case "theirVersions": $zoneArr = &GetVersions($playerID == 1 ? 2 : 1); return $zoneArr;
    default: return null;
  }
}

function MZAddZone($player, $zoneName, $cardID) {
  switch($zoneName) {
    case "myCommander": AddCommander($player, CardID:$cardID); break;
    case "theirCommander": AddCommander($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myReserveDeck": AddReserveDeck($player, CardID:$cardID); break;
    case "theirReserveDeck": AddReserveDeck($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myMainDeck": AddMainDeck($player, CardID:$cardID); break;
    case "theirMainDeck": AddMainDeck($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myCardPane": AddCardPane($player, CardID:$cardID); break;
    case "theirCardPane": AddCardPane($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myCommanders": AddCommanders($player, CardID:$cardID); break;
    case "theirCommanders": AddCommanders($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myReserves": AddReserves($player, CardID:$cardID); break;
    case "theirReserves": AddReserves($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myCards": AddCards($player, CardID:$cardID); break;
    case "theirCards": AddCards($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myNumReserve": AddNumReserve($player, CardID:$cardID); break;
    case "theirNumReserve": AddNumReserve($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myCountsDisplay": AddCountsDisplay($player, CardID:$cardID); break;
    case "theirCountsDisplay": AddCountsDisplay($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "mySort": AddSort($player, CardID:$cardID); break;
    case "theirSort": AddSort($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myCardNotes": AddCardNotes($player, CardID:$cardID); break;
    case "theirCardNotes": AddCardNotes($player == 1 ? 2 : 1, CardID:$cardID); break;
    case "myVersions": AddVersions($player, CardID:$cardID); break;
    case "theirVersions": AddVersions($player == 1 ? 2 : 1, CardID:$cardID); break;
    default: break;
  }
}

function MZClearZone($player, $zoneName) {
  switch($zoneName) {
    case "myCommander": $zone = &GetCommander($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCommander": $zone = &GetCommander($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myReserveDeck": $zone = &GetReserveDeck($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirReserveDeck": $zone = &GetReserveDeck($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myMainDeck": $zone = &GetMainDeck($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirMainDeck": $zone = &GetMainDeck($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCardPane": $zone = &GetCardPane($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCardPane": $zone = &GetCardPane($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCommanders": $zone = &GetCommanders($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCommanders": $zone = &GetCommanders($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myReserves": $zone = &GetReserves($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirReserves": $zone = &GetReserves($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCards": $zone = &GetCards($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCards": $zone = &GetCards($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myNumReserve": $zone = &GetNumReserve($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirNumReserve": $zone = &GetNumReserve($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCountsDisplay": $zone = &GetCountsDisplay($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCountsDisplay": $zone = &GetCountsDisplay($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "mySort": $zone = &GetSort($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirSort": $zone = &GetSort($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCardNotes": $zone = &GetCardNotes($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCardNotes": $zone = &GetCardNotes($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myVersions": $zone = &GetVersions($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirVersions": $zone = &GetVersions($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    default: break;
  }
}

?>