<?php
// SWU-PGN/1.0 spec §4 / §5 / §23 / §24 / Appendix B: file shape, the strict header grammar, section banners,
// STORY verbatim, unknown sections skipped, and every FATAL code of §24 (nothing else ever thrown).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_parse.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

// 64-hex player ids (§5.1) so these files are also clean for the validator.
$hdrTags = SwuPgnTestHeaderTags(['P1Id' => 'sha256:' . str_repeat('a', 64), 'P2Id' => 'sha256:' . str_repeat('b', 64)]);
$hdr = SwuPgnTestHeaderText($hdrTags);          // 17 lines, Game first
$ev = '{"seq":"R1.A.1","t":"PASS","p":1}';

// Expects SwuPgnParse($text) to throw a SwuPgnParseException carrying §24 code $code (and $line when given).
function expectFatal(string $text, string $code, string $what, ?int $line = null, array $caps = []): void
{
    try {
        SwuPgnParse($text, $caps);
        SwuPgnTestCheck(false, "$what → $code", 'no exception');
    } catch (SwuPgnParseException $e) {
        $ok = $e->errorCode === $code && ($line === null || $e->lineNumber === $line) && strncmp($e->getMessage(), 'SWU-PGN: ', 9) === 0;
        SwuPgnTestCheck($ok, "$what → $code", "got {$e->errorCode} line " . var_export($e->lineNumber, true) . ': ' . $e->getMessage());
    } catch (Throwable $e) {
        SwuPgnTestCheck(false, "$what → $code", 'wrong exception ' . get_class($e) . ': ' . $e->getMessage());
    }
}

// ---- the first line: the Game tag, alone (§4, §19, §24 not-swupgn / unsupported-major) ------------------------
$doc = SwuPgnParse("\n  \n" . $hdr . "%%% EVENTS\n$ev\n");
SwuPgnTestEq($doc['headers']['Game'] ?? null, 'SWU-PGN/1.0', '§4: blank lines before the Game line are fine');
expectFatal('', 'not-swupgn', '§24: an empty file');
expectFatal("\n\n  \n", 'not-swupgn', '§24: a file of blank lines');
expectFatal("hello\n" . $hdr . "%%% EVENTS\n", 'not-swupgn', '§20 not-swupgn: text before the [Game] line', 1);
expectFatal("[Format \"Premier\"]\n" . $hdr . "%%% EVENTS\n", 'not-swupgn', '§4: another tag first is not SWU-PGN');
expectFatal(str_replace("[Game \"SWU-PGN/1.0\"]\n", "[Game \"SWU-PGN/1.0\"] [Format \"Premier\"]\n", $hdr) . "%%% EVENTS\n", 'not-swupgn', '§4: the Game tag must be ALONE on its line');
expectFatal(str_replace('SWU-PGN/1.0', 'SWU-PGN/one', $hdr) . "%%% EVENTS\n", 'not-swupgn', 'Appendix B: Game value is SWU-PGN/<digits>.<digits>');
expectFatal(str_replace('SWU-PGN/1.0', 'SWU-PGN/2.0', $hdr) . "%%% EVENTS\n", 'unsupported-major', '§20 unsupported-major: SWU-PGN/2.0', 1);
expectFatal(str_replace('SWU-PGN/1.0', 'SWU-PGN/0.9', $hdr) . "%%% EVENTS\n", 'unsupported-major', '§18: major 0 is refused too');
SwuPgnTestEq(SwuPgnParse(str_replace('SWU-PGN/1.0', 'SWU-PGN/1.7', $hdr) . "%%% EVENTS\n")['headers']['Game'], 'SWU-PGN/1.7', '§18: a higher minor parses');
$doc = SwuPgnParse("\xEF\xBB\xBF" . str_replace("\n", "\r\n", $hdr . "%%% EVENTS\n$ev"));
SwuPgnTestEq([$doc['headers']['Game'], $doc['events'][0]['t'] ?? null], ['SWU-PGN/1.0', 'PASS'], '[P-02]: a BOM is stripped, \\r\\n accepted, a missing final line feed accepted');

