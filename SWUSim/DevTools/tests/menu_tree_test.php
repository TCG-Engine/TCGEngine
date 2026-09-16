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
$check($labels($tree[1]['options'][0]['pools']) === ['Standard', 'Preview'], 'Twin Suns Free-for-all offers Standard and Preview');
$check($labels($tree[1]['options'][1]['pools']) === ['Standard', 'Preview'], 'Twin Suns Teams offers Standard and Preview');
$check(($tree[0]['options'][1]['label'] ?? '') === 'Arenabot', 'the bot opponent is labelled Arenabot');
$check(SWUGetFormat('botpractice')['displayName'] === 'Arenabot', "botpractice's display name is Arenabot");
$check(SWUGetFormat('twinsuns')['displayName'] === 'Twin Suns', "twinsuns' display name is unchanged (SWUDeck and the stats pages show it)");

// ── Viewer filtering ────────────────────────────────────────────────────────────────────────────────
$gameTypes = fn($t) => array_map(fn($g) => $g['id'], $t);
$options = function ($t, $gt) { foreach ($t as $g) { if ($g['id'] === $gt) return array_map(fn($o) => $o['id'], $g['options']); } return []; };
$pools = function ($t, $gt, $opt) {
    foreach ($t as $g) { if ($g['id'] !== $gt) continue; foreach ($g['options'] as $o) { if ($o['id'] === $opt) return array_map(fn($p) => $p['format'], $o['pools']); } }
    return null;
};
$out = SWUMenuTreeFor(false, true);
$check($gameTypes($out) === ['constructed', 'solo'], 'logged out: no Twin Suns');
$check($pools($out, 'constructed', 'pvp') === ['open'], 'logged out: PvP offers Open only');
$check($pools($out, 'constructed', 'arenabot') === $pools(SWUMenuTreeFor(true, true), 'constructed', 'arenabot'), 'logged out: Arenabot keeps every enabled pool (it needs no account)');
$check($options($out, 'solo') === ['goldfish', 'hotseat'], 'logged out: 1P Mode offers Goldfish and Hotseat');
$in = SWUMenuTreeFor(true, false);
$check(!in_array('arenabot', $options($in, 'constructed'), true), 'the Arenabot gate refuses → no Arenabot option');
$check(in_array('twinsuns', $gameTypes($in), true), 'logged in: Twin Suns is offered');
$previewsOff = SWUMenuTreeFor(true, true, fn(string $id): bool => !str_contains($id, 'preview'));
$check($pools($previewsOff, 'constructed', 'pvp') === ['premier', 'eternal', 'padawan', 'open'], 'a disabled preview format is not offered');
$check($pools($previewsOff, 'twinsuns', 'teams') === ['teamsuns'], 'a disabled Team Suns Preview leaves Teams with Standard');
$noFfa = SWUMenuTreeFor(true, true, fn(string $id): bool => !in_array($id, ['twinsuns', 'twinsuns-preview'], true));
$check($options($noFfa, 'twinsuns') === ['teams'], 'an option whose every pool is disabled disappears');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
