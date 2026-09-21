<?php
// The SWUSim game-setup menu tree (AppCore/SWU/Formats.php) — docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2.
// A coverage check in BOTH directions: no format can exist without a menu path, and the menu can offer nothing that is
// not a registered format.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/menu_tree_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Formats.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$leaves = SWUMenuLeaves(SWUMenuTree());

// ── Registry ↔ tree ─────────────────────────────────────────────────────────────────────────────────
foreach ($leaves as $l) {
    $check(SWUGetFormat($l['format']) !== null, "leaf {$l['gameType']}/{$l['option']}/" . ($l['pool'] ?? '-') . " stores a registered format ('{$l['format']}')");
}
$counts = [];
foreach ($leaves as $l) {
    if ($l['option'] !== 'arenabot') $counts[$l['format']] = ($counts[$l['format']] ?? 0) + 1;
}
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    if ($id === 'botpractice') continue;
    $check(($counts[$id] ?? 0) === 1, "format '$id' is reachable by exactly one PvP / Twin Suns / 1P path (found " . ($counts[$id] ?? 0) . ')');
}
$check(!isset($counts['botpractice']), 'botpractice is reachable only through Arenabot');
$arena = array_values(array_filter($leaves, fn($l) => $l['option'] === 'arenabot'));
$check(count($arena) > 0 && count(array_filter($arena, fn($l) => $l['format'] !== 'botpractice')) === 0, 'every Arenabot leaf stores botpractice');
$pvpPools = array_values(array_map(fn($l) => $l['pool'], array_filter($leaves, fn($l) => $l['option'] === 'pvp')));
$check(count(array_filter($arena, fn($l) => !in_array($l['cardPool'], $pvpPools, true))) === 0, 'every Arenabot card pool is a Constructed pool');

// ── What each path stores ───────────────────────────────────────────────────────────────────────────
$leaf = function ($gt, $opt, $pool) use ($leaves) {
    foreach ($leaves as $l) { if ($l['gameType'] === $gt && $l['option'] === $opt && $l['pool'] === $pool) return $l; }
    return null;
};
$check(($leaf('constructed', 'pvp', 'premier')['format'] ?? null) === 'premier' && ($leaf('constructed', 'pvp', 'premier')['cardPool'] ?? null) === 'premier', 'Constructed → PvP → Premier stores premier with card pool premier');
$check(($leaf('constructed', 'arenabot', 'eternal')['format'] ?? null) === 'botpractice' && ($leaf('constructed', 'arenabot', 'eternal')['cardPool'] ?? null) === 'eternal', 'Constructed → Arenabot → Eternal stores botpractice with card pool eternal');
$check(($leaf('twinsuns', 'teams', 'teamsuns-preview')['cardPool'] ?? null) === 'teamsuns-preview', 'Twin Suns → Teams → Preview stores teamsuns-preview as its own card pool');
$check(($leaf('solo', 'goldfish', null)['cardPool'] ?? null) === 'open' && ($leaf('solo', 'hotseat', null)['format'] ?? null) === 'hotseat', '1P Mode stores its mode with card pool open');

// ── Labels ──────────────────────────────────────────────────────────────────────────────────────────
$tree = SWUMenuTree();
$labels = fn($pools) => array_map(fn($p) => $p['label'], $pools);
// Every preview label names the set it opens (owner, 2026-09-18), derived from PreviewSets.php — so this
// asserts the DERIVATION, not the literal "IC27", and keeps passing when the set rolls over or the window
// closes. SWUPreviewSetSuffix() returns '' between windows, and the label is then plainly "Preview".
$pv = SWUPreviewSetSuffix();
$check($labels($tree[1]['options'][0]['pools']) === ['Standard', 'Preview' . $pv], 'Twin Suns Free-for-all offers Standard and Preview' . $pv);
$check($labels($tree[1]['options'][1]['pools']) === ['Standard', 'Preview' . $pv], 'Twin Suns Teams offers Standard and Preview' . $pv);
// ⚠ Pin the suffix itself against the source of truth, or the two checks above pass for an
// implementation that never appends anything at all.
$pvSets = require './AppCore/SWU/PreviewSets.php';
$check($pv === (empty($pvSets) ? '' : ' (' . implode('/', $pvSets) . ')'),
    'the preview suffix is derived from PreviewSets.php (currently ' . var_export($pv, true) . ')');