// ---- header lines (§4 "The header lines", Appendix B.1) ------------------------------------------------------
$doc = SwuPgnParse($hdr . "[Format \"Premier\"]\t [Note \"say \\\"hi\\\" \\\\ ok\"]\n[X1 \"\"]\n%%% EVENTS\n$ev\n");
SwuPgnTestEq([$doc['headers']['Format'] ?? null, $doc['headers']['Note'] ?? null, $doc['headers']['X1'] ?? null], ['Premier', 'say "hi" \\ ok', ''], '§4: several tags on a line; \\" and \\\\ are the escapes; an empty value');
$doc = SwuPgnParse($hdr . "[result \"P1\"]\n%%% EVENTS\n");
SwuPgnTestEq([$doc['headers']['Result'], $doc['headers']['result']], ['Incomplete', 'P1'], '§4: tag names are case-sensitive (result ≠ Result)');
SwuPgnTestEq(SwuPgnParse($hdr . "[Bogus1 \"x\"]\n%%% EVENTS\n")['headers']['Bogus1'] ?? null, 'x', '§5.2: unknown tags are accepted');
SwuPgnTestEq(SwuPgnParse($hdr . "%%% EVENTS\n")['headerLines']['Rounds'] ?? null, 17, 'each header tag keeps its 1-based line');
expectFatal($hdr . "[Format \"Premier\"] trailing\n%%% EVENTS\n", 'bad-header-line', '§4: other text on a header line', 18);
expectFatal($hdr . "[Weird]\n%%% EVENTS\n", 'bad-header-line', '§4: a tag with no value');
expectFatal($hdr . "[X \"unterminated\n%%% EVENTS\n", 'bad-header-line', '§4: a value cannot hold a line break');
expectFatal($hdr . "[Bad-Name \"x\"]\n%%% EVENTS\n", 'bad-header-line', '§4: tag names are ASCII letters and digits only');
expectFatal($hdr . "[X\"x\"]\n%%% EVENTS\n", 'bad-header-line', 'Appendix B: at least one space between name and value');
expectFatal($hdr . "[X \"a\tb\"]\n%%% EVENTS\n", 'bad-header-line', 'Appendix B: a control character in a value');
expectFatal($hdr . "[X \"\xff\"]\n%%% EVENTS\n", 'bad-header-line', 'a value that is not UTF-8');
expectFatal($hdr . "[[1,2]]\n%%% EVENTS\n", 'bad-header-line', 'a JSON array before the first banner is a broken header line');
expectFatal($hdr . "[Note \"a \\n b\"]\n%%% EVENTS\n", 'bad-escape', '§20 bad-escape: \\n is not an escape', 18);
expectFatal($hdr . "[Note \"trailing \\\"]\n%%% EVENTS\n", 'bad-header-line', 'an escaped closing quote leaves the value unterminated');
expectFatal($hdr . "[Format \"A\"]\n[Format \"B\"]\n%%% EVENTS\n", 'duplicate-tag', '§4: a tag appears twice', 19);
expectFatal($hdr . "[Game \"SWU-PGN/1.0\"]\n%%% EVENTS\n", 'duplicate-tag', '§4: a second Game tag is a repeat');
expectFatal($hdr . "[hasOwnProperty \"x\"]\n%%% EVENTS\n", 'reserved-key', '§20 reserved-tag-name: [hasOwnProperty "x"]', 18);
expectFatal($hdr . "[constructor \"x\"]\n%%% EVENTS\n", 'reserved-key', '§23: constructor as a tag name');
SwuPgnTestEq(count(SwuPgnReservedNames()), 13, '§23: thirteen reserved names');
expectFatal($hdr . "{\"seq\":\"R1.A.1\",\"t\":\"PASS\"}\n%%% EVENTS\n", 'record-outside-section', '§24: a record before the first banner', 18);
expectFatal($hdr . "hello\n%%% EVENTS\n", 'record-outside-section', '§24: any other text before the first banner');

// §5.1 required tags → missing-tag
foreach (SwuPgnRequiredHeaderTags() as $tag) {
    if ($tag === 'Game') continue;    // a missing Game line is not-swupgn
    expectFatal(SwuPgnTestHeaderText(SwuPgnTestHeaderTags([$tag => null])) . "%%% EVENTS\n", 'missing-tag', "§5.1: $tag is required");
}
SwuPgnTestEq(count(SwuPgnRequiredHeaderTags()), 17, '§5.1 lists 17 required tags');
try { SwuPgnParse(SwuPgnTestHeaderText(SwuPgnTestHeaderTags(['Seed' => null])) . "%%% EVENTS\n"); } catch (SwuPgnParseException $e) {
    SwuPgnTestEq($e->getMessage(), 'SWU-PGN: missing required header tag [Seed]', 'missing-tag keeps its message');
}
expectFatal(SwuPgnTestHeaderText(SwuPgnTestHeaderTags(['Rounds' => null])), 'missing-tag', 'a header-only file is checked for its tags at the end');

