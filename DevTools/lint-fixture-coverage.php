<?php
/**
 * DevTools/lint-fixture-coverage.php
 *
 * Standalone, advisory lint over GrandArchiveSim integration-test fixtures
 * (Tests/Integration/GrandArchiveSim/<slug>/). Flags fixtures whose
 * assertions.json probably doesn't cover what the fixture claims to test.
 *
 * Why this exists: DevTools/generate-fixture-assertions.php only captures
 * Damage and zone-count changes when it auto-generates baseline assertions —
 * it never looks at Counters or TurnEffects. For a counter/status-based
 * mechanic (e.g. Static Counter, Buff Counters, Stealth, Recover), that can
 * silently produce a fixture whose assertions.json is just
 * `decision_queue_empty` — a test that passes identically whether the
 * mechanic works or is completely broken. This script is a cheap, ongoing
 * guard against that class of problem. It is a HEURISTIC: false positives
 * are expected and fine. It does not fail the build, and it is not wired
 * into any test runner — run it manually.
 *
 * Usage: php DevTools/lint-fixture-coverage.php [--root=GrandArchiveSim] [--slug=xxx]
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
chdir($repoRoot);

$rootName = 'GrandArchiveSim';
$onlySlug = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $rootName = substr($arg, 7);
    elseif (str_starts_with($arg, '--slug=')) $onlySlug = substr($arg, 7);
}

require_once $repoRoot . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime($rootName);

// ---------------------------------------------------------------------
// Mechanic keyword list: the task's own seed list, unioned with names
// pulled live from Schemas/<root>/GameSchema.txt's `Counters:` badge
// lines (e.g. `Counters: BuffCounterCount=Badge(...)` -> "buff").
// ---------------------------------------------------------------------

function LintExtractSchemaCounterKeywords($repoRoot, $rootName) {
    $keywords = [];
    $schemaPath = $repoRoot . '/Schemas/' . $rootName . '/GameSchema.txt';
    if (!is_file($schemaPath)) return $keywords;

    // GameSchema.txt has several scattered `Counters:` lines belonging to different
    // objects (a couple for Intent/Memory-ish narrow per-card display counters,
    // one big contiguous run for the Field object around line 128-143). Grouping by
    // CONSECUTIVE `Counters:` lines and taking the largest group gets us the Field
    // object's canonical mechanic badge list without hardcoding a line number (which
    // would silently go stale) and without pulling in noise like "HandCardCostDifference"
    // or "ServilePossessionsBanishCount" from the small, card-specific blocks elsewhere.
    $blocks = [];
    $current = [];
    foreach (file($schemaPath, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^Counters:\s*([A-Za-z0-9_]+)\s*=/', $line, $m)) {
            $current[] = $m[1];
        } elseif (!empty($current)) {
            $blocks[] = $current;
            $current = [];
        }
    }
    if (!empty($current)) $blocks[] = $current;
    if (empty($blocks)) return $keywords;

    usort($blocks, fn($a, $b) => count($b) <=> count($a));
    $canonicalBlock = $blocks[0];

    foreach ($canonicalBlock as $name) {
        $name = preg_replace('/CounterCount$/', '', $name);
        $name = preg_replace('/Count$/', '', $name);
        $name = preg_replace('/^Current/', '', $name);
        if ($name === '') continue;

        $keywords[strtolower($name)] = true;
        // Also split CamelCase into individual words (e.g. "DisplaySomething" -> "display", "something").
        foreach (preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $word = strtolower($word);
            if (strlen($word) >= 3) $keywords[$word] = true;
        }
    }
    return $keywords;
}

$baseKeywords = [
    'counter', 'buff', 'static', 'prep', 'cascade', 'recover', 'stealth',
    'scavenge', 'aura', 'level', 'augury', 'brew',
];
$mechanicKeywords = array_fill_keys($baseKeywords, true) + LintExtractSchemaCounterKeywords($repoRoot, $rootName);

// Generic words pulled from the schema that would false-positive on nearly
// everything and carry no mechanic-specific signal on their own.
foreach (['display', 'current', 'damage', 'image', 'true'] as $noise) {
    unset($mechanicKeywords[$noise]);
}

function LintSlugTokens($slug) {
    $parts = preg_split('/[^a-z0-9]+/i', strtolower($slug));
    return array_values(array_filter($parts, fn($p) => strlen($p) >= 3));
}

// Returns the list of matched keywords (possibly empty).
//
// Match direction matters: only "token CONTAINS keyword" (plus exact match) is used,
// never the reverse. Reverse matching (keyword contains token) is what let short,
// common English word fragments accidentally hide inside a mechanic keyword — e.g.
// slug token "the" is a literal substring of the keyword "wither", which falsely
// flagged fixtures like "seep-into-the-mind" as being about the Wither counter.
// Very short keywords (< 4 chars, e.g. "age", "hp") are also restricted to exact
// token matches only, since as substrings they hit too many unrelated words
// ("damage", "aquamirage", ...) to carry real signal.
function LintSlugMatchesMechanicKeywords($slug, $keywords) {
    $hits = [];
    foreach (LintSlugTokens($slug) as $token) {
        foreach ($keywords as $keyword => $_) {
            $isMatch = ($token === $keyword)
                || (strlen($keyword) >= 4 && str_contains($token, $keyword));
            if ($isMatch) {
                $hits[$keyword] = true;
            }
        }
    }
    return array_keys($hits);
}

// ---------------------------------------------------------------------
// Loading a fixture's expected_final_gamestate.txt into the live engine
// runtime so we can resolve an assertion's mzId to the CardID actually
// sitting there. Mirrors the pattern used by
// DevTools/generate-fixture-assertions.php and DevTools/RunIntegrationTests.php.
// ---------------------------------------------------------------------

function LintLoadFixtureFinalGamestate($repoRoot, $rootName, $slug, $fixtureDir) {
    $expectedPath = $fixtureDir . '/expected_final_gamestate.txt';
    if (!is_file($expectedPath)) return null;

    $gameName = 'lint_' . $slug . '_' . uniqid();
    $gameDir = $repoRoot . '/' . $rootName . '/Games/' . $gameName;
    RegressionEnsureDir($gameDir);

    $text = file_get_contents($expectedPath);
    file_put_contents(
        $gameDir . '/Gamestate.txt',
        RegressionNormalizeGamestateTextForRoot($rootName, $text)
    );

    $GLOBALS['gameName'] = $gameName;
    ParseGamestate('./' . $rootName . '/');

    return $gameDir;
}

// Resolve a card_property_equals assertion's mzId to the CardID sitting at
// that slot in the currently-loaded gamestate — using the SAME playerID
// perspective the real assertion evaluator uses (RegressionEvaluateAssertion:
// $playerID = viewerPlayerID ?? 1).
function LintResolveAssertionCardId($assertion) {
    global $playerID;
    $saved = $playerID ?? 1;
    $playerID = intval($assertion['viewerPlayerID'] ?? 1);
    $mzId = strval($assertion['mzId'] ?? '');
    $obj = GetZoneObject($mzId);
    $cardId = (is_object($obj) && property_exists($obj, 'CardID')) ? $obj->CardID : null;
    $playerID = $saved;
    return $cardId;
}

function LintIsLowCoverage($assertions) {
    $nonEmpty = array_values(array_filter($assertions, fn($a) => is_array($a) && !empty($a['type'])));
    if (count($nonEmpty) === 0) return true;
    if (count($nonEmpty) === 1 && ($nonEmpty[0]['type'] ?? '') === 'decision_queue_empty') return true;
    return false;
}

// ---------------------------------------------------------------------
// Main scan
// ---------------------------------------------------------------------

$fixtureRoot = $repoRoot . '/Tests/Integration/' . $rootName;
if (!is_dir($fixtureRoot)) {
    echo "No fixture directory found for {$rootName}\n";
    exit(1);
}

$slugs = array_values(array_filter(scandir($fixtureRoot), function ($e) use ($fixtureRoot) {
    return $e !== '.' && $e !== '..' && is_dir($fixtureRoot . '/' . $e);
}));
sort($slugs);

$totalFixtures = 0;
$flaggedSlugs = [];

foreach ($slugs as $slug) {
    if ($onlySlug !== null && $slug !== $onlySlug) continue;

    $dir = $fixtureRoot . '/' . $slug;
    $assertionsPath = $dir . '/assertions.json';
    if (!is_file($assertionsPath)) continue; // not a real fixture dir

    ++$totalFixtures;

    $metaPath = $dir . '/meta.json';
    $meta = is_file($metaPath) ? json_decode(file_get_contents($metaPath), true) : [];
    $testedCards = (is_array($meta) && is_array($meta['testedCards'] ?? null)) ? $meta['testedCards'] : [];

    $assertions = json_decode(file_get_contents($assertionsPath), true);
    if (!is_array($assertions)) $assertions = [];

    $mechanicHits = LintSlugMatchesMechanicKeywords($slug, $mechanicKeywords);
    $flaggedThisFixture = false;

    // --- Check 1: slug/testedCards imply a counter-based mechanic, but no
    //     assertion touches Counters/TurnEffects for the tested card. ---
    if (!empty($mechanicHits)) {
        $candidateAssertions = array_values(array_filter($assertions, function ($a) {
            return is_array($a)
                && ($a['type'] ?? '') === 'card_property_equals'
                && in_array($a['property'] ?? '', ['Counters', 'TurnEffects'], true);
        }));

        $covered = false;
        if (!empty($candidateAssertions)) {
            if (empty($testedCards)) {
                // No testedCards to pin down — but SOMETHING in the fixture does
                // check Counters/TurnEffects, so give it the benefit of the doubt.
                $covered = true;
            } else {
                $gameDir = LintLoadFixtureFinalGamestate($repoRoot, $rootName, $slug, $dir);
                if ($gameDir === null) {
                    // Can't verify (missing expected_final_gamestate.txt) — don't
                    // penalize the fixture for tooling gaps, same as above.
                    $covered = true;
                } else {
                    foreach ($candidateAssertions as $a) {
                        $cardId = LintResolveAssertionCardId($a);
                        if ($cardId !== null && in_array($cardId, $testedCards, true)) {
                            $covered = true;
                            break;
                        }
                    }
                    RegressionDeleteDirRecursive($gameDir);
                }
            }
        }

        if (!$covered) {
            echo "[WARN] {$slug}: likely under-tested — mechanic implies Counters/TurnEffects change but no assertion checks it"
                . ' (matched keywords: ' . implode(', ', $mechanicHits) . ")\n";
            $flaggedThisFixture = true;
        }
    }

    // --- Check 2: assertions.json is empty, or just decision_queue_empty. ---
    if (LintIsLowCoverage($assertions)) {
        $nonEmptyCount = count(array_filter($assertions, fn($a) => is_array($a) && !empty($a['type'])));
        echo "[INFO] {$slug}: assertions.json has only {$nonEmptyCount} assertion(s) — cheap to eyeball, may be under-testing the fixture\n";
        $flaggedThisFixture = true;
    }

    if ($flaggedThisFixture) $flaggedSlugs[$slug] = true;
}

echo "\n" . count($flaggedSlugs) . " fixtures flagged out of {$totalFixtures} total.\n";

exit(0);
