<?php
// scaffold-cards.php <SET> [--dry]
//
// Creates an empty implementation stub (header + card text + `// TODO: UNIMPLEMENTED`)
// for every NON-VANILLA card in <SET> that isn't already covered, then refreshes the
// CardID->file index. ADDITIVE ONLY — never overwrites or deletes an existing file.
//
//   docker exec -w /var/www/html/TCGEngine <container> \
//     php -d xdebug.mode=off SWUSim/DevTools/scaffold-cards.php TS26 [--dry]
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repo = getenv('REPO_ROOT') ?: (function () {
    $d = __DIR__;
    while ($d !== '/' && $d !== '' && !(is_dir("$d/SWUSim") && is_dir("$d/Core"))) $d = dirname($d);
    return $d;
})();

require __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
require __DIR__ . '/../GeneratedCode/GeneratedAbilityStubs.php';
require $repo . '/AppCore/SWU/Overrides.php';
require $repo . '/AppCore/SWU/DeckValidation.php';
require __DIR__ . '/HeaderGen.php';
require __DIR__ . '/regen-card-index.php';   // SWUSimBuildCardIndex / SWUSimWriteCardIndex

// Recognized keyword names, parsed from GeneratedKeywordCode.php's $<Name>_Cards vars.
function scaffold_keyword_names(string $keywordCodePath): array {
    $src = @file_get_contents($keywordCodePath);
    if ($src === false) return [];
    preg_match_all('/\$([A-Za-z0-9]+)_Cards\b/', $src, $m);
    return array_values(array_unique($m[1]));
}

// All CardIDs whose set prefix (before the first "_") equals $set.
function scaffold_set_card_ids(string $set): array {
    global $titleData;
    $out = [];
    foreach (array_keys($titleData) as $cid) {
        if (strtoupper(explode('_', $cid)[0]) === strtoupper($set)) $out[] = $cid;
    }
    sort($out);
    return $out;
}

// Text with parenthetical reminders, [bracketed] costs, recognized keyword tokens (and
// any trailing value number, e.g. "Raid 2") + punctuation removed. Empty residue ==
// keyword-only (e.g. a pure "Piloting [2 resources Command] (reminder)" unit).
function scaffold_text_residue(string $text, array $keywords): string {
    if (trim($text) === '') return '';
    $t = preg_replace('/\([^)]*\)/', ' ', $text);        // reminder text
    $t = preg_replace('/\[[^\]]*\]/', ' ', $t);          // [bracketed] costs (Piloting/Smuggle/…)
    foreach ($keywords as $k) {
        $t = preg_replace('/\b' . preg_quote($k, '/') . '\b(\s+\d+)?/i', ' ', $t);
    }
    $t = preg_replace('/[^A-Za-z0-9]+/', ' ', $t);        // separators/punctuation
    return trim($t);
}

function scaffold_has_trigger_stub(string $cid): bool {
    return HasWhenPlayedAbility($cid) || HasOnAttackAbility($cid) || HasWhenDefeatedAbility($cid)
        || HasOnDefenseAbility($cid) || HasOnAttackEndAbility($cid)
        || HasWhenPlayedAsUpgradeAbility($cid) || HasWhenPlayedUsingSmuggleAbility($cid);
}

// Rule A: Leader always; else a trigger stub, or text/deploy residue after keywords.
function scaffold_is_non_vanilla(string $cid, array $keywords): bool {
    global $typeData, $textData, $deployTextData;
    if (($typeData[$cid] ?? '') === 'Leader') return true;
    if (scaffold_has_trigger_stub($cid)) return true;
    $residue = scaffold_text_residue($textData[$cid] ?? '', $keywords)
             . scaffold_text_residue($deployTextData[$cid] ?? '', $keywords);
    return trim($residue) !== '';
}

function scaffold_stub_body(string $cid): string {
    $reprints = array_values(array_filter(SWUReprintGroup($cid), fn($p) => $p !== $cid));
    sort($reprints);
    return "<?php\n" . splitter_card_header($cid, $reprints) . "// TODO: UNIMPLEMENTED\n";
}