// §5.2 count tags → bad-count
expectFatal(str_replace('[Rounds "1"]', '[Rounds "two"]', $hdr) . "%%% EVENTS\n", 'bad-count', '§20 rounds-not-digits', 17);
expectFatal($hdr . "[RecorderErrors \"none\"]\n%%% EVENTS\n", 'bad-count', '§20 bad-recorder-errors');
expectFatal($hdr . "[Undos \"-1\"]\n%%% EVENTS\n", 'bad-count', '§5.2: Undos has no sign');
expectFatal($hdr . "[WriterRevision \"1.5\"]\n%%% EVENTS\n", 'bad-count', '§5.2: WriterRevision is digits');
expectFatal($hdr . "[GameNumber \"9007199254740992\"]\n%%% EVENTS\n", 'bad-count', '§5.2: a count above 2^53−1');
expectFatal($hdr . "[Undos \" 1\"]\n%%% EVENTS\n", 'bad-count', '§5: no space in a count');
SwuPgnTestEq(SwuPgnParse($hdr . "[GameNumber \"9007199254740991\"] [Undos \"0\"] [RecorderErrors \"007\"]\n%%% EVENTS\n")['headers']['GameNumber'], '9007199254740991', '§5.2: 2^53−1, 0 and leading zeros are counts');
SwuPgnTestEq(SwuPgnParse($hdr . "[Format \"two\"]\n%%% EVENTS\n")['headers']['Format'], 'two', 'a non-count tag holds anything');

// ---- banners and sections ------------------------------------------------------------------------------------
$doc = SwuPgnParse($hdr . "\n%%% events\n$ev\n\n\n  %%%   Cards  \n{\"id\":\"SOR#108\",\"name\":\"Wampa\"}\n");
SwuPgnTestEq(count($doc['events']), 1, '§4: banner names are case-insensitive; blank lines ignored');
SwuPgnTestEq($doc['cards'][0]['name'] ?? null, 'Wampa', '§4: sections may appear in any order');
SwuPgnTestEq($doc['sections'], ['EVENTS', 'CARDS'], 'doc lists banners in file order');
SwuPgnTestEq($doc['lines']['events'], [20], 'records keep their 1-based line numbers');
SwuPgnTestCheck($doc['story'] === null && $doc['dropped'] === [], 'no STORY banner → story is null; nothing is ever dropped');
expectFatal($hdr . "%%% EVENTS\n$ev\n%%% events\n", 'duplicate-section', '§20 duplicate-section: two EVENTS banners', 20);
expectFatal($hdr . "%%% X_MINE\nfoo\n%%% x_mine\n", 'duplicate-section', '§4: every name, unknown ones too, at most once');

// §18 / [P-03]: an unknown section is skipped without parsing (its lines need not be JSON).
$doc = SwuPgnParse($hdr . "%%% NOTES\nhello {not json\n[Tag \"x\"]\n{\"__proto__\":1}\n%%% EVENTS\n$ev\n");
SwuPgnTestEq([$doc['sections'], count($doc['events'])], [['NOTES', 'EVENTS'], 1], '[P-03]: unknown section lines are skipped unparsed, reserved keys and all');
$doc = SwuPgnParse($hdr . "%%% FUTURE\n{\"a\":1}\n");
SwuPgnTestEq($doc['sections'], ['FUTURE'], '§4: a JSON line under an unknown banner is not an error');

// §4: inside a section a `[` line is a record (the JSON path); non-objects are kept for the validator.
$doc = SwuPgnParse($hdr . "%%% EVENTS\n[1,2]\n\"str\"\n7\n{}\n{\"0\":\"x\"}\n");
SwuPgnTestEq(count($doc['events']), 5, '§4: JSON that is not an object still parses');
SwuPgnTestCheck($doc['events'][0] === [1, 2] && $doc['events'][3] instanceof stdClass, 'non-object JSON is kept as-is; an empty object stays an object');
SwuPgnTestEq(array_keys($doc['notObject']['events'] ?? []), [0, 1, 2], 'the parser marks which records were not JSON objects ({"0":…} is an object, so not marked)');

// I-JSON numbers: an integral float is the integer (as in every JSON reader), a fraction stays a float.
$doc = SwuPgnParse($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"PASS\",\"p\":1.0,\"x\":1.5,\"y\":1e2}\n");
SwuPgnTestCheck($doc['events'][0]['p'] === 1 && $doc['events'][0]['x'] === 1.5 && $doc['events'][0]['y'] === 100, '1.0 reads as 1 and 1e2 as 100; 1.5 stays a fraction (number-range is the validator\'s)');

