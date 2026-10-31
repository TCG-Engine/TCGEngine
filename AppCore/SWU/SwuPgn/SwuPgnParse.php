<?php
// SWU-PGN parser — text → doc (SWU-PGN/1.0 spec §4 file shape, §5 header tags, §23 limits, §24 fatal codes,
// Appendix B grammar).
//
// [P-01]: a well-formed file parses; a malformed one is REJECTED (nothing in it is read) with a SwuPgnParseException
// whose `errorCode` is the §24 fatal code and whose `lineNumber` is the 1-based line, when there is one. Everything that
// parses but is wrong (schemas, cross-record rules, records that are JSON but not objects, numbers out of range) is the
// validator's (SwuPgnValidate). [P-05]: a file over a cap is rejected, never truncated.

class SwuPgnParseException extends RuntimeException
{
    public ?int $lineNumber;
    public string $errorCode;     // §24: not-swupgn, unsupported-major, too-large, … reserved-key

    public function __construct(string $message, ?int $lineNumber = null, string $errorCode = 'bad-json')
    {
        parent::__construct($message);
        $this->lineNumber = $lineNumber;
        $this->errorCode = $errorCode;
    }
}

// §23: "Caps of 16 MiB, 1 MiB per line, 1,000,000 records and a depth of 64 are ample."
const SWUPGN_CAP_FILE_BYTES = 16777216;
const SWUPGN_CAP_LINE_BYTES = 1048576;
const SWUPGN_CAP_RECORDS = 1000000;
const SWUPGN_CAP_DEPTH = 64;

// [P-05] the reader's caps; a caller (e.g. an upload endpoint) may pass lower ones.
function SwuPgnParseCaps(array $over = []): array
{
    $caps = ['bytes' => SWUPGN_CAP_FILE_BYTES, 'line' => SWUPGN_CAP_LINE_BYTES, 'records' => SWUPGN_CAP_RECORDS, 'depth' => SWUPGN_CAP_DEPTH];
    foreach ($caps as $k => $v) if (is_int($over[$k] ?? null) && $over[$k] > 0) $caps[$k] = $over[$k];
    return $caps;
}

// §5.1, in the spec's order (the first missing one is the one reported).
function SwuPgnRequiredHeaderTags(): array
{
    return ['Game', 'GameId', 'Date', 'CardPool', 'Engine', 'Seed', 'P1Id', 'P2Id', 'P1', 'P2',
        'P1Leader', 'P1Base', 'P2Leader', 'P2Base', 'Result', 'Reason', 'Rounds'];
}

// §5.2: count tags — `Rounds` always, the rest when present (`bad-count`).
function SwuPgnCountTags(): array
{
    return ['Rounds', 'RecorderErrors', 'Undos', 'WriterRevision', 'GameNumber'];
}

// §4: the five NDJSON banners → doc key. STORY is the sixth, and is prose.
function _SwuPgnRecordSections(): array
{
    return ['DECKS' => 'decks', 'CARDS' => 'cards', 'SETUP' => 'setup', 'EVENTS' => 'events', 'ANNOTATIONS' => 'annotations'];
}

// §5: base-10 digits, no sign/space/exponent, within 2^53−1.
function SwuPgnIsCount($v): bool
{
    if (!is_string($v) || !preg_match('/^\d+\z/', $v)) return false;
    $d = ltrim($v, '0');
    return strlen($d) < 16 || (strlen($d) === 16 && strcmp($d, '9007199254740991') <= 0);
}

function SwuPgnTryParse(string $text, array $caps = []): array
{
    try {
        return ['doc' => SwuPgnParse($text, $caps), 'error' => null, 'code' => null];
    } catch (SwuPgnParseException $e) {
        return ['doc' => null, 'error' => $e->getMessage(), 'code' => $e->errorCode];
    }
}

