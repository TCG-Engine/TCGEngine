<?php
// Rewrites deck gamestate files from FFG UIDs to SET_NNN. Spec §5.
//
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php              # dry run (default)
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php --apply
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php --limit=200 --verbose
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php --deck=12345 --verbose   # CANARY
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php --deck=12345 --apply     # one deck only
//   php AppCore/SWU/migrations/tools/rewrite-deck-files.php --games-dir=/tmp/Games-copy --apply
//
// DRY RUN IS THE DEFAULT and writes nothing. It reports file count, identifier count, and every
// value that LOOKS like a card identifier but does not map. **A non-empty unmapped list blocks the
// cutover** — it means the dictionaries are stale — so --apply refuses while any exist.
//
// No database access: the map comes from the generated dictionaries, so this runs under LAMPP's
// mysqli-less CLI PHP.
//
// ── Why this is structure-AGNOSTIC ───────────────────────────────────────────
// A Gamestate.txt is positional and length-prefixed (a count, then that many object lines, ~30
// zones in a fixed order). The obvious tool walks that structure and translates the card zones.
//
// Do not do that. The zone list has GROWN over time — this tree alone holds files with 26, 27 and
// 30 zones — and the version blob has at least two delimiter generations (`<s0>` in older files,
// `<v0>`/`<v1>`/`<v2>` in newer). A zone table derived from today's WriteGamestate() mislabels every
// older layout, and a mislabeled zone means translating a sort mode or a stat as if it were a card.
// Measured, not theorised: the first draft of this tool proposed "translating" the values `Title`,
// `Cost`, `SetNum` and `SWUDB`. Positional deck-file zones are what caused the Leader2 sideboard
// data loss, and reconstructing every historical layout is not a bet worth taking over ~102k files.
//
// So this ignores structure entirely and uses the MAP as the oracle. It splits each line into
// fields on every delimiter the format has ever used and replaces a field only when it is an EXACT
// map hit with disposition 'map'. Consequences:
//
//   * Format-agnostic — old, current and future layouts all work, including nested version blobs.
//   * A count (`51`), a flag (`0`) or a sort mode (`Title`) is never in the map, so it is never
//     touched. The sentinels `-1`/`0`/`1` ARE in the map, as class 2 'keep' — also untouched.
//   * Byte-for-byte identical output except for the identifiers themselves. No line is added,
//     removed or reordered, and no file is upgraded to a newer zone layout. Asserted, not assumed.
//
// The only theoretical false positive is a non-card field whose value exactly equals a real card
// identifier. Every rewriting key is a 10-digit UID, a 10-hex asset hash, or a SET_NNN, while the
// non-card fields are small counts, flags and mode names. --verbose prints every distinct
// translation it would make, so the dry run is auditable before anything is written.
//
// Rollback is a restore of the deck-file archive taken in runbook §2.2.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §5

$repoRoot = dirname(__DIR__, 4);
require_once $repoRoot . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../lib/IdentifierMap.php';

// Every delimiter the deck format has used. Splitting on all of them flattens a nested version
// blob into fields without needing to know which generation wrote it.
const DECK_DELIMS = ['<v0>', '<v1>', '<v2>', '<s0>', '<s1>', '<vname>'];

// Does this value look like it is TRYING to be a card identifier? Used only for the unmapped
// report — a value matching this that is absent from the map is what blocks the cutover. Sort
// modes and counts do not match, so they never pollute the gate.
function deck_looks_like_id(string $v): bool
{
    if (preg_match('/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/', $v)) return true;   // SET_NNN / SET_T##
    if (preg_match('/^\d{10}$/', $v)) return true;                           // FFG UID
    if (preg_match('/^[0-9a-f]{10}$/', $v)) return true;                     // leader-unit asset hash
    return false;
}

$apply = false; $verbose = false; $limit = 0; $allowUnmapped = false; $gamesDirArg = null; $onlyDeck = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') $apply = true;
    elseif ($arg === '--dry-run') $apply = false;
    elseif ($arg === '--verbose') $verbose = true;
    elseif ($arg === '--allow-unmapped') $allowUnmapped = true;
    elseif (strpos($arg, '--limit=') === 0) $limit = max(0, (int)substr($arg, 8));
    // CANARY: one specific deck. --limit=N takes the first N alphabetically, which tells you
    // nothing about a deck you can actually open and eyeball afterwards.
    elseif (strpos($arg, '--deck=') === 0) $onlyDeck = preg_replace('/[^0-9]/', '', substr($arg, 7));
    // Lets the rehearsal run against a COPY of the tree rather than the live one.
    elseif (strpos($arg, '--games-dir=') === 0) $gamesDirArg = rtrim(substr($arg, 12), '/');
    else { fwrite(STDERR, "unknown argument: $arg\n"); exit(2); }
}