// ---- STORY verbatim (§4, [P-04]) -----------------------------------------------------------------------------
$story = ["", "  ", " ROUND 1    initiative: Player 1 ", "", "  1. Player 1 plays Wampa", "       ↳ {\"not\":\"json\"}", "[Tag \"not a header\"]", "{\"__proto__\":1}", "", "   "];
$doc = SwuPgnParse($hdr . "%%% STORY\n" . implode("\r\n", $story) . "\n%%% EVENTS\n$ev\n");
SwuPgnTestEq($doc['story'], array_slice($story, 2, 6), '§4: STORY kept verbatim (inner blank lines, spaces, JSON- and [-looking lines); only the blank lines at its very start and end are dropped');
SwuPgnTestEq(count($doc['events']), 1, 'STORY ends at the next banner');
SwuPgnTestEq(SwuPgnParse($hdr . "%%% STORY\n\n\n")['story'], [], 'an all-blank STORY is an empty list (present)');

// ---- JSON lines: bad-json, duplicate-key, reserved-key, too-deep ---------------------------------------------
expectFatal($hdr . "%%% EVENTS\n$ev\n{\"seq\":\"x\",\n", 'bad-json', '§24 bad-json', 20);
try { SwuPgnParse($hdr . "%%% EVENTS\n{bad\n"); } catch (SwuPgnParseException $e) { SwuPgnTestEq($e->getMessage(), 'SWU-PGN: invalid JSON on line 19', 'bad-json keeps its message and line'); }
expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"PASS\",\"p\":1,\"p\":2}\n", 'duplicate-key', '§20 duplicate-key: a PASS with two p keys', 19);
expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"X\",\"v\":[{\"a\":1},{\"b\":{\"c\":1,\"c\":1}}]}\n", 'duplicate-key', '[P-07]: a repeat deep inside');
expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"PASS\",\"p\":1,\"\\u0070\":2}\n", 'duplicate-key', 'keys compare after unescaping (\\u0070 is p)');
SwuPgnTestEq(count(SwuPgnParse($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"X\",\"a\":{\"k\":1},\"b\":{\"k\":1},\"s\":\"{\\\"k\\\":1,\\\"k\\\":2}\"}\n")['events']), 1, 'the same key in two objects, or inside a string, is no repeat');
foreach (SwuPgnReservedNames() as $name) {
    expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"PASS\",\"p\":1,\"x_ext\":{\"$name\":1}}\n", 'reserved-key', "§23: $name as a nested key");
}
expectFatal($hdr . "%%% DECKS\n{\"p\":1,\"x_more\":{\"__proto__\":{}}}\n", 'reserved-key', '§20 deck-reserved-key: __proto__ in a DECKS extension field', 19);
expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.start\",\"t\":\"ROUND_START\",\"round\":1,\"active\":1,\"keyframe\":{\"players\":{\"1\":{\"cards\":[{\"statusTokens\":{\"__proto__\":1}}]}}}}\n", 'reserved-key', '§20 proto-token: __proto__ as a keyframe status token');
expectFatal($hdr . "%%% ANNOTATIONS\n{\"ref\":\"R1.A.1\",\"text\":\"x\",\"toString\":1}\n", 'reserved-key', '§23: annotations too');
expectFatal($hdr . "%%% EVENTS\n{\"seq\":\"R1.A.1\",\"t\":\"STATUS_TOKEN\",\"card\":\"SOR#108\",\"token\":\"toString\",\"count\":1}\n", 'reserved-key', '§20 reserved-token-name: STATUS_TOKEN token toString');
expectFatal($hdr . "%%% SETUP\n{\"seq\":\"R0.S.1\",\"t\":\"STATUS_TOKEN\",\"card\":\"SOR#108\",\"token\":\"prototype\",\"count\":1}\n", 'reserved-key', '§23: in SETUP as well');
expectFatal($hdr . "%%% ANNOTATIONS\n{\"ref\":\"R1.A.1\",\"line\":[{\"seq\":\"R1.A.2\",\"t\":\"STATUS_TOKEN\",\"card\":\"SOR#108\",\"token\":\"valueOf\",\"count\":1}]}\n", 'reserved-key', '§20 nested-reserved-token: inside an annotation line');
SwuPgnTestEq(count(SwuPgnParse($hdr . "%%% ANNOTATIONS\n{\"ref\":\"R1.A.1\",\"text\":\"toString\",\"by\":\"__proto__\"}\n%%% CARDS\n{\"id\":\"SOR#1\",\"name\":\"constructor\"}\n")['annotations']), 1, '§23: a reserved name as a VALUE is fine');
expectFatal($hdr . "%%% EVENTS\n" . str_repeat('[', 65) . str_repeat(']', 65) . "\n", 'too-deep', '§23: deeper than 64', 19);
SwuPgnTestEq(count(SwuPgnParse($hdr . "%%% EVENTS\n" . str_repeat('[', 64) . str_repeat(']', 64) . "\n")['events']), 1, '§23: exactly 64 deep is fine');
expectFatal($hdr . "%%% EVENTS\n" . str_repeat('[', 5000) . "\n", 'too-deep', 'a hostile 5000-deep line is refused, no crash');

