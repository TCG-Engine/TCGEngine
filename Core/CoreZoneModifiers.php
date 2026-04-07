<?php

  include_once __DIR__ . '/DeterministicRNG.php';

  function SearchZoneForCard($zoneName, $cardID, $playerID = "") {
    if($zoneName === null || $zoneName === "") return null;
    $zoneName = explode("-", $zoneName)[0];//In case it's an mzid
    $zone = &GetZone($zoneName);
    for($i=0; $i<count($zone); ++$i) {
      $card = $zone[$i];
      if($card->CardID == $cardID && !$card->Removed()) {
        return $card;
      }
    }
    return null;
  }

  function ZoneCount($zoneName) {
    if($zoneName === null || $zoneName === "") return 0;
    $zoneName = explode("-", $zoneName)[0];//In case it's an mzid
    $zone = &GetZone($zoneName);
    $count = 0;
    for($i=0; $i<count($zone); ++$i) {
      if(!$zone[$i]->Removed()) {
        ++$count;
      }
    }
    return $count;
  }

  function ZoneMZIndices($zoneName) {
    if($zoneName === null || $zoneName === "") return "";
    $zoneName = explode("-", $zoneName)[0];//In case it's an mzid
    $zone = &GetZone($zoneName);
    $mzIndices = [];
    for($i=0; $i<count($zone); ++$i) {
      if(!$zone[$i]->Removed()) {
        array_push($mzIndices, $zoneName . "-" . $i);
      }
    }
    return implode("&", $mzIndices);
  }

  function ZoneObjMZIndices($zone, $prefix) {
    $mzIndices = [];
    for($i=0; $i<count($zone); ++$i) {
      if(!$zone[$i]->Removed()) {
        array_push($mzIndices, $prefix . "-" . $i);
      }
    }
    return implode("&", $mzIndices);
  }

  function PropertyContains($property, $value) {
    if($property === null || $property === "") return false;
    $propertyArr = explode(",", $property);
    return in_array($value, $propertyArr);
  }

  function ShuffleZone($zoneName) {
    if($zoneName === null || $zoneName === "") return;
    $zone = &GetZone($zoneName);
    EngineShuffle($zone);
  }

?>
