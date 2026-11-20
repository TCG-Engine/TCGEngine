<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../Cosmetics/Catalog.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

$html = SWUCosmeticSelectHtml('background', 'death-star');
ok(strpos($html, 'data-slot="background"') !== false, "select carries data-slot");
ok(strpos($html, 'class="cos-select"') !== false, "default class cos-select");
ok(strpos($html, '<option value="death-star" data-asset="/TCGEngine/Assets/Boards/SWUSim/death-star.webp" selected>Death Star</option>') !== false, "current option marked selected with asset URL");
ok(substr_count($html, ' selected>') === 1, "exactly one selected option");
ok(strpos($html, '<option value="default"') !== false && strpos($html, 'value="default" data-asset="/TCGEngine/Assets/Boards/SWUSim/default.webp"') !== false, "non-current option present, not selected");

$mat = SWUCosmeticSelectHtml('playmat', 'none', 'swu-gear-cos');
ok(strpos($mat, 'class="swu-gear-cos"') !== false, "custom class honored");
ok(strpos($mat, '<option value="none" data-asset="" selected>None</option>') !== false, "null-asset option -> empty data-asset");

ok(SWUCosmeticSelectHtml('bogus','x') === '<select class="cos-select" data-slot="bogus"></select>', "unknown slot -> empty option list");

// Option order. Card backs: the default first, then the rest A→Z by label, case-insensitively (owner 2026-11-08).
// Backgrounds and playmats: catalog order, untouched.
$optionIds = function (string $html) { preg_match_all('/<option value="([^"]*)"/', $html, $m); return $m[1]; };
$cat = SWUCosmeticCatalog();
$backIds = $optionIds(SWUCosmeticSelectHtml('cardback', 'classic'));
$def = SWUCosmeticDefault('cardback');
$rest = array_keys(array_filter($cat['cardback'], fn($o) => empty($o['isDefault'])));
usort($rest, fn($a, $b) => strcasecmp($cat['cardback'][$a]['label'], $cat['cardback'][$b]['label']));
ok($backIds === array_merge([$def], $rest), "card backs: default first, then A-Z by label");
ok(count($backIds) === count($cat['cardback']) && count(array_unique($backIds)) === count($backIds), "card backs: every option once");
// The catalog itself is NOT alphabetical (Force Fam is appended last), so the check above can fail.
$catRest = array_keys(array_filter($cat['cardback'], fn($o) => empty($o['isDefault'])));
ok($catRest !== $rest, "card back catalog order differs from A-Z (keeps the sort check discriminating)");
ok($optionIds(SWUCosmeticSelectHtml('background', 'default')) === array_keys($cat['background']), "backgrounds: catalog order");
ok($optionIds(SWUCosmeticSelectHtml('playmat', 'none')) === array_keys($cat['playmat']), "playmats: catalog order");

echo "PASS=$pass FAIL=$fail\n";
