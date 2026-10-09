<?php
// SWU-PGN reader: parse() and validate() (spec §4, §5, §18). Pure PHP, no engine.
//   php DevTools/tdd-regression/test_swupgn_parse_validate.php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$SAMPLE = implode("\n", [
  '[Game "SWU-PGN/1.0"]',
  '[GameId "g1"]',
  '[Date "2026-06-16T00:00:00Z"]',
  '[CardPool "LOF"] [Engine "writer@2.3.1"]',
  '[Seed "abc"] [Perspective "P1"]',
  '[P1Id "sha256:a"] [P2Id "sha256:b"] [P1 "Player 1"] [P2 "Player 2"]',
  '[P1Leader "SOR#010"] [P1Base "SOR#028"] [P2Leader "SOR#005"] [P2Base "SOR#020"]',
  '[Result "P1"] [Reason "BaseDestroyed"] [Rounds "4"]',
  '',
  '%%% DECKS',
  '{"p":1,"leader":"SOR#010","base":"SOR#028","deck":[["SOR#087",2]]}',
  '',
  '%%% SETUP',
  '{"seq":"R1.S.0","t":"INIT","p1DeckOrder":["SOR#087"],"p2DeckOrder":["SOR#045"]}',
  '',
  '%%% EVENTS',
  '{"seq":"R1.A.1","t":"PLAY","p":1,"card":"SOR#108","zone":"ground","cost":2}',
  '',
  '%%% ANNOTATIONS',
  '{"ref":"R1.A.1","nag":"?!","text":"too greedy"}',
]);

// ── parse ────────────────────────────────────────────────────────────────────
$doc = SwuPgnParse($SAMPLE);
swupgnCheck($doc['header']['game'] === 'SWU-PGN/1.0' && $doc['header']['rounds'] === 4
  && $doc['header']['perspective'] === 'P1' && $doc['header']['result'] === 'P1', 'parses header tags including multiple tags per line');
swupgnCheck(count($doc['decks']) === 1 && $doc['decks'][0]['deck'][0] === ['SOR#087', 2] && count($doc['setup']) === 1
  && count($doc['events']) === 1 && $doc['events'][0]['t'] === 'PLAY' && $doc['annotations'][0]['nag'] === '?!', 'parses each section into records');
swupgnCheck(count(SwuPgnParse($SAMPLE . "\n\n   \n")['events']) === 1, 'ignores blank lines and tolerates trailing whitespace');
swupgnCheck(count(SwuPgnParse(str_replace("\n", "\r\n", $SAMPLE))['events']) === 1, 'tolerates CRLF line endings (spec §4)');
swupgnCheck(SwuPgnParse("\u{FEFF}" . $SAMPLE)['header']['game'] === 'SWU-PGN/1.0', 'a leading byte-order mark does not hide the first header line');
swupgnCheck(SwuPgnParse(str_replace('[Reason "BaseDestroyed"]', '[Reason "said \\"hi\\" \\\\ bye"]', $SAMPLE))['header']['reason'] === 'said "hi" \\ bye',
  'a backslash in a header value takes the next character literally');
swupgnCheck(!array_key_exists('perspective', SwuPgnParse(str_replace(' [Perspective "P1"]', '', $SAMPLE))['header'])
  || SwuPgnParse(str_replace(' [Perspective "P1"]', '', $SAMPLE))['header']['perspective'] === null, 'an absent Perspective reads as null');

