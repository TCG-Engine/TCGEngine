<?php
// Creating a High Ground room is MODERATORS ONLY for now (owner, 2026-11-02); joining one stays open to anyone.
// Tested OUTSIDE local dev — a production Host with DEVENV off — because local dev counts as a moderator, so a check made
// there cannot tell an open gate from a closed one. The menu (SharedUI/Sites/SWUSim/MainMenu.php) and the endpoint
// (APIs/Lobbies/JoinQueue.php) use the same helper (SWUSim/Mod/DevGate.php).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/high_ground_create_gate_test.php
@session_start();   // before $_SESSION is set: the gate's CheckSession() must find this session, not start a fresh one
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Mod/DevGate.php';
require_once './AccountFiles/AccountSessionAPI.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

putenv('DEVENV=false');   // the CLI carries DEVENV=true in the container; the gate must be tested without it
unset($_SERVER['REMOTE_ADDR']);
$as = function (?string $user) {
    $_SERVER['HTTP_HOST'] = 'petranaki.net';
    if ($user === null) unset($_SESSION['useruid']); else $_SESSION['useruid'] = $user;
    return SWUHighGroundCreateAllowed();
};
$mod = ApprovedModeratorUserNames()[0];
$check($as(null) === false, 'production, logged out: refused');
$check($as('someplayer') === false, 'production, an ordinary account: refused');
$check($as($mod) === true, "production, a moderator ($mod): allowed");

$as('someplayer');
$check(is_string(SWUHighGroundCreateRefusal()) && SWUHighGroundCreateRefusal() !== '', 'the endpoint refusal has a message for a non-moderator');
$as($mod);
$check(SWUHighGroundCreateRefusal() === null, 'the endpoint refusal is null for a moderator');

// The endpoint itself, in a separate CLI process as a production request from an ordinary account. Runs only up to the
// gate: a refused creation exits before any lobby is written.
$endpoint = function (array $post, string $user): array {
    $code = '$_SERVER["HTTP_HOST"]="petranaki.net"; unset($_SERVER["REMOTE_ADDR"]); putenv("DEVENV=false");'
          . '@session_start(); $_SESSION["useruid"]=' . var_export($user, true) . ';'
          . '$_POST=' . var_export($post, true) . '; chdir("APIs/Lobbies"); include "JoinQueue.php";';
    $out = shell_exec('php -d xdebug.mode=off -d display_errors=0 -r ' . escapeshellarg($code) . ' 2>/dev/null');
    $j = json_decode((string)$out, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$out, 0, 300)];
};
$base = ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'premier', 'queueType' => 'bo1', 'deckLink' => ''];
$r = $endpoint($base + ['highGround' => '1'], 'someplayer');
$check(($r['success'] ?? null) === false && ($r['message'] ?? '') === 'Creating a High Ground room is limited to moderators for now.',
    'JoinQueue refuses a High Ground creation from an ordinary account: ' . json_encode($r));
$r = $endpoint($base, 'someplayer');
$check(($r['message'] ?? '') !== 'Creating a High Ground room is limited to moderators for now.',
    'JoinQueue does not apply the gate to a plain private room: ' . json_encode($r));

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
