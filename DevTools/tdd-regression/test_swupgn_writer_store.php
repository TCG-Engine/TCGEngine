<?php
// SWU-PGN writer side folder (Games/<id>/Pgn/): meta, events log, state, checkpoints, health log.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_store.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';

$g = 'swupgnstore_' . getmypid();
$dir = SwuPgnRecDir($g);
SwuPgnTestCheck(str_ends_with($dir, "/SWUSim/Games/$g/Pgn"), 'the side folder is Games/<game>/Pgn', $dir);
SwuPgnTestEq(SwuPgnMetaRead($g), null, 'no meta before the recording starts');

$meta = ['recording' => true, 'startedAt' => '2026-10-22T00:00:00Z', 'format' => 'premier', 'cardPool' => ['SOR'],
    'matchId' => null, 'gameNumber' => null, 'seed' => 'abc', 'finished' => false, 'undos' => 0, 'recorderErrors' => 0,
    'accounts' => [1 => 5, 2 => null]];
SwuPgnMetaWrite($g, $meta);
SwuPgnTestEq(SwuPgnMetaRead($g), $meta, 'meta round-trips');

SwuPgnEventsAppend($g, [['seq' => 'R0.S.1', 't' => 'A'], ['seq' => 'R0.S.2', 't' => 'B']]);
SwuPgnEventsAppend($g, [['seq' => 'R0.S.3', 't' => 'C']]);
$ev = SwuPgnEventsRead($g);
SwuPgnTestEq(array_column($ev, 't'), ['A', 'B', 'C'], 'events append in order');
SwuPgnEventsTruncate($g, 1);
SwuPgnTestEq(array_column(SwuPgnEventsRead($g), 't'), ['A'], 'truncate keeps exactly the first N events');
SwuPgnEventsAppend($g, [['seq' => 'R0.S.2', 't' => 'D']]);
SwuPgnTestEq(array_column(SwuPgnEventsRead($g), 't'), ['A', 'D'], 'appending after a truncate continues from the cut');

// An empty JSON object inside a keyframe (a unit's statusTokens) must come back as an object, not [] — the reader's
// validator rejects a list there.
SwuPgnEventsTruncate($g, 0);
SwuPgnEventsAppend($g, [['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => ['players' => ['1' => ['cards' => [
    ['id' => 'SOR#95', 'statusTokens' => new stdClass()], ['id' => 'SOR#46', 'statusTokens' => ['advantage' => 1]]]]]]]]);
$back = SwuPgnEventsRead($g)[0]['keyframe']['players'][1]['cards'];
SwuPgnTestCheck($back[0]['statusTokens'] instanceof stdClass && json_encode($back[0]['statusTokens']) === '{}', 'an empty statusTokens reads back as {}');
SwuPgnTestEq($back[1]['statusTokens'], ['advantage' => 1], 'a non-empty one is unchanged');
SwuPgnEventsTruncate($g, 0);

SwuPgnTestEq(SwuPgnStateRead($g), [], 'no state yet → []');
$state = ['counters' => ['round' => 1], 'ledger' => ['x' => 1], 'prev' => ['seats' => []]];
SwuPgnStateWrite($g, $state);
SwuPgnTestEq(SwuPgnStateRead($g), $state, 'state round-trips');
file_put_contents("$dir/state.json", '{not json');
SwuPgnTestEq(SwuPgnStateRead($g), [], 'a corrupted state file reads as [] (no throw)');
file_put_contents("$dir/state.json", '5');
SwuPgnTestEq(SwuPgnStateRead($g), [], 'a state file holding a JSON scalar reads as [] (no type error)');

SwuPgnTestEq(SwuPgnCheckpointLoad($g, 'u3'), null, 'an unknown checkpoint is null');
SwuPgnCheckpointSave($g, 'u3', $state, 7, 'abc');
SwuPgnTestEq(SwuPgnCheckpointLoad($g, 'u3'), ['state' => $state, 'eventCount' => 7, 'prefixHash' => 'abc'], 'a checkpoint round-trips with its event count and the fingerprint of those events');
SwuPgnTestEq(SwuPgnCheckpointLoad($g, '../../x'), null, 'a checkpoint key cannot escape the folder');
SwuPgnCheckpointSave($g, '../escaped', $state, 1);
SwuPgnTestCheck(!is_file("$dir/escaped.json") && !is_file("$dir/checkpoints/../escaped.json"), 'saving with a traversal key writes nothing outside checkpoints/');

$health = sys_get_temp_dir() . '/swupgn-health-' . getmypid() . '.jsonl';
$GLOBALS['SWUPGN_HEALTH_FILE'] = $health;
SwuPgnHealthAppend(['game' => $g, 'ok' => true]);
SwuPgnHealthAppend(['game' => $g, 'ok' => false]);
$lines = file($health, FILE_IGNORE_NEW_LINES);
SwuPgnTestEq(count($lines), 2, 'one health line per append');
SwuPgnTestEq(json_decode($lines[1], true), ['game' => $g, 'ok' => false], 'each health line is one JSON object');
@unlink($health);

exec('rm -rf ' . escapeshellarg(dirname($dir)));
SwuPgnTestFinish();