$HEAD = implode("\n", [
  '[Game "SWU-PGN/1.0"]', '[GameId "g1"]', '[Date "2026-06-16T00:00:00Z"]',
  '[CardPool "LOF"] [Engine "e"] [Seed "s"]',
  '[P1Id "a"] [P2Id "b"] [P1 "Player 1"] [P2 "Player 2"]',
  '[P1Leader "SOR#010"] [P1Base "SOR#028"] [P2Leader "SOR#005"] [P2Base "SOR#020"]',
  '[Result "P1"] [Reason "X"] [Rounds "1"]',
]);
swupgnCheck(swupgnThrows(fn() => SwuPgnParse(str_replace('[Result "P1"] ', '', $HEAD)), '/required header tag \[Result\]/'), 'throws on a missing required header tag');
swupgnCheck(swupgnThrows(fn() => SwuPgnParse($HEAD . "\n%%% EVENTS\n{not json}"), '/invalid JSON on line 9/'), 'throws with the line number on invalid JSON');
swupgnCheck(swupgnThrows(fn() => SwuPgnParse($HEAD . "\n" . '{"seq":"R1.A.1","t":"PASS","p":1}'), '/before any %%% section on line 8/'), 'throws when a record appears before any section');
$forward = SwuPgnParse($HEAD . "\n%%% BOGUS\n" . '{"x":1}' . "\n\n%%% EVENTS\n" . '{"seq":"R1.A.1","t":"PASS","p":1}');
swupgnCheck(array_column($forward['events'], 'seq') === ['R1.A.1'], 'ignores a record under an unrecognized section instead of throwing');

$header = fn(string $rounds) => implode("\n", [
  '[Game "SWU-PGN/1.0"] [GameId "g"] [Date "d"] [CardPool "SOR"] [Engine "e"] [Seed "s"]',
  '[P1Id "sha256:a"] [P2Id "sha256:b"] [P1 "Player 1"] [P2 "Player 2"]',
  '[P1Leader "SOR#010"] [P1Base "SOR#028"] [P2Leader "SOR#005"] [P2Base "SOR#020"]',
  "[Result \"P1\"] [Reason \"r\"] [Rounds \"$rounds\"]",
  '', '%%% EVENTS',
]);
swupgnCheck(SwuPgnParse($header('7'))['header']['rounds'] === 7, 'parses a numeric Rounds tag');
swupgnCheck(SwuPgnParse($header('seven'))['header']['rounds'] === 0, 'a non-numeric Rounds tag reads as 0, never NaN');
swupgnCheck(!array_key_exists('recorderErrors', SwuPgnParse($header('4'))['header']), 'RecorderErrors is absent when the tag is');
swupgnCheck(SwuPgnParse(str_replace('[Rounds "4"]', '[Rounds "4"] [RecorderErrors "2"]', $header('4')))['header']['recorderErrors'] === 2, 'reads RecorderErrors when present');
$dropped = true;
foreach (['garbage', '1e309', '-1', ''] as $bad) {
  if (array_key_exists('undos', SwuPgnParse(str_replace('[Rounds "4"]', "[Rounds \"4\"] [Undos \"$bad\"]", $header('4')))['header'])) $dropped = false;
}
swupgnCheck($dropped, 'drops a non-numeric count tag rather than reading it as zero');
swupgnCheck(SwuPgnParse(str_replace('[Rounds "4"]', '[Rounds "4"] [Undos "3"]', $header('4')))['header']['undos'] === 3, 'reads a numeric Undos tag');
swupgnCheck(count(SwuPgnParse($header('4') . "\n%%% EVENTS\n[\"not\",\"an\",\"event\"]\n")['events']) === 1, 'a [-prefixed record inside a JSON section reaches the record path, not the header');

$story = SwuPgnParse(swupgnFixture('minimal.swupgn'))['story'];
swupgnCheck($story[0] === ' ── setup ──' && in_array('', $story, true) && end($story) !== '', 'STORY keeps its prose verbatim, blank lines inside included, edges trimmed');

// ── validate ─────────────────────────────────────────────────────────────────
$good = swupgnFixture('minimal.swupgn');
$errors = fn(array $r) => array_values(array_filter($r['issues'], fn($i) => $i['severity'] === 'error'));
$warns  = fn(array $r) => array_values(array_filter($r['issues'], fn($i) => $i['severity'] === 'warning'));
$any = fn(array $issues, string $re) => count(array_filter($issues, fn($i) => preg_match($re, $i['message']))) > 0;
$withEvent = fn(string $line) => str_replace("%%% EVENTS\n", "%%% EVENTS\n$line\n", $good);

