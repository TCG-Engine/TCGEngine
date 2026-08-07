<?php
// The SET_NNN identity migration's classifier — the single source of truth for "what does this
// stored identifier become?".
//
// Two tools depend on this and MUST NOT disagree:
//   - zzCardIdentifierCensus.php   decides whether the cutover may proceed (class 3 blocks it)
//   - tools/materialize-id-map.php emits the SQL map table the migration actually joins against
// If the gate classifies a value as mappable and the map omits it, the migration silently drops
// rows the operator was told were safe. Hence one implementation, used by both.
//
// NO DATABASE ACCESS. Everything here is derived from the generated card dictionaries, so it runs
// under LAMPP's CLI PHP (which has no mysqli) as happily as under Apache.
//
// Classification, per the spec (§2 "Identifier classification"):
//   class 1  already SET_NNN        -> canonicalised through CardIDOverride, then stored
//   class 2  known non-card value   -> preserved VERBATIM (base colours, sentinels)
//   class 3  unresolvable           -> not in the map at all; the migration's INNER JOIN drops it
//
// The ordering matters. An implicit "if it doesn't map, keep it" rule would let the mixed key space
// this migration exists to eliminate creep straight back in — so class 2 is an explicit allowlist
// and anything outside it that fails to resolve is class 3.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §2, §6

require_once __DIR__ . '/../../CardIdentity.php';   // THE classifier — permanent runtime code

// ⚠ The classifier itself lives in AppCore/SWU/CardIdentity.php, NOT here. Stats ingress depends on
// it at runtime forever, and this directory is deletable once the cutover weekend is over — a copy
// here would become the version that silently rots, or the one whose deletion breaks ingress.
// These are thin aliases so the migration tooling and its tests keep their existing names.

function SWUMigrationBaseColours(): array { return SWUCardIdentityBaseColours(); }
function SWUMigrationSentinels(): array   { return SWUCardIdentitySentinels(); }
function SWUMigrationIsSetNnn(string $v): bool { return SWUCardIdentityIsSetNnn($v); }
function SWUMigrationLeaderUnitMap(): array { return SWUCardIdentityLeaderUnitMap(); }
function SWUMigrationClassify(string $value, bool $poly): array { return SWUCardIdentityClassify($value, $poly); }

// (table, column, isPolymorphic) — every column holding a card identifier. 'poly' marks one that
// legitimately holds a bucket key (a card id OR a base colour).
function SWUMigrationTargets(): array
{
    return [
        ['carddeckstats',          'cardID',             false],
        ['cardmetastats',          'cardID',             false],
        ['deckstats',              'leaderID',           false],
        ['opponentdeckstats',      'leaderID',           false],
        ['opponentnamedbasestats', 'leaderID',           false],
        ['opponentnamedbasestats', 'baseID',             true],
        ['deckmetastats',          'leaderID',           false],
        ['deckmetastats',          'baseID',             true],
        ['deckmetamatchupstats',   'leaderID',           false],
        ['deckmetamatchupstats',   'baseID',             true],
        ['deckmetamatchupstats',   'opponentLeaderID',   false],
        ['deckmetamatchupstats',   'opponentBaseID',     true],
        ['completedgame',          'WinningHero',        false],
        ['completedgame',          'LosingHero',         false],
        ['favoritedeck',           'hero',               false],
        ['favoritedeck',           'baseId',             true],
        ['meleetournamentdeck',    'leader',             false],
        ['meleetournamentdeck',    'base',               true],
        ['matchhistory',           'keyCard1ID',         false],
        ['matchhistory',           'keyCard2ID',         false],
        ['matchhistory',           'keyCard3ID',         false],
        ['matchhistory',           'opponentKeyCard1ID', false],
        ['matchhistory',           'opponentKeyCard2ID', false],
        ['matchhistory',           'opponentKeyCard3ID', false],
    ];
}

// The complete map, built from the dictionary universe rather than from the database — so it is
// identical whichever box it runs on, and a stored value simply being absent from it IS class 3.
//
// Returns oldID => ['to' => newID, 'disposition' => 'map'|'keep', 'via' => string].
//   'map'  the migration writes 'to'
//   'keep' the migration writes the ORIGINAL stored value, untouched (class 2)
//
// 'keep' is a distinct disposition rather than a self-mapping row because the identifier columns
// collate utf8mb4_general_ci: a single 'green' row matches 'Green' and 'GREEN' too, and rewriting
// those to the map's lowercase spelling would silently alter 258k rows of legitimate data.
function SWUMigrationBuildMap(): array
{
    $map = [];

    // Every dictionary key, in both the shapes a stored row can carry.
    foreach (array_keys($GLOBALS['titleData'] ?? []) as $key) {
        $key = (string)$key;
        $r = SWUMigrationClassify($key, false);
        if ($r['class'] === 1) $map[$key] = ['to' => $r['to'], 'disposition' => 'map', 'via' => $r['via']];

        if (function_exists('UUIDLookup')) {
            $uuid = UUIDLookup($key);
            if ($uuid !== null && $uuid !== '' && (string)$uuid !== $key) {
                $r = SWUMigrationClassify((string)$uuid, false);
                if ($r['class'] === 1) {
                    $map[(string)$uuid] = ['to' => $r['to'], 'disposition' => 'map', 'via' => $r['via']];
                }
            }
        }
    }

    // Leader-unit legacy ids — the Palpatine class.
    foreach (SWUMigrationLeaderUnitMap() as $asset => $cardID) {
        $map[(string)$asset] = [
            'to' => CardIDOverride($cardID), 'disposition' => 'map', 'via' => 'leader-unit-legacy',
        ];
    }

    // Class 2 passthroughs, so a single JOIN covers every legitimate value and only class 3 misses.
    foreach (SWUMigrationBaseColours() as $c) {
        $map[$c] = ['to' => $c, 'disposition' => 'keep', 'via' => 'base-colour'];
    }
    foreach (SWUMigrationSentinels() as $s) {
        $map[$s] = ['to' => $s, 'disposition' => 'keep', 'via' => 'sentinel'];
    }

    return $map;
}

// Look a value up in the map the way MySQL will.
//
// ⚠ Use this, never a bare $map[$value]. The identifier columns collate utf8mb4_general_ci, so the
// JOIN matches 'Green' against the map's 'green' row — but PHP's array lookup is case-SENSITIVE and
// would report all 258,367 colour rows as class 3. A PHP-side audit disagreeing with the SQL that
// actually runs is the whole failure mode this migration's gate exists to prevent.
function SWUMigrationMapLookup(array $map, string $value): ?array
{
    if (isset($map[$value])) return $map[$value];

    // Build the case-folded index ONCE, not per call.
    //
    // This used to fall back to a linear scan of the whole map. Correct, but quadratic: a MISS —
    // which is the common case, since most fields are counts, flags and deck names — walked all
    // ~4,800 entries doing a strtolower each. Over 105k deck files that is billions of comparisons,
    // and the prod-scale dry run had not finished after 10 minutes.
    static $folded = null, $builtFor = -1;
    if ($folded === null || $builtFor !== count($map)) {
        $folded = [];
        foreach ($map as $k => $v) $folded[strtolower((string)$k)] = $v;
        $builtFor = count($map);
    }
    return $folded[strtolower($value)] ?? null;
}