function SwuPgnParse(string $text, array $caps = []): array
{
    $caps = SwuPgnParseCaps($caps);
    $fail = function (string $code, string $msg, ?int $line = null) {
        throw new SwuPgnParseException('SWU-PGN: ' . $msg . ($line !== null ? " on line $line" : ''), $line, $code);
    };
    if (strlen($text) > $caps['bytes']) $fail('too-large', "the file is larger than {$caps['bytes']} bytes");
    if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) $text = substr($text, 3);   // [P-02] strip a BOM

    $records = _SwuPgnRecordSections();
    $doc = ['headers' => [], 'headerLines' => [], 'story' => null, 'sections' => [], 'dropped' => [], 'lines' => [], 'notObject' => []];
    foreach ($records as $key) {
        $doc[$key] = [];
        $doc['lines'][$key] = [];
    }

    $section = null;          // null = the header part; else the banner name
    $gameSeen = false;
    $recordCount = 0;
    $story = [];
    $lines = explode("\n", $text);
    foreach ($lines as $i => $raw) {
        $lineNo = $i + 1;
        if (strlen($raw) > $caps['line']) $fail('line-too-long', "a line is longer than {$caps['line']} bytes", $lineNo);
        if ($raw !== '' && $raw[strlen($raw) - 1] === "\r") $raw = substr($raw, 0, -1);
        $line = trim($raw, " \t");

        // §4 / B.1: the first non-blank line is the Game tag, alone (the magic number).
        if (!$gameSeen) {
            if ($line === '') continue;
            if (!preg_match('/^\[Game[ \t]+"SWU-PGN\/(\d+)\.(\d+)"\]\z/', $line, $m)) $fail('not-swupgn', 'the first line is not [Game "SWU-PGN/<major>.<minor>"]', $lineNo);
            if (ltrim($m[1], '0') !== '1') $fail('unsupported-major', "SWU-PGN/{$m[1]}.{$m[2]} is a major version this reader does not read", $lineNo);
            $doc['headers']['Game'] = "SWU-PGN/{$m[1]}.{$m[2]}";
            $doc['headerLines']['Game'] = $lineNo;
            $gameSeen = true;
            continue;
        }

        // §4 / B.1 banner: %%% then a name of letters, digits and _ (case-insensitive; each at most once).
        if (strncmp($line, '%%%', 3) === 0 && preg_match('/^[A-Za-z0-9_]+\z/', trim(substr($line, 3), " \t"))) {
            if ($section === null) _SwuPgnHeaderDone($doc, $fail);
            if ($section === 'STORY') $doc['story'] = _SwuPgnTrimBlankEnds($story);
            $section = strtoupper(trim(substr($line, 3), " \t"));
            if (in_array($section, $doc['sections'], true)) $fail('duplicate-section', "%%% $section appears twice", $lineNo);
            $doc['sections'][] = $section;
            if ($section === 'STORY') $story = [];
            continue;
        }

        // [P-04] STORY is prose — kept verbatim, never JSON-parsed.
        if ($section === 'STORY') {
            $story[] = $raw;
            continue;
        }
        if ($line === '') continue;   // §4: blank lines are ignored everywhere else

        if ($section === null) {
            if ($line[0] !== '[') $fail('record-outside-section', 'a record before the first %%% section', $lineNo);
            _SwuPgnReadHeaderLine($line, $lineNo, $doc, $fail);
            continue;
        }

        $key = $records[$section] ?? null;
        if ($key === null) continue;     // [P-03] a section this version does not define: skipped, unparsed

        if (++$recordCount > $caps['records']) $fail('too-many-records', "more than {$caps['records']} records", $lineNo);
        $value = _SwuPgnDecodeJsonLine($line, $lineNo, $caps['depth'], $fail, $isObject);
        if ($key === 'setup' || $key === 'events') _SwuPgnReservedStatusToken($value, $lineNo, $fail);
        if ($key === 'annotations' && is_array($value) && is_array($value['line'] ?? null)) {
            foreach ($value['line'] as $e) _SwuPgnReservedStatusToken($e, $lineNo, $fail);
        }
        if (!$isObject) $doc['notObject'][$key][count($doc[$key])] = true;
        $doc[$key][] = $value;
        $doc['lines'][$key][] = $lineNo;
    }

    if (!$gameSeen) $fail('not-swupgn', 'the file is empty');
    if ($section === null) _SwuPgnHeaderDone($doc, $fail);
    if ($section === 'STORY') $doc['story'] = _SwuPgnTrimBlankEnds($story);
    return $doc;
}

// §4: "drop the blank lines at the very start and end" of STORY (B.1 blank-line = *WSP).
function _SwuPgnTrimBlankEnds(array $lines): array
{
    while ($lines && trim($lines[0], " \t") === '') array_shift($lines);
    while ($lines && trim(end($lines), " \t") === '') array_pop($lines);
    return $lines;
}

// §5.1 required tags, checked when the header ends.
function _SwuPgnHeaderDone(array $doc, callable $fail): void
{
    foreach (SwuPgnRequiredHeaderTags() as $tag) {
        if (!array_key_exists($tag, $doc['headers'])) $fail('missing-tag', "missing required header tag [$tag]");
    }
}

