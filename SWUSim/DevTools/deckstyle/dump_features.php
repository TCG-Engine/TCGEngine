<?php
// Deck-style classifier training data: one JSON line per owner-labelled deck, for fit_deckstyle.py.
//   A — every curated fixture with an owner label ('# DeckStyle:' over '# Style:'): ash-meta-2026-09, hmw-meta-2026-09, force-fam-HMW-predictions;
//   B — every real tournament list in meta-baselines/*/fixtures.tar.gz whose EXACT archetype (SWUBotFixtureFileName) has a curated label.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d memory_limit=2G SWUSim/DevTools/deckstyle/dump_features.php <out.jsonl>
chdir(dirname(__DIR__, 3));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Rl/CardTags.php';
require_once './SWUSim/Custom/BotDeckStyle.php';
$OUT = $argv[1] ?? '/tmp/deckstyle_features.jsonl';
$S = ['hyperaggro' => 0, 'softaggro' => 1, 'midrange' => 2, 'softcontrol' => 3, 'hardcontrol' => 4];
$lab = function (string $t) use ($S) { preg_match('/^# DeckStyle:\s*(\S+)/m', $t, $d); preg_match('/^# Style:\s*(\S+)/m', $t, $m); $s = strtolower($d[1] ?? $m[1] ?? ''); return isset($S[$s]) ? $s : null; };
$rows = []; $byName = [];
foreach (['ash-meta-2026-09', 'hmw-meta-2026-09', 'force-fam-HMW-predictions'] as $g) foreach (glob("SWUSim/Tests/BotFixtures/$g/*.txt") ?: [] as $p) {
    $t = (string)file_get_contents($p); $l = $lab($t); $d = SWUBotDeckFromFixtureText($t);
    if (!$l || !$d['leader']) continue;
    $n = basename($p, '.txt'); $byName[$n] ??= $l;
    $rows[] = ['set' => 'A', 'grp' => $g === 'ash-meta-2026-09' ? 'ASH' : 'U', 'arch' => $n, 'label' => $l, 'deck' => $d];
}
$tmp = sys_get_temp_dir() . '/deckstyle-dump-' . getmypid();
foreach (glob('SWUSim/Tests/BotFixtures/meta-baselines/*/fixtures.tar.gz') ?: [] as $tar) {
    $dir = "$tmp/" . basename(dirname($tar)); @mkdir($dir, 0777, true); exec('tar xzf ' . escapeshellarg($tar) . ' -C ' . escapeshellarg($dir));
    foreach (glob("$dir/fx/*.txt") ?: [] as $p) {
        $d = SWUBotDeckFromFixtureText((string)file_get_contents($p)); if (!$d['leader'] || !$d['base']) continue;
        $n = SWUBotFixtureFileName($d['leader'], $d['base'], $d['cards']);
        if (isset($byName[$n])) $rows[] = ['set' => 'B', 'grp' => '', 'arch' => $n, 'label' => $byName[$n], 'deck' => $d];
    }
}
exec('rm -rf ' . escapeshellarg($tmp));
$fh = fopen($OUT, 'w');
foreach ($rows as $r) {
    $f = SWUBotDeckFeatures($r['deck']); unset($f['baseAspect']);
    fwrite($fh, json_encode(['set' => $r['set'], 'grp' => $r['grp'], 'arch' => $r['arch'], 'label' => $r['label'], 'rank' => $S[$r['label']], 'f' => $f]) . "\n");
}
echo count($rows) . " decks -> $OUT\n";
