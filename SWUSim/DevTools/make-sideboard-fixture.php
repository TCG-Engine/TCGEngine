<?php
// Seed a match parked in the SIDEBOARD state, so SWUSim/Sideboard.php can actually be opened.
//
// That page 404s without a real match, which makes it the one player-facing screen with no cheap
// way to look at it — and it is why it kept its original cyan-on-navy styling through a site-wide
// redesign that touched every other page. This makes it a one-liner.
//
//   php SWUSim/DevTools/make-sideboard-fixture.php          # create (prints the URL)
//   php SWUSim/DevTools/make-sideboard-fixture.php --remove # delete it again
//
// ⚠ Uses a SACRIFICIAL match id well outside the real counter's range, so it can never collide
// with a real match or be mistaken for one. It writes nothing else: no lobby, no game, no row.
require_once __DIR__ . '/../../Core/Match/Match.php';

const FIXTURE_ID = 'zzsideboardfixture';
const SEAT       = 1;

$path = MatchPath('SWUSim', FIXTURE_ID);
if ($path === '') { fwrite(STDERR, "could not resolve a match path\n"); exit(1); }

if (in_array('--remove', $argv, true)) {
    if (is_file($path)) { unlink($path); @rmdir(dirname($path)); echo "removed " . FIXTURE_ID . "\n"; }
    else echo "nothing to remove\n";
    exit(0);
}

// A real Premier list: a leader, a base, a main deck with some duplicates (so the quantity badge
// renders) and a sideboard with something in it (so BOTH grids have content — an empty grid hides
// exactly the styling this fixture exists to check).
$main = array_merge(
    array_fill(0, 3, 'SOR_235'), array_fill(0, 3, 'SOR_236'), array_fill(0, 2, 'SOR_010'),
    array_fill(0, 2, 'SOR_038'), ['SOR_100', 'SOR_101', 'SOR_102', 'SOR_103', 'SOR_104']
);
$side = ['SOR_105', 'SOR_105', 'SOR_106'];
$deck = ['leader' => 'SOR_001', 'base' => 'SOR_023', 'mainDeck' => $main, 'sideboard' => $side];

@mkdir(dirname($path), 0775, true);
MatchWrite([
    'matchId'   => FIXTURE_ID,
    'rootName'  => 'SWUSim',
    'format'    => 'premier',
    'queueType' => 'bo3',
    'bestOf'    => 3,
    'winsNeeded'=> 2,
    // ⚠ NOT 'in_progress'. Sideboard.php redirects straight to the next game when the state is
    // in_progress and a game exists, so the page would never render.
    'state'     => 'sideboarding',
    'players'   => [
        '1' => ['originalDeck' => $deck, 'currentDeck' => $deck, 'authKey' => 'fixture', 'userId' => null],
        '2' => ['originalDeck' => $deck, 'currentDeck' => $deck, 'authKey' => 'fixture', 'userId' => null],
    ],
    'games'     => [['gameName' => 'zzfixturegame1', 'gameNumber' => 1, 'winner' => 2]],
    'wins'      => ['1' => 0, '2' => 1],
    'currentGameNumber' => 1,
    'winner'    => null,
    'createdAt' => time(),
]);

echo "created " . FIXTURE_ID . "\n";
echo "  /TCGEngine/SWUSim/Sideboard.php?matchId=" . FIXTURE_ID . "&playerID=" . SEAT . "&authKey=fixture\n";
