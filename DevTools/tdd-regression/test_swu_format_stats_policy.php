<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swu_format_stats_policy.php
//
// AppCore/SWU/Formats.php is the SINGLE authority on which formats produce statistics. Before
// 2026-08-06 the list ['premier','eternal','twinsuns','padawan'] was retyped in eight places and had
// already drifted — padawan was accepted by the meta APIs but missing from both page dropdowns.
//
// READ-ONLY: pure registry calls, no database, no POST.
//
// Design: docs/superpowers/specs/2026-08-06-swu-preview-format-stats-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';

$checks = [];

// Formats that record statistics.
$stats = SWUStatsFormats();
foreach (['premier', 'eternal', 'twinsuns', 'padawan'] as $f) {
    $checks["'$f' produces stats"] = in_array($f, $stats, true);
}
// Preview formats are first-class formats separated by their own key.
foreach (['preview', 'twinsuns-preview', 'padawan-preview', 'eternal-preview'] as $f) {
    $checks["preview '$f' produces stats"] = in_array($f, $stats, true);
}
// Open and the local/solo MODES never do. Goldfish is one player; Hotseat is one person on both seats.
foreach (['open', 'goldfish', 'hotseat'] as $f) {
    $checks["'$f' produces NO stats"] = !in_array($f, $stats, true);
}

// Registration is a separate question from eligibility: open/goldfish/hotseat ARE registered (so the
// endpoint accepts them and records nothing), while an unknown value is not (so it is rejected).
foreach (['premier', 'open', 'goldfish', 'hotseat', 'preview'] as $f) {
    $checks["'$f' is registered"] = SWUFormatIsRegistered($f) === true;
}
$checks['unknown id is not registered'] = SWUFormatIsRegistered('nope') === false;
$checks['empty id is not registered']   = SWUFormatIsRegistered('') === false;
$checks['unknown id produces no stats'] = !in_array('nope', SWUStatsFormats(), true);

// Read vs write asymmetry: a format disabled after its preview window closes must stay READABLE so
// its historical rows can still be selected. Writes require enabled; reads do not.
$checks['read list is a superset of the write list'] =
    count(array_diff(SWUStatsFormats(true), SWUStatsFormats(false))) === 0;

// The deferred-widening trigger. format is varchar(16) AND a PRIMARY KEY component, so an id longer
// than 16 would error on insert (or silently merge rows under a non-strict server). Fail here first.
$tooLong = [];
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    if (strlen($id) > 16) $tooLong[] = $id;
}
$checks['every format id is <= 16 chars'] = $tooLong === [];

// Mitigation for 400-on-unregistered (design doc §4 "Accepted risk"): every format the lobby can
// offer must be registered, so a format added to the UI without a registry entry fails here rather
// than destroying submissions in production. SWUSim reads this same registry, so the invariant holds
// by construction today — this pins it against a future hardcoded format string in match creation.
$unregistered = [];
foreach (array_keys(SWUListFormats()) as $id) {
    if (!SWUFormatIsRegistered($id)) $unregistered[] = $id;
}
$checks['every offered format is registered'] = $unregistered === [];

// ── Read APIs derive their whitelist ────────────────────────────────────────
// Reads use SWUStatsFormats(FALSE): a preview format is disabled once its window closes, and its
// historical rows must remain selectable. Default stays premier and invalid values still fall back
// to premier, so existing responses are byte-identical — the API change is purely additive.
// FOUR read APIs, not two: the matchup pair lives under APIs/, not Stats/, which is exactly why a
// survey scoped to Stats/ missed them on 2026-08-06.
foreach ([
    'Stats/CardMetaStatsAPI', 'Stats/DeckMetaStatsAPI',
    'APIs/MetaMatchupStatsAPI', 'APIs/DeckMetaMatchupStatsAPI',
] as $api) {
    $src = @file_get_contents(__DIR__ . "/../../$api.php");
    $checks["$api derives its whitelist"]  = $src !== false && strpos($src, 'SWUStatsFormats(false)') !== false;
    $checks["$api has no literal list"]    = $src !== false && strpos($src, "'premier','eternal','twinsuns','padawan'") === false;
    $checks["$api still defaults premier"] = $src !== false && strpos($src, "\$format = 'premier';") !== false;
}

// ── Dropdowns are rendered, not hardcoded ───────────────────────────────────
// This is where the drift showed: padawan was accepted by the APIs but absent from both selects, so
// it could not be chosen in the UI at all.
foreach (['DeckMetaStats', 'CardMetaStats'] as $page) {
    $src = @file_get_contents(__DIR__ . "/../../Stats/$page.php");
    $checks["$page renders its format options"] = $src !== false && strpos($src, 'SWUStatsFormats(false)') !== false;
    $checks["$page has no hardcoded options"]   = $src !== false && strpos($src, '<option value="twinsuns">') === false;
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    if ($tooLong) echo "  too long: " . implode(', ', $tooLong) . "\n";
    if ($unregistered) echo "  unregistered: " . implode(', ', $unregistered) . "\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
