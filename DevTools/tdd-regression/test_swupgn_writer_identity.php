<?php
// SWU-PGN writer identity: the server secret, keyed ids (§17), GameId, and the Engine build id (§5.3).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_identity.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';

$noKeys = sys_get_temp_dir() . '/swupgn-nokeys-' . getmypid() . '.php';   // an APIKeys stand-in without the secret
file_put_contents($noKeys, "<?php\n");
$GLOBALS['SWUPGN_KEYS_FILE'] = $noKeys;

// Keyed (published) scheme: the same account hashes the SAME in every game.
putenv('SWUPGN_ID_SECRET=test-secret-1'); putenv('DEVENV=false');
$k1 = SwuPgnHashId('acct:42', 'g1'); $k2 = SwuPgnHashId('acct:42', 'g2');
SwuPgnTestCheck(preg_match('/^sha256:[0-9a-f]{64}$/', $k1) === 1, 'a hashed id is sha256:<64 hex>', $k1);
SwuPgnTestCheck($k1 === $k2, 'keyed: the same account hashes the same across games');
SwuPgnTestCheck(SwuPgnHashId('acct:43', 'g1') !== $k1, 'keyed: different accounts hash differently');
SwuPgnTestCheck(SwuPgnSubKey('id') !== '' && SwuPgnSubKey('id') !== SwuPgnSubKey('seal'), 'the id and seal keys are derived separately');
putenv('SWUPGN_ID_SECRET=test-secret-2');
SwuPgnTestCheck(SwuPgnHashId('acct:42', 'g1') !== $k1, 'the secret is re-read: changing it changes the id');

// No secret, not dev: §17's per-game salted fallback.
putenv('SWUPGN_ID_SECRET');
SwuPgnTestEq(SwuPgnIdSecret(), '', 'no env, no APIKeys value, not dev → no secret');
SwuPgnTestEq(SwuPgnSubKey('seal'), '', 'no secret → no sub-key (sealing must refuse)');
$s1 = SwuPgnHashId('acct:42', 'g1');
SwuPgnTestEq($s1, 'sha256:' . hash('sha256', 'g1:acct:42'), 'unkeyed: sha256("<gameSalt>:<value>")');
SwuPgnTestCheck($s1 !== SwuPgnHashId('acct:42', 'g2'), 'unkeyed: the same account hashes differently per game');

// DEVENV: a fixed dev secret.
putenv('DEVENV=true');
SwuPgnTestEq(SwuPgnIdSecret(), 'dev-swupgn-secret', 'dev → the fixed dev secret');
putenv('DEVENV=false');

// APIKeys.php global (when no env).
$withKey = sys_get_temp_dir() . '/swupgn-keys-' . getmypid() . '.php';
file_put_contents($withKey, "<?php\n\$swupgnIdSecret = 'from-apikeys';\n");
$GLOBALS['SWUPGN_KEYS_FILE'] = $withKey;
SwuPgnTestEq(SwuPgnIdSecret(), 'from-apikeys', 'APIKeys.php supplies the secret when the env does not');
$GLOBALS['SWUPGN_KEYS_FILE'] = $noKeys;

// GameId: GUID-shaped, stable.
$gid = SwuPgnGameId('SWUSim', '123');
SwuPgnTestCheck(preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $gid) === 1, 'GameId is GUID-shaped', $gid);
SwuPgnTestEq(SwuPgnGameId('SWUSim', '123'), $gid, 'GameId is stable for the same game');
SwuPgnTestCheck(SwuPgnGameId('SWUSim', '124') !== $gid, 'GameId differs per game');

// Engine id from .git.
$repo = sys_get_temp_dir() . '/swupgn-repo-' . getmypid();
@mkdir("$repo/.git/refs/heads", 0777, true);
file_put_contents("$repo/.git/HEAD", "ref: refs/heads/main\n");
file_put_contents("$repo/.git/refs/heads/main", "abcdef1234567890abcdef1234567890abcdef12\n");
SwuPgnTestEq(SwuPgnEngineId($repo), 'petranaki@abcdef1', 'Engine from a loose ref');
unlink("$repo/.git/refs/heads/main");
file_put_contents("$repo/.git/packed-refs", "# pack-refs with: peeled fully-peeled sorted\n1234567abcdef1234567abcdef1234567abcdef0 refs/heads/main\n");
SwuPgnTestEq(SwuPgnEngineId($repo), 'petranaki@1234567', 'Engine from packed-refs');
file_put_contents("$repo/.git/HEAD", "fedcba9876543210fedcba9876543210fedcba98\n");
SwuPgnTestEq(SwuPgnEngineId($repo), 'petranaki@fedcba9', 'Engine from a detached HEAD');
exec('rm -rf ' . escapeshellarg($repo));
SwuPgnTestEq(SwuPgnEngineId($repo), 'petranaki@unknown', 'no .git → the @unknown sentinel');

@unlink($noKeys); @unlink($withKey);
SwuPgnTestFinish();