$check(!empty($pvSets) ? str_ends_with($labels($tree[0]['options'][1]['pools'])[1], $pv) : true,
    'a Constructed preview pool carries the same suffix (Premier Preview' . $pv . ')');
// The CHOICE label carries "(beta)" (owner, 2026-09-18) so the heuristic bot is not mistaken for the
// competitive bot that was promised. The displayName below deliberately does NOT — it is the format's
// name for SWUDeck and the stats pages, not a player-facing pick.
$check(($tree[0]['options'][0]['label'] ?? '') === 'Arenabot (beta)', 'the bot opponent is labelled Arenabot (beta)');
// Arenabot is the FIRST Constructed opponent, so the menu opens on it (owner, 2026-09-21).
$check(array_map(fn($o) => $o['id'], $tree[0]['options']) === ['arenabot', 'pvp'], 'Constructed lists Arenabot first, then PvP');
$check(SWUGetFormat('botpractice')['displayName'] === 'Arenabot', "botpractice's display name stays Arenabot (no beta suffix)");
$check(SWUGetFormat('twinsuns')['displayName'] === 'Twin Suns', "twinsuns' display name is unchanged (SWUDeck and the stats pages show it)");

// ── Viewer filtering ────────────────────────────────────────────────────────────────────────────────
$gameTypes = fn($t) => array_map(fn($g) => $g['id'], $t);
$options = function ($t, $gt) { foreach ($t as $g) { if ($g['id'] === $gt) return array_map(fn($o) => $o['id'], $g['options']); } return []; };
$pools = function ($t, $gt, $opt) {
    foreach ($t as $g) { if ($g['id'] !== $gt) continue; foreach ($g['options'] as $o) { if ($o['id'] === $opt) return array_map(fn($p) => $p['format'], $o['pools']); } }
    return null;
};
// Login no longer filters anything (owner, 2026-09-21: guests play every format and lose only chat), so the viewer
// tree has no logged-in parameter. With every format enabled, the full-access tree IS the whole tree.
$all = SWUMenuTreeFor(true, fn(string $id): bool => true);
$check($all === SWUMenuTree(), 'with every format enabled, the viewer tree is the full tree (no login filtering)');
$in = SWUMenuTreeFor(true);
$check(in_array('twinsuns', $gameTypes($in), true), 'Twin Suns is offered');
$check($options($in, 'solo') === ['goldfish', 'hotseat'], '1P Mode offers Goldfish and Hotseat');
$noBot = SWUMenuTreeFor(false);
$check(!in_array('arenabot', $options($noBot, 'constructed'), true), 'the Arenabot gate refuses → no Arenabot option');
$check($options($noBot, 'constructed') === ['pvp'], 'with Arenabot closed, PvP is the only Constructed opponent');
$previewsOff = SWUMenuTreeFor(true, fn(string $id): bool => !str_contains($id, 'preview'));
$check($pools($previewsOff, 'constructed', 'pvp') === ['premier', 'eternal', 'padawan', 'open'], 'a disabled preview format is not offered');
$check($pools($previewsOff, 'twinsuns', 'teams') === ['teamsuns'], 'a disabled Team Suns Preview leaves Teams with Standard');
$noFfa = SWUMenuTreeFor(true, fn(string $id): bool => !in_array($id, ['twinsuns', 'twinsuns-preview'], true));
$check($options($noFfa, 'twinsuns') === ['teams'], 'an option whose every pool is disabled disappears');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
