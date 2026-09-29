<?php
// The label registry (SWUSim/Custom/BotDeckLabels.json) is generated from the OWNER-LABELLED fixtures in
// SWUSim/Tests/BotFixtures/meta-2026-09/ by SWUSim/DevTools/regen-deck-labels.php. The field set
// (meta-2026-09-field/, deleted 2026-09-29) was EXCLUDED on purpose: its styles were unreviewed guesses, and an unreviewed
// label must never trigger the 75% instant label.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_decklabels_test.php
chdir(dirname(__DIR__, 3));
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};

$path = './SWUSim/Custom/BotDeckLabels.json';
$check(is_file($path), 'the registry exists');
$reg = json_decode((string)@file_get_contents($path), true);
$check(is_array($reg) && isset($reg['decks']), 'the registry parses');
$decks = $reg['decks'] ?? [];
$files = glob('./SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') ?: [];
$check(count($decks) === count($files), 'one entry per owner-labelled fixture (' . count($files) . ')', (string)count($decks));

$STYLES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];
$byFile = [];
foreach ($decks as $d) $byFile[$d['file']] = $d;
foreach ($files as $f) {
    $name = basename($f, '.txt');
    $d = $byFile[$name] ?? null;
    if ($d === null) { $check(false, "$name is in the registry"); continue; }
    // The classifier's label is '# DeckStyle:' when the fixture carries one (what the deck IS), else '# Style:'
    // (how the bot pilots it). They differ only where the owner has ruled so — Maul Blue Force, 2026-09-22.
    $text = (string)file_get_contents($f);
    preg_match('/^# DeckStyle:\s*(\S+)/m', $text, $dm);
    preg_match('/^# Style:\s*(\S+)/m', $text, $m);
    $want = $dm[1] ?? $m[1] ?? '';
    $check(($d['style'] ?? '') === $want, "$name is labelled $want", strval($d['style'] ?? ''));
    $check(in_array($d['style'], $STYLES, true), "$name has one of the five archetype ids");
    $check(preg_match('/^[A-Z0-9]+_[0-9]{3}$/', strval($d['leader'] ?? '')) === 1, "$name has a SET_NNN leader", strval($d['leader'] ?? ''));
    $check(($d['total'] ?? 0) >= 45, "$name has a full main deck", strval($d['total'] ?? 0));
    $check(array_sum($d['cards'] ?? []) === ($d['total'] ?? -1), "$name's counts sum to its total");
}
// The registry must hold ONLY the reviewed meta set.
// ⚠ This used to be "no field basename appears in the registry", which broke on the 2026-09-29 rename:
// every fixture is now named <leader>_<set>_<base-archetype>, so a field deck and a meta deck that are the
// SAME DECK now share a basename (luke-skywalker_ash_data-vault is in both dirs, byte-identical). A name
// collision across directories is therefore no longer evidence of anything. Asserting the registry keys are
// a SUBSET of the meta dir says what was actually meant, and is immune to that coincidence.
$metaNames = array_map(fn($p) => basename($p, '.txt'), glob('./SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') ?: []);
$stray = array_values(array_diff(array_keys($byFile), $metaNames));
$check(empty($stray), 'the registry holds only reviewed meta-2026-09 fixtures', implode(',', $stray));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
