<?php
// SWU-PGN parser — text → doc (SWU-PGN/1.0 spec §4 file shape, §5 header tags).
//
// Throws ONLY the four errors §4 documents, each as a SwuPgnParseException with the spec's exact
// message. Everything else that is wrong with a file (bad shapes, wrong values, records that are
// JSON but not objects) parses fine and is reported by SwuPgnValidate() instead — §4 says a `[`
// line inside a section "reaches the JSON path so that validate() can reject it".

class SwuPgnParseException extends RuntimeException
{
    public ?int $lineNumber;

    public function __construct(string $message, ?int $lineNumber = null)
    {
        parent::__construct($message);
        $this->lineNumber = $lineNumber;
    }
}

// §5.1, in the spec's order (the first missing one is the one reported).
function SwuPgnRequiredHeaderTags(): array
{
    return ['Game', 'GameId', 'Date', 'CardPool', 'Engine', 'Seed', 'P1Id', 'P2Id', 'P1', 'P2',
        'P1Leader', 'P1Base', 'P2Leader', 'P2Base', 'Result', 'Reason', 'Rounds'];
}

// §4: the five NDJSON banners → doc key. STORY is the sixth, and is prose.
function _SwuPgnRecordSections(): array
{
    return ['DECKS' => 'decks', 'CARDS' => 'cards', 'SETUP' => 'setup', 'EVENTS' => 'events', 'ANNOTATIONS' => 'annotations'];
}

function SwuPgnTryParse(string $text): array
{
    try {
        return ['doc' => SwuPgnParse($text), 'error' => null];
    } catch (SwuPgnParseException $e) {
        return ['doc' => null, 'error' => $e->getMessage()];
    }
}

function SwuPgnParse(string $text): array
{
    if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) $text = substr($text, 3);   // tolerate a UTF-8 BOM

    $records = _SwuPgnRecordSections();
    $doc = ['headers' => [], 'story' => null, 'sections' => [], 'dropped' => [], 'lines' => []];
    foreach ($records as $key) {
        $doc[$key] = [];
        $doc['lines'][$key] = [];
    }

    $section = null;          // null = still in the header part
    $lineNo = 0;
    foreach (explode("\n", $text) as $raw) {
        $lineNo++;
        if ($raw !== '' && $raw[strlen($raw) - 1] === "\r") $raw = substr($raw, 0, -1);
        $line = trim($raw);

        // §4: a banner is a line starting with %%%, then the section name (case-insensitive).
        if (strncmp($line, '%%%', 3) === 0) {
            $section = strtoupper(trim(substr($line, 3)));
            if (count($doc['sections']) < 1000) $doc['sections'][] = $section;
            if ($section === 'STORY' && $doc['story'] === null) $doc['story'] = [];
            continue;
        }

        // §4: STORY is prose — kept verbatim, blank lines included, never JSON-parsed.
        if ($section === 'STORY') {
            if (count($doc['story']) < SWUPGN_MAX_STORY_LINES) $doc['story'][] = $raw;
            else $doc['dropped']['story'] = ($doc['dropped']['story'] ?? 0) + 1;
            continue;
        }

        if ($line === '') continue;   // §4: blank lines are ignored everywhere else

        if ($section === null) {
            // §4: every header line starts with `[`; all of them come before the first banner.
            if ($line[0] === '[') {
                _SwuPgnReadHeaderLine($line, $doc['headers']);
                continue;
            }
            throw new SwuPgnParseException("SWU-PGN: record before any %%% section on line $lineNo", $lineNo);
        }

        $key = $records[$section] ?? null;
        if ($key === null) {
            throw new SwuPgnParseException("SWU-PGN: JSON record in unrecognized section on line $lineNo", $lineNo);
        }

        $ok = false;
        $value = _SwuPgnDecodeJsonLine($line, $ok);
        if (!$ok) throw new SwuPgnParseException("SWU-PGN: invalid JSON on line $lineNo", $lineNo);

        $cap = $key === 'events' ? SWUPGN_MAX_EVENTS : SWUPGN_MAX_SECTION_RECORDS;
        if (count($doc[$key]) >= $cap) {
            $doc['dropped'][$key] = ($doc['dropped'][$key] ?? 0) + 1;
            continue;
        }
        $doc[$key][] = $value;
        $doc['lines'][$key][] = $lineNo;
    }

    foreach (SwuPgnRequiredHeaderTags() as $tag) {
        if (!array_key_exists($tag, $doc['headers'])) {
            throw new SwuPgnParseException("SWU-PGN: missing required header tag [$tag]");
        }
    }
    return $doc;
}

// §4: /\[([A-Za-z0-9]+)\s+"((?:[^"\\]|\\.)*)"\]/g — several tags per line, last one wins.
function _SwuPgnReadHeaderLine(string $line, array &$headers): void
{
    $m = [];
    if (!preg_match_all('/\[([A-Za-z0-9]+)\s+"((?:[^"\\\\]|\\\\.)*)"\]/s', $line, $m, PREG_SET_ORDER)) return;
    foreach ($m as $tag) {
        // "a backslash means take the next character literally"
        $headers[$tag[1]] = preg_replace('/\\\\(.)/s', '$1', $tag[2]);
    }
}

// Decodes one NDJSON line. Objects become PHP arrays except an EMPTY object, which stays a
// stdClass — so `{}` and `[]` remain distinguishable for validate() and re-encode faithfully.
function _SwuPgnDecodeJsonLine(string $line, bool &$ok)
{
    try {
        $v = json_decode($line, false, SWUPGN_MAX_JSON_DEPTH, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $ok = false;
        return null;
    }
    $ok = true;
    return _SwuPgnFromJson($v);
}

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
    }
    return $v;
}
