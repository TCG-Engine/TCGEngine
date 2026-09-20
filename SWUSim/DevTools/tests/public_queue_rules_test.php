<?php
// SWUSim public-queue rules (SWUSim/PublicQueue.php) — docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §2.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/public_queue_rules_test.php
chdir(dirname(__DIR__, 3));
require_once './APIs/Lobbies/Classes/Player.php';
require_once './SWUSim/Custom/DeckImport.php';
require_once './SWUSim/PublicQueue.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$fixture = fn($rel) => trim(implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/' . $rel)), fn($l) => !str_starts_with($l, '#'))));
$LEGAL = $fixture('meta-2026-09/aggro_vader_yellow.txt');
$ILLEGAL = $fixture('premier_deck_a.txt');

$check(SWUPublicQueueRefusal('premier') === null, 'premier may queue');
$check(SWUPublicQueueRefusal('padawan-preview') === null, 'padawan-preview may queue');
// Owner, 2026-09-20: the Twin Suns family now queues too, REVERSING the "refused toward private
// rooms" this block used to assert. The refusal is gone entirely — SWUPublicQueueRefusal returns null
// — and the room-vs-quick-match distinction is made later, by the lobby adapter, not by a refusal.
foreach (['twinsuns', 'twinsuns-preview', 'teamsuns', 'teamsuns-preview'] as $f) {
    $check(SWUPublicQueueRefusal($f) === null, "$f may queue (as a public room)");
}
$check(SWUPublicQueueRefusal('goldfish') === "Public matchmaking isn't open for this format.", 'a local mode gets the generic refusal');
$check(SWUPublicQueueRefusal('botpractice') === "Public matchmaking isn't open for this format.", 'Arenabot still never queues');

// A Twin Suns list must pass its own format here, and a Premier list must NOT — the deck gate is what
// still refuses a wrong-format deck now that the blanket format refusal is gone.
$TWINSUNS = $fixture('twinsuns_deck_a.txt');
$check(SWUPublicQueueDeckErrors('twinsuns', $TWINSUNS) === [], 'a Twin Suns-legal list has no errors');
$check(!empty(SWUPublicQueueDeckErrors('twinsuns', $LEGAL)), 'a Premier list is refused for twinsuns');

$check(SWUPublicQueueDeckErrors('premier', $LEGAL) === [], 'a Premier-legal list has no errors');
$check(!empty(SWUPublicQueueDeckErrors('premier', $ILLEGAL)), 'an SOR list is not Premier-legal');
$check(SWUPublicQueueDeckErrors('open', $ILLEGAL) === [], 'Open accepts the SOR list');
$check(SWUPublicQueueDeckErrors('premier', '   ') === ['Deck link is required.'], 'an empty deck input is refused');

// ── Pairing-time release (§2.3) ──
$p1 = new Player(1, $LEGAL, '', null);
$p2 = new Player(2, $ILLEGAL, '', null);
$lobby = (object)['format' => 'premier', 'players' => [$p1, $p2], 'numPlayers' => 2, 'maxPlayers' => 2, 'ready' => true];
$failing = SWUQueueFailingSeats($lobby);
$check(array_keys($failing) === [$p2->getAuthKey()], 'only the illegal seat fails at pairing');
$check(str_contains($failing[$p2->getAuthKey()] ?? '', 'no longer legal for premier'), 'the failing seat is told why');
$lobby->testFailAtPairing = [$p1->getAuthKey()];
$both = SWUQueueFailingSeats($lobby);
$check(count($both) === 2 && str_contains($both[$p1->getAuthKey()] ?? '', 'test hook'), 'the test hook fails a legal seat');
unset($lobby->testFailAtPairing);
SWUQueueDropSeats($lobby, [$p2->getAuthKey() => 'gone-reason']);
$check(count($lobby->players) === 1 && $lobby->players[0]->getAuthKey() === $p1->getAuthKey(), 'the surviving seat keeps the lobby');
$check($lobby->numPlayers === 1 && $lobby->ready === false, 'the lobby is searching again');
$check(($lobby->queueNotices[$p2->getAuthKey()] ?? null) === 'gone-reason', 'the released seat has a notice');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
