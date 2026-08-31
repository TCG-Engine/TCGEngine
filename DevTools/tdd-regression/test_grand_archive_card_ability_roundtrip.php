<?php
// TDD guard for GrandArchiveSim card_abilities persistence (CardAbilityDB).
//
// A card ability saved through the card editor lives in the card_abilities table and is read back
// by zzGameCodeGenerator to emit GeneratedMacroCode.php. Any byte lost or coerced on that round trip
// (a mangled prereq, a dropped listener zone, a flipped implementation flag) silently changes what
// the generator emits — and because GeneratedCode is gitignored, nobody notices until a card misbehaves.
//
// This pins the fields that matter: ability_code, prereq_code, ability_type, listener_zones,
// is_implemented, update-in-place (no duplicate rows), and created_at ordering. Runs entirely inside
// a throwaway database so it never touches real card data.
//
//   php DevTools/tdd-regression/test_grand_archive_card_ability_roundtrip.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(dirname(dirname(__DIR__)));
include_once './CardEditor/Database/CardAbilityDB.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$hostname = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
$username = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$scratchDatabase = 'tcgengine_ga_ability_test_' . getmypid();

$server = mysqli_connect($hostname, $username, $password);
if (!$server) { echo "FAIL: could not reach MySQL at $hostname\n"; exit(1); }
mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
if (!mysqli_query($server, "CREATE DATABASE `$scratchDatabase`")) {
    echo 'FAIL: could not create scratch database: ' . mysqli_error($server) . "\n";
    exit(1);
}

const ROOT = 'GrandArchiveSim';
const CARD = 'ZZZTEST';

try {
    $conn = mysqli_connect($hostname, $username, $password, $scratchDatabase);
    $db = new CardAbilityDB($conn);   // constructor runs EnsureSchema -> creates card_abilities

    // ── insert: a macro ability with byte-hostile content ───────────────────
    $codeA = "// marker \"quoted\" 'single'\n\$local = 1;\n\$this->Field[0]->Damage += \$local;\necho \"\u{00FC}nic\u{00F4}de \u{2014} dash\";\nreturn \$local;";
    $prereqA = "if (\$player !== 1) { return false; }\nreturn true;";
    $idA = $db->saveAbility(null, ROOT, CARD, 'Enter', $codeA, $prereqA, 'Enters Firing', 1, 'macro', 'Field,Garden');
    $check(is_numeric($idA) && (int)$idA > 0, 'saveAbility inserts a macro ability and returns its id');

    // ── insert: a listener ability ──────────────────────────────────────────
    $codeB = "// GA listener marker\nSetFlashMessage('heard ally die');\nreturn;";
    $idB = $db->saveAbility(null, ROOT, CARD, 'AllyDestroyed', $codeB, null, 'Vigilant', 0, 'listener', 'Field,Alley');
    $check(is_numeric($idB) && (int)$idB > 0, 'saveAbility inserts a listener ability and returns its id');

    // ── load back, verify byte-fidelity field by field ──────────────────────
    $rows = $db->loadCardAbilities(ROOT, CARD);
    $check(count($rows) === 2, 'loadCardAbilities returns both abilities (got ' . count($rows) . ')');
    $byMacro = [];
    foreach ($rows as $row) $byMacro[$row['macro_name']] = $row;

    $a = $byMacro['Enter'] ?? null;
    $check($a !== null, 'macro ability loads by macro name');
    if ($a !== null) {
        $check($a['ability_code'] === $codeA, 'ability_code round-trips byte-identically');
        $check($a['prereq_code'] === $prereqA, 'prereq_code round-trips byte-identically');
        $check($a['ability_type'] === 'macro', "ability_type is 'macro' (got '{$a['ability_type']}')");
        $check($a['listener_zones'] === null || $a['listener_zones'] === '', 'macro ability stores no listener_zones (saveAbility nulls it)');
        $check((int)$a['is_implemented'] === 1, 'is_implemented flag round-trips (1)');
    }

    $b = $byMacro['AllyDestroyed'] ?? null;
    $check($b !== null, 'listener ability loads by macro name');
    if ($b !== null) {
        $check($b['ability_code'] === $codeB, 'listener ability_code round-trips byte-identically');
        $check($b['ability_type'] === 'listener', "listener ability_type is 'listener' (got '{$b['ability_type']}')");
        $check($b['listener_zones'] === 'Field,Alley', "listener_zones round-trips (got '{$b['listener_zones']}')");
        $check((int)$b['is_implemented'] === 0, 'listener is_implemented flag round-trips (0)');
    }

    // ── update-in-place: no duplicate rows, code replaced ───────────────────
    $codeA2 = "// updated body\nreturn 2;";
    $updatedId = $db->saveAbility($idA, ROOT, CARD, 'Enter', $codeA2, $prereqA, 'Enters Firing', 1, 'macro', null);
    $check((int)$updatedId === (int)$idA, 'saveAbility updates an existing row in place (same id returned)');
    $rows = $db->loadCardAbilities(ROOT, CARD);
    $check(count($rows) === 2, 'update does not create a duplicate row (still 2)');
    $again = null;
    foreach ($rows as $row) if ((int)$row['id'] === (int)$idA) $again = $row;
    $check($again !== null && $again['ability_code'] === $codeA2, 'updated ability_code round-trips');

    // ── ordering: loadCardAbilities orders by created_at ASC ────────────────
    mysqli_query($conn, "UPDATE card_abilities SET created_at = '2020-01-01 00:00:00' WHERE id = " . (int)$idA);
    mysqli_query($conn, "UPDATE card_abilities SET created_at = '2021-01-01 00:00:00' WHERE id = " . (int)$idB);
    $ordered = $db->loadCardAbilities(ROOT, CARD);
    $check(
        count($ordered) === 2 && (int)$ordered[0]['id'] === (int)$idA && (int)$ordered[1]['id'] === (int)$idB,
        'loadCardAbilities returns rows ordered by created_at ASC'
    );

    // ── delete ──────────────────────────────────────────────────────────────
    $check($db->cardHasAbilities(ROOT, CARD) === true, 'cardHasAbilities true while rows exist');
    $check($db->deleteAbility($idA, ROOT, CARD) === true, 'deleteAbility removes a row');
    $check($db->cardHasAbilities(ROOT, CARD) === true, 'cardHasAbilities true with one row remaining');
    $db->deleteAbility($idB, ROOT, CARD);
    $check($db->cardHasAbilities(ROOT, CARD) === false, 'cardHasAbilities false after deleting all rows');

    mysqli_close($conn);
} finally {
    mysqli_query($server, "DROP DATABASE IF EXISTS `$scratchDatabase`");
    mysqli_close($server);
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
