<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swudeck_card_browser.php
//
// The SWUDeck main-menu card browser replaced an iframe into the SWUCardList engine root, whose
// GENERATED NextTurnRender.php had gone stale and pointed at ./SWUDeck/concat — an art tree deleted
// in the 2026-08-05 shared-corpus migration. Every image 404'd. These checks pin the replacement.
//
// READ-ONLY: renders markup into a buffer. No database, no POST.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-card-browser-design.md
header('Content-Type: text/plain');
$root = dirname(__DIR__, 2);
require_once $root . '/SharedUI/Sites/SWUDeck/CardBrowser.php';

$checks = [];
$ids    = GetAllCardIds();
$mocks  = SWULoadMockCards();

ob_start();
SWUDeckRenderCardBrowser();
$html = ob_get_clean();

// ── One tile per dictionary id ──────────────────────────────────────────────
// Guards the silent-drop regression: a filter bug that renders 400 cards still "looks fine".
$tileCount = substr_count($html, 'class="cb-tile"');
$checks['one tile per dictionary id'] = $tileCount === count($ids);

// ── Every image src routes through the shared corpus ────────────────────────
// This is the actual defect being fixed. A hand-built path must fail here, not in production.
preg_match_all('/<img[^>]+src="([^"]+)"/', $html, $m);
$srcs = $m[1];
$checks['grid emitted image sources'] = count($srcs) >= count($ids);
// CARD ART must come from the shared corpus. The six aspect-icon <img>s the filter bar adds in Task 2
// live under Assets/Images/icons/SWU/ and are UI chrome, not card art — exempt them by name so the
// exemption is explicit rather than a loosened pattern that would also let a per-app tree through.
$allowed = ['/TCGEngine/AppCore/SWU/Images/', '/TCGEngine/Assets/Images/icons/SWU/'];
$bad = array_values(array_filter($srcs, function ($s) use ($allowed) {
    foreach ($allowed as $p) if (strpos($s, $p) === 0) return false;
    return true;
}));
$checks['every src is under AppCore/SWU/Images or the icon set'] = $bad === [];

// Tiles use the square 'concat' art, not the full card.
$checks['tiles use concat art'] = substr_count($html, '/AppCore/SWU/Images/concat/') >= count($ids);

// ── Spot-check a known card ─────────────────────────────────────────────────
$checks['SOR_033 tile present']   = strpos($html, 'concat/SOR_033.webp') !== false;
$checks['SOR_033 title rendered'] = stripos($html, 'Death Trooper') !== false;

// ── Mocks are badged, and ONLY mocks ────────────────────────────────────────
// SWULoadMockCards() returns 36 but HMW_T02/HMW_T03 are TOKENS and absent from the dictionary, so
// the badge count is driven by the iteration, never by the raw mock file.
$expectedMocks = count(array_intersect(array_keys($mocks), $ids));
$checks['34 mocks are in the dictionary'] = $expectedMocks === 34;
$checks['badge count matches dictionary mocks'] = substr_count($html, 'data-mock="1"') === $expectedMocks;
$checks['mock art uses the mock_ prefix']       = strpos($html, 'concat/mock_HMW_004.webp') !== false;
$checks['a released card is not badged']        =
    preg_match('/data-mock="1"[^>]*data-id="SOR_033"/', $html) === 0;

// ── Tokens stay excluded ────────────────────────────────────────────────────
$checks['no token tiles'] = preg_match('/data-id="[A-Z0-9]{2,5}_T\d\d"/', $html) === 0;

// ── Facets are derived, never hardcoded ─────────────────────────────────────
// The meta-page dropdowns drifted because their options were typed by hand; padawan was accepted by
// the APIs but unselectable in the UI. Facets here are computed from the dictionary.
$facets = _SWUCardBrowserFacets($ids);
$checks['12 sets discovered']   = count($facets['sets']) === 12;
$checks['5 types discovered']   = count($facets['types']) === 5;
$checks['6 aspects discovered'] = count($facets['aspects']) === 6;
// 56 cards have an empty aspect string; splitting it must not create a blank facet.
$checks['aspects exclude blanks'] = !in_array('', $facets['aspects'], true);
$sortedSets = $facets['sets']; sort($sortedSets);
$checks['facets are sorted'] = $facets['sets'] === $sortedSets;

