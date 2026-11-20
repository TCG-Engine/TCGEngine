<?php
// Creating a Limited (Sealed / Draft) room is OPEN TO EVERYONE, guests included (owner, 2026-11-05; moderators only before).
// Tested OUTSIDE local dev — a production Host with DEVENV off — because local dev counts as a moderator, so a check made
// there cannot tell an open gate from a mods-only one. The menu (SharedUI/Sites/SWUSim/MainMenu.php) and the endpoint
// (APIs/Lobbies/JoinQueue.php) use the same helper (SWUSim/Mod/DevGate.php). Same shape as high_ground_create_gate_test.php.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/limited_create_gate_test.php
@session_start();   // before $_SESSION is set: the gate's CheckSession() must find this session, not start a fresh one
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Mod/DevGate.php';
require_once './AccountFiles/AccountSessionAPI.php';
require_once './SWUSim/Rooms.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
const LT_GATE_MESSAGE = 'Creating a Limited room is limited to moderators for now.';

putenv('DEVENV=false');   // the CLI carries DEVENV=true in the container; the gate must be tested without it
unset($_SERVER['REMOTE_ADDR']);
$as = function (?string $user) {
    $_SERVER['HTTP_HOST'] = 'petranaki.net';
    if ($user === null) unset($_SESSION['useruid']); else $_SESSION['useruid'] = $user;
    return SWULimitedCreateAllowed();
};
$mod = ApprovedModeratorUserNames()[0];
$check($as(null) === true, 'production, logged out (a guest): allowed');
$check($as('someplayer') === true, 'production, an ordinary account: allowed');
$check($as($mod) === true, "production, a moderator ($mod): allowed");

$as('someplayer');
$check(SWULimitedCreateRefusal() === null, 'the endpoint refusal is null for an ordinary account');
$as(null);
$check(SWULimitedCreateRefusal() === null, 'the endpoint refusal is null for a guest');

// The endpoint itself, in a separate CLI process as a production request from an ordinary account. A Limited room needs
// no deck, so a creation the gate lets through may write a room: its disk snapshot is removed afterwards.
$endpoint = function (array $post, string $user): array {
    $code = '$_SERVER["HTTP_HOST"]="petranaki.net"; unset($_SERVER["REMOTE_ADDR"]); putenv("DEVENV=false");'
          . '@session_start(); $_SESSION["useruid"]=' . var_export($user, true) . ';'
          . '$_POST=' . var_export($post, true) . '; chdir("APIs/Lobbies"); include "JoinQueue.php";';
    $out = shell_exec('php -d xdebug.mode=off -d display_errors=0 -r ' . escapeshellarg($code) . ' 2>/dev/null');
    $j = json_decode((string)$out, true);
    return is_array($j) ? $j : ['RAW' => substr((string)$out, 0, 300)];
};
$base = ['rootName' => 'SWUSim', 'createPrivate' => '1', 'format' => 'limited', 'queueType' => 'bo1', 'deckLink' => '', 'preconstructedDeck' => ''];
foreach (['sealed', 'draft'] as $mode) {
    $r = $endpoint($base + ['limitedMode' => $mode], 'someplayer');
    $check(!array_key_exists('RAW', $r) && ($r['message'] ?? '') !== LT_GATE_MESSAGE,
        "JoinQueue lets a $mode Limited creation from an ordinary account past the gate: " . json_encode(array_intersect_key($r, ['success' => 1, 'message' => 1, 'RAW' => 1])));
    foreach ([SWURoomPath(strval($r['lobbyID'] ?? '')), SWURoomInvitePath(strval($r['inviteCode'] ?? ''))] as $f) if ($f !== '' && is_file($f)) @unlink($f);
}

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
