<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Cosmetics/Catalog.php';
$U=999100; $c=GetLocalMySQLConnection(); $c->query("DELETE FROM usercosmetic WHERE usersId=$U");
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

// catalog
ok(SWUCosmeticSlots()===['background','cardback','playmat'], "three slots");
ok(SWUCosmeticDefault('playmat')==='none', "playmat default none");
ok(SWUCosmeticResolve('playmat','none')['asset']===null, "none asset null");
ok(SWUCosmeticResolve('background','bogus')['id']==='default', "unknown -> default");

// defaults for unset user
$cos = LoadUserCosmetics($U);
ok($cos['background']['id']==='default' && $cos['cardback']['id']==='classic' && $cos['playmat']['id']==='none', "unset -> all defaults");

// set + read back
ok(SetUserCosmetic($U,'background','spcgnd')===true, "set background spcgnd");
ok(SetUserCosmetic($U,'background','bogus')===false, "reject invalid choice");
ok(SetUserCosmetic($U,'nope','x')===false, "reject invalid slot");
$cos = LoadUserCosmetics($U);
ok($cos['background']['id']==='spcgnd' && strpos($cos['background']['asset'],'spcgnd')!==false, "saved background applied");
ok($cos['playmat']['id']==='none', "other slots still default");

// seat resolver: null user -> defaults
ok(SWUResolveSeatCosmetics(null)['cardback']['id']==='classic', "null seat -> defaults");

$c->query("DELETE FROM usercosmetic WHERE usersId=$U");
echo "PASS=$pass FAIL=$fail\n";
