<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
$U = 999999; $DL = 'https://swudb.com/deck/_dbtest_';
$c = GetLocalMySQLConnection();
// clean slate
$c->query("DELETE FROM favoritedeck WHERE usersId=$U");
$c->query("DELETE FROM favoritedeckmatchup WHERE usersId=$U");
$pass = 0; $fail = 0;
function ok($cond,$msg){ global $pass,$fail; if($cond){$pass++;}else{$fail++; echo "FAIL: $msg\n";} }

// no saved deck yet → 0 affected, no-op
ok(RecordSavedDeckResult($U,$DL,true) === 0, "result on unsaved deck returns 0");

// save a deck, then record
AddSavedDeck($U,$DL,'T','ASH_005','JTL_024','premier',null);
ok(RecordSavedDeckResult($U,$DL,true) === 1, "win increments (1 affected)");
ok(RecordSavedDeckResult($U,$DL,false) === 1, "loss increments (1 affected)");
$row = LoadSavedDecks($U)[0];
ok((int)$row['wins']===1 && (int)$row['losses']===1, "wins=1 losses=1");
ok(!empty($row['lastUsed']), "lastUsed got bumped");

// matchups upsert
ok(RecordSavedDeckMatchup($U,$DL,'ASH_006','Green 30HP',true)===true, "matchup insert win");
ok(RecordSavedDeckMatchup($U,$DL,'ASH_006','Green 30HP',false)===true, "matchup increment loss");
ok(RecordSavedDeckMatchup($U,$DL,'JTL_005','Red Force',true)===true, "matchup second bucket");
$ms = LoadSavedDeckMatchups($U,$DL);
ok(count($ms)===2, "two matchup rows");
$first = $ms[0]; // most-played first → Green 30HP has 2 games
ok($first['oppBase']==='Green 30HP' && (int)$first['wins']===1 && (int)$first['losses']===1, "Green 30HP 1-1");

// cleanup
$c->query("DELETE FROM favoritedeck WHERE usersId=$U");
$c->query("DELETE FROM favoritedeckmatchup WHERE usersId=$U");
echo "PASS=$pass FAIL=$fail\n";