// ── Filterable attributes exist ─────────────────────────────────────────────
foreach (['data-title', 'data-set', 'data-type', 'data-aspects', 'data-mock', 'data-id'] as $attr) {
    $checks["tiles carry $attr"] = substr_count($html, $attr . '=') >= count($ids);
}
// data-title must be lowercased server-side so the filter is a plain substring test.
$checks['data-title is lowercased'] = strpos($html, 'data-title="death trooper"') !== false;
// Subtitles are searchable: CardSubtitle('SOR_001') === 'Aspiring to Authority'.
$checks['data-title includes subtitle'] =
    strpos($html, 'data-title="director krennic aspiring to authority"') !== false;

// ── Lazy loading ────────────────────────────────────────────────────────────
// 2,212 tiles are only affordable because the browser fetches art on scroll.
$checks['images are lazy'] = substr_count($html, 'loading="lazy"') >= count($ids);

// ── No engine coupling ──────────────────────────────────────────────────────
$checks['no SWUCardList reference'] = stripos($html, 'SWUCardList') === false;
$checks['no iframe']                = stripos($html, '<iframe') === false;

// ── Filter bar ──────────────────────────────────────────────────────────────
$checks['search input rendered'] = strpos($html, 'id="cbSearch"') !== false;
$checks['filter bar rendered']   = strpos($html, 'id="cbFilters"') !== false;
// Facet controls are rendered from the derived lists, so every value is selectable.
foreach ($facets['sets'] as $s)  $checks["set facet $s"]  = strpos($html, 'value="' . $s . '"') !== false;
foreach ($facets['types'] as $t) $checks["type facet $t"] = strpos($html, 'value="' . strtolower($t) . '"') !== false;
foreach ($facets['aspects'] as $a) {
    $checks["aspect facet $a"] = strpos($html, 'data-aspect="' . strtolower($a) . '"') !== false;
    // Reuses the six icons Stats/DeckMetaStats.php already renders; names match CardAspect() exactly.
    $checks["aspect icon $a"]  = strpos($html, '/Assets/Images/icons/SWU/' . $a . '.webp') !== false;
}
$checks['mock filter rendered'] = strpos($html, 'id="cbMockFilter"') !== false;

// ── Enlarge overlay ─────────────────────────────────────────────────────────
// concat tiles have a BLANK rules box, so enlarging must swap to the WebpImages art or the browser
// cannot answer "what does this card do?".
$checks['lightbox rendered'] = strpos($html, 'id="cbLightbox"') !== false;
// The JS derives full art from the tile's own src by swapping the folder, so the literal
// WebpImages URL never appears in the markup. Assert the MECHANISM is correct rather than
// string-matching a URL: applying the same swap must reproduce SWUCardImagePath($id, 'card')
// exactly. This catches a divergence in stem naming (the mock_ prefix, _back suffixes) that a
// string match would sail past.
$checks['lightbox swaps concat for full art'] =
    strpos($html, "replace('/concat/', '/WebpImages/')") !== false;
$swapMismatch = [];
foreach (['SOR_033', 'SOR_001', 'HMW_004', 'JTL_001'] as $probe) {
    if (!in_array($probe, $ids, true)) { $swapMismatch[] = "$probe not in dictionary"; continue; }
    $swapped = str_replace('/concat/', '/WebpImages/', SWUCardImagePath($probe, 'tile'));
    if ($swapped !== SWUCardImagePath($probe, 'card')) {
        $swapMismatch[] = "$probe: $swapped !== " . SWUCardImagePath($probe, 'card');
    }
}
$checks['the swap reproduces the card-art path'] = $swapMismatch === [];

// The JS builds the full-art URL from the tile's data-id; it must go through the shared corpus root.
$checks['no per-app art tree in js'] = stripos($html, 'SWUDeck/concat') === false
    && stripos($html, 'SWUSim/concat') === false;

// ── MainMenu is wired to the browser, not the engine iframe ─────────────────
// Source-scan with comments STRIPPED. test_swu_format_stats_policy.php was wrong twice because a
// bare strpos matched the very comment explaining the removal.
$menuSrc = @file_get_contents($root . '/SharedUI/Sites/SWUDeck/MainMenu.php');
$checks['MainMenu readable'] = $menuSrc !== false;
$menuCode = '';
if ($menuSrc !== false) {
    foreach (token_get_all($menuSrc) as $tk) {
        if (is_array($tk)) {
            if ($tk[0] === T_COMMENT || $tk[0] === T_DOC_COMMENT) continue;
            $menuCode .= $tk[1];
        } else {
            $menuCode .= $tk;
        }
    }
}
// The inline <script> is a T_INLINE_HTML token, so // JS comments survive stripping. Remove the
// remaining JS line comments too, or the explanatory note about the removed iframe re-triggers this.
$menuCode = preg_replace('~^\s*//.*$~m', '', $menuCode);