$GLOBALS['_deckCanary'] = ($onlyDeck !== null && $onlyDeck !== '');

$map = SWUMigrationBuildMap();
if (count($map) < 1000) {
    fwrite(STDERR, "FATAL: the id map has only " . count($map) . " entries — the dictionary did not\n"
                 . "load, or predates the token-inclusion fix. Regenerate before running this.\n");
    exit(1);
}

// Split a line on every known delimiter, KEEPING the delimiters so it rejoins byte-identically.
function deck_split(string $line): array
{
    static $pattern = null;
    if ($pattern === null) {
        $pattern = '/(' . implode('|', array_map('preg_quote', DECK_DELIMS)) . ')/';
    }
    return preg_split($pattern, $line, -1, PREG_SPLIT_DELIM_CAPTURE);
}

// A saved version carries its leader id inside a COMPOUND token that is not itself a map key:
//
//     0:4352150438<v0>SOR_027<v0>TWI_141<v1>...   the snapshot blob, "<index>:<id>"
//     3503494534 This_is_regional_governor        the snapshot label, "<id> <name>"
//
// Splitting on DECK_DELIMS flattens the blob's card list correctly, but these two shapes survive
// as single tokens, so the whole-token lookup below never matches them. That is what left exactly
// one stray UUID per saved version in ~1,900 decks after the 2026-08-06 pass — every card id
// around them converted, and the sweep found the leftovers only in the Versions zone.
//
// Returns the identifier field of a compound token, or null if the token is not compound.
function deck_compound_field(string $v): ?string
{
    if (preg_match('/^\d+:(\S+)$/', $v, $m)) return $m[1];      // "<index>:<id>"
    if (preg_match('/^(\S+)[ \t]/', $v, $m)) return $m[1];      // "<id> <name>"
    return null;
}

function deck_rewrite_line(array $map, string $line, array &$stat): string
{
    if ($line === '') return $line;
    $parts = deck_split($line);
    $changed = false;
    foreach ($parts as $idx => $part) {
        if (in_array($part, DECK_DELIMS, true)) continue;   // a delimiter, never a value
        $v = trim($part);
        if ($v === '') continue;

        // Whole token first; only then the identifier field of a compound token. A free-text
        // field whose first word is not an identifier simply misses the map and is left alone.
        $target = $v;
        $e = SWUMigrationMapLookup($map, $target);
        if ($e === null) {
            $inner = deck_compound_field($v);
            if ($inner !== null) {
                $hit = SWUMigrationMapLookup($map, $inner);
                if ($hit !== null) { $target = $inner; $e = $hit; }
            }
        }

        if ($e === null) {
            // Only an identifier-SHAPED value counts as unmapped. Everything else is a count, a
            // flag, a deck name or a sort mode, and is simply not our business.
            $shape = deck_compound_field($v) ?? $v;
            if (deck_looks_like_id($shape)) {
                $stat['unmapped']++;
                $stat['unmappedValues'][$shape] = ($stat['unmappedValues'][$shape] ?? 0) + 1;
            }
            continue;
        }
        if ($e['disposition'] === 'keep') { $stat['same']++; continue; }     // class 2, verbatim
        if ($e['to'] === $target)         { $stat['same']++; continue; }     // already canonical

        $stat['mapped']++;
        $stat['mappedPairs'][$target . ' -> ' . $e['to']] = true;
        if ($target === $v) {
            $parts[$idx] = str_replace($v, $e['to'], $part);   // keeps surrounding whitespace
        } else {
            // Replace ONLY the identifier field. The index prefix and the name suffix may both
            // contain digits, and a blind str_replace of a 10-digit run could corrupt either.
            $newV = preg_replace_callback(
                '/^(\d+:)?' . preg_quote($target, '/') . '/',
                fn($m) => ($m[1] ?? '') . $e['to'],
                $v, 1);
            $parts[$idx] = str_replace($v, $newV, $part);
        }
        $changed = true;
    }
    return $changed ? implode('', $parts) : $line;
}

// Rewrite a whole file. Returns null only if the result would not be structurally identical.
function deck_rewrite_file(array $map, string $text, array &$stat): ?string
{
    $eol = (strpos($text, "\r\n") !== false) ? "\r\n" : "\n";
    $lines = explode("\n", str_replace("\r\n", "\n", $text));

    $out = [];
    foreach ($lines as $line) $out[] = deck_rewrite_line($map, $line, $stat);

    // The invariant that makes this safe: same number of lines, always. Values may change, shape
    // may not. If this trips, the split/join round trip is lossy — do not write.
    if (count($out) !== count($lines)) return null;

    return implode($eol, $out);
}

