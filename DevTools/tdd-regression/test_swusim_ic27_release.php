<?php
// IC27 released (owner, 2026-11-21: "Preview season is over since IC27 was officially released"): legal in Premier / Eternal
// and every pool built on them, nothing is previewing, and every preview pool is off — hidden from selectors and the setup
// menu, still resolvable for anything in flight. The card data itself flipped to official on 2026-11-07.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_ic27_release.php
chdir(dirname(__DIR__, 2));
require_once './SWUSim/Custom/DeckImport.php';   // Formats + DeckValidation + dictionaries + SWUResolveToImplementedPrint

$checks = [];
$released = ['premier', 'metapremier', 'eternal', 'padawan', 'twinsuns', 'teamsuns', 'dualingsuns',
             'at1v1-premier', 'at1v1-eternal', 'at4p-premier', 'at4p-eternal'];
foreach ($released as $f) {
    $checks["$f includes IC27"] = in_array('IC27', SWUFormatLegalSets($f), true);
    $checks["$f is not a preview format"] = SWUFormatIsPreview($f) === false;
}
$preview = require './AppCore/SWU/PreviewSets.php';
$checks['nothing is previewing'] = $preview === [];
$checks['menu labels carry no preview suffix'] = SWUPreviewSetSuffix() === '';

$previewFormats = ['preview', 'eternal-preview', 'padawan-preview', 'twinsuns-preview', 'teamsuns-preview'];
foreach ($previewFormats as $pf) {
    $checks["$pf is disabled"] = empty(SWUGetFormat($pf)['enabled']);
    $checks["$pf is hidden from format selectors"] = !array_key_exists($pf, SWUListFormats());
    $checks["$pf still resolves for in-flight games"] = SWUGetFormat($pf) !== null;
    $checks["$pf refuses the public queue"] = !SWUFormatAllowsPublicQueue($pf);
}
// The setup menu offers no preview pool anywhere (Constructed / Arenabot / PvP / Twin Suns / Team Suns).
$offered = [];
foreach (SWUMenuTree() as $gt) foreach ($gt['options'] as $opt) foreach ($opt['pools'] as $p) $offered[] = $p['format'];
$checks['the setup menu offers no preview pool: ' . implode(',', array_intersect($offered, $previewFormats))]
    = array_intersect($offered, $previewFormats) === [];
$checks['the setup menu still offers Premier and Eternal'] = in_array('premier', $offered, true) && in_array('eternal', $offered, true);

// A real Premier list with one IC27 card swapped in: Premier-legal now (it was preview-only until today).
$text = <<<'DECK'
Leader
1 JTL_006
Base
1 ASH_026
Deck
3 SEC_082
3 SEC_186
2 ASH_224
3 SEC_213
3 LAW_211
3 JTL_115
3 JTL_217
3 SEC_215
3 JTL_081
3 SEC_189
3 LAW_135
3 JTL_212
3 JTL_221
3 JTL_085
2 ASH_194
3 LAW_217
1 SEC_195
2 LOF_091
1 ASH_199
DECK;
$r = SWUResolveDeckInput($text);
$checks['fixture resolves'] = !empty($r['success']);
$withIc27 = $r['mainDeck']; $withIc27[0] = 'IC27_022';
$checks['premier accepts an IC27 card'] = SWUCheckFormat('premier', $r['leader'], $r['base'], $withIc27, []) === [];
$checks['eternal accepts an IC27 card'] = SWUCheckFormat('eternal', $r['leader'], $r['base'], $withIc27, []) === [];
// Premier's bans still bite (release adds a set; it does not loosen the ban list).
$withBan = $r['mainDeck']; $withBan[0] = 'ASH_248';
$checks['premier still refuses a banned card'] = !empty(SWUCheckFormat('premier', $r['leader'], $r['base'], $withBan, []));

$bad = array_keys(array_filter($checks, fn($v) => !$v));
echo empty($bad) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(' | ', $bad) . "\n";
exit(empty($bad) ? 0 : 1);
