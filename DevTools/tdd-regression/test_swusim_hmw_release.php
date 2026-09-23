<?php
// HMW released (owner, 2026-09-16): legal in Premier / Eternal (and so Padawan / Twin Suns, which build on the Eternal
// list); the preview window is IC27 only. docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §1.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_hmw_release.php
chdir(dirname(__DIR__, 2));
require_once './SWUSim/Custom/DeckImport.php';   // Formats + DeckValidation + dictionaries + SWUResolveToImplementedPrint

$checks = [];
foreach (['premier', 'eternal', 'padawan', 'twinsuns', 'teamsuns'] as $f) {
    $checks["$f includes HMW"] = in_array('HMW', SWUFormatLegalSets($f), true);
    $checks["$f excludes IC27"] = !in_array('IC27', SWUFormatLegalSets($f), true);
    $checks["$f is not a preview format"] = SWUFormatIsPreview($f) === false;
}
$preview = require './AppCore/SWU/PreviewSets.php';
$checks['the preview set list is exactly IC27'] = $preview === ['IC27'];
foreach (['preview' => 'premier', 'eternal-preview' => 'eternal', 'padawan-preview' => 'padawan',
          'twinsuns-preview' => 'twinsuns', 'teamsuns-preview' => 'teamsuns'] as $pf => $bf) {
    $checks["$pf = $bf + IC27"] = array_values(array_diff(SWUFormatLegalSets($pf), SWUFormatLegalSets($bf))) === ['IC27'];
    $checks["$pf is still a preview format"] = SWUFormatIsPreview($pf) === true;
}

// A real Premier list with one HMW card swapped in: Premier-legal now. An IC27 card: preview formats and Open only.
$text = implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/meta-2026-09/vader_yellow.txt')),
    fn($l) => !str_starts_with($l, '#')));
$r = SWUResolveDeckInput($text);
$checks['fixture resolves'] = !empty($r['success']);
$withHmw = $r['mainDeck']; $withHmw[0] = 'HMW_059';
$checks['premier accepts an HMW card'] = SWUCheckFormat('premier', $r['leader'], $r['base'], $withHmw, []) === [];
$withIc27 = $r['mainDeck']; $withIc27[0] = 'IC27_022';
$checks['premier refuses an IC27 card'] = !empty(SWUCheckFormat('premier', $r['leader'], $r['base'], $withIc27, []));
$checks['preview accepts an IC27 card'] = SWUCheckFormat('preview', $r['leader'], $r['base'], $withIc27, []) === [];
$checks['open accepts an IC27 card'] = SWUCheckFormat('open', $r['leader'], $r['base'], $withIc27, []) === [];

// Reprint aliasing is untouched: HMW reprints still load their implemented originals; HMW-only cards load as themselves.
$checks['HMW_022 still aliases to JTL_020'] = SWUResolveToImplementedPrint('HMW_022') === 'JTL_020';
$checks['HMW_239 still aliases to LOF_224'] = SWUResolveToImplementedPrint('HMW_239') === 'LOF_224';
$checks['HMW_059 loads as itself'] = SWUResolveToImplementedPrint('HMW_059') === 'HMW_059';

$bad = array_keys(array_filter($checks, fn($v) => !$v));
echo empty($bad) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(' | ', $bad) . "\n";
exit(empty($bad) ? 0 : 1);