// ── Walk ────────────────────────────────────────────────────────────────────
$gamesDir = $gamesDirArg !== null ? $gamesDirArg : $repoRoot . '/SWUDeck/Games';
if (!is_dir($gamesDir)) { fwrite(STDERR, "FATAL: no such directory: $gamesDir\n"); exit(1); }

if ($onlyDeck !== null && $onlyDeck !== '') {
    $one = $gamesDir . '/' . $onlyDeck . '/Gamestate.txt';
    if (!is_file($one)) { fwrite(STDERR, "FATAL: no such deck: $one\n"); exit(1); }
    $files = [$one];
} else {
    $files = glob($gamesDir . '/*/Gamestate.txt');
}
sort($files);

// Finding NOTHING is an error, not a clean run. A mistyped --games-dir otherwise prints
// "0 unmapped ... nothing to do" and exits 0, which during a maintenance window reads exactly like
// "the rewrite is complete". Fail loudly instead, and point at the likely cause: the archive keeps
// its Games/ prefix, so /tmp/X is usually wrong where /tmp/X/Games is right.
if (!$files) {
    fwrite(STDERR, "FATAL: no */Gamestate.txt found under $gamesDir\n");
    $nested = glob($gamesDir . '/*/*/Gamestate.txt');
    if ($nested) {
        fwrite(STDERR, sprintf("Found %d one level deeper. Did you mean --games-dir=%s/Games ?\n",
            count($nested), $gamesDir));
    }
    exit(1);
}

if ($limit > 0) $files = array_slice($files, 0, $limit);

// A function so APPLY can run it twice: analyse, then write. That ordering IS the gate — "a
// non-empty unmapped list blocks the cutover" is worthless if the files are already rewritten by
// the time the list prints.
function deck_walk(array $map, array $files, bool $write, bool $verbose): array
{
    $stat = ['same' => 0, 'mapped' => 0, 'unmapped' => 0, 'unmappedValues' => [], 'mappedPairs' => []];
    $r = ['seen' => 0, 'changed' => 0, 'written' => 0, 'malformed' => [], 'writeFailed' => []];

    foreach ($files as $path) {
        $text = @file_get_contents($path);
        if ($text === false) { $r['malformed'][] = "$path (unreadable)"; continue; }
        $r['seen']++;

        $before = $stat;
        $new = deck_rewrite_file($map, $text, $stat);
        if ($new === null) { $stat = $before; $r['malformed'][] = $path; continue; }
        if ($new === $text) continue;
        $r['changed']++;
        if ($verbose && !$write) printf("  would change %s\n", $path);

        if ($write) {
            // A canary must be trivially revertible — the whole point is to open the deck, decide,
            // and put it back if it looks wrong. One file, so the copy costs nothing.
            if ($GLOBALS['_deckCanary'] ?? false) {
                $bak = $path . '.precanary';
                if (!file_exists($bak)) @copy($path, $bak);
            }
            // Write-then-rename: a reader must never see a half-written deck file. Same-directory
            // temp so the rename stays atomic (a cross-filesystem rename is a copy).
            //
            // The rename REPLACES THE INODE, so the deck file ends up owned by whoever ran this
            // tool rather than by the web server. Run as root — which is how the migration runs —
            // that leaves every rewritten deck root-owned and mode 644, so Apache can still read
            // it but no longer write it. The symptom is nasty precisely because it is not a data
            // problem: decks render perfectly and every save fails with "Permission denied" from
            // GamestateParser's file_put_contents. That took down deck saving on 2026-08-06.
            // Carry the original owner and mode across the rename so the tool is ownership-neutral.
            $owner = @stat($path);
            $tmp = $path . '.migtmp';
            if (@file_put_contents($tmp, $new) === false) { $r['writeFailed'][] = $path; @unlink($tmp); continue; }
            if ($owner !== false) {
                @chmod($tmp, $owner['mode'] & 0777);
                @chown($tmp, $owner['uid']);
                @chgrp($tmp, $owner['gid']);
            }
            if (!@rename($tmp, $path))                    { $r['writeFailed'][] = $path; @unlink($tmp); continue; }
            $r['written']++;
        }
    }
    $r['stat'] = $stat;
    return $r;
}

printf("%s over %d deck file(s) in %s\n\n", $apply ? 'APPLY' : 'DRY RUN', count($files), $gamesDir);

