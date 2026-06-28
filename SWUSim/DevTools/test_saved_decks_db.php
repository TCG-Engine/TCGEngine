<?php
// curl http://localhost:3400/TCGEngine/SWUSim/DevTools/test_saved_decks_db.php
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';

$uid = 999999; // throwaway test user
$conn = GetLocalMySQLConnection(); $conn->query("DELETE FROM favoritedeck WHERE usersId=$uid"); $conn->close();

$P=0;$F=[];
function ok($n,$c){global $P,$F; $c?$P++:$F[]=$n;}

$rawLink = 'raw:'.sha1('{"x":1}');
ok('add url deck',  AddSavedDeck($uid, 'https://swudb.com/deck/abc', 'Aggro', 'SOR_010', 'SOR_022', 'premier'));
ok('add raw deck',  AddSavedDeck($uid, $rawLink, 'My Raw', 'JTL_005', 'JTL_020', 'premier', base64_encode('{"x":1}')));
$rows = LoadSavedDecks($uid);
ok('loads two', count($rows) === 2);
ok('has cols', isset($rows[0]['baseId'], $rows[0]['wins'], $rows[0]['isFavorite'], $rows[0]['decklink']));
ok('favorite pins to top', SetSavedDeckFavorite($uid, $rawLink, 1) && LoadSavedDecks($uid)[0]['decklink'] === $rawLink);
ok('rename', RenameSavedDeck($uid, 'https://swudb.com/deck/abc', 'Renamed') && in_array('Renamed', array_column(LoadSavedDecks($uid),'name')));
ok('delete', DeleteSavedDeck($uid, 'https://swudb.com/deck/abc') && count(LoadSavedDecks($uid)) === 1);

echo empty($F) ? "PASS ($P)\n" : "FAIL: ".implode(', ',$F)."\n";
