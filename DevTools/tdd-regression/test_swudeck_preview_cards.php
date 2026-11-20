<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swudeck-web-server-1 php DevTools/tdd-regression/test_swudeck_preview_cards.php
//
// Preview (mock) cards must reach SWUDeck's dictionary and browse catalog, or the deckbuilder
// cannot search, validate or render an HMW/IC27 deck. A mock has no upstream UUID, so it keys as
// its SET_NNN — which is why this depends on the SET_NNN dictionary landing first.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §3
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../AppCore/SWU/MockCardMerge.php';

$checks = [];
$mocks = SWULoadMockCards();
// SWULoadMockCards() also returns [] for a MISSING or unparseable file -- that must fail, not read as "between sets".
$checks['mock source file exists and returns an array'] = file_exists(SWUMockCardsPath()) && is_array(require SWUMockCardsPath());

// Between preview sets the mock source is EMPTY on purpose: a set's mocks are deleted when it flips to official FFG
// data (HMW 2026-09-16, IC27 2026-11-07). Then there is no preview card to resolve, and the per-card checks below
// have nothing to look at -- only the client wiring is still checkable. They come back on their own with the next
// preview set's first mock; a non-empty source whose cards do not resolve still fails.
if (is_array($mocks) && count($mocks) === 0) {
    $js = glob(__DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries_*.js');
    $checks['client JS still emits MockCardImageIDs'] = $js && strpos(file_get_contents($js[0]), 'var MockCardImageIDs') !== false;
    $fails = array_keys(array_filter($checks, fn($v) => $v !== true));
    echo empty($fails) ? "PASS (" . count($checks) . " checks; no preview set is mocked, so the per-card checks wait for the next one)\n"
                       : "FAIL: " . implode(', ', $fails) . "\n";
    exit(empty($fails) ? 0 : 1);
}

// Pick a Leader mock and a Unit mock from the tracked source rather than hardcoding ids, so this
// test survives the preview set changing.
$leaderID = null; $unitID = null;
foreach ($mocks as $id => $m) {
    if (($m['type'] ?? '') === 'Leader' && $leaderID === null) $leaderID = $id;
    if (($m['type'] ?? '') === 'Unit'   && $unitID   === null) $unitID   = $id;
}
$checks['found a Leader mock'] = $leaderID !== null;
$checks['found a Unit mock']   = $unitID   !== null;

if ($leaderID && $unitID) {
    // ── Every property SWUDeck reads must resolve, not come back blank ───────
    $checks['mock title resolves']  = CardTitle($leaderID) === ($mocks[$leaderID]['title'] ?? null);
    $checks['mock type resolves']   = CardType($leaderID) === 'Leader';
    $checks['mock set resolves']    = CardSet($leaderID) === ($mocks[$leaderID]['set'] ?? null);
    $checks['mock set is not blank']= CardSet($leaderID) !== '' && CardSet($leaderID) !== null;
    $checks['mock rarity is a single letter'] =
        in_array(CardRarity($leaderID), ['C','U','R','L','S'], true);
    $checks['mock cost resolves']   = (string)CardCost($unitID) === (string)($mocks[$unitID]['cost'] ?? '');
    $checks['mock arena resolves']  = CardArena($unitID) !== '' && CardArena($unitID) !== null;

    $expectedTraits = is_array($mocks[$unitID]['trait'] ?? null)
        ? implode(',', $mocks[$unitID]['trait']) : (string)($mocks[$unitID]['trait'] ?? '');
    $checks['mock traits resolve']  = CardTrait($unitID) === $expectedTraits;

    $expectedAspects = is_array($mocks[$unitID]['aspect'] ?? null)
        ? implode(',', $mocks[$unitID]['aspect']) : (string)($mocks[$unitID]['aspect'] ?? '');
    $checks['mock aspects resolve'] = CardAspect($unitID) === $expectedAspects;

    // ── and the card must be in the browse catalog, or it cannot be deckbuilt ─
    $all = GetAllCardIds();
    $checks['mock leader is in GetAllCardIds'] = in_array($leaderID, $all, true);
    $checks['mock unit is in GetAllCardIds']   = in_array($unitID, $all, true);

    // ── a mock has no UUID, and that must not break the translation table ────
    $checks['UUIDLookup(mock) is the id itself or null'] =
        in_array(UUIDLookup($leaderID), [null, $leaderID], true);

    // ── ART: the file the client will request must actually exist ────────────
    // Plan 3 shipped green tests while every tile 404'd. Assert the file, not the path.
    $artStem = 'mock_' . $leaderID;
    $checks['mock leader art exists']  = file_exists(__DIR__ . '/../../AppCore/SWU/Images/WebpImages/' . $artStem . '.webp');
    $checks['mock leader tile exists'] = file_exists(__DIR__ . '/../../AppCore/SWU/Images/concat/' . $artStem . '.webp');
    $checks['mock unit art exists']    = file_exists(__DIR__ . '/../../AppCore/SWU/Images/WebpImages/mock_' . $unitID . '.webp');
}

// ── The client can resolve mock art ──────────────────────────────────────────
$js = glob(__DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries_*.js');
$jsSrc = $js ? file_get_contents($js[0]) : '';
$checks['client JS emits MockCardImageIDs'] = strpos($jsSrc, 'var MockCardImageIDs') !== false;
$checks['MockCardImageIDs lists a mock'] = $leaderID && strpos($jsSrc, '"' . $leaderID . '"') !== false;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
