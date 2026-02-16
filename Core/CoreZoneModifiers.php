<?php

  function SearchZoneForCard($zoneName, $cardID, $playerID = "") {
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

?>