// Coverage oracle: every CardID referenced as a QUOTED string anywhere under Custom/
// (per-card files, the monoliths, AND engine files — GameLogic passives / cost-modifiers,
// CombatLogic reactive triggers, KeywordEffects grants). A card handled by ANY of these is
// "covered"; a genuinely-unimplemented card is referenced nowhere. Quotes are required so
// unquoted `// SOR_033`-style header/dev comments don't count on their own.
function scaffold_covered_cids(string $customRoot): array {
    $covered = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($customRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $f) {
        if ($f->getExtension() !== 'php') continue;
        $src = @file_get_contents($f->getPathname());
        if ($src === false) continue;
        // DATA files under Custom/ list CardIDs without implementing anything (CardMocks.php,
        // CardTraitSupplement.php). Counting them as coverage silently suppresses every stub for a
        // mocked set — scaffolding HMW proposed 0 files while Tarkin and Carbonite Chamber sat
        // unimplemented. They opt out with the marker below; add it to any new data file.
        if (strpos($src, 'SCAFFOLD-IGNORE') !== false) continue;
        if (preg_match_all('/[\'"]([A-Z][A-Z0-9]{1,4}_(?:T\d\d|\d{2,3}))/', $src, $m)) {
            foreach ($m[1] as $cid) $covered[$cid] = true;   // matches "CID", "CID:0", "CID#1", "CID-3-3"
        }
    }
    return $covered;
}

// Pure planner: what WOULD be created, and any filename collisions. Writes nothing.
function scaffold_plan(string $set, array $keywords, array $covered, string $cardsDir): array {
    $create = [];       // cid => relative path
    $collisions = [];   // [cid, relpath] where the target file exists for a DIFFERENT card
    $claimed = [];      // relpath => cid, to detect two same-name cards in one run
    foreach (scaffold_set_card_ids($set) as $cid) {
        if (preg_match('/_T\d\d$/', $cid)) continue;             // engine tokens (Shield/Experience/unit tokens) are generically handled — never per-card files
        if (CardIDOverride($cid) !== $cid) continue;             // reprints fold into the canonical file
        if (!scaffold_is_non_vanilla($cid, $keywords)) continue;
        if (isset($covered[$cid])) continue;                     // already handled somewhere under Custom/
        $base = splitter_card_basename($cid);
        $rel  = strtolower($set) . "/$base.php";
        $abs  = "$cardsDir/$rel";
        if (is_file($abs) || isset($claimed[$rel])) { $collisions[] = [$cid, $rel]; continue; }
        $create[$cid] = $rel;
        $claimed[$rel] = $cid;
    }
    return ['create' => $create, 'collisions' => $collisions];
}

// --- main (only on direct CLI invocation) ---
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $set = strtoupper($argv[1] ?? '');
    $dry = in_array('--dry', $argv, true);
    if ($set === '') { fwrite(STDERR, "usage: scaffold-cards.php <SET> [--dry]\n"); exit(2); }
    $cardsDir   = __DIR__ . '/../Custom/cards';
    $customRoot = __DIR__ . '/../Custom';
    $keywords = scaffold_keyword_names(__DIR__ . '/../GeneratedCode/GeneratedKeywordCode.php');
    $covered  = scaffold_covered_cids($customRoot);
    $plan     = scaffold_plan($set, $keywords, $covered, $cardsDir);

    echo "set $set: " . count($plan['create']) . " stub(s) to create, "
       . count($plan['collisions']) . " collision(s)\n";
    foreach ($plan['create'] as $cid => $rel) echo "  + $cid  ->  $rel\n";
    foreach ($plan['collisions'] as [$cid, $rel]) fwrite(STDERR, "  ! collision: $cid wants $rel (exists / claimed) — name manually\n");

    if ($dry) { echo "(dry run — nothing written)\n"; exit(0); }

    foreach ($plan['create'] as $cid => $rel) {
        $abs = "$cardsDir/$rel";
        @mkdir(dirname($abs), 0777, true);
        file_put_contents($abs, scaffold_stub_body($cid));   // additive: plan already excluded existing files
    }
    // Refresh the index from actual headers (now including the new stubs).
    SWUSimWriteCardIndex($cardsDir, SWUSimBuildCardIndex($cardsDir));
    echo "created " . count($plan['create']) . " stub(s); index refreshed\n";
}
