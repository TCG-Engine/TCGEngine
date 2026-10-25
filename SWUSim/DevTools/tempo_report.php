<?php
// TEMPO REPORT — every labelled fixture's tempo score (BotTempo.php), for calibration and to keep the `# Flavours:` headers honest.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tempo_report.php [--pieces] [filter]
// Rows: score | detection says tempo | header says tempo | fixture. --pieces lists each fixture's tempo cards by kind. Exit 1 when any
// header disagrees with detection (the rule is "> 28%" of the main Deck section; spec 2026-10-20-swusim-tempo-flavour-design.md).
chdir('/var/www/html/TCGEngine');
require 'SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once 'SWUSim/Custom/BotTempo.php';

$pieces = in_array('--pieces', $argv, true);
$filter = '';
foreach (array_slice($argv, 1) as $a) if ($a !== '--pieces') $filter = $a;
$rows = [];
foreach (['ash-meta-2026-09', 'hmw-meta-2026-09', 'force-fam-HMW-predictions', 'ideas'] as $dir) {
    foreach (glob("SWUSim/Tests/BotFixtures/$dir/*.txt") ?: [] as $f) {
        if ($filter !== '' && !str_contains($f, $filter)) continue;
        $leader = ''; $cards = []; $sec = ''; $header = false;
        foreach (file($f, FILE_IGNORE_NEW_LINES) as $l) {
            if (preg_match('/^# Flavours:\s*(.*)$/', $l, $m)) $header = in_array('tempo', array_map('trim', explode(',', $m[1])), true);
            if (in_array($l, ['Leader', 'Base', 'Deck', 'Sideboard'], true)) { $sec = $l; continue; }
            if (!preg_match('/^(\d+)\s+(\S+)/', $l, $m)) continue;
            if ($sec === 'Leader' && $leader === '') $leader = $m[2];
            if ($sec === 'Deck') $cards[$m[2]] = ($cards[$m[2]] ?? 0) + intval($m[1]);
        }
        $rows[] = [SWUBotTempoScore($leader, $cards), SWUBotIsTempoDeck($leader, $cards), $header, "$dir/" . basename($f, '.txt'), $leader, $cards];
    }
}
usort($rows, fn($a, $b) => $b[0] <=> $a[0]);
$bad = 0;
foreach ($rows as [$score, $det, $hdr, $name, $leader, $cards]) {
    $flag = $det === $hdr ? '  ' : '!!';
    if ($det !== $hdr) $bad++;
    printf("%s %3d%%  detect=%-5s header=%-5s %s\n", $flag, round(100 * $score), $det ? 'tempo' : '-', $hdr ? 'tempo' : '-', $name);
    if (!$pieces) continue;
    if (in_array($leader, SWU_BOT_TEMPO_LEADERS, true)) printf("        leader %s +%d\n", $leader, SWU_BOT_TEMPO_LEADER_WEIGHT);
    foreach ($cards as $id => $n) {
        $k = SWUBotTempoPieceKind($id);
        if ($k !== null) printf("        %-9s %dx %-8s %s (cost %s)\n", $k, $n, $id, CardTitle($id), CardCost($id));
    }
}
printf("\n%d fixtures, %d header(s) disagree with detection\n", count($rows), $bad);
exit($bad > 0 ? 1 : 0);
