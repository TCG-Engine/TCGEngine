<?php
// Every opponent the deck simulator OFFERS must also RESOLVE. Nothing tested this, and on 2026-09-29 the bot
// fixture rename (<leader-title>_<set>_<base-archetype>, e.g. "director-krennic_law_blue-splash") broke the
// whole feature: swuSimulationOpponentPath() validated ids against /^[a-z0-9_]+$/, a class with no HYPHEN, so
// 82 of the 83 ids that swuSimulationOpponents() itself lists were refused and every pick threw
// "Choose an available opponent." The list and the validator disagreed, which no amount of renaming should be
// able to cause again.
//
// ⚠ Do NOT assert a literal id or a deck count here. Both change whenever the fixture roster does, and a test
// that has to be edited for every roster change is the thing that stops being run. The INVARIANT is
// list ⊆ resolvable, plus the path-traversal refusals.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/simulation_opponents_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUDeck/SimulationRuntime.php';

$fails = 0;
$check = function (bool $ok, string $label, string $detail = '') use (&$fails) {
    if (!$ok) { $fails++; echo "FAIL: {$label}" . ($detail !== '' ? "  [{$detail}]" : '') . "\n"; }
    else echo "PASS: {$label}" . ($detail !== '' ? "  [{$detail}]" : '') . "\n";
};

$ops = swuSimulationOpponents();
$ids = array_values(array_map(fn($o) => is_array($o) ? strval($o['id'] ?? '') : strval($o), $ops));
$check(count($ids) >= 20, 'the simulator offers a populated opponent list', strval(count($ids)));
$check(count($ids) === count(array_unique($ids)), 'no duplicate opponent ids',
    implode(',', array_diff_assoc($ids, array_unique($ids))));

// THE INVARIANT: everything offered resolves to a real fixture file.
$unresolvable = [];
foreach ($ids as $id) {
    try {
        $p = swuSimulationOpponentPath($id);
        if (!is_file($p)) $unresolvable[] = "{$id} (no file)";
    } catch (Throwable $e) { $unresolvable[] = "{$id} ({$e->getMessage()})"; }
}
$check(empty($unresolvable), 'every offered opponent RESOLVES to a fixture',
    implode('; ', array_slice($unresolvable, 0, 5)) . (count($unresolvable) > 5 ? ' …+' . (count($unresolvable) - 5) : ''));

// The hyphen specifically — the character whose absence caused the outage.
$hyphenated = array_values(array_filter($ids, fn($i) => strpos($i, '-') !== false));
$check(!empty($hyphenated), 'the roster contains hyphenated ids (the rename convention is in use)',
    strval(count($hyphenated)) . ' of ' . count($ids));

// Widening the class must not have opened a traversal. An id becomes a path, so '.' and '/' must stay out.
foreach (['../../etc/passwd', 'foo/bar', 'foo.bar', '..', '.', '', 'Foo-Bar', 'foo bar', 'foo%2Fbar'] as $evil) {
    $refused = false;
    try { swuSimulationOpponentPath($evil); } catch (Throwable $e) { $refused = true; }
    $check($refused, "refuses the unsafe id '" . $evil . "'");
}

echo $fails === 0 ? "\nALL PASS\n" : "\n{$fails} FAILED\n";
exit($fails === 0 ? 0 : 1);