$r = deck_walk($map, $files, false, $verbose);
$stat = $r['stat'];
$unmappedValues = $stat['unmappedValues'];

// THE GATE. Refuse to write while anything identifier-shaped is unresolvable.
if ($apply && $unmappedValues && !$allowUnmapped) {
    printf("REFUSING TO APPLY: %d distinct unmapped identifier(s).\n", count($unmappedValues));
    arsort($unmappedValues);
    foreach (array_slice($unmappedValues, 0, 30, true) as $v => $n) printf("  %-14s %d occurrence(s)\n", $v, $n);
    echo "\nNothing was written. Regenerate the card dictionaries and re-run the dry run.\n"
       . "Override with --allow-unmapped only once you have decided those ids are genuinely dead;\n"
       . "they are left untouched either way, which leaves those files mixed-key.\n";
    exit(1);
}
if ($apply && $r['malformed'] && !$allowUnmapped) {
    printf("REFUSING TO APPLY: %d file(s) failed the structural round trip.\n", count($r['malformed']));
    foreach (array_slice($r['malformed'], 0, 20) as $m) echo "  $m\n";
    echo "\nNothing was written.\n";
    exit(1);
}

if ($apply) {
    $r = deck_walk($map, $files, true, $verbose);
    $stat = $r['stat'];
    $unmappedValues = $stat['unmappedValues'];
}

// ── Report ──────────────────────────────────────────────────────────────────
printf("files:        %d seen, %d %s\n", $r['seen'], $r['changed'],
    $apply ? sprintf("changed, %d written", $r['written']) : 'would change');
printf("identifiers:  %d translated (%d distinct), %d already canonical, %d unmapped (%d distinct)\n",
    $stat['mapped'], count($stat['mappedPairs']), $stat['same'],
    $stat['unmapped'], count($unmappedValues));

if ($verbose && $stat['mappedPairs']) {
    echo "\nTRANSLATIONS (every distinct one — audit before --apply):\n";
    $pairs = array_keys($stat['mappedPairs']); sort($pairs);
    foreach (array_slice($pairs, 0, 40) as $p) echo "  $p\n";
    if (count($pairs) > 40) printf("  ... and %d more\n", count($pairs) - 40);
}
if ($r['malformed']) {
    printf("\nSKIPPED — failed the structural round trip (%d):\n", count($r['malformed']));
    foreach (array_slice($r['malformed'], 0, 20) as $m) echo "  $m\n";
    echo "NOT modified.\n";
}
if ($r['writeFailed']) {
    printf("\nWRITE FAILED (%d):\n", count($r['writeFailed']));
    foreach (array_slice($r['writeFailed'], 0, 20) as $m) echo "  $m\n";
}
if ($unmappedValues) {
    printf("\nUNMAPPED IDENTIFIERS (%d distinct) — THIS BLOCKS CUTOVER:\n", count($unmappedValues));
    arsort($unmappedValues);
    foreach (array_slice($unmappedValues, 0, 30, true) as $v => $n) printf("  %-14s %d occurrence(s)\n", $v, $n);
    if (count($unmappedValues) > 30) printf("  ... and %d more\n", count($unmappedValues) - 30);
    echo "Left untouched.\n";
}

if (!$apply) {
    echo "\nDRY RUN — nothing was written.\n";
    if ($unmappedValues && !$allowUnmapped) { echo "Resolve the unmapped identifiers before --apply.\n"; exit(1); }
    printf("Re-run with --apply to rewrite %d file(s).\n", $r['changed']);
    exit($r['malformed'] ? 1 : 0);
}

if ($r['writeFailed'] || $r['malformed']) { echo "\nCompleted WITH ERRORS — see above.\n"; exit(1); }
if ($GLOBALS['_deckCanary']) {
    $bak = $gamesDir . '/' . $onlyDeck . '/Gamestate.txt.precanary';
    echo "\nCANARY: deck $onlyDeck rewritten. Original saved as Gamestate.txt.precanary.\n";
    echo "  Open it:   NextTurn.php?gameName=$onlyDeck&playerID=1&folderPath=SWUDeck\n";
    echo "  Check:     leader, base, main deck and SIDEBOARD all render; card art loads.\n";
    echo "  Revert:    mv " . $bak . " " . $gamesDir . "/$onlyDeck/Gamestate.txt\n";
    echo "  Accept:    rm " . $bak . "   (do this before the full run — a stray .precanary is confusing)\n";
    exit(0);
}
echo "\nDone. Verify with a second dry run: it must report 0 files changing.\n";
exit(0);