$checks['MainMenu includes CardBrowser'] = strpos($menuCode, "CardBrowser.php") !== false;
$checks['MainMenu has no cardSearchFrame'] = strpos($menuCode, 'cardSearchFrame') === false;
$checks['MainMenu has no SWUCardList iframe src'] = strpos($menuCode, 'folderPath=SWUCardList') === false;
$checks['MainMenu still defines openCardSearch']  = strpos($menuCode, 'function openCardSearch') !== false;
$checks['MainMenu still defines closeCardSearch'] = strpos($menuCode, 'function closeCardSearch') !== false;

// ── SWUCardList is gone ─────────────────────────────────────────────────────
// The app root existed only to draw this grid. Its generated NextTurnRender.php was gitignored, so
// nothing in the repo recorded that it had gone stale against the 2026-08-05 art migration. Deleting
// it removes the artifact that could go stale; this check keeps it deleted.
$checks['SWUCardList root deleted']   = !is_dir($root . '/SWUCardList');
$checks['SWUCardList schema deleted'] = !is_dir($root . '/Schemas/SWUCardList');

// No LIVE reference remains. Scanned with comments stripped, because .gitignore entries and prose in
// the migration README legitimately mention the name.
//
// The same pass also enforces the standing art-tree rule: no live code may name a PER-APP art folder.
// Those trees were deleted in the 2026-08-05 shared-corpus migration. This would NOT have caught the
// bug that motivated this work — that string lived in a gitignored generated file — but it is nearly
// free here and keeps the rule honest for the SWU apps that remain.
//
// The Discord legacy-URL shim at SWUDeck/WebpImages/ must keep working. Verified 2026-08-06: its
// index.php resolves paths via __DIR__ and SWUCardImageFsPath(), and names no literal art tree in
// code, so it passes without an exemption.
$live = $artTree = [];
$scan = ['/SharedUI', '/SWUDeck', '/AppCore/SWU', '/Stats', '/APIs'];
$perApp = '~\b(SWUDeck|SWUSim|SWUCardList)/(concat|crops|WebpImages)\b~i';
// Repo-root PHP is scanned too — NOT decoration. A directories-only scan passed clean while
// zzCodeGeneratorMain.php still registered 'SWUCardList' => 'SWU Card List' in the generator's root
// picker, which would have offered a root whose schema no longer exists.
$files = array_filter(glob($root . '/*.php') ?: [], 'is_file');
foreach ($scan as $dir) {
    $d = $root . $dir;
    if (!is_dir($d)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $files[] = $f->getPathname();
}
foreach ($files as $path) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js'], true)) continue;
    $src = @file_get_contents($path);
    if ($src === false) continue;
    $code = $src;
    if ($ext === 'php') {
        $code = '';
        foreach (token_get_all($src) as $tk) {
            if (is_array($tk)) {
                if ($tk[0] === T_COMMENT || $tk[0] === T_DOC_COMMENT) continue;
                $code .= $tk[1];
            } else { $code .= $tk; }
        }
    }
    // The inline <script> blocks are T_INLINE_HTML, so JS line comments survive token stripping.
    $code = preg_replace('~^\s*//.*$~m', '', $code);
    $rel  = str_replace($root, '', $path);
    if (stripos($code, 'SWUCardList') !== false) $live[] = $rel;
    if (preg_match($perApp, $code))              $artTree[] = $rel;
}
$checks['no live SWUCardList reference'] = $live === [];
$checks['no live per-app art tree reference'] = $artTree === [];

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    echo "  tiles=$tileCount ids=" . count($ids) . " badges="
       . substr_count($html, 'data-mock="1"') . " expectedMocks=$expectedMocks\n";
    if (!empty($bad))          echo "  bad src: " . implode(', ', array_slice($bad, 0, 5)) . "\n";
    if (!empty($swapMismatch)) echo "  swap mismatch: " . implode('; ', $swapMismatch) . "\n";
    if (!empty($live))         echo "  live SWUCardList refs: " . implode(', ', $live) . "\n";
    if (!empty($artTree))      echo "  per-app art tree refs: " . implode(', ', $artTree) . "\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
