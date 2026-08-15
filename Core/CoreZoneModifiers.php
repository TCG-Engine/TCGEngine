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

  // ── Subcard mzIDs ──────────────────────────────────────────────────────────────────────────────
  // An mzID normally addresses a card in a zone: "<zone>-<index>". A SUBCARD mzID extends that to
  // address an object ATTACHED to that card — an upgrade, a token (Shield/Experience), a captive:
  //
  //     myGroundArena-0.u2   →  Subcards[2] of the unit at myGroundArena index 0
  //
  // This exists so an effect that targets an upgrade can offer the upgrade ITSELF as a choice, on the
  // board, still attached to its host — instead of staging bare CardIDs into a TempZone and showing a
  // context-free popup in which the player cannot tell which unit each upgrade is on.
  //
  // The sub index is the RAW `Subcards` array key, NOT a filtered ordinal. That is deliberate: the key
  // is the only index the server and the renderer can each compute independently (the client walks
  // Subcards by key), and it is the only thing that tells two otherwise-identical instances apart —
  // two Shield tokens on one unit are distinct targets and nothing else distinguishes them.
  //
  // ⚠ The ".u" separator is load-bearing; do not "simplify" it to ".2". `is_numeric("0.2")` is TRUE,
  // and the GENERATED GetZoneObject() ends with `return $zoneArr[$mzArr[1]];` from a by-ref function
  // after guarding on `isset($zoneArr[intval($mzArr[1])])`. For "0.2" the guard passes on intval()==0
  // and the return then AUTO-VIVIFIES a bogus "0.2" string key into the live arena zone, which
  // serializes straight into the gamestate. `is_numeric("0.u2")` is false, so the guard rejects it and
  // GetZoneObject returns null — an un-taught caller gets a clean miss instead of silent corruption.
  function MZIsSubcardID($mzID) {
    return is_string($mzID) && preg_match('/^(.+)-(\d+)\.u(\d+)$/', $mzID) === 1;
  }

  // "myGroundArena-0.u2" → ['host'=>'myGroundArena-0','zone'=>'myGroundArena','hostIndex'=>0,'subIndex'=>2]
  // Returns null for a plain mzID, so callers can branch on it.
  function MZParseSubcardID($mzID) {
    if(!is_string($mzID) || !preg_match('/^(.+)-(\d+)\.u(\d+)$/', $mzID, $m)) return null;
    return ['host' => $m[1] . '-' . $m[2], 'zone' => $m[1],
            'hostIndex' => intval($m[2]), 'subIndex' => intval($m[3])];
  }

  // Resolve ANY mzID — plain or subcard — to its object, or null. Use this instead of GetZoneObject in
  // the GENERIC paths that only hold the mzID string (decision validation, choice counting); code that
  // already has the host in hand should index Subcards directly.
  // A subcard flagged `removed`, or whose host is gone, resolves to null so a stale pick reads as gone
  // rather than as a live target.
  function MZResolveObject($mzID) {
    $sub = MZParseSubcardID($mzID);
    if($sub === null) return GetZoneObject($mzID);
    $host = GetZoneObject($sub['host']);
    if($host === null || !empty($host->removed)) return null;
    $subcards = $host->Subcards ?? null;
    if(!is_array($subcards) || !isset($subcards[$sub['subIndex']])) return null;
    $obj = $subcards[$sub['subIndex']];
    if(is_array($obj)) $obj = (object)$obj;
    if(!is_object($obj) || !empty($obj->removed)) return null;
    return $obj;
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
