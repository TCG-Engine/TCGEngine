<?php
// Which formats may use the public matchmaking queue — docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §1.
// Owner, 2026-09-16: all seven Constructed pools. Owner, 2026-09-20: + the Twin Suns family, which queues into a
// public ROOM rather than a quick match. 1P modes never queue.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/public_queue_policy_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Formats.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Owner, 2026-09-20: the Twin Suns family now queues too, REVERSING the 2026-09-16 decision this file
// was written to pin. The two groups stay separate because they queue DIFFERENTLY — Constructed pairs
// into a quick match, the Twin Suns family pairs into a public ROOM with a host who starts it (see
// lobby_adapter_test.php for the waiting-room half). 1P/local modes still never queue.
$queuedQuick = ['premier', 'preview', 'eternal', 'eternal-preview', 'padawan', 'padawan-preview', 'open'];
$queuedRoom  = ['twinsuns', 'twinsuns-preview', 'teamsuns', 'teamsuns-preview'];
$queued = array_merge($queuedQuick, $queuedRoom);
$never  = ['goldfish', 'hotseat', 'botpractice'];

foreach ($queued as $f) $check(SWUFormatAllowsPublicQueue($f, true) === true, "$f allows the public queue");
foreach ($never as $f)  $check(SWUFormatAllowsPublicQueue($f, true) === false, "$f never allows the public queue");
$check(SWUFormatAllowsPublicQueue('no-such-format', true) === false, 'an unknown format never queues');
foreach ($queued as $f) $check(SWUFormatAllowsPublicQueue($f, false) === false, "switch off: $f does not queue");
$check(SWUPublicQueueEnabled() === true, 'the site-wide switch is on');

// The config guard: publicQueue ⇒ a NON-LOCAL format. Checked over EVERY definition, so a future edit
// that flags a 1P mode fails here rather than in production.
//
// ⚠ The seat-count half of this guard was DELIBERATELY REMOVED (owner, 2026-09-20). It used to read
// "!SWUFormatIsRoomFormat($id) && empty($f['localMode'])" — 2-seat AND non-local. Room formats now
// queue, so only the local-mode half survives. The room/quick split is still asserted, just below,
// per format rather than as a blanket ban.
$flagged = [];
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    $f = SWUGetFormat($id);
    if (empty($f['publicQueue'])) continue;
    $flagged[] = $id;
    $check(empty($f['localMode']), "flagged format $id is not a local mode");
}
sort($flagged); $want = $queued; sort($want);
$check($flagged === $want, 'exactly the 7 Constructed pools + the 4 Twin Suns formats carry publicQueue (' . implode(',', $flagged) . ')');

// The room/quick split, asserted per format so neither group can drift into the other.
foreach ($queuedQuick as $f) $check(SWUFormatIsRoomFormat($f) === false, "$f queues as a QUICK match (not a room)");
foreach ($queuedRoom  as $f) $check(SWUFormatIsRoomFormat($f) === true,  "$f queues as a public ROOM");

// The menu tree carries the flag per BRANCH, not per format — the same pool list hangs under both
// Constructed/PvP and Constructed/Arenabot, and only the PvP copy queues. So the expectation is an
// explicit list of the branches that may offer a queue, deliberately NOT re-derived from the
// production expression (`!isset($opt['format']) && …`), which would agree with itself by construction.
$queueBranches = ['constructed/pvp', 'twinsuns/ffa', 'twinsuns/teams'];
foreach (SWUMenuTree() as $gt) {
    foreach ($gt['options'] as $opt) {
        foreach ($opt['pools'] as $p) {
            $branch = "{$gt['id']}/{$opt['id']}";
            $want = in_array($branch, $queueBranches, true) && SWUFormatAllowsPublicQueue($p['format'], true);
            $check(($p['publicQueue'] ?? null) === $want, "tree {$branch}/{$p['format']} publicQueue is " . ($want ? 'true' : 'false'));
        }
    }
}
// …and pin the two ends of that derivation, so a tree that reported `false` everywhere could not pass
// by agreeing with a registry that also said `false`.
$seenTree = [];
foreach (SWUMenuTree() as $gt) foreach ($gt['options'] as $opt) foreach ($opt['pools'] as $p) {
    if (!empty($p['publicQueue'])) $seenTree[] = $p['format'];
}
sort($seenTree); $seenTree = array_values(array_unique($seenTree));
$wantTree = $queued; sort($wantTree);
$check($seenTree === $wantTree, 'the menu tree offers a queue for exactly the queued formats (' . implode(',', $seenTree) . ')');
$check(in_array('twinsuns', $seenTree, true) && in_array('teamsuns', $seenTree, true),
       'the tree offers a public queue on both Twin Suns branches');
$in = SWUMenuTreeFor(true);
$inPvp = array_values(array_filter($in[0]['options'], fn($o) => $o['id'] === 'pvp'))[0] ?? [];
$check(($inPvp['pools'][0]['publicQueue'] ?? null) === true, 'the viewer-filtered tree keeps the flag');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
