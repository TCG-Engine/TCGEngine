<?php
// Guard: PRIVATE matches get NO forced sideboard timer (players take as long as they want); PUBLIC matches
// keep the shared countdown and still auto-fill an un-ready seat with its original deck on timeout.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_sideboard_private_no_timer.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';   // pulls in Match.php + registers nothing

$ROOT = 'SBTimerTest';
MatchRegisterHooks($ROOT, [
    'resolveLobbyDecks' => function ($l) { return null; },
    'validateDeck'      => function ($d, $f) { return true; },
    'setupGame'         => function ($lobby, $opts) {
        $n = 'sbt' . substr(md5(uniqid('', true)), 0, 8);
        @mkdir(__DIR__ . '/../../SBTimerTest/Games/' . $n, 0777, true); // so MatchWriteRef's pointer write lands
        return $n;
    },
]);

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$players = [1 => ['originalDeck' => ['mainDeck' => ['cardX']], 'authKey' => 'a1'],
            2 => ['originalDeck' => ['mainDeck' => ['cardY']], 'authKey' => 'a2']];
$deck1 = ['mainDeck' => ['cardX']];

// ── PRIVATE: no deadline, timeout never fires ────────────────────────────────
$priv = MatchCreate($ROOT, 'premier', 'bo3', $players, true);
$m = MatchRead($ROOT, $priv);
$check(($m['isPrivate'] ?? null) === true, 'private match stores isPrivate=true');
MatchBeginSideboarding($ROOT, $priv, 1);
$m = MatchRead($ROOT, $priv);
$check(!isset($m['sideboardDeadline']), 'PRIVATE: no sideboardDeadline set');
MatchSubmitSideboardDeck($ROOT, $priv, 1, $deck1);   // only seat 1 submits
// Even simulating "way past 180s", the timeout must NOT force seat 2 or advance the match.
MatchSideboardTimeoutCheck($ROOT, $priv);
$m = MatchRead($ROOT, $priv);
$check(($m['state'] ?? '') === 'sideboarding', 'PRIVATE: still sideboarding after timeout check (no forced advance)');
$check(!MatchSideboardSeatReady($m, 2), 'PRIVATE: seat 2 NOT force-readied');

// ── PUBLIC: deadline set, timeout auto-fills the un-ready seat ────────────────
$pub = MatchCreate($ROOT, 'premier', 'bo3', $players, false);
$m = MatchRead($ROOT, $pub);
$check(($m['isPrivate'] ?? null) === false, 'public match stores isPrivate=false');
MatchBeginSideboarding($ROOT, $pub, 1);
$m = MatchRead($ROOT, $pub);
$check(isset($m['sideboardDeadline']), 'PUBLIC: sideboardDeadline IS set');
MatchSubmitSideboardDeck($ROOT, $pub, 1, $deck1);    // only seat 1 submits
MatchWithLock($ROOT, $pub, function (&$mm) { $mm['sideboardDeadline'] = time() - 1; }); // deadline passed
MatchSideboardTimeoutCheck($ROOT, $pub);
$m = MatchRead($ROOT, $pub);
$check(($m['state'] ?? '') === 'in_progress', 'PUBLIC: timeout advanced the match (seat 2 auto-filled + spawned)');

// cleanup — Games AND Matches. Matches/ used to be left behind, so the final rmdir silently failed on
// a non-empty dir and every run accreted another M<n> dir plus a bumped MatchIDCounter.txt. Some of
// that debris had been committed, so simply RUNNING this test showed up as repo changes.
foreach ([$priv, $pub] as $id) { $p = MatchPath($ROOT, $id); if (is_file($p)) @unlink($p); }
$sbRoot = __DIR__ . '/../../SBTimerTest';
foreach (['Games', 'Matches'] as $sub) {
    @array_map('unlink', glob("$sbRoot/$sub/*/*") ?: []);   // files inside each per-id dir
    @array_map('rmdir',  glob("$sbRoot/$sub/*") ?: []);      // the per-id dirs (no-op on plain files)
    @array_map('unlink', glob("$sbRoot/$sub/*") ?: []);      // loose files, e.g. MatchIDCounter.txt
    @rmdir("$sbRoot/$sub");
}
@rmdir($sbRoot);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