// §4 / B.1: header-line = *WSP tag-pair *( *WSP tag-pair ) *WSP; tag-pair = "[" name 1*WSP DQUOTE value DQUOTE "]".
// The only escapes are \" and \\ (`bad-escape`); anything else on the line is `bad-header-line`.
function _SwuPgnReadHeaderLine(string $line, int $lineNo, array &$doc, callable $fail): void
{
    $n = strlen($line);
    $i = 0;
    while (true) {
        while ($i < $n && ($line[$i] === ' ' || $line[$i] === "\t")) $i++;
        if ($i >= $n) return;
        if ($line[$i] !== '[' || !preg_match('/\G\[([A-Za-z0-9]+)[ \t]+"/', $line, $m, 0, $i)) $fail('bad-header-line', 'a header line holds something other than [Tag "value"] pairs', $lineNo);
        $tag = $m[1];
        $i += strlen($m[0]);
        $value = '';
        while (true) {
            if ($i >= $n) $fail('bad-header-line', "the value of [$tag] is not closed", $lineNo);
            $c = $line[$i];
            if ($c === '"') break;
            if ($c === '\\') {
                $next = $line[$i + 1] ?? '';
                if ($next !== '"' && $next !== '\\') $fail('bad-escape', "[$tag] uses an escape other than \\\\ and \\\"", $lineNo);
                $value .= $next;
                $i += 2;
                continue;
            }
            if (ord($c) < 0x20 || ord($c) === 0x7F) $fail('bad-header-line', "[$tag] holds a control character", $lineNo);
            $value .= $c;
            $i++;
        }
        if (($line[$i + 1] ?? '') !== ']') $fail('bad-header-line', 'a header line holds something other than [Tag "value"] pairs', $lineNo);
        $i += 2;
        if (!preg_match('//u', $value)) $fail('bad-header-line', "[$tag] is not UTF-8", $lineNo);
        if (in_array($tag, SwuPgnReservedNames(), true)) $fail('reserved-key', "[$tag] is a reserved name", $lineNo);
        if (array_key_exists($tag, $doc['headers'])) $fail('duplicate-tag', "[$tag] appears twice", $lineNo);
        if (in_array($tag, SwuPgnCountTags(), true) && !SwuPgnIsCount($value)) $fail('bad-count', "[$tag] is not a count (base-10 digits within 2^53-1)", $lineNo);
        $doc['headers'][$tag] = $value;
        $doc['headerLines'][$tag] = $lineNo;
    }
}

// [P-06] / §23: a STATUS_TOKEN whose token is a reserved name.
function _SwuPgnReservedStatusToken($r, int $lineNo, callable $fail): void
{
    if (is_array($r) && ($r['t'] ?? null) === 'STATUS_TOKEN' && is_string($r['token'] ?? null) && in_array($r['token'], SwuPgnReservedNames(), true)) {
        $fail('reserved-key', "STATUS_TOKEN token \"{$r['token']}\" is a reserved name", $lineNo);
    }
}

// One NDJSON line: bad-json, too-deep, then duplicate-key / reserved-key from a scan of its keys. Objects become PHP
// arrays except an EMPTY object, which stays a stdClass — so `{}` and `[]` remain distinguishable and re-encode faithfully.
function _SwuPgnDecodeJsonLine(string $line, int $lineNo, int $depth, callable $fail, ?bool &$isObject = null)
{
    try {
        $v = json_decode($line, false, $depth + 1, JSON_THROW_ON_ERROR);   // PHP counts one level above the containers
    } catch (JsonException $e) {
        if ($e->getCode() === JSON_ERROR_DEPTH) $fail('too-deep', "a record nests deeper than $depth", $lineNo);
        $fail('bad-json', 'invalid JSON', $lineNo);
    }
    $isObject = $v instanceof stdClass;
    if (strpos($line, ':') !== false) _SwuPgnScanKeys($line, $lineNo, $fail);
    return _SwuPgnFromJson($v);
}

// [P-06] / [P-07]: every object key at any depth — a reserved name is `reserved-key`, a repeat within one object
// `duplicate-key` (json_decode silently keeps the last). The line is already valid JSON, so a token scan suffices: a
// string followed by `:` is a key, compared after unescaping.
function _SwuPgnScanKeys(string $line, int $lineNo, callable $fail): void
{
    preg_match_all('/"[^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+"|[{}\[\]:]/', $line, $m);
    $toks = $m[0];
    $stack = [];      // per open container: a key set for an object, null for an array
    $reserved = array_flip(SwuPgnReservedNames());
    foreach ($toks as $i => $t) {
        if ($t === '{') $stack[] = [];
        elseif ($t === '[') $stack[] = null;
        elseif ($t === '}' || $t === ']') array_pop($stack);
        elseif ($t !== ':' && ($toks[$i + 1] ?? null) === ':' && $stack && is_array($stack[count($stack) - 1])) {
            $k = (string)json_decode($t);
            if (isset($reserved[$k])) $fail('reserved-key', "\"$k\" is a reserved key", $lineNo);
            $top = count($stack) - 1;
            if (isset($stack[$top][$k])) $fail('duplicate-key', 'duplicate key "' . substr($k, 0, 64) . '"', $lineNo);
            $stack[$top][$k] = true;
        }
    }
}

// I-JSON: an integral number is an integer whatever its spelling (1.0, 1e2) — as every JSON reader sees it; a fraction
// or an integer beyond ±(2^53−1) is kept as written for the validator's `number-range`.
function _SwuPgnFromJson($v)
{
    if ($v instanceof stdClass) {
        $a = get_object_vars($v);
        if (!$a) return $v;
        foreach ($a as $k => $x) $a[$k] = _SwuPgnFromJson($x);
        return $a;
    }
    if (is_array($v)) {
        foreach ($v as $k => $x) $v[$k] = _SwuPgnFromJson($x);
        return $v;
    }
    if (is_float($v) && is_finite($v) && floor($v) === $v && abs($v) <= 9007199254740991) return (int)$v;
    return $v;
}