$r = SwuPgnValidate($good);
swupgnCheck($r['valid'] === true && $r['formatVersion'] === 'SWU-PGN/1.0' && $r['issues'] === [], 'accepts a conformant minimal file');

$r = SwuPgnValidate(str_replace('[Result "Incomplete"]', '', $good));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/Result/'), 'rejects a file missing a required header tag');

$ok = true;
foreach (['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect', 'unknown'] as $reason) {
  if ($errors(SwuPgnValidate($withEvent("{\"seq\":\"R1.A.9\",\"t\":\"DEFEAT\",\"card\":\"SOR#108\",\"reason\":\"$reason\"}"))) !== []) $ok = false;
}
$bad = SwuPgnValidate($withEvent('{"seq":"R1.A.9","t":"DEFEAT","card":"SOR#108","reason":"vaporised"}'));
swupgnCheck($ok && $bad['valid'] === false && $any($errors($bad), '/reason/'), 'accepts every DEFEAT reason in the closed set and rejects one outside it');

$ok = true;
foreach (['R1.A.2', 'R1.start'] as $at) {
  $r = SwuPgnValidate($withEvent("{\"seq\":\"$at-undo\",\"t\":\"UNDO\",\"at\":\"$at\",\"by\":2}"));
  if ($errors($r) !== [] || $any($r['issues'], '/UNDO/')) $ok = false;
}
swupgnCheck($ok, 'accepts an UNDO note, including one that reached back to a round boundary');

$ok = true;
foreach (['action', 'epic', 'triggered', 'keyword', 'replacement', 'constant', 'event', 'delayed'] as $kind) {
  if ($errors(SwuPgnValidate($withEvent("{\"seq\":\"R1.A.9\",\"t\":\"ABILITY_ACTIVATE\",\"p\":1,\"card\":\"SOR#010\",\"kind\":\"$kind\"}"))) !== []) $ok = false;
}
$bad = SwuPgnValidate($withEvent('{"seq":"R1.A.9","t":"ABILITY_ACTIVATE","p":1,"card":"SOR#010","kind":"mystical"}'));
swupgnCheck($ok && $bad['valid'] === false && $any($errors($bad), '/kind/'), 'accepts every ABILITY_ACTIVATE kind and rejects one outside the set');

$r = SwuPgnValidate(str_replace('%%% EVENTS', '%%% EVENT', $good));
swupgnCheck($errors($r) === [] && $any($warns($r), '/unknown section/') && $r['valid'] === true, 'warns on an unrecognized %%% banner without erroring');

$ok = true;
foreach (['null', '42', '"a string"', '[1,2,3]'] as $junk) {
  $r = null;
  if (!swupgnNoThrow(function () use (&$r, $withEvent, $junk) { $r = SwuPgnValidate($withEvent($junk)); }) || $r['valid'] !== false) $ok = false;
}
swupgnCheck($ok, 'reports a non-object record instead of crashing on it');

$r = SwuPgnValidate($withEvent('{"seq":"R1.A.9","t":"FUTURE_THING","p":1}'));
swupgnCheck($any($warns($r), '/FUTURE_THING/') && $r['valid'] === true, 'tolerates an unknown event type as a warning, not an error');

$r = SwuPgnValidate(str_replace('"deck":[["SOR#108",5]]}', '"deck":[["SOR#108",5]],"sideboard":[["SOR#099",2]]}', $good));
swupgnCheck($r['valid'] === true && $r['issues'] === [], 'accepts a deck that includes a sideboard');
$r = SwuPgnValidate(str_replace('"deck":[["SOR#108",5]]}', '"deck":[["SOR#108",5]],"extra":1}', $good));
swupgnCheck($r['valid'] === false, 'rejects a field a deck record does not define (DECKS is closed)');

