<?php
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_saved_decks_endpoint.php
header('Content-Type: text/plain');
$_SESSION['userid'] = 999998;
$_POST = ['action'=>'save', 'deckInput'=>'{"metadata":{"name":"Test Deck"},"leader":{"id":"SOR_010"},"base":{"id":"SOR_022"},"deck":[]}'];
$GLOBALS['__SAVEDECKS_TEST'] = true;     // router returns instead of echoing under test
require __DIR__ . '/../../Database/ConnectionManager.php';
require __DIR__ . '/../../Database/functions.inc.php';
$conn = GetLocalMySQLConnection(); $conn->query("DELETE FROM favoritedeck WHERE usersId=999998"); $conn->close();
$r = require __DIR__ . '/../SavedDecks.php';
$rows = LoadSavedDecks(999998);
echo ($r['success'] && count($rows)===1 && $rows[0]['hero']==='SOR_010' && $rows[0]['baseId']==='SOR_022')
     ? "PASS\n" : "FAIL: ".json_encode($r)." rows=".json_encode($rows)."\n";
