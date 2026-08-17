<?php

// A root that reflects ANOTHER root's card data must load that root's dictionary -- always, even if
// a dictionary file exists in its own GeneratedCode/.
//
// 2026-08-17, northbeach.gg: one bad `zzCardCodeGenerator.php?rootName=HellbreakDeck` run in August
// left a 5,445-byte EMPTY GeneratedCardDictionaries.php there (the real one is 239,743). The loader
// preferred it because it merely tested is_file(), so every CardType() returned '' for three days.
// GeneratedCode/ is gitignored, so no git pull could remove it, and regenerating ENGINE code did not
// touch it. The file PARSED, so nothing failed loudly -- Monster/Location silently became
// unselectable while main-deck cards still worked.
//
// These tests pin the resolution ORDER, which is the actual defect. The admin routes that wrote the
// file are separately guarded by name, but any other route (zzCardCodeGenerator3.php, a manual copy,
// a restored backup, an rsync) would reproduce it.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './Core/EngineActionRunner.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

$repoRoot = getcwd();

// ---------------------------------------------------------------------------
// Reflection root resolution, straight from the schema.
// ---------------------------------------------------------------------------
$check(EngineAssetReflectionRoot($repoRoot, 'HellbreakDeck') === 'HellbreakSim',
    'HellbreakDeck reflects HellbreakSim');
$check(EngineAssetReflectionRoot($repoRoot, 'AzukiDeck') === 'AzukiSim',
    'AzukiDeck reflects AzukiSim');
$check(EngineAssetReflectionRoot($repoRoot, 'SWUSim') === 'SWUSim',
    'a root that owns its card data reflects itself');
$check(EngineAssetReflectionRoot($repoRoot, 'NotARealRoot') === '',
    'an unknown root reflects nothing rather than guessing');

// ---------------------------------------------------------------------------
// Dictionary path: reflection decides it, NOT whether a local file happens to exist.
// ---------------------------------------------------------------------------
// This is the regression. The old code only consulted reflection when the local file was ABSENT,
// so a leftover file silently outranked the real data.
$deckDict = EngineDictionaryPath($repoRoot, 'HellbreakDeck');
$check($deckDict === $repoRoot . '/HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php',
    'HellbreakDeck resolves to HellbreakSim\'s dictionary');
$check(strpos($deckDict, '/HellbreakDeck/GeneratedCode/') === false,
    'HellbreakDeck NEVER resolves to a dictionary inside its own GeneratedCode/');

$simDict = EngineDictionaryPath($repoRoot, 'SWUSim');
$check($simDict === $repoRoot . '/SWUSim/GeneratedCode/GeneratedCardDictionaries.php',
    'a self-reflecting root still uses its own dictionary');

// ---------------------------------------------------------------------------
// Detection: a reflecting root must own no card-data artifacts at all.
// ---------------------------------------------------------------------------
// This is the check that would have caught the outage on the box, not just in code. Card art (.js
// twin) and the cache are part of the same bad-run wreckage, so all three are named.
$reflectingRoots = [];
foreach (glob('./Schemas/*', GLOB_ONLYDIR) as $schemaDir) {
    $root = basename($schemaDir);
    $reflection = EngineAssetReflectionRoot($repoRoot, $root);
    if ($reflection !== '' && $reflection !== $root) $reflectingRoots[] = $root;
}
$check(in_array('HellbreakDeck', $reflectingRoots, true),
    'the reflecting-root sweep actually finds HellbreakDeck (guards against an empty sweep passing)');

foreach ($reflectingRoots as $root) {
    foreach (['GeneratedCardDictionaries.php', 'cardArrayCache.json'] as $artifact) {
        $path = './' . $root . '/GeneratedCode/' . $artifact;
        $check(!is_file($path), "$root owns no $artifact (it reflects " . EngineAssetReflectionRoot($repoRoot, $root) . ')');
    }
    $strayJs = glob('./' . $root . '/GeneratedCode/GeneratedCardDictionaries*.js');
    $check($strayJs === [] || $strayJs === false,
        "$root owns no client dictionary .js (NextTurn globs these and takes [0])");
}

if($failures > 0) {
    fwrite(STDERR, PHP_EOL . "FAILED: {$failures} of {$checks} checks." . PHP_EOL);
    exit(1);
}
echo PHP_EOL . "ALL PASS ({$checks} checks)" . PHP_EOL;

?>
