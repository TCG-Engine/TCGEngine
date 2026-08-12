<?php
// TDD guard: 'undoKind' must survive the client → ProcessInput → EngineActionRunner hop.
// SubmitEngineInput appends params to the QUERY STRING and ProcessInput reads $_GET, but
// EngineActionRunner case 10004 originally read $_POST['undoKind'] — so Undo Phase was unreachable.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_undokind_param.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ProcessInput builds its options array from $_GET. Assert the source text wires undoKind from $_GET,
// and that EngineActionRunner reads it from $options first. A source-level assertion is the right shape
// here: the alternative is a full HTTP round-trip, which this harness cannot drive.
$pi = file_get_contents('./ProcessInput.php');
$check(strpos($pi, "'undoKind' => \$_GET[\"undoKind\"] ?? ''") !== false
    || strpos($pi, "'undoKind' => \$_GET['undoKind'] ?? ''") !== false,
    'ProcessInput passes undoKind from $_GET into the action options');

$ear = file_get_contents('./Core/EngineActionRunner.php');
$check(strpos($ear, "\$options['undoKind']") !== false,
    'EngineActionRunner case 10004 reads undoKind from $options');
$check(strpos($ear, "\$_POST['undoKind']") === false,
    'EngineActionRunner no longer reads undoKind from $_POST (never populated on a GET)');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
