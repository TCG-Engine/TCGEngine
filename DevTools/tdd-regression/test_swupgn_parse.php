<?php
// SWU-PGN/1.0 spec §4 / §5: file shape, header tags, section banners, STORY verbatim, the four
// documented parse errors (and nothing else ever thrown).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_parse.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$hdr = SwuPgnTestHeaderText(SwuPgnTestHeaderTags());
$ev = '{"seq":"R1.A.1","t":"PASS","p":1}';

// ---- header tags (§4 "The header lines") --------------------------------------------------------
$doc = SwuPgnParse("[Format \"Premier\"] [CardPool \"SOR,JTL\"]\n" . $hdr . "[Note \"say \\\"hi\\\" \\\\ ok\"]\n%%% EVENTS\n$ev\n");
SwuPgnTestEq($doc['headers']['Format'] ?? null, 'Premier', '§4: several tags on one line — first one read');
SwuPgnTestEq($doc['headers']['CardPool'] ?? null, 'SOR', '§4: same tag twice — the LAST one wins');
SwuPgnTestEq($doc['headers']['Note'] ?? null, 'say "hi" \\ ok', '§4: backslash takes the next character literally (\\" and \\\\)');
$doc = SwuPgnParse(str_replace('[Result "Incomplete"]', "[Result \"Incomplete\"]\n[result \"P1\"]", $hdr) . "%%% EVENTS\n");
SwuPgnTestEq([$doc['headers']['Result'], $doc['headers']['result']], ['Incomplete', 'P1'], '§4: tag names are case-sensitive (result ≠ Result)');
$doc = SwuPgnParse($hdr . "[Weird]\n[X \"unterminated\n%%% EVENTS\n");
SwuPgnTestCheck(!isset($doc['headers']['Weird']) && !isset($doc['headers']['X']), '§4: a [ line with no well-formed tag yields nothing (and does not throw)');
$doc = SwuPgnParse($hdr . "[Bogus1 \"x\"]\n%%% EVENTS\n");
SwuPgnTestEq($doc['headers']['Bogus1'] ?? null, 'x', '§5.2: unknown tags are accepted');

// ---- banners, sections, NDJSON ------------------------------------------------------------------
$doc = SwuPgnParse($hdr . "\n%%% events\n$ev\n\n\n%%%   Cards  \n{\"id\":\"SOR#108\",\"name\":\"Wampa\"}\n%%% EVENTS\n{\"seq\":\"R1.A.2\",\"t\":\"PASS\",\"p\":2}\n");
SwuPgnTestEq(count($doc['events']), 2, '§4: banner names are case-insensitive; a repeated banner appends; blank lines ignored');
SwuPgnTestEq($doc['cards'][0]['name'] ?? null, 'Wampa', '§4: sections may appear in any order');
SwuPgnTestEq($doc['sections'], ['EVENTS', 'CARDS', 'EVENTS'], 'doc lists banners in file order');
SwuPgnTestEq($doc['lines']['events'], [20, 26], 'records keep their 1-based line numbers');
SwuPgnTestCheck($doc['story'] === null, 'no STORY banner → story is null (absent, not empty)');

$doc = SwuPgnParse($hdr . "%%% EVENTS\n[1,2]\n\"str\"\n7\n{}\n");
SwuPgnTestEq(count($doc['events']), 4, '§4: a [ line inside a section is a record (reaches the JSON path for validate())');
SwuPgnTestCheck($doc['events'][0] === [1, 2] && $doc['events'][3] instanceof stdClass, 'non-object JSON is kept as-is; an empty object stays an object');

// CRLF tolerated, BOM tolerated.
$crlf = str_replace("\n", "\r\n", $hdr . "%%% EVENTS\n$ev\n");
$doc = SwuPgnParse("\xEF\xBB\xBF" . $crlf);
SwuPgnTestEq([$doc['headers']['Game'], $doc['events'][0]['t'] ?? null], ['SWU-PGN/1.0', 'PASS'], '§4/§19: \\r\\n line endings and a UTF-8 BOM are tolerated');

// ---- STORY verbatim -----------------------------------------------------------------------------
$story = ["", " ROUND 1    initiative: Player 1 ", "", "  1. Player 1 plays Wampa", "       ↳ {\"not\":\"json\"}", "[Tag \"not a header\"]", "   "];
$doc = SwuPgnParse($hdr . "%%% STORY\n" . implode("\r\n", $story) . "\n%%% EVENTS\n$ev\n");
SwuPgnTestEq($doc['story'], $story, '§4: STORY lines kept verbatim — blank lines, leading/trailing spaces, JSON-looking and [ lines; only the \\r is dropped');
SwuPgnTestEq(count($doc['events']), 1, 'STORY ends at the next banner');