$r = SwuPgnValidate(preg_replace('/\{"seq":"R1\.S\.0","t":"INIT"[^\n]*/', '{"seq":"NOT-A-SEQ","t":"INIT","p1DeckOrder":[],"p2DeckOrder":[]}', $good));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/^setup /'), 'flags a malformed seq in the SETUP section');

$annLine = '{"ref":"R2.A.1","nag":"?!","text":"attacking the base rather than developing the board"}';
swupgnCheck(str_contains($good, $annLine), 'fixture carries the annotation the next two checks extend');
$r = SwuPgnValidate(str_replace($annLine, substr($annLine, 0, -1) . ',"futureField":{"a":1}}', $good));
swupgnCheck($r['valid'] === true && $errors($r) === [], 'accepts an annotation carrying a field this version does not know');
$r = SwuPgnValidate(str_replace($annLine, substr($annLine, 0, -1) . ',"id":"n1","ts":1}' . "\n" . '{"ref":"R2.A.1","text":"disagree","by":"someone","id":"n2","parent":"n1","ts":2}', $good));
swupgnCheck($r['valid'] === true && $errors($r) === [], 'accepts a threaded annotation (spec §15 id/parent/ts)');

$badKeyframe = preg_replace('/"keyframe":\{.*\}\}\}/', '"keyframe":{"players":{"1":{"cards":"x","hand":[],"discard":[]}}}', $good, 1);
swupgnCheck($badKeyframe !== $good && SwuPgnValidate($badKeyframe)['valid'] === false, 'rejects a malformed keyframe (non-array cards)');
$r = SwuPgnValidate(str_replace('{"seq":"R1.A.1a","t":"EXHAUST","card":"SOR#108"}', '{"seq":"R1.A.1a","t":"EXHAUST","card":"SOR#108"}' . "\n" . '{"seq":"R1.A.1z","t":"DRAW","p":1,"count":1,"cards":5}', $good));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/R1\.A\.1z/'), 'rejects a DRAW whose cards is not an array, naming its seq');

$r = SwuPgnValidate(str_replace('{"seq":"R1.A.1a","t":"EXHAUST","card":"SOR#108"}', '{"seq":"R1.A.1a","t":"EXHAUST","card":"SOR#108"}' . "\n" . '{"seq":"R1.A.1y","t":"LEADER_FLIP","p":1,"card":"TWI#017","onStartingSide":false}', $good));
swupgnCheck($r['issues'] === [], 'recognises every event type a writer emits (no spurious forward-compat warning)');

$r = SwuPgnValidate(str_replace('[Game "SWU-PGN/1.0"]', '[Game "SWU-PGN/1.7"]', $good));
swupgnCheck($r['valid'] === true, 'accepts a higher minor version (spec §18)');
$r = SwuPgnValidate(str_replace('[Game "SWU-PGN/1.0"]', '[Game "SWU-PGN/2.0"]', $good));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/Game/'), 'rejects a different major version (spec §18)');
$r = SwuPgnValidate(str_replace('[Result "Incomplete"]', '[Result "Won"]', $good));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/Result/'), 'rejects a Result outside P1/P2/Draw/Incomplete');
$r = SwuPgnValidate($withEvent('{"seq":"R1.A.9","t":"EXHAUST_RESOURCES","p":3,"amount":-1}'));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/\/p /') && $any($r['issues'], '/\/amount /'), 'rejects a seat outside 1/2 and a negative resource amount');
$r = SwuPgnValidate($withEvent('{"seq":"R1.A.9","t":"MOVE","card":"X","from":"hand"}'));
swupgnCheck($r['valid'] === false && $any($r['issues'], '/to/'), 'rejects a MOVE missing its destination');

swupgnFinish();
