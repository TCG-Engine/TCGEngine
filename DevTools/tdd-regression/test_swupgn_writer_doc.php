<?php
// SWU-PGN document writer: doc → .swupgn text (SWU-PGN/1.0 spec §4), the inverse of SwuPgnParse.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_doc.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$doc = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/appendix-a.swupgn'));
$text = SwuPgnWriteText($doc);
$back = SwuPgnParse($text);
foreach (['headers', 'story', 'decks', 'cards', 'setup', 'events', 'annotations'] as $k) {
    SwuPgnTestEq($back[$k], $doc[$k], "Appendix A round-trips: $k");
}
SwuPgnTestEq($back['sections'], ['STORY', 'DECKS', 'CARDS', 'SETUP', 'EVENTS', 'ANNOTATIONS'], 'sections in the canonical order');
SwuPgnTestCheck(SwuPgnValidate($back)['errors'] === [], 'the written file validates');
SwuPgnTestEq(substr($text, -1), "\n", 'the file ends with a newline');

// Header values are escaped (§4: \" and \\).
$doc2 = $doc;
$doc2['headers']['Reason'] = 'He said "gg" \\o/';
$back2 = SwuPgnParse(SwuPgnWriteText($doc2));
SwuPgnTestEq($back2['headers']['Reason'], 'He said "gg" \\o/', 'a value with a quote and a backslash round-trips');

// Records are one JSON object per line; an empty object stays an object.
$doc3 = ['headers' => $doc['headers'], 'story' => null, 'decks' => [], 'cards' => [], 'setup' => [],
    'events' => [['seq' => 'R1.A.1', 't' => 'STATS', 'card' => 'SOR#108', 'power' => 1, 'hp' => 1, 'keywords' => [], 'x' => new stdClass()]]];
$t3 = SwuPgnWriteText($doc3);
SwuPgnTestCheck(str_contains($t3, '"x":{}') && !str_contains($t3, '%%% STORY'), 'an empty object is written {}, and no STORY banner when there is none');
SwuPgnTestCheck(str_contains($t3, '"card":"SOR#108"') && !str_contains($t3, '\\/'), 'slashes and unicode are not escaped');

SwuPgnTestFinish();
