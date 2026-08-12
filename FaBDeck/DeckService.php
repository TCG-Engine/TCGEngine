<?php

require_once __DIR__ . '/../Database/ConnectionManager.php';

function FaBDeckLoadOwnedDeck($deckID, $userID) {
    if (!preg_match('/^\d+$/', (string)$deckID) || trim((string)$userID) === '') return null;
    $conn = GetLocalMySQLConnection(); if (!$conn) return null;
    $stmt = $conn->prepare('SELECT * FROM ownership WHERE assetType = 1 AND assetIdentifier = ? AND assetOwner = ? AND assetStatus = 1 LIMIT 1');
    if (!$stmt) {$conn->close(); return null;}
    $stmt->bind_param('ss', $deckID, $userID); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close(); $conn->close(); return $row;
}

function FaBDeckReadDeckState($deckID) {
    $failure = FaBEmptyResolvedDeck('Could not load the selected FaBDeck deck.');
    if (!preg_match('/^\d+$/', (string)$deckID)) return $failure;
    $file = __DIR__ . '/Games/' . $deckID . '/Gamestate.txt';
    if (!is_file($file)) return $failure;
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines) || count($lines) < 4) return $failure;
    $position = 2;
    $readZone = function() use (&$lines, &$position) {
        if (!isset($lines[$position]) || !preg_match('/^\d+$/', trim($lines[$position]))) return null;
        $count = intval(trim($lines[$position++])); $cards=[];
        for($i=0;$i<$count;++$i) {
            if(!isset($lines[$position])) return null;
            $parts=preg_split('/\s+/',trim($lines[$position++]));
            if(($parts[0]??'')!=='' && $parts[0]!=='-')$cards[]=$parts[0];
        }
        return $cards;
    };
    $hero=$readZone(); $readZone();
    $weapons=$readZone(); $readZone();
    $equipment=$readZone(); $readZone();
    $mainDeck=$readZone(); $readZone();
    // CardPane, Heroes, WeaponsCatalog, EquipmentCatalog, Cards (both seats).
    for($i=0;$i<10;++$i) if($readZone()===null)return $failure;
    $inventory=$readZone(); $readZone();
    if($hero===null||$weapons===null||$equipment===null||$mainDeck===null||$inventory===null)return $failure;
    $result = ['success'=>false,'message'=>'','hero'=>$hero[0] ?? '','weapons'=>$weapons,'equipment'=>$equipment,'mainDeck'=>$mainDeck,'inventory'=>$inventory,'unresolved'=>[]];
    return FaBFinalizeResolvedDeck($result);
}

function FaBDeckResolveOwnedDeck($deckID, $userID) {
    if (!FaBDeckLoadOwnedDeck($deckID, $userID)) return FaBEmptyResolvedDeck('That FaBDeck deck is unavailable or does not belong to your account.');
    return FaBDeckReadDeckState($deckID);
}

?>
