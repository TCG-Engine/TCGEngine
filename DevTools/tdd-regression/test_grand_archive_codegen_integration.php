<?php
// TDD guard for the GrandArchiveSim codegen pipeline: seed an ability -> run the generator -> lint
// the emitted macro file -> verify the expected macro and listener symbols appear.
//
// This is the same command the MCP server runs after every save_card_abilities, so it is the real
// integration seam. It proves that a newly authored ability actually reaches GeneratedMacroCode.php
// as valid PHP, not just that it sat in the database.
//
// Side-effect safety: the generator's only DB-dependent outputs are GeneratedMacroCode.php and
// GeneratedMacroCount.js; every other file it writes is schema-derived and regenerates identically.
// This test points the generator at a throwaway database, backs up those two files, and restores
// them (plus deletes any new timestamped GeneratedUI_*.js) before it exits.
//
//   php DevTools/tdd-regression/test_grand_archive_codegen_integration.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(dirname(dirname(__DIR__)));
include_once './CardEditor/Database/CardAbilityDB.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$hostname = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$username = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$scratchDatabase = 'tcgengine_ga_codegen_test_' . getmypid();

const ROOT = 'GrandArchiveSim';
const CARD = 'ZZZTEST';
const MACRO_MARKER = 'GA_CODEGEN_MACRO_MARKER';
const LISTENER_MARKER = 'GA_CODEGEN_LISTENER_MARKER';

$server = mysqli_connect($hostname, $username, $password);
if (!$server) { echo "FAIL: could not reach MySQL at $hostname\n"; exit(1); }
mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
if (!mysqli_query($server, "CREATE DATABASE `$scratchDatabase`")) {
    echo 'FAIL: could not create scratch database: ' . mysqli_error($server) . "\n";
    exit(1);
}

$macroPath  = ROOT . '/GeneratedCode/GeneratedMacroCode.php';
$countPath  = ROOT . '/GeneratedCode/GeneratedMacroCount.js';
$macroBackup = sys_get_temp_dir() . '/ga-codegen-macro-' . getmypid() . '.bak';
$countBackup = sys_get_temp_dir() . '/ga-codegen-count-' . getmypid() . '.bak';
$beforeUI = glob(ROOT . '/GeneratedUI_*.js') ?: [];

$priorDbEnv = getenv('MYSQL_DATABASE_NAME');

try {
    // Seed a macro ability and a listener ability in the throwaway DB.
    $conn = mysqli_connect($hostname, $username, $password, $scratchDatabase);
    $db = new CardAbilityDB($conn);
    $idMacro = $db->saveAbility(
        null, ROOT, CARD, 'Enter',
        "// " . MACRO_MARKER . "\nSetFlashMessage('seeded enter');\nreturn;",
        null, 'Seeded Enter', 1, 'macro', null
    );
    $idListener = $db->saveAbility(
        null, ROOT, CARD, 'AllyDestroyed',
        "// " . LISTENER_MARKER . "\nreturn;",
        null, 'Seeded Listener', 1, 'listener', 'Field'
    );
    mysqli_close($conn);
    $check(is_numeric($idMacro) && (int)$idMacro > 0, 'seeded a macro ability');
    $check(is_numeric($idListener) && (int)$idListener > 0, 'seeded a listener ability');

    // Back up the two DB-dependent generated files.
    file_put_contents($macroBackup, @file_get_contents($macroPath));
    file_put_contents($countBackup, @file_get_contents($countPath));

    // Run the generator against the scratch DB.
    putenv('MYSQL_DATABASE_NAME=' . $scratchDatabase);
    $output = [];
    $returnCode = 0;
    exec('php zzGameCodeGenerator.php rootName=' . ROOT . ' 2>&1', $output, $returnCode);
    $combined = implode("\n", $output);

    $check($returnCode === 0, 'generator exits 0 (got ' . $returnCode . ')');
    $check(stripos($combined, 'Could not generate') === false, 'generator reports no "Could not generate"');
    $check(stripos($combined, 'Fatal error') === false && stripos($combined, 'Parse error') === false, 'generator output has no fatal/parse error');

    // Lint the emitted macro file.
    $lintOut = [];
    $lintCode = 0;
    exec('php -l "' . $macroPath . '" 2>&1', $lintOut, $lintCode);
    $check($lintCode === 0, 'php -l passes on the regenerated macro file (got: ' . trim(implode(' ', $lintOut)) . ')');

    // Verify the expected symbols appear.
    $generated = file_get_contents($macroPath);
    $check(strpos($generated, '$enterAbilities["' . CARD . ':0"]') !== false, 'macro symbol $enterAbilities["' . CARD . ':0"] emitted');
    $check(strpos($generated, MACRO_MARKER) !== false, 'macro body marker reached the generated file');
    $check(strpos($generated, '$macroListenerAbilities["AllyDestroyed"]["' . CARD . ':0"]') !== false, 'listener symbol $macroListenerAbilities["AllyDestroyed"]["' . CARD . ':0"] emitted');
    $check(strpos($generated, LISTENER_MARKER) !== false, 'listener body marker reached the generated file');
    $check(strpos($generated, '$macroListenerZones["AllyDestroyed"]["' . CARD . ':0"]') !== false, 'listener zones emitted');
} finally {
    // Restore the environment regardless of assertion results.
    if ($priorDbEnv === false) putenv('MYSQL_DATABASE_NAME');
    else putenv('MYSQL_DATABASE_NAME=' . $priorDbEnv);

    file_put_contents($macroPath, @file_get_contents($macroBackup));
    file_put_contents($countPath, @file_get_contents($countBackup));
    @unlink($macroBackup);
    @unlink($countBackup);

    $afterUI = glob(ROOT . '/GeneratedUI_*.js') ?: [];
    foreach ($afterUI as $f) {
        if (!in_array($f, $beforeUI, true)) @unlink($f);
    }

    mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
    mysqli_close($server);
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
