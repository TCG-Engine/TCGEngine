<?php
// Which formats may use the public matchmaking queue — docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §1.
// Owner, 2026-09-16: all seven Constructed pools; the Twin Suns family stays private rooms; 1P modes never queue.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/public_queue_policy_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Formats.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$queued = ['premier', 'preview', 'eternal', 'eternal-preview', 'padawan', 'padawan-preview', 'open'];
$never  = ['twinsuns', 'twinsuns-preview', 'teamsuns', 'teamsuns-preview', 'goldfish', 'hotseat', 'botpractice'];

foreach ($queued as $f) $check(SWUFormatAllowsPublicQueue($f, true) === true, "$f allows the public queue");
foreach ($never as $f)  $check(SWUFormatAllowsPublicQueue($f, true) === false, "$f never allows the public queue");
$check(SWUFormatAllowsPublicQueue('no-such-format', true) === false, 'an unknown format never queues');
foreach ($queued as $f) $check(SWUFormatAllowsPublicQueue($f, false) === false, "switch off: $f does not queue");
$check(SWUPublicQueueEnabled() === true, 'the site-wide switch is on');

// The config guard: publicQueue ⇒ a 2-seat, non-local format. Checked over EVERY definition, so a future edit that
// flags a Twin Suns or 1P format fails here rather than in production.
$flagged = [];
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    $f = SWUGetFormat($id);
    if (empty($f['publicQueue'])) continue;
    $flagged[] = $id;
    $check(!SWUFormatIsRoomFormat($id) && empty($f['localMode']), "flagged format $id is 2-seat and not a local mode");
}
sort($flagged); $want = $queued; sort($want);
$check($flagged === $want, 'exactly the seven Constructed pools carry publicQueue (' . implode(',', $flagged) . ')');

// The menu tree carries the flag per pool: PvP pools queue, Arenabot and Twin Suns pools do not.
foreach (SWUMenuTree() as $gt) {
    foreach ($gt['options'] as $opt) {
        foreach ($opt['pools'] as $p) {
            $want = ($gt['id'] === 'constructed' && $opt['id'] === 'pvp');
            $check(($p['publicQueue'] ?? null) === $want, "tree {$gt['id']}/{$opt['id']}/{$p['format']} publicQueue is " . ($want ? 'true' : 'false'));
        }
    }
}
$in = SWUMenuTreeFor(true, true);
$check(($in[0]['options'][0]['pools'][0]['publicQueue'] ?? null) === true, 'the viewer-filtered tree keeps the flag');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
