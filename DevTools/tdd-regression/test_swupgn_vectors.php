<?php
// SWU-PGN reader conformance. Every fixture in fixtures/swupgn/ is read through the PHP reader
// (AppCore/SWU/SwuPgn/) and compared against the outputs of the format's reference reader, captured
// in <name>.expected.json: validate issues, the folded board, the rendered story, the keyframe gate's
// mismatches, and the board at EVERY seq (stateAt), as an md5 of canonical JSON.
//
// The five spec vectors (minimal/organic/upgrades/pilot/capture) are also checked against their
// normative <name>.fold.json and <name>.render.txt (spec §20). real-6r-undo and real-7r are full
// recorded games; real-7r and legacy-sample come from earlier writers, so their keyframe gate
// REPORTS mismatches and the reader must report exactly the same ones.
//
//   php DevTools/tdd-regression/test_swupgn_vectors.php
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_vectors.php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$names = array_map(fn($f) => basename($f, '.swupgn'), glob(SWUPGN_FIXTURES . '/*.swupgn'));
sort($names);
swupgnCheck(count($names) === 8, 'found all 8 fixtures (' . count($names) . ')');

foreach ($names as $name) {
  $text = swupgnFixture("$name.swupgn");
  $exp = json_decode(swupgnFixture("$name.expected.json"), true);
  $doc = SwuPgnParse($text);
  $events = $doc['events'];

  // validate: every fixture is issue-free under the reference reader.
  $report = SwuPgnValidate($text);
  swupgnCheck($report['issues'] === $exp['issues'] && $report['valid'] === true,
    "$name: validates with no issue at all" . ($report['issues'] ? ' — got ' . json_encode($report['issues']) : ''));

  // fold
  $fold = SwuPgnFold($events);
  swupgnCheck(swupgnCanonJson($fold) === swupgnCanonJson($exp['fold']), "$name: folds to the reference board");

  // render + embedded story
  $render = SwuPgnRender($doc);
  swupgnCheck($render === $exp['render'], "$name: renders the reference story byte for byte");
  $storyMatches = trim(implode("\n", $doc['story'])) === trim($render);
  swupgnCheck($storyMatches === $exp['storyMatchesRender'], "$name: embedded STORY " . ($exp['storyMatchesRender'] ? 'equals' : 'differs from') . ' the renderer, as the reference says');

  // keyframe gate
  $gate = SwuPgnCheckKeyframes($events);
  swupgnCheck(swupgnCanonJson($gate['mismatches']) === swupgnCanonJson($exp['mismatches']) && $gate['ok'] === (count($exp['mismatches']) === 0),
    "$name: keyframe gate reports the reference's " . count($exp['mismatches']) . ' mismatch(es)' . ($gate['mismatches'] !== $exp['mismatches'] ? ' — got ' . json_encode(array_slice($gate['mismatches'], 0, 3)) : ''));

  // stateAt at every seq
  $firstBad = null;
  foreach ($exp['states'] as $i => [$seq, $hash]) {
    if (md5(swupgnCanonJson(SwuPgnStateAt($events, $seq))) !== $hash) { $firstBad = "#$i $seq"; break; }
  }
  swupgnCheck($firstBad === null && count($exp['states']) === count($events),
    "$name: stateAt matches the reference at all " . count($events) . ' seqs' . ($firstBad ? " — first divergence at $firstBad" : ''));

  // The normative vector files themselves (spec §20).
  if (is_file(SWUPGN_FIXTURES . "/$name.fold.json")) {
    swupgnCheck(swupgnCanonJson($fold) === swupgnCanonJson(json_decode(swupgnFixture("$name.fold.json"), true)), "$name: fold equals the normative .fold.json");
    swupgnCheck(trim($render) === trim(swupgnFixture("$name.render.txt")), "$name: render equals the normative .render.txt");
  }
}

swupgnFinish();
