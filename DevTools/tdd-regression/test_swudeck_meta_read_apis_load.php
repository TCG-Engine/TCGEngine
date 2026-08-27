<?php
// RUN VIA CLI:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_meta_read_apis_load.php
//
// The FOUR meta read APIs must actually EXECUTE and return JSON.
//
// Why this exists: on 2026-08-06 the opt-in `format` filter was added to all four, each calling
// SWUStatsFormats(false). test_swu_format_stats_policy.php asserts that call is PRESENT by grepping
// the source — but a source-text check cannot tell whether the function is REACHABLE. The two under
// Stats/ include AppCore/SWU/Formats.php; the two under APIs/ did not, so every request fataled with
// "Call to undefined function SWUStatsFormats()". The Matchup Breakout modal spun on "Loading..."
// forever and TournamentSim's meta data source was dead.
//
// A grep proves the call exists. Only running the endpoint proves it runs.
//
// READ-ONLY: sentinel ids and an out-of-range week window, so every query matches zero rows.
// No inserts, no deletes — safe against the prod-clone dev database.
header('Content-Type: text/plain');

$BASE = 'http://localhost/TCGEngine';
// Week 999999 exists in no row, and ZZNOSUCH* match no leader/base, so responses are empty
// regardless of what the database holds.
$WEEK = 'startWeek=999999&endWeek=999999';
$SENTINEL = 'leaderID=ZZNOSUCHLEADER&baseID=ZZNOSUCHBASE';

$endpoints = [
    'APIs/DeckMetaMatchupStatsAPI'              => "$SENTINEL&$WEEK",
    'APIs/DeckMetaMatchupStatsAPI (consolidate)'=> "$SENTINEL&$WEEK&consolidate=1",
    'APIs/DeckMetaMatchupStatsAPI (format)'     => "$SENTINEL&$WEEK&consolidate=1&format=eternal",
    'APIs/MetaMatchupStatsAPI'                  => "$WEEK",
    'APIs/MetaMatchupStatsAPI (format)'         => "$WEEK&format=eternal",
    'Stats/DeckMetaStatsAPI'                    => "$WEEK",
    'Stats/DeckMetaStatsAPI (format)'           => "$WEEK&format=eternal",
    'Stats/CardMetaStatsAPI'                    => "$WEEK",
    'Stats/CardMetaStatsAPI (format)'           => "$WEEK&format=eternal",
];

$checks = [];
$details = [];
foreach ($endpoints as $label => $query) {
    $file = preg_replace('/ \(.*\)$/', '', $label);          // strip the variant suffix
    $ch = curl_init("$BASE/$file.php?$query");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60]);
    $body = curl_exec($ch);
    curl_close($ch);

    // A PHP fatal is emitted as HTML with a 200, which is precisely why the browser hung instead of
    // erroring — assert on the BODY, never on the status code.
    $fatal = $body !== false && stripos($body, 'Fatal error') !== false;
    $checks["$label emits no PHP fatal"] = !$fatal;

    $json = $body === false ? null : json_decode($body, true);
    $checks["$label returns JSON"] = is_array($json);
    if ($fatal || !is_array($json)) {
        $details[$label] = substr(preg_replace('/\s+/', ' ', strip_tags((string)$body)), 0, 200);
    }
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    foreach ($details as $label => $snippet) echo "  [$label] $snippet\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
