<?php
// The label registry (SWUSim/Custom/BotDeckLabels.json) is generated from the OWNER-LABELLED fixture GROUPS by
// SWUSim/DevTools/regen-deck-labels.php (SWU_PRECON_GROUPS, owner 2026-10-01): every group feeds the Arenabot pre-con
// picker; only a group marked 'classify' — the reviewed tournament set, ash-meta-2026-09 — may feed the deck-style
// CLASSIFIER. The field set (meta-2026-09-field/, deleted 2026-09-29) was EXCLUDED on purpose: its styles were
// unreviewed guesses, and an unreviewed label must never trigger the 75% instant label. A creator's predictions are
// reviewed, but they are offered to PLAY against, not used as ground truth for a player's list.
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
$groups = $reg['groups'] ?? [];
$check(array_column($groups, 'id') === ['ash-meta-2026-09', 'force-fam-HMW-predictions'],
    'the picker groups, in order', json_encode(array_column($groups, 'id')));
$check(array_column($groups, 'label') === ['ASH Meta September 2026', 'Force Fam HMW Predictions'],
    'the picker group labels (owner 2026-10-01)', json_encode(array_column($groups, 'label')));
$classifyIds = array_column(array_filter($groups, fn($g) => !empty($g['classify'])), 'id');
$check($classifyIds === ['ash-meta-2026-09'], 'only the reviewed tournament set feeds the classifier', json_encode($classifyIds));

$STYLES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];
// Keyed by GROUP and file: the same file name can sit in two groups (ahsoka-tano_ash_yellow does — the ASH
// tournament list and Ninin's HMW list are different decks), so a lookup by file alone shadows one of them.
$byKey = [];
foreach ($decks as $d) $byKey[$d['group'] . '/' . $d['file']] = $d;
$check(count(array_unique(array_column($decks, 'key'))) === count($decks), 'every pre-con key is unique');
$files = [];
foreach ($groups as $g) {
    $gFiles = glob('./SWUSim/Tests/BotFixtures/' . $g['id'] . '/*.txt') ?: [];
    $check(count($gFiles) > 0, "{$g['id']} has fixtures (the glob resolved)");
    $inGroup = array_values(array_filter($decks, fn($d) => ($d['group'] ?? '') === $g['id']));
    $check(count($inGroup) === count($gFiles), "{$g['id']}: one entry per owner-labelled fixture (" . count($gFiles) . ')', (string)count($inGroup));
    foreach ($gFiles as $f) $files[] = [$g['id'], $f];
}
foreach ($files as [$gid, $f]) {
    $name = basename($f, '.txt');
    $d = $byKey[$gid . '/' . $name] ?? null;
    if ($d === null) { $check(false, "$gid/$name is in the registry"); continue; }
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
// The CLASSIFIER must see ONLY the reviewed meta set — through the function it actually calls, which filters the
// JSON down to the 'classify' groups.
// ⚠ This used to be "no field basename appears in the registry", which broke on the 2026-09-29 rename:
// every fixture is now named <leader>_<set>_<base-archetype>, so a field deck and a meta deck that are the
// SAME DECK now share a basename (luke-skywalker_ash_data-vault is in both dirs, byte-identical). A name
// collision across directories is therefore no longer evidence of anything. Asserting the registry keys are
// a SUBSET of the meta dir says what was actually meant, and is immune to that coincidence.
require_once './SWUSim/Custom/BotDeckStyle.php';
$metaNames = array_map(fn($p) => basename($p, '.txt'), glob('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/*.txt') ?: []);
$classifier = SWUBotDeckLabelRegistry();
$stray = array_values(array_filter(array_map(fn($d) => ($d['group'] ?? '') . '/' . $d['file'], $classifier),
    fn($k) => !str_starts_with($k, 'ash-meta-2026-09/')));
$check(empty($stray), 'the classifier holds only reviewed ash-meta-2026-09 fixtures', implode(',', $stray));
$check(count($classifier) === count($metaNames), 'the classifier holds every reviewed ash-meta-2026-09 fixture (' . count($metaNames) . ')', (string)count($classifier));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
