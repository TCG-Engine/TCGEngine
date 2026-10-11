<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swu_i18n_images.php
//
// Localized card art: mapping localized API records onto our CardIDs, and the per-language manifest.
// Localized records translate relation names ("Unidad", "Líder"), so the mapping joins on cardUid to the
// English cache instead of re-deriving IDs from type names.
// Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md §1
header('Content-Type: text/plain');
require_once __DIR__ . '/../../AppCore/SWU/CardI18nImages.php';

$fails = 0;
function check($name, $cond) { global $fails; echo ($cond ? "  ok: " : "  FAIL: ") . $name . "\n"; if (!$cond) $fails++; }

$es = json_decode(file_get_contents(__DIR__ . '/fixtures/i18n-es-sample.json'));
$en = json_decode(file_get_contents(__DIR__ . '/fixtures/i18n-en-index-sample.json'), true);
$index = SWUI18nEnglishIndex($en);

// ── locales ────────────────────────────────────────────────────────────────
check('three locales', SWUI18nLocales() === ['es', 'it', 'fr']);
check('es is a locale', SWUI18nIsLocale('es'));
check('en is not a download locale', !SWUI18nIsLocale('en'));
check('junk is not a locale', !SWUI18nIsLocale('../x'));
check('locale root', substr(SWUI18nLocaleRoot('it'), -strlen('AppCore/SWU/Images/i18n/it')) === 'AppCore/SWU/Images/i18n/it');

// ── English index ──────────────────────────────────────────────────────────
check('index has every fixture card', count($index) === count($en));
$byType = [];
foreach ($en as $c) { $byType[$index[$c['cardUid']]['type']][] = $index[$c['cardUid']]; }
check('index type is the English type name', isset($byType['Leader']) && isset($byType['Unit']) && isset($byType['Base']));
check('entries without cardUid are ignored', SWUI18nEnglishIndex([['id' => 'X_001', 'type' => []]]) === []);

// ── planning ───────────────────────────────────────────────────────────────
$plans = [];
foreach ($es->data as $rec) $plans[] = SWUI18nPlanRecord($rec, $index);

$unknown = array_values(array_filter($plans, fn($p) => ($p['skip'] ?? '') === 'unknown_uid'));
check('unknown cardUid is skipped', count($unknown) === 1);

$planned = array_values(array_filter($plans, fn($p) => !isset($p['skip'])));
check('every known record is planned (v4 x4 + v5 flat x1)', count($planned) === 5);

$leaderPlan = array_values(array_filter($planned, fn($p) => $p['type'] === 'Leader'))[0] ?? null;
check('leader plan exists', $leaderPlan !== null);
check('leader front url is the localized cdn url', $leaderPlan && str_starts_with($leaderPlan['frontUrl'], 'https://'));
check('leader has a back id', $leaderPlan && $leaderPlan['backID'] === $leaderPlan['cardID'] . '_back');
check('leader back is a LeaderUnit', $leaderPlan && $leaderPlan['backType'] === 'LeaderUnit');

$unitPlans = array_values(array_filter($planned, fn($p) => $p['type'] === 'Unit'));
check('the v4 unit and its v5 flat twin both plan', count($unitPlans) === 2);
check('both unit shapes resolve the same front url', count($unitPlans) === 2 && $unitPlans[0]['frontUrl'] === $unitPlans[1]['frontUrl']);
check('a unit has no back', $unitPlans && $unitPlans[0]['backID'] === null && $unitPlans[0]['backUrl'] === null);

$tokenPlan = array_values(array_filter($planned, fn($p) => str_contains($p['cardID'], '_T')))[0] ?? null;
check('token keeps the English SET_T## id', $tokenPlan !== null);

// flip leader: English type Leader with empty power and hp -> the back renders as a Leader
$flipIndex = ['uidflip' => ['id' => 'TWI_017', 'type' => 'Leader', 'power' => null, 'hp' => '']];
$flipRec = json_decode(json_encode(['cardUid' => 'uidflip',
    'artFront' => ['formats' => ['card' => ['url' => 'https://cdn/f.png']]],
    'artBack'  => ['formats' => ['card' => ['url' => 'https://cdn/b.png']]]]));
$flip = SWUI18nPlanRecord($flipRec, $flipIndex);
check('flip leader back type is Leader', ($flip['backType'] ?? '') === 'Leader');

$noArt = json_decode(json_encode(['cardUid' => 'uidflip', 'artFront' => ['data' => null]]));
check('record with no front art is skipped', (SWUI18nPlanRecord($noArt, $flipIndex)['skip'] ?? '') === 'no_art');
check('record with no uid is skipped', (SWUI18nPlanRecord(new stdClass(), $flipIndex)['skip'] ?? '') === 'no_uid');

// ── manifest from disk ─────────────────────────────────────────────────────
$tmp = sys_get_temp_dir() . '/swu-i18n-test-' . getmypid();
foreach (['WebpImages', 'concat', 'crops'] as $d) @mkdir("$tmp/$d", 0777, true);
touch("$tmp/WebpImages/SOR_010.webp"); touch("$tmp/WebpImages/SOR_005.webp"); touch("$tmp/WebpImages/SOR_005_back.webp");
touch("$tmp/concat/SOR_005.webp");                       // SOR_010 concat deliberately missing
touch("$tmp/crops/SOR_005_cropped.png");
touch("$tmp/WebpImages/notes.txt");                      // not an image: ignored
$m = SWUI18nBuildManifest('es', $tmp, '2026-09-17T00:00:00Z');
check('manifest locale', $m['locale'] === 'es');
check('manifest generated stamp', $m['generated'] === '2026-09-17T00:00:00Z');
check('full list is sorted stems incl _back', $m['WebpImages'] === ['SOR_005', 'SOR_005_back', 'SOR_010']);
check('a missing file is absent', $m['concat'] === ['SOR_005']);
check('crop stems drop _cropped', $m['crops'] === ['SOR_005']);
array_map('unlink', glob("$tmp/*/*")); foreach (['WebpImages', 'concat', 'crops'] as $d) rmdir("$tmp/$d"); rmdir($tmp);

check('no derived-type skips configured by default for an unknown locale', SWUI18nSkipDerivedTypes('xx') === []);

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
