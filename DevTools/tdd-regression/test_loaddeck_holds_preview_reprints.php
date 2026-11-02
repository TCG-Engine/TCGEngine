<?php
// Deck EXPORT holds back reprints from a set that is still previewing (owner, 2026-10-26): the IC27 printings of
// reprinted cards broke deck imports on sites that do not have IC27 yet. Until the set leaves AppCore/SWU/PreviewSets.php
// the export emits the newest RELEASED printing; the board and the deckbuilder keep showing the newest printing of all.
// A card that exists ONLY in the preview set has nothing to fall back to and is exported as itself.
//
// The local SWUDeck dictionary carries IC27 only as preview mocks (no reprints), so the IC27 printings below are
// added to the reprint universe by hand — exactly how they appear once official IC27 data is generated (they fold to
// their earliest printing through Overrides.php). Run in its own process: the display maps are built once.
// Run: docker exec -w /var/www/html/TCGEngine otmtcge-swudeck-web-server-1 php DevTools/tdd-regression/test_loaddeck_holds_preview_reprints.php
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUDeck/Custom/DeckFormats.php';   // SWUDeckSetReprintUniverse
require_once $root . '/AppCore/SWU/CardDisplayID.php';
SWUDeckSetReprintUniverse();
$GLOBALS['SWUReprintUniverse'][] = 'IC27_010';   // Echo Base, reprint of SOR_024
$GLOBALS['SWUReprintUniverse'][] = 'IC27_017';   // Death Trooper, reprint of SOR_033 (also SEC_030)
$checks = [];

$checks['IC27 is the previewing set this test is about'] = in_array('IC27', (array)(include $root . '/AppCore/SWU/PreviewSets.php'), true);

// the board / deckbuilder direction is unchanged: newest printing of all
$checks['display still shows the IC27 printing']          = SWUDisplayCardID('SOR_024') === 'IC27_010';

// the export direction skips it
$checks['export: Echo Base stays SOR_024']                 = SWUDisplayCardID('SOR_024', true) === 'SOR_024';
$checks['export: Death Trooper takes its newest RELEASED printing'] = SWUDisplayCardID('SOR_033', true) === 'SEC_030';
$checks['export: a stored IC27 printing goes out as the released one'] = SWUDisplayCardID('IC27_010', true) === 'SOR_024';
$checks['export: a released reprint still moves (Wampa)'] = SWUDisplayCardID('SOR_164', true) === 'LOF_164';
$checks['export: a Special printing is still never chosen'] = SWUDisplayCardID('SOR_172', true) === 'SOR_172';
$previewOnly = null;
foreach ($GLOBALS['SWUReprintUniverse'] as $id) {
    if (SWUCardSet($id) === 'IC27' && $id !== 'IC27_010' && $id !== 'IC27_017' && CardIDOverride($id) === $id) { $previewOnly = $id; break; }
}
$checks['export: a preview-only card goes out as itself'] = $previewOnly !== null && SWUDisplayCardID($previewOnly, true) === $previewOnly;

// the export never changes which card a thing IS
$bad = [];
foreach ($GLOBALS['SWUReprintUniverse'] as $id) if (CardIDOverride(SWUDisplayCardID($id, true)) !== CardIDOverride($id)) $bad[] = $id;
$checks['export never changes canonical identity'] = empty($bad);

// LoadDeck — the single export egress — asks for the released printing
$code = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/APIs/LoadDeck.php'));
$checks['LoadDeck exports the released printing'] = preg_match('~\$emitID\s*=.*?SWUDisplayCardID\(\$setNnn,\s*true\)~s', $code) === 1;

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
