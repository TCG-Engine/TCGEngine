<?php
// Deck EXPORT holds back reprints from a set that is still previewing (owner, 2026-10-26): the IC27 printings of
// reprinted cards broke deck imports on sites that do not have IC27 yet. Until the set leaves AppCore/SWU/PreviewSets.php
// the export emits the newest RELEASED printing; the board and the deckbuilder keep showing the newest printing of all.
// A card that exists ONLY in the preview set has nothing to fall back to and is exported as itself.
//
// IC27's printings come from official FFG data since 2026-11-07 (they fold to their earliest printing through
// Overrides.php). Examples are non-Special reprints: a Special printing is never displayed, which is why the original
// Echo Base IC27_010 / Death Trooper IC27_017 examples stopped working when official data marked both Special.
// ⚠ IC27-SPECIFIC, with an end date: when IC27 releases (2026-11-20) and leaves PreviewSets.php, the hold-back no longer
// applies to it and this test's premise is over -- DELETE it then (owner, 2026-11-08). The hold-back itself is generic.
// Run in its own process: the display maps are built once.
// Run: docker exec -w /var/www/html/TCGEngine otmtcge-swudeck-web-server-1 php DevTools/tdd-regression/test_loaddeck_holds_preview_reprints.php
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUDeck/Custom/DeckFormats.php';   // SWUDeckSetReprintUniverse
require_once $root . '/AppCore/SWU/CardDisplayID.php';
SWUDeckSetReprintUniverse();
$checks = [];

$checks['IC27 is the previewing set this test is about'] = in_array('IC27', (array)(include $root . '/AppCore/SWU/PreviewSets.php'), true);
$checks['IC27 reprint printings are in the reprint universe'] = in_array('IC27_015', $GLOBALS['SWUReprintUniverse'], true)
    && in_array('IC27_090', $GLOBALS['SWUReprintUniverse'], true);

// the board / deckbuilder direction is unchanged: newest printing of all
$checks['display still shows the IC27 printing']          = SWUDisplayCardID('JTL_033') === 'IC27_015';   // Onyx Squadron Brute

// the export direction skips it
$checks['export: Onyx Squadron Brute stays JTL_033']       = SWUDisplayCardID('JTL_033', true) === 'JTL_033';
// SOR_113 -> JTL_113 -> IC27_090: the export takes the newest RELEASED printing, not the original
$checks['export: Homestead Militia takes its newest RELEASED printing'] = SWUDisplayCardID('SOR_113') === 'IC27_090'
    && SWUDisplayCardID('SOR_113', true) === 'JTL_113';
$checks['export: a stored IC27 printing goes out as the released one'] = SWUDisplayCardID('IC27_015', true) === 'JTL_033';
$checks['export: a released reprint still moves (Wampa)'] = SWUDisplayCardID('SOR_164', true) === 'LOF_164';
$checks['export: a Special printing is still never chosen'] = SWUDisplayCardID('SOR_172', true) === 'SOR_172';
$previewOnly = null;
foreach ($GLOBALS['SWUReprintUniverse'] as $id) {
    if (SWUCardSet($id) === 'IC27' && CardIDOverride($id) === $id) { $previewOnly = $id; break; }
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
