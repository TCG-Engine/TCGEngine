<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../Mod/DevGate.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

// getenv path (CLI / if FPM env is ever fixed)
putenv('DEVENV=true');
ok(SWUIsLocalDevRequest() === true, "DEVENV=true -> true");

// host path (clear DEVENV so the host check is what's exercised)
putenv('DEVENV=');
$_SERVER['HTTP_HOST'] = 'localhost:3100';
ok(SWUIsLocalDevRequest() === true, "localhost host -> true");
$_SERVER['HTTP_HOST'] = '127.0.0.1:3100';
ok(SWUIsLocalDevRequest() === true, "loopback host -> true");
$_SERVER['HTTP_HOST'] = 'swustats.net';
ok(SWUIsLocalDevRequest() === false, "production host -> false");
unset($_SERVER['HTTP_HOST']);
ok(SWUIsLocalDevRequest() === false, "no host -> false");

echo "PASS=$pass FAIL=$fail\n";