// ---- the four documented errors (§4 "When to throw an error") -----------------------------------
function expectParseError(string $text, string $message, string $what): void
{
    try {
        SwuPgnParse($text);
        SwuPgnTestCheck(false, $what, 'no exception');
    } catch (SwuPgnParseException $e) {
        SwuPgnTestCheck($e->getMessage() === $message, $what, 'message was: ' . $e->getMessage());
    } catch (Throwable $e) {
        SwuPgnTestCheck(false, $what, 'wrong exception ' . get_class($e) . ': ' . $e->getMessage());
    }
}
$missing = SwuPgnTestHeaderText(SwuPgnTestHeaderTags(['Seed' => null]));
expectParseError($missing . "%%% EVENTS\n", 'SWU-PGN: missing required header tag [Seed]', '§4: missing required tag → "SWU-PGN: missing required header tag [Seed]"');
foreach (SwuPgnRequiredHeaderTags() as $tag) {
    $t = SwuPgnTestHeaderText(SwuPgnTestHeaderTags([$tag => null]));
    try { SwuPgnParse($t . "%%% EVENTS\n"); $ok = false; } catch (SwuPgnParseException $e) { $ok = $e->getMessage() === "SWU-PGN: missing required header tag [$tag]"; }
    SwuPgnTestCheck($ok, "§5.1: $tag is required");
}
SwuPgnTestEq(count(SwuPgnRequiredHeaderTags()), 17, '§5.1 lists 17 required tags');
expectParseError($hdr . "%%% EVENTS\n$ev\n{\"seq\":\"x\",\n", 'SWU-PGN: invalid JSON on line 20', '§4: bad JSON → "invalid JSON on line N" (1-based)');
expectParseError($hdr . "%%% NOTES\nhello\n", 'SWU-PGN: JSON record in unrecognized section on line 19', '§4: record under an unknown banner');
expectParseError($hdr . "%%%\n{}\n", 'SWU-PGN: JSON record in unrecognized section on line 19', '§4: record under a nameless banner');
expectParseError($hdr . "{\"seq\":\"R1.A.1\",\"t\":\"PASS\"}\n%%% EVENTS\n", 'SWU-PGN: record before any %%% section on line 18', '§4: JSON line before any banner');
expectParseError("\n\n$ev\n", 'SWU-PGN: record before any %%% section on line 3', '§4: record-before-section is a line error, reported before missing tags');
try { SwuPgnParse($hdr . "%%% EVENTS\n{bad\n"); } catch (SwuPgnParseException $e) { SwuPgnTestEq($e->lineNumber, 19, 'SwuPgnParseException carries the line number'); }
$r = SwuPgnTryParse($hdr . "%%% EVENTS\n{bad\n");
SwuPgnTestCheck($r['doc'] === null && $r['error'] === 'SWU-PGN: invalid JSON on line 19', 'SwuPgnTryParse returns the error instead of throwing');
$r = SwuPgnTryParse($hdr . "%%% EVENTS\n$ev\n");
SwuPgnTestCheck(is_array($r['doc']) && $r['error'] === null, 'SwuPgnTryParse returns the doc on success');
expectParseError($hdr . "%%% EVENTS\n" . str_repeat('[', 200) . str_repeat(']', 200) . "\n", 'SWU-PGN: invalid JSON on line 19', 'JSON nested beyond the reader\'s depth limit is reported as invalid JSON (no crash)');

// ---- untrusted input: nothing but SwuPgnParseException, ever -----------------------------------
mt_srand(1234);
$pieces = ["%%% EVENTS", "%%% STORY", "%%% cards", "%%% WHAT", "[Game \"SWU-PGN/1.0\"]", "{\"t\":\"MOVE\"}", "{", "}", "[", "\"", "\\", "\r", "\xff\xfe", "null", "{\"a\":{\"b\":[1,{}]}}", "", "  ", "%%%", "[A \"\\\"]"];
$other = 0;
for ($i = 0; $i < 400; $i++) {
    $lines = [];
    $n = mt_rand(0, 25);
    for ($j = 0; $j < $n; $j++) $lines[] = $pieces[mt_rand(0, count($pieces) - 1)];
    $text = (mt_rand(0, 1) ? $hdr : '') . implode("\n", $lines);
    try { SwuPgnParse($text); } catch (SwuPgnParseException $e) {
        if (!preg_match('/^SWU-PGN: (missing required header tag \[[A-Za-z0-9]+\]|invalid JSON on line \d+|JSON record in unrecognized section on line \d+|record before any %%% section on line \d+)$/', $e->getMessage())) $other++;
    } catch (Throwable $e) { $other++; echo "  unexpected: " . $e->getMessage() . "\n"; }
}
SwuPgnTestEq($other, 0, '§3 Folder: 400 random garbage files only ever raise the four documented parse errors');

// Section record limits bound memory: excess records are counted, not stored.
$big = $hdr . "%%% EVENTS\n" . str_repeat("$ev\n", SWUPGN_MAX_EVENTS + 5);
$doc = SwuPgnParse($big);
SwuPgnTestEq([count($doc['events']), $doc['dropped']['events'] ?? 0], [SWUPGN_MAX_EVENTS, 5], 'events beyond SWUPGN_MAX_EVENTS are dropped and counted');

SwuPgnTestFinish();