// ---- caps reject, never truncate ([P-05], §23) -----------------------------------------------------------------
$caps = SwuPgnParseCaps();
SwuPgnTestEq([$caps['bytes'], $caps['line'], $caps['records'], $caps['depth']], [16777216, 1048576, 1000000, 64], '§23: default caps 16 MiB, 1 MiB per line, 1,000,000 records, depth 64');
expectFatal($hdr . "%%% EVENTS\n" . str_repeat("$ev\n", 6), 'too-many-records', '[P-05]: one record over the cap rejects the file', 24, ['records' => 5]);
SwuPgnTestEq(count(SwuPgnParse($hdr . "%%% EVENTS\n" . str_repeat("$ev\n", 5), ['records' => 5])['events']), 5, 'exactly at the record cap parses');
expectFatal($hdr . "%%% STORY\n" . str_repeat('x', 101) . "\n", 'line-too-long', '[P-05]: a line over the cap (STORY lines too)', 19, ['line' => 100]);
expectFatal($hdr . "%%% EVENTS\n$ev\n", 'too-large', '[P-05]: a file over the size cap', null, ['bytes' => 100]);
expectFatal($hdr . "%%% EVENTS\n{\"a\":[[1]]}\n", 'too-deep', 'a lower depth cap', 19, ['depth' => 2]);

// ---- SwuPgnTryParse ------------------------------------------------------------------------------------------
$r = SwuPgnTryParse($hdr . "%%% EVENTS\n{bad\n");
SwuPgnTestCheck($r['doc'] === null && $r['error'] === 'SWU-PGN: invalid JSON on line 19' && $r['code'] === 'bad-json', 'SwuPgnTryParse returns error + code instead of throwing');
$r = SwuPgnTryParse($hdr . "%%% EVENTS\n$ev\n");
SwuPgnTestCheck(is_array($r['doc']) && $r['error'] === null && $r['code'] === null, 'SwuPgnTryParse returns the doc on success');

// ---- untrusted input: nothing but a fatal SwuPgnParseException, ever -----------------------------------------
$fatal = ['not-swupgn', 'unsupported-major', 'too-large', 'line-too-long', 'too-many-records', 'too-deep', 'bad-header-line', 'bad-escape',
    'duplicate-tag', 'missing-tag', 'bad-count', 'duplicate-section', 'record-outside-section', 'bad-json', 'duplicate-key', 'reserved-key'];
mt_srand(1234);
$pieces = ["%%% EVENTS", "%%% STORY", "%%% cards", "%%% WHAT", "[Game \"SWU-PGN/1.0\"]", "{\"t\":\"MOVE\"}", "{", "}", "[", "\"", "\\", "\r", "\xff\xfe",
    "null", "{\"a\":{\"b\":[1,{}]}}", "", "  ", "%%%", "[A \"\\\"]", "{\"a\":1,\"a\":2}", "{\"__proto__\":1}", "[Rounds \"x\"]", "\xEF\xBB\xBF"];
$other = [];
for ($i = 0; $i < 400; $i++) {
    $lines = [];
    $n = mt_rand(0, 25);
    for ($j = 0; $j < $n; $j++) $lines[] = $pieces[mt_rand(0, count($pieces) - 1)];
    $text = (mt_rand(0, 1) ? $hdr : '') . implode("\n", $lines);
    try { SwuPgnParse($text); } catch (SwuPgnParseException $e) {
        if (!in_array($e->errorCode, $fatal, true)) $other[] = $e->errorCode;
    } catch (Throwable $e) { $other[] = get_class($e) . ': ' . $e->getMessage(); }
}
SwuPgnTestEq(array_slice(array_unique($other), 0, 5), [], '[P-01]: 400 random garbage files only ever raise a §24 fatal code');

SwuPgnTestFinish();
