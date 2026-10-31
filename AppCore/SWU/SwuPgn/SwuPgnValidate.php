<?php
// SWU-PGN validator (SWU-PGN/1.0 spec §2.1 levels, §3 [V-01]…[V-09], §24 error model).
//
// SwuPgnValidate($doc) never throws and never changes the doc. It returns
//   ['ok' => no errors, 'level' => malformed|well-formed|valid|verified|complete, 'errors' => [...], 'warnings' => [...],
//    'notices' => [...]]
// with each issue ['code' (a §24 name), 'message', 'line' => ?int (1-based file line), 'seq' => ?string].
// `notices` holds what §5.3 / §16 ask a reader to SURFACE but is no §24 issue (an @unknown / -dirty Engine, an
// unseeded Seed, a STORY that differs from the render); it never changes the level.
//
// The level is the highest one every issue allows (§24 "Level at most"); the file is folded to check its keyframes
// only when it has no errors (§2.1: folding a file that breaks a schema proves nothing).

const SWUPGN_READER_MINOR = 0;          // this reader reads SWU-PGN/1.0
const SWUPGN_INT_MAX = 9007199254740991; // §23 / §2.1 rule 5: ±(2^53−1)

// §25 Pilot writer products — owner fills in. Closed registry: the `Engine` product (text before the first `@`,
// compared exactly) of the 1.0 pilot writers; a file with no WriterRevision from one of them is pre-release.
const SWUPGN_PILOT_WRITER_PRODUCTS = [];

function SwuPgnCheckVersion($game): string
{
    if (!is_string($game) || !preg_match('/^SWU-PGN\/(\d+)\.(\d+)\z/', $game, $m)) return 'unsupported';
    if (ltrim($m[1], '0') !== '1') return 'unsupported';             // §18: refuse a different MAJOR
    return ltrim($m[2], '0') === '' ? 'ok' : 'newer-minor';          // §18: accept a higher MINOR
}

// §6.4 closed vocabularies.
function SwuPgnDefeatReasons(): array
{
    return ['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect', 'unknown'];
}

function _SwuPgnClosedVocab(string $name): array
{
    return [
        'kind' => ['unit', 'upgrade'],
        'abilityKind' => ['action', 'epic', 'triggered', 'keyword', 'replacement', 'constant', 'event', 'delayed'],
        'duration' => ['attack', 'phase', 'round', 'whileSourceInPlay', 'until', 'delayed', 'game'],
        'endReason' => ['expired', 'fired', 'sourceLeft', 'targetLeft', 'cancelled'],
        'per' => ['phase', 'round', 'game'],
        'defeat' => SwuPgnDefeatReasons(),
        'result' => ['P1', 'P2', 'Draw', 'Incomplete'],
    ][$name];
}

// §2.1 levels, lowest first.
function SwuPgnLevels(): array
{
    return ['malformed', 'well-formed', 'valid', 'verified', 'complete'];
}

// §24: code => [severity, level at most].
function SwuPgnIssueCodes(): array
{
    $c = [];
    foreach (['not-swupgn', 'unsupported-major', 'too-large', 'line-too-long', 'too-many-records', 'too-deep', 'bad-header-line',
        'bad-escape', 'duplicate-tag', 'missing-tag', 'bad-count', 'duplicate-section', 'record-outside-section', 'bad-json',
        'duplicate-key', 'reserved-key'] as $k) $c[$k] = ['fatal', 'malformed'];
    foreach (['schema', 'not-object', 'card-index', 'duplicate-seq', 'dangling-ref', 'bad-zone', 'bad-token-name', 'deck-build-move',
        'credit-zone', 'token-zone', 'round-start-active', 'effect-end-unknown', 'fortify-host', 'same-zone-move', 'result-mismatch',
        'number-range', 'over-cap'] as $k) $c[$k] = ['error', 'well-formed'];
    $c['pre-release'] = ['warning', 'well-formed'];
    $c['keyframe-mismatch'] = ['warning', 'valid'];
    $c['recorder-errors'] = ['warning', 'verified'];
    $c['unknown-defeat'] = ['warning', 'verified'];
    foreach (['newer-minor', 'unknown-value', 'unknown-type', 'unknown-section', 'dangling-parent', 'too-many-issues'] as $k) $c[$k] = ['warning', null];
    return $c;
}

function _SwuPgnIssue(array &$v, string $code, string $message, ?int $line = null, ?string $seq = null): void
{
    [$severity, $cap] = SwuPgnIssueCodes()[$code] ?? ['warning', null];
    if ($cap !== null) $v['level'] = min($v['level'], array_search($cap, SwuPgnLevels(), true));   // counts even past the list cap
    if ($severity !== 'warning') $v['errorCount']++;
    if ($v['count'] >= SWUPGN_MAX_ISSUES) {
        $v['truncated'] = true;
        return;
    }
    $v['count']++;
    $v[$severity === 'warning' ? 'warnings' : 'errors'][] = ['code' => $code, 'message' => $message, 'line' => $line, 'seq' => $seq];
}

function _SwuPgnNotice(array &$v, string $code, string $message): void
{
    $v['notices'][] = ['code' => $code, 'message' => $message, 'line' => null, 'seq' => null];
}

// §2.1 / §24: parse + validate in one call. A file that does not parse is `malformed` with its fatal code.
function SwuPgnValidateText(string $text, array $opts = []): array
{
    $p = SwuPgnTryParse($text, is_array($opts['caps'] ?? null) ? $opts['caps'] : []);
    if ($p['doc'] === null) {
        $line = preg_match('/ on line (\d+)\z/', (string)$p['error'], $m) ? (int)$m[1] : null;
        return ['ok' => false, 'level' => 'malformed', 'errors' => [['code' => $p['code'], 'message' => $p['error'], 'line' => $line, 'seq' => null]],
            'warnings' => [], 'notices' => []];
    }
    return SwuPgnValidate($p['doc'], $opts);
}

// $opts['pilotProducts']: the §25 registry to judge `pre-release` by (default SWUPGN_PILOT_WRITER_PRODUCTS).
function SwuPgnValidate(array $doc, array $opts = []): array
{
    $v = ['errors' => [], 'warnings' => [], 'notices' => [], 'count' => 0, 'errorCount' => 0, 'truncated' => false, 'level' => 4, 'newer' => false];
    $h = is_array($doc['headers'] ?? null) ? $doc['headers'] : [];

    _SwuPgnValidateHeaders($v, $h, is_array($doc['headerLines'] ?? null) ? $doc['headerLines'] : [], $opts);

    // §18 / [V-03]: an unknown section warns unless it carries the X_ prefix.
    $known = ['STORY', 'DECKS', 'CARDS', 'SETUP', 'EVENTS', 'ANNOTATIONS'];
    foreach (is_array($doc['sections'] ?? null) ? $doc['sections'] : [] as $name) {
        if (is_string($name) && !in_array($name, $known, true) && strncasecmp($name, 'X_', 2) !== 0) {
            _SwuPgnIssue($v, 'unknown-section', "%%% $name is a section this version does not define (skipped)");
        }
    }

    // Every record of every JSON section: not-object, reserved keys (a doc built without the parser), number-range,
    // and the token-name grammar where §6.1 applies it.
    $sections = ['decks' => true, 'cards' => true, 'setup' => true, 'events' => true, 'annotations' => false];
    foreach ($sections as $key => $tokens) {
        foreach (is_array($doc[$key] ?? null) ? $doc[$key] : [] as $i => $r) {
            $line = _SwuPgnLineOf($doc, $key, $i);
            $seq = is_array($r) && is_string($r['seq'] ?? null) ? $r['seq'] : null;
            if (!_SwuPgnRecordIsObject($doc, $key, $i, $r)) {
                _SwuPgnIssue($v, 'not-object', 'a ' . strtoupper($key) . ' record is JSON but not an object', $line);
                continue;
            }
            $found = ['range' => null, 'reserved' => null, 'tokens' => []];
            _SwuPgnScanValue($r, $tokens, $found);
            if ($key === 'annotations' && is_array($r['line'] ?? null)) _SwuPgnScanValue($r['line'], true, $found);
            if ($found['reserved'] !== null) _SwuPgnIssue($v, 'reserved-key', "\"{$found['reserved']}\" is a reserved key", $line, $seq);
            if ($found['range'] !== null) _SwuPgnIssue($v, 'number-range', "{$found['range']} is not an integer within ±(2^53−1)", $line, $seq);
            foreach (array_slice(array_keys($found['tokens']), 0, 5) as $tok) _SwuPgnIssue($v, 'bad-token-name', '"' . _SwuPgnShort($tok) . '" breaks the TOKEN: grammar of §6.1', $line, $seq);
        }
    }

    _SwuPgnValidateDecks($v, $doc);
    _SwuPgnValidateCards($v, $doc);
    $seqs = _SwuPgnValidateEvents($v, $doc, $h);
    _SwuPgnValidateAnnotations($v, $doc, $seqs['all']);
    _SwuPgnValidateResult($v, $h, $seqs['gameEnds'], $doc);

    // §2.1: fold to check the keyframes only when the file has no errors.
    if ($v['errorCount'] === 0) {
        $events = [];
        foreach (['setup', 'events'] as $key) foreach (is_array($doc[$key] ?? null) ? $doc[$key] : [] as $r) $events[] = $r;
        $kf = SwuPgnCheckKeyframes($events);
        $bySeq = [];
        foreach ((array)($kf['mismatches'] ?? []) as $m) {
            $s = is_string($m['seq'] ?? null) ? $m['seq'] : '?';
            $bySeq[$s][] = $m;
        }
        foreach ($bySeq as $s => $ms) {
            $first = $ms[0];
            $what = ($first['path'] ?? '?') . (array_key_exists('expected', $first) ? ' (keyframe ' . json_encode($first['expected']) . ', fold ' . json_encode($first['got'] ?? null) . ')' : '');
            _SwuPgnIssue($v, 'keyframe-mismatch', "the keyframe at $s does not match the fold: $what" . (count($ms) > 1 ? ' and ' . (count($ms) - 1) . ' more' : ''),
                $seqs['lines'][$s] ?? null, $s === '?' ? null : (string)$s);
        }
    }

    // §5.3 / §16: worth surfacing, not issues.
    $engine = $h['Engine'] ?? null;
    if (is_string($engine) && preg_match('/@unknown\z/', $engine)) _SwuPgnNotice($v, 'engine-unknown', 'Engine is @unknown: this file is untraceable');
    if (is_string($engine) && preg_match('/-dirty\z/', $engine)) _SwuPgnNotice($v, 'engine-dirty', 'Engine is a -dirty development build');
    if (($h['Seed'] ?? null) === 'unseeded') _SwuPgnNotice($v, 'unseeded', 'Seed is "unseeded": deterministic replay is impossible');
    if (is_array($doc['story'] ?? null) && $v['errorCount'] === 0) {
        $m = SwuPgnStoryMatches($doc);
        if (!$m['matches']) _SwuPgnNotice($v, 'story-drift', "%%% STORY differs from the rendered events at story line {$m['line']} (§16: the events are the truth)");
    }

    if ($v['truncated']) {
        $v['warnings'][] = ['code' => 'too-many-issues', 'message' => 'more than ' . SWUPGN_MAX_ISSUES . ' issues; the rest were not listed', 'line' => null, 'seq' => null];
    }
    return ['ok' => $v['errorCount'] === 0, 'level' => SwuPgnLevels()[$v['level']], 'errors' => $v['errors'], 'warnings' => $v['warnings'], 'notices' => $v['notices']];
}

// ── shared checks ────────────────────────────────────────────────────────────────────────────────────────────────

function _SwuPgnLineOf(array $doc, string $section, int $i): ?int
{
    $l = $doc['lines'][$section][$i] ?? null;
    return is_int($l) ? $l : null;
}

function _SwuPgnIsObject($r): bool
{
    return $r instanceof stdClass || (is_array($r) && $r !== [] && !array_is_list($r));
}

// The parser marks the lines that were not JSON objects (so `{"0":1}`, a list-shaped PHP array, still counts as one).
function _SwuPgnRecordIsObject(array $doc, string $key, int $i, $r): bool
{
    if (isset($doc['notObject'])) return empty($doc['notObject'][$key][$i]) && (is_array($r) || $r instanceof stdClass);
    return _SwuPgnIsObject($r);
}

function _SwuPgnShort(string $s): string
{
    return strlen($s) > 80 ? substr($s, 0, 77) . '...' : $s;
}

// Deep scan of one record: the first number outside ±(2^53−1) or not an integer, the first reserved key, and every
// string (or key) that begins `TOKEN:` and breaks the grammar (when $tokens).
function _SwuPgnScanValue($x, bool $tokens, array &$found, int $depth = 0): void
{
    if ($depth > 200) return;
    if (is_string($x)) {
        if ($tokens && strncmp($x, 'TOKEN:', 6) === 0 && !SwuPgnTokenIdOk($x) && count($found['tokens']) < 20) $found['tokens'][$x] = true;
        return;
    }
    if (is_float($x) || (is_int($x) && ($x > SWUPGN_INT_MAX || $x < -SWUPGN_INT_MAX))) {
        if ($found['range'] === null) $found['range'] = is_float($x) && !is_finite($x) ? 'a non-finite number' : var_export($x, true);
        return;
    }
    if ($x instanceof stdClass) $x = get_object_vars($x);
    if (!is_array($x)) return;
    foreach ($x as $k => $y) {
        if (is_string($k)) {
            if ($found['reserved'] === null && in_array($k, SwuPgnReservedNames(), true)) $found['reserved'] = $k;
            if ($tokens && strncmp($k, 'TOKEN:', 6) === 0 && !SwuPgnTokenIdOk($k) && count($found['tokens']) < 20) $found['tokens'][$k] = true;
        }
        _SwuPgnScanValue($y, $tokens, $found, $depth + 1);
    }
}

function _SwuPgnTypeOk($v, string $type): bool
{
    switch ($type) {
        case 'seat': return $v === 1 || $v === 2;
        case 'str': return is_string($v);
        case 'nestr': return is_string($v) && $v !== '';
        case 'int': return is_int($v);
        case 'nat': return is_int($v) && $v >= 0;
        case 'pos': return is_int($v) && $v >= 1;
        case 'bool': return is_bool($v);
        case 'strs': return is_array($v) && array_is_list($v) && count(array_filter($v, 'is_string')) === count($v);
        case 'list': return is_array($v) && array_is_list($v);
        case 'obj': return _SwuPgnIsObject($v);
        case 'map': return $v instanceof stdClass || (is_array($v) && ($v === [] ? false : !array_is_list($v)));
        case 'zone': return _SwuPgnIsZone($v);
        case 'ref': return is_string($v) && (strncmp($v, 'base@', 5) !== 0 || SwuPgnSeatOfBaseRef($v) === 1 || SwuPgnSeatOfBaseRef($v) === 2);
        case 'src': return is_string($v) && (SwuPgnSeatOfBaseRef($v) === 1 || SwuPgnSeatOfBaseRef($v) === 2 || _SwuPgnLooksLikeCardId($v));
        case 'fx': return SwuPgnEffectIdOk($v);
        case 'seatOrBoth': return $v === 1 || $v === 2 || $v === 'both';
    }
    return true;
}

function _SwuPgnTypeName(string $type): string
{
    return ['seat' => 'seat 1 or 2', 'str' => 'a string', 'nestr' => 'a non-empty string', 'int' => 'an integer', 'nat' => 'a non-negative integer',
        'pos' => 'an integer of 1 or more', 'bool' => 'a boolean', 'strs' => 'an array of strings', 'list' => 'an array', 'obj' => 'an object',
        'map' => 'an object', 'zone' => 'a §6.2 zone', 'ref' => 'a card id or base@1 / base@2', 'src' => 'a card id or base@N ([W-32]: omit it when no card is responsible)',
        'fx' => 'an effect id fx@N (§6.1)', 'seatOrBoth' => '1, 2 or "both"'][$type] ?? $type;
}

// §6.1 a printed card or token id, loosely (a reader MUST NOT reject an id it cannot resolve).
function _SwuPgnLooksLikeCardId(string $v): bool
{
    return strncmp($v, 'TOKEN:', 6) === 0 || (bool)preg_match('/^[A-Za-z0-9]+#[A-Za-z0-9-]+(:\d+)?\z/', $v);
}

// §18: a closed-vocabulary value. Known → fine; a string in a NEWER minor → `unknown-value` warning [V-02]; otherwise
// (the file's own minor, or a value of the wrong type) → `schema`.
function _SwuPgnClosed(array &$v, $value, string $vocab, string $what, ?int $line, ?string $seq): bool
{
    if (in_array($value, _SwuPgnClosedVocab($vocab), true)) return true;
    $shown = is_string($value) ? '"' . _SwuPgnShort($value) . '"' : json_encode($value);
    if (is_string($value) && $v['newer']) _SwuPgnIssue($v, 'unknown-value', "$what $shown is not a value this reader knows (newer minor version)", $line, $seq);
    else _SwuPgnIssue($v, 'schema', "$what $shown is outside the closed set", $line, $seq);
    return false;
}

// Checks $obj's fields against [field => [type, required]]; `zone` failures are `bad-zone`, everything else `schema`.
function _SwuPgnShape(array &$v, array $obj, array $spec, string $what, ?int $line, ?string $seq): void
{
    foreach ($spec as $f => [$type, $required]) {
        if (!array_key_exists($f, $obj)) {
            if ($required) _SwuPgnIssue($v, 'schema', "$what is missing $f", $line, $seq);
            continue;
        }
        if (_SwuPgnTypeOk($obj[$f], $type)) continue;
        if ($type === 'zone') _SwuPgnIssue($v, 'bad-zone', "$what.$f " . _SwuPgnShort((string)json_encode($obj[$f])) . ' is not in the zone vocabulary (§6.2)', $line, $seq);
        else _SwuPgnIssue($v, 'schema', "$what.$f must be " . _SwuPgnTypeName($type), $line, $seq);
    }
}

// ── header (§5) ──────────────────────────────────────────────────────────────────────────────────────────────────

function _SwuPgnIsRfc3339($v): bool
{
    if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.\d{1,9})?(Z|[+-](\d{2}):(\d{2}))\z/', $v, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) && (int)$m[4] < 24 && (int)$m[5] < 60 && (int)$m[6] <= 60
        && (!isset($m[9]) || ((int)$m[9] < 24 && (int)$m[10] < 60));
}

function _SwuPgnValidateHeaders(array &$v, array $h, array $lines, array $opts): void
{
    $at = function (string $tag) use ($lines): ?int { return is_int($lines[$tag] ?? null) ? $lines[$tag] : null; };
    $bad = function (string $tag, string $msg) use (&$v, $at) { _SwuPgnIssue($v, 'schema', "[$tag] $msg", $at($tag)); };

    // The parser enforces these; a doc built in code is held to them too.
    $game = $h['Game'] ?? null;
    if ($game !== null) {
        if (!is_string($game) || !preg_match('/^SWU-PGN\/\d+\.\d+\z/', $game)) _SwuPgnIssue($v, 'not-swupgn', '[Game] is not "SWU-PGN/<major>.<minor>"');
        elseif (SwuPgnCheckVersion($game) === 'unsupported') _SwuPgnIssue($v, 'unsupported-major', "$game is a major version this reader does not read");
        elseif (SwuPgnCheckVersion($game) === 'newer-minor') {
            $v['newer'] = true;
            _SwuPgnIssue($v, 'newer-minor', "$game is a newer minor version than this reader's 1." . SWUPGN_READER_MINOR . '; what it adds is ignored', $at('Game'));
        }
    }
    foreach (SwuPgnRequiredHeaderTags() as $tag) {
        if (!array_key_exists($tag, $h)) _SwuPgnIssue($v, 'missing-tag', "missing required header tag [$tag]");
    }
    foreach (SwuPgnCountTags() as $tag) {
        if (array_key_exists($tag, $h) && !SwuPgnIsCount($h[$tag])) _SwuPgnIssue($v, 'bad-count', "[$tag] is not a count", $at($tag));
    }
    foreach ($h as $tag => $val) {
        if (in_array((string)$tag, SwuPgnReservedNames(), true)) _SwuPgnIssue($v, 'reserved-key', "[$tag] is a reserved name");
    }

    // §5.1 / §5.2 value formats ("a value outside it fails validation").
    $str = function (string $tag) use ($h) { return is_string($h[$tag] ?? null) ? $h[$tag] : null; };
    if (($x = $str('GameId')) !== null && !preg_match('/^.{1,128}\z/su', $x)) $bad('GameId', 'is 1–128 characters');
    foreach (['Date', 'EndDate'] as $tag) {
        if (array_key_exists($tag, $h) && !_SwuPgnIsRfc3339($h[$tag])) $bad($tag, 'is not an RFC 3339 date-time with seconds and an offset');
    }
    if (array_key_exists('CardPool', $h) && !preg_match('/^[A-Z0-9]+(,[A-Z0-9]+)*\z/', (string)$h['CardPool'])) $bad('CardPool', 'is set codes (upper-case letters and digits) joined by ","');
    if (array_key_exists('Engine', $h) && !preg_match('/^[A-Za-z0-9._-]+@[\x21-\x7E]+\z/', (string)$h['Engine'])) $bad('Engine', 'is <product>@<build>');
    if (array_key_exists('Seed', $h) && (string)$h['Seed'] === '') $bad('Seed', 'is required and non-empty (§5.3: "unseeded" when there is none)');
    foreach (['P1Id', 'P2Id', 'Match'] as $tag) {
        if (array_key_exists($tag, $h) && !preg_match('/^sha256:[0-9a-f]{64}\z/', (string)$h[$tag])) $bad($tag, 'is sha256: + 64 lowercase hex digits');
    }
    foreach (['P1Leader', 'P1Base', 'P2Leader', 'P2Base'] as $tag) {
        if (array_key_exists($tag, $h) && !preg_match('/^[A-Z0-9]+#\d{3,}\z/', (string)$h[$tag])) $bad($tag, 'is a card id (SET#NNN)');
    }
    foreach (['WriterRevision', 'GameNumber'] as $tag) {
        if (array_key_exists($tag, $h) && SwuPgnIsCount($h[$tag]) && ltrim((string)$h[$tag], '0') === '') $bad($tag, 'is 1 or more');
    }
    if (array_key_exists('Perspective', $h) && !in_array($h['Perspective'], ['P1', 'P2'], true)) $bad('Perspective', 'is P1 or P2 (seats never grow, §18)');
    if (array_key_exists('Result', $h)) {
        $r = $h['Result'];
        // §18: `P` + digits is a seat (an error in any minor); any other unknown name is an outcome.
        if (is_string($r) && preg_match('/^P\d+\z/', $r) && $r !== 'P1' && $r !== 'P2') $bad('Result', "\"$r\" is not a seat (seats never grow, §18)");
        else _SwuPgnClosed($v, $r, 'result', '[Result]', $at('Result'), null);
    }

    // [V-04] / §25: pre-release = no WriterRevision and an Engine product in the closed pilot registry.
    $products = is_array($opts['pilotProducts'] ?? null) ? $opts['pilotProducts'] : SWUPGN_PILOT_WRITER_PRODUCTS;
    $engine = $str('Engine');
    if (!array_key_exists('WriterRevision', $h) && $engine !== null) {
        $product = explode('@', $engine, 2)[0];
        if (in_array($product, $products, true)) _SwuPgnIssue($v, 'pre-release', "a pre-release file: no [WriterRevision] and Engine product \"$product\" is a 1.0 pilot writer (Appendix C)", $at('Engine'));
    }
    // §5.2: RecorderErrors above zero → at most verified.
    if (SwuPgnIsCount($h['RecorderErrors'] ?? null) && ltrim($h['RecorderErrors'], '0') !== '') {
        _SwuPgnIssue($v, 'recorder-errors', "the writer lost {$h['RecorderErrors']} record(s) while recording", $at('RecorderErrors'));
    }
}

// ── DECKS (§7), CARDS (§6.5) ─────────────────────────────────────────────────────────────────────────────────────

function _SwuPgnDeckListOk($list): bool
{
    if (!is_array($list) || !array_is_list($list)) return false;
    foreach ($list as $pair) {
        if (!is_array($pair) || !array_is_list($pair) || count($pair) !== 2 || !is_string($pair[0]) || !is_int($pair[1]) || $pair[1] < 1) return false;
    }
    return true;
}

// §7: `p` required; leader/base strings; deck/sideboard [id, count] lists. Open schema: unknown fields are ignored.
function _SwuPgnValidateDecks(array &$v, array $doc): void
{
    $seen = [];
    foreach (is_array($doc['decks'] ?? null) ? $doc['decks'] : [] as $i => $d) {
        if (!_SwuPgnRecordIsObject($doc, 'decks', $i, $d) || !is_array($d)) continue;
        $line = _SwuPgnLineOf($doc, 'decks', $i);
        _SwuPgnShape($v, $d, ['p' => ['seat', 1], 'leader' => ['str', 0], 'base' => ['str', 0]], 'DECKS', $line, null);
        if (($d['p'] ?? null) === 1 || ($d['p'] ?? null) === 2) {
            if (isset($seen[$d['p']])) _SwuPgnIssue($v, 'schema', "two DECKS lines for seat {$d['p']} (one line per player)", $line);
            $seen[$d['p']] = true;
        }
        foreach (['deck', 'sideboard'] as $f) {
            if (array_key_exists($f, $d) && !_SwuPgnDeckListOk($d[$f])) _SwuPgnIssue($v, 'schema', "DECKS $f must be an array of [id, count ≥ 1]", $line);
        }
    }
}

function _SwuPgnValidateCards(array &$v, array $doc): void
{
    foreach (is_array($doc['cards'] ?? null) ? $doc['cards'] : [] as $i => $c) {
        if (!_SwuPgnRecordIsObject($doc, 'cards', $i, $c) || !is_array($c)) continue;
        $line = _SwuPgnLineOf($doc, 'cards', $i);
        if (!is_string($c['id'] ?? null) || !is_string($c['name'] ?? null)) {
            _SwuPgnIssue($v, 'card-index', 'a CARDS entry lacks a string id or a string name', $line);
            continue;
        }
        if (preg_match('/:\d+\z/', $c['id'])) _SwuPgnIssue($v, 'schema', "CARDS id {$c['id']} is a base id, with no :N copy suffix", $line);
        if (array_key_exists('backSideName', $c) && !is_string($c['backSideName'])) _SwuPgnIssue($v, 'schema', 'CARDS backSideName must be a string', $line);
        if (array_key_exists('kind', $c)) _SwuPgnClosed($v, $c['kind'], 'kind', 'CARDS kind', $line, null);
    }
}

// ── SETUP (§8) + EVENTS (§9, §10) ────────────────────────────────────────────────────────────────────────────────

// Per known `t`: field => [type, required]. §9: the schema checks shape; the §10 fields it does NOT require are
// DAMAGE.damageType, STATUS_TOKEN.token, DRAW.count and `p` on PLAY and LEADER_FLIP. §10.2 note fields marked `?` are optional.
function _SwuPgnEventShapes(): array
{
    $pc = ['p' => ['seat', 1], 'card' => ['ref', 1]];
    $play = ['p' => ['seat', 0], 'card' => ['ref', 1], 'zone' => ['zone', 0], 'cost' => ['nat', 0]];
    return [
        'INIT' => ['p1DeckOrder' => ['strs', 1], 'p2DeckOrder' => ['strs', 1]],
        'MOVE' => ['card' => ['ref', 1], 'from' => ['zone', 1], 'to' => ['zone', 1], 'p' => ['seat', 0], 'attachedTo' => ['ref', 0], 'exhausted' => ['bool', 0], 'kind' => ['closed', 0]],
        'PLAY' => $play, 'PLAY_SMUGGLE' => $play,
        'PLAY_EVENT' => ['p' => ['seat', 1]] + $play,
        'PLAY_UPGRADE' => ['p' => ['seat', 1]] + $play + ['target' => ['ref', 0]],
        'DEPLOY_LEADER' => ['p' => ['seat', 1]] + $play + ['epic' => ['bool', 0], 'kind' => ['closed', 0], 'target' => ['ref', 0]],
        'ABILITY_ACTIVATE' => $pc + ['kind' => ['closed', 0], 'title' => ['str', 0], 'ability' => ['str', 0], 'epic' => ['bool', 0], 'grantedBy' => ['ref', 0], 'limit' => ['obj', 0], 'usedBy' => ['ref', 0]],
        'ABILITY_RESET' => ['card' => ['ref', 1], 'ability' => ['str', 0]],
        'LEADER_FLIP' => ['p' => ['seat', 0], 'card' => ['ref', 1], 'onStartingSide' => ['bool', 1]],
        'TAKE_CONTROL' => $pc + ['zone' => ['zone', 0], 'from' => ['seat', 0], 'exhausted' => ['bool', 0], 'owner' => ['seat', 0]],
        'CAPTURE' => $pc + ['by' => ['ref', 0]],
        'RESCUE' => $pc,
        'EXHAUST_RESOURCES' => ['p' => ['seat', 1], 'amount' => ['nat', 1]],
        'READY_RESOURCES' => ['p' => ['seat', 1], 'amount' => ['nat', 1]],
        'CREATE_TOKEN' => ['p' => ['seat', 1], 'token' => ['str', 1], 'zone' => ['zone', 1], 'power' => ['int', 0], 'hp' => ['int', 0], 'kind' => ['closed', 0]],
        'DAMAGE' => ['src' => ['src', 0], 'tgt' => ['ref', 1], 'amt' => ['nat', 1], 'damageType' => ['str', 0], 'hp' => ['int', 1]],
        'OVERWHELM' => ['p' => ['seat', 1], 'tgt' => ['ref', 1], 'amt' => ['nat', 1], 'hp' => ['int', 1]],
        'HEAL' => ['src' => ['src', 0], 'tgt' => ['ref', 1], 'amt' => ['nat', 1], 'hp' => ['int', 1]],
        'DEFEAT' => ['card' => ['ref', 1], 'reason' => ['closed', 1], 'defeatedBy' => ['src', 0]],
        'EXHAUST' => ['card' => ['ref', 1]], 'READY' => ['card' => ['ref', 1]],
        'STATS' => ['card' => ['ref', 1], 'power' => ['int', 1], 'hp' => ['int', 1], 'keywords' => ['strs', 0], 'traits' => ['strs', 0], 'blank' => ['bool', 0],
            'leaderUnit' => ['bool', 0], 'copyOf' => ['str', 0], 'name' => ['str', 0], 'cost' => ['int', 0], 'aspects' => ['strs', 0]],
        'EFFECT_START' => ['id' => ['fx', 1], 'src' => ['ref', 1], 'p' => ['seat', 1], 'targets' => ['strs', 0], 'seat' => ['seatOrBoth', 0], 'duration' => ['closed', 1],
            'untilText' => ['str', 0], 'ability' => ['str', 0], 'text' => ['str', 0]],
        'EFFECT_END' => ['id' => ['fx', 1], 'reason' => ['closed', 1], 'targets' => ['strs', 0]],
        'DRAW' => ['p' => ['seat', 1], 'count' => ['nat', 0], 'cards' => ['strs', 1]],
        'DISCARD' => ['p' => ['seat', 1], 'cards' => ['strs', 1]],
        'RESOURCE' => $pc,
        'SHIELD_GAIN' => ['card' => ['ref', 1], 'count' => ['int', 0]],
        'SHIELD_USE' => ['card' => ['ref', 1], 'count' => ['int', 0]],
        'EXPERIENCE_GAIN' => ['card' => ['ref', 1], 'count' => ['int', 1]],
        'STATUS_TOKEN' => ['card' => ['ref', 1], 'token' => ['str', 0], 'count' => ['int', 1]],
        'ROUND_START' => ['round' => ['nat', 1]],
        'ROUND_END' => ['round' => ['nat', 1]],
        'PHASE_START' => ['phase' => ['str', 1], 'active' => ['seat', 0]],
        'CLAIM_INITIATIVE' => ['p' => ['seat', 1]],
        // §10.2 notes
        'ATTACK' => ['p' => ['seat', 1], 'atk' => ['ref', 1], 'def' => ['ref', 1], 'defenderType' => ['str', 1]],
        'PASS' => ['p' => ['seat', 1]], 'MULLIGAN' => ['p' => ['seat', 1]], 'KEEP_HAND' => ['p' => ['seat', 1]], 'SHUFFLE' => ['p' => ['seat', 1]],
        'CHOICE' => ['p' => ['seat', 1], 'offered' => ['strs', 1], 'chose' => ['nat', 1], 'prompt' => ['str', 0], 'blind' => ['str', 0]],
        'MODAL_CHOICE' => ['p' => ['seat', 1], 'offered' => ['strs', 1], 'chose' => ['nat', 1]],
        'SEARCH' => ['p' => ['seat', 1], 'found' => ['strs', 0], 'zone' => ['zone', 0], 'owner' => ['seat', 0], 'seen' => ['strs', 0]],
        'REVEAL' => ['p' => ['seat', 1], 'zone' => ['zone', 1], 'cards' => ['strs', 1]],
        'LOOK' => ['p' => ['seat', 1], 'owner' => ['seat', 1], 'zone' => ['zone', 1], 'cards' => ['strs', 1], 'count' => ['nat', 1]],
        'NAME' => ['p' => ['seat', 1], 'name' => ['str', 1]],
        'TRIGGER' => ['card' => ['ref', 1], 'p' => ['seat', 0]],
        'PHASE_END' => ['phase' => ['str', 1]],
        'GAME_END' => ['winner' => ['winner', 1], 'reason' => ['str', 1]],
        'UNDO' => ['at' => ['str', 1], 'by' => ['seat', 0]],
    ];
}

// §16: the records numbered as a player's own action (only those may carry `ms`, §5.2.1).
function _SwuPgnMayCarryMs(array $e, string $t, string $seq): bool
{
    if ($t === 'ROUND_START' || $t === 'PHASE_START') return true;
    if (preg_match('/\.\d+[a-z]+(-undo)?\z/', $seq)) return false;          // a lettered consequence
    if (in_array($t, ['PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'ATTACK', 'PASS', 'CLAIM_INITIATIVE'], true)) return true;
    return $t === 'ABILITY_ACTIVATE' && (in_array($e['kind'] ?? null, ['action', 'epic'], true) || ($e['epic'] ?? null) === true);
}

// Returns ['all' => seq set, 'lines' => [seq => line], 'gameEnds' => list of winners].
function _SwuPgnValidateEvents(array &$v, array $doc, array $h): array
{
    $shapes = _SwuPgnEventShapes();
    $closedField = ['MOVE.kind' => 'kind', 'CREATE_TOKEN.kind' => 'kind', 'DEPLOY_LEADER.kind' => 'kind', 'ABILITY_ACTIVATE.kind' => 'abilityKind',
        'DEFEAT.reason' => 'defeat', 'EFFECT_START.duration' => 'duration', 'EFFECT_END.reason' => 'endReason'];

    // Pass 1: every seq of SETUP + EVENTS (rule 2 looks forward as well as back).
    $all = [];
    $lines = [];
    $records = [];
    foreach (['setup', 'events'] as $key) {
        foreach (is_array($doc[$key] ?? null) ? $doc[$key] : [] as $i => $e) {
            if (!_SwuPgnRecordIsObject($doc, $key, $i, $e) || !is_array($e)) continue;
            $records[] = [$key, $i, $e];
            if (is_string($e['seq'] ?? null)) {
                $all[$e['seq']] = true;
                $lines[$e['seq']] = $lines[$e['seq']] ?? _SwuPgnLineOf($doc, $key, $i);
            }
        }
    }

    $seen = [];
    $unknownSeen = [];
    $inits = 0;
    $effects = [];      // [V-09] active effects in file order: id => target set, or null for a seat effect
    $gameEnds = [];
    foreach ($records as [$key, $i, $e]) {
        $line = _SwuPgnLineOf($doc, $key, $i);
        $seq = is_string($e['seq'] ?? null) ? $e['seq'] : null;
        $issue = function (string $code, string $msg) use (&$v, $line, $seq) { _SwuPgnIssue($v, $code, $msg, $line, $seq); };

        // §9 / §9.1 seq (the schema pattern, looser than B.3 for pre-release files) — rule 1: unique.
        if ($seq === null) $issue('schema', 'every event needs a string seq');
        else {
            if (!preg_match('/^R\d+\.(S|A|G)(\.[A-Za-z0-9-]+)?\z|^R\d+\.(start|end)(-undo)?\z/', $seq)) $issue('schema', "seq \"" . _SwuPgnShort($seq) . "\" does not match the §9.1 pattern");
            if (isset($seen[$seq])) $issue('duplicate-seq', "seq $seq is used twice");
            $seen[$seq] = true;
        }
        $t = $e['t'] ?? null;
        if (!is_string($t)) {
            $issue('schema', 'every event needs a string t');
            continue;
        }

        // Fields legal on any record: for (rule 2), ms (§5.2.1), simul.
        if (array_key_exists('for', $e)) {
            if (!is_string($e['for'])) $issue('schema', 'for must be a string seq');
            elseif (!isset($all[$e['for']])) $issue('dangling-ref', 'for names ' . _SwuPgnShort($e['for']) . ', which is no seq in the file');
        }
        if (array_key_exists('ms', $e)) {
            if (!is_int($e['ms']) || $e['ms'] < 0) $issue('schema', 'ms is a non-negative integer duration from Date');
            elseif ($seq !== null && !_SwuPgnMayCarryMs($e, $t, $seq)) $issue('schema', "ms is only on numbered actions, ROUND_START and PHASE_START (not this $t)");
        }
        if (array_key_exists('simul', $e) && !is_string($e['simul'])) $issue('schema', 'simul must be a seq string');

        if (!isset($shapes[$t])) {
            // [V-03] / §18: an unknown type does nothing; it warns unless it carries the X_ prefix.
            if (strncmp($t, 'X_', 2) !== 0 && !isset($unknownSeen[$t]) && count($unknownSeen) < 100) {
                $unknownSeen[$t] = true;
                $issue('unknown-type', 'unknown event type ' . _SwuPgnShort($t) . ' (ignored by the fold)');
            }
            continue;
        }

        foreach ($shapes[$t] as $f => [$type, $req]) {
            if ($type === 'closed' || $type === 'winner') {
                if (!array_key_exists($f, $e)) {
                    if ($req) $issue('schema', "$t is missing $f");
                } elseif ($type === 'winner') {
                    if (!($e[$f] === 1 || $e[$f] === 2 || $e[$f] === 'Draw')) {
                        if (is_string($e[$f]) && !preg_match('/^P?\d+\z/', $e[$f]) && $v['newer']) $issue('unknown-value', "GAME_END winner \"" . _SwuPgnShort($e[$f]) . '" is not a value this reader knows (newer minor version)');
                        else $issue('schema', 'GAME_END winner must be 1, 2 or "Draw"');
                    }
                } else _SwuPgnClosed($v, $e[$f], $closedField["$t.$f"], "$t.$f", $line, $seq);
                continue;
            }
            _SwuPgnShape($v, $e, [$f => [$type, $req]], $t, $line, $seq);
        }
        if (is_array($e['offered'] ?? null)) {
            foreach ($e['offered'] as $ref) {
                if (is_string($ref) && strncmp($ref, 'base@', 5) === 0 && !_SwuPgnTypeOk($ref, 'ref')) $issue('schema', "$t.offered \"" . _SwuPgnShort($ref) . '" is not base@1 or base@2');
            }
        }

        if ($t === 'INIT' && $key === 'setup') {
            if (++$inits > 1) $issue('schema', '%%% SETUP holds one INIT');
            if ($seq !== 'R0.S.0' && $seq !== 'R1.S.0') $issue('schema', 'INIT seq is "R0.S.0" ("R1.S.0" in early files)');
        }
        if ($t === 'GAME_END' && array_key_exists('winner', $e)) $gameEnds[] = $e['winner'];
        _SwuPgnValidateEventRules($v, $issue, $t, $e, $seq, $line, $effects);
    }
    if (in_array('SETUP', is_array($doc['sections'] ?? null) ? $doc['sections'] : [], true) && $inits === 0) {
        _SwuPgnIssue($v, 'schema', '%%% SETUP has no INIT record');
    }
    return ['all' => $all, 'lines' => $lines, 'gameEnds' => $gameEnds];
}

// Rules specific to one event type.
function _SwuPgnValidateEventRules(array &$v, callable $issue, string $t, array $e, ?string $seq, ?int $line, array &$effects): void
{
    switch ($t) {
        case 'MOVE':
            $from = $e['from'] ?? null;
            $to = $e['to'] ?? null;
            if (!_SwuPgnIsZone($from) || !_SwuPgnIsZone($to)) break;     // bad-zone already said
            $card = is_string($e['card'] ?? null) ? $e['card'] : '';
            $token = strncmp($card, 'TOKEN:', 6) === 0;
            // Rule 4: only an attachment changing in place moves from a zone to itself, and only inside an arena.
            if ($from === $to && !SwuPgnIsArena($to)) $issue('same-zone-move', "a MOVE from $from to $from (only an arena may be both)");
            if ($from === 'outsideTheGame' && $to === 'deck') $issue('deck-build-move', '[W-41]: building the decks is not a move');
            if (_SwuPgnReservedToken($card) === 'credit' && ($from === 'resource' || $to === 'resource')) $issue('credit-zone', '[W-42]: a Credit token lives in base, never resource');
            // [V-07]: a token leaving an arena or base goes to outsideTheGame — except one (not Credit/Force) moving with its unit to the other arena.
            if ($token && $from !== $to && (SwuPgnIsArena($from) || $from === 'base') && $to !== 'outsideTheGame'
                && !(SwuPgnIsArena($from) && SwuPgnIsArena($to) && _SwuPgnReservedToken($card) === null)) {
                $issue('token-zone', "[V-07]: a token leaving $from goes to outsideTheGame, not $to");
            }
            // [W-15]: attachedTo on the attaching move (an upgrade into an arena, or into base for Fortify) and on no other.
            $attaching = ($e['kind'] ?? null) === 'upgrade' && (SwuPgnIsArena($to) || $to === 'base');
            if ($attaching && !array_key_exists('attachedTo', $e)) $issue('schema', 'an attaching MOVE must name its host with attachedTo');
            if (!$attaching && array_key_exists('attachedTo', $e)) $issue('schema', 'attachedTo belongs only on the attaching move (exits are host-less)');
            if (array_key_exists('exhausted', $e) && $from !== 'resource' && $to !== 'resource') $issue('schema', 'exhausted belongs only on a MOVE into or out of resource');
            // [V-06] Fortify on the other seat's base.
            $host = SwuPgnSeatOfBaseRef($e['attachedTo'] ?? null);
            if ($to === 'base' && ($host === 1 || $host === 2) && _SwuPgnSeat($e['p'] ?? null) !== null && $host !== $e['p']) $issue('fortify-host', "[V-06]: attached to base@$host by seat {$e['p']}");
            break;
        case 'PLAY_UPGRADE':
            $host = SwuPgnSeatOfBaseRef($e['target'] ?? null);
            if (($host === 1 || $host === 2) && _SwuPgnSeat($e['p'] ?? null) !== null && $host !== $e['p']) $issue('fortify-host', "[V-06]: played onto base@$host by seat {$e['p']}");
            break;
        case 'DEPLOY_LEADER':
            $pilot = ($e['kind'] ?? null) === 'upgrade';
            if ($pilot !== array_key_exists('target', $e)) $issue('schema', 'kind "upgrade" and target come together (a deploy as a pilot)');
            break;
        case 'ABILITY_ACTIVATE':
            if (!array_key_exists('limit', $e)) break;
            $lim = $e['limit'];
            if (!is_array($lim)) break;     // the shape already said
            if (!_SwuPgnTypeOk($lim['max'] ?? null, 'pos')) $issue('schema', 'limit.max must be an integer of 1 or more');
            if (!array_key_exists('per', $lim)) $issue('schema', 'limit is missing per');
            else _SwuPgnClosed($v, $lim['per'], 'per', 'limit.per', $line, $seq);
            if (!is_string($e['ability'] ?? null)) $issue('schema', '[W-49]: a record with limit must carry ability');
            break;
        case 'TAKE_CONTROL':
            if (($e['zone'] ?? null) === 'resource' && !array_key_exists('from', $e) && !array_key_exists('owner', $e)) $issue('schema', '[W-56]: a resource TAKE_CONTROL without from must name the owner');
            break;
        case 'DEFEAT':
            $r = $e['reason'] ?? null;
            if (is_string($r) && ($r === 'unknown' || !in_array($r, SwuPgnDefeatReasons(), true))) $issue('unknown-defeat', "DEFEAT reason \"" . _SwuPgnShort($r) . '" reads as unknown: the writer could not resolve a cause');
            break;
        case 'DAMAGE':
            if (($e['damageType'] ?? null) === 'overwhelm') $issue('schema', 'overwhelm onto a base is its own OVERWHELM record, never damageType "overwhelm"');
            break;
        case 'CREATE_TOKEN':
            if (is_string($e['token'] ?? null) && strncmp($e['token'], 'TOKEN:', 6) !== 0) $issue('schema', 'CREATE_TOKEN token is a TOKEN: id');
            break;
        case 'STATS':
            foreach (['name', 'cost', 'aspects'] as $f) {
                if (array_key_exists($f, $e) && !array_key_exists('copyOf', $e)) $issue('schema', "STATS $f comes only with copyOf");
            }
            break;
        case 'STATUS_TOKEN':
            if (is_string($e['token'] ?? null) && !SwuPgnTokenNameOk($e['token'])) $issue('bad-token-name', 'STATUS_TOKEN token "' . _SwuPgnShort($e['token']) . '" breaks the token-name grammar of §6.1');
            break;
        case 'EFFECT_START':
            $hasT = array_key_exists('targets', $e);
            $hasS = array_key_exists('seat', $e);
            if ($hasT === $hasS) $issue('schema', 'an EFFECT_START carries exactly one of targets and seat');
            if ($hasT && $e['targets'] === []) $issue('schema', 'EFFECT_START targets names at least one target');
            if ($hasT && is_array($e['targets'])) {
                foreach ($e['targets'] as $x) if (is_string($x) && !_SwuPgnTypeOk($x, 'ref')) $issue('schema', "EFFECT_START target \"" . _SwuPgnShort($x) . '" is not base@1 or base@2');
            }
            if (($e['duration'] ?? null) === 'until' && !_SwuPgnTypeOk($e['untilText'] ?? null, 'nestr')) $issue('schema', '[W-48]: duration "until" requires a non-empty untilText');
            if (SwuPgnEffectIdOk($e['id'] ?? null)) {
                $effects[$e['id']] = $hasT && _SwuPgnTypeOk($e['targets'], 'strs') ? array_fill_keys($e['targets'], true) : null;
            }
            break;
        case 'EFFECT_END':
            $id = $e['id'] ?? null;
            if (!SwuPgnEffectIdOk($id)) break;
            if (!array_key_exists($id, $effects)) {
                $issue('effect-end-unknown', "[V-09]: no active effect $id (never started, or already ended)");
                break;
            }
            $tg = $e['targets'] ?? null;
            if (_SwuPgnTypeOk($tg, 'strs') && $tg !== []) {
                if ($effects[$id] === null) {
                    $issue('effect-end-unknown', "[V-09]: $id is a seat effect with no targets to end");
                    break;
                }
                foreach ($tg as $x) {
                    if (!isset($effects[$id][$x])) $issue('effect-end-unknown', "[V-09]: $id has no target " . _SwuPgnShort($x));
                    unset($effects[$id][$x]);
                }
                if (!$effects[$id]) unset($effects[$id]);
            } else unset($effects[$id]);
            break;
        case 'UNDO':
            if (is_string($e['at'] ?? null) && $seq !== null && $seq !== $e['at'] . '-undo') $issue('schema', "an UNDO's seq is its at plus -undo ({$e['at']}-undo)");
            break;
        case 'ROUND_START':
        case 'ROUND_END':
            if ($t === 'ROUND_START' && _SwuPgnSeat($e['active'] ?? null) === null) $issue('round-start-active', '[V-08]: a ROUND_START states its active seat (1 or 2)');
            if (array_key_exists('keyframe', $e)) {
                foreach (array_slice(_SwuPgnKeyframeIssues($e['keyframe'], $v['newer']), 0, 20) as [$code, $msg]) {
                    if ($code === 'unknown-value') _SwuPgnIssue($v, 'unknown-value', $msg, $line, $seq);
                    else $issue($code, "keyframe: $msg");
                }
            }
            break;
    }
}

// §11 / §13: a keyframe's shape → list of [code, message]. List over 1000 → over-cap; a card zone outside §6.2 →
// bad-zone; everything else that is not the §11 shape (incl. a damaged keyframe, §13) → schema.
function _SwuPgnKeyframeIssues($kf, bool $newer): array
{
    $out = [];
    if (!is_array($kf) || !_SwuPgnIsObject($kf)) return [['schema', 'is not an object']];
    $closed = function ($val, string $vocab, string $what) use (&$out, $newer) {
        if (in_array($val, _SwuPgnClosedVocab($vocab), true)) return;
        if (is_string($val) && $newer) $out[] = ['unknown-value', "keyframe $what \"" . _SwuPgnShort($val) . '" is not a value this reader knows (newer minor version)'];
        else $out[] = ['schema', "$what is outside the closed set"];
    };
    $chk = function ($obj, string $prefix, array $spec) use (&$out) {
        foreach ($spec as $f => [$type, $required]) {
            if (!array_key_exists($f, $obj)) {
                if ($required) $out[] = ['schema', "$prefix$f is missing"];
                continue;
            }
            $val = $obj[$f];
            if (in_array($type, ['strs', 'list'], true) && is_array($val) && array_is_list($val) && count($val) > 1000) {
                $out[] = ['over-cap', "$prefix$f holds " . count($val) . ' entries (at most 1000)'];
                if ($type === 'strs' && !_SwuPgnTypeOk(array_slice($val, 0, 1001), 'strs')) $out[] = ['schema', "$prefix$f must be " . _SwuPgnTypeName($type)];
                continue;
            }
            $ok = $type === 'initiative' ? ($val === null || $val === 1 || $val === 2) : _SwuPgnTypeOk($val, $type);
            if (!$ok) $out[] = $type === 'zone' ? ['bad-zone', "$prefix$f " . _SwuPgnShort((string)json_encode($val)) . ' is not in the zone vocabulary'] : ['schema', "$prefix$f must be " . ($type === 'initiative' ? '1, 2 or null' : _SwuPgnTypeName($type))];
        }
    };
    $chk($kf, '', ['round' => ['nat', 0], 'phase' => ['str', 0], 'initiative' => ['initiative', 0], 'initiativeTaken' => ['bool', 0], 'active' => ['seat', 0],
        'players' => ['obj', 1], 'effects' => ['list', 0]]);
    foreach (is_array($kf['effects'] ?? null) && array_is_list($kf['effects']) && count($kf['effects']) <= 1000 ? $kf['effects'] : [] as $n => $fx) {
        if (!is_array($fx) || !_SwuPgnIsObject($fx)) {
            $out[] = ['schema', "effects[$n] is not an object"];
            continue;
        }
        $chk($fx, "effects[$n].", ['id' => ['fx', 1], 'src' => ['str', 1], 'p' => ['seat', 1], 'targets' => ['strs', 0], 'seat' => ['seatOrBoth', 0], 'duration' => ['str', 1]]);
        if (is_string($fx['duration'] ?? null)) $closed($fx['duration'], 'duration', "effects[$n].duration");
    }
    $players = is_array($kf['players'] ?? null) ? $kf['players'] : [];
    foreach (array_keys($players) as $k) {
        if ((string)$k !== '1' && (string)$k !== '2') $out[] = ['schema', "players.$k is not a seat (seats never grow)"];
    }
    if (!_SwuPgnIsObject($kf['players'] ?? null)) return $out;
    foreach ([1, 2] as $s) {
        $pl = $players[$s] ?? null;
        $pre = "players.$s.";
        if (!is_array($pl) || !_SwuPgnIsObject($pl)) {
            $out[] = ['schema', "players.$s is missing or not an object (a keyframe carries every seat)"];
            continue;
        }
        $chk($pl, $pre, ['seat' => ['seat', 0], 'baseHp' => ['int', 1], 'baseMaxHp' => ['int', 1], 'handSize' => ['nat', 1], 'hand' => ['strs', 1],
            'deckSize' => ['nat', 0], 'resourcesReady' => ['nat', 1], 'resourcesExhausted' => ['nat', 1], 'resources' => ['strs', 0],
            'baseEpicActionUsed' => ['bool', 0], 'baseUpgrades' => ['strs', 0], 'baseCaptured' => ['strs', 0], 'baseDefeated' => ['bool', 0],
            'credits' => ['nat', 1], 'hasForce' => ['bool', 1], 'discard' => ['strs', 1], 'cards' => ['list', 1], 'leader' => ['obj', 0],
            'abilityUses' => ['list', 0], 'foreignResources' => ['strs', 0]]);
        if (is_array($pl['leader'] ?? null) && _SwuPgnIsObject($pl['leader'])) {
            $chk($pl['leader'], $pre . 'leader.', ['id' => ['str', 1], 'deployed' => ['bool', 1], 'exhausted' => ['bool', 1], 'epicActionUsed' => ['bool', 1], 'onStartingSide' => ['bool', 0]]);
        }
        foreach (is_array($pl['abilityUses'] ?? null) && array_is_list($pl['abilityUses']) && count($pl['abilityUses']) <= 1000 ? $pl['abilityUses'] : [] as $n => $u) {
            if (!is_array($u) || !_SwuPgnIsObject($u)) {
                $out[] = ['schema', "{$pre}abilityUses[$n] is not an object"];
                continue;
            }
            $chk($u, "{$pre}abilityUses[$n].", ['card' => ['str', 1], 'ability' => ['str', 1], 'count' => ['nat', 1], 'per' => ['str', 1], 'max' => ['nat', 1]]);
            if (is_string($u['per'] ?? null)) $closed($u['per'], 'per', "{$pre}abilityUses[$n].per");
        }
        $attached = 0;
        foreach (is_array($pl['cards'] ?? null) && array_is_list($pl['cards']) && count($pl['cards']) <= 1000 ? $pl['cards'] : [] as $n => $c) {
            if (!is_array($c) || !_SwuPgnIsObject($c)) {
                $out[] = ['schema', "{$pre}cards[$n] is not an object"];
                continue;
            }
            $cp = $pre . 'cards[' . (is_string($c['id'] ?? null) ? _SwuPgnShort($c['id']) : $n) . '].';
            $chk($c, $cp, ['id' => ['str', 1], 'zone' => ['zone', 1], 'damage' => ['nat', 1], 'exhausted' => ['bool', 1], 'upgrades' => ['strs', 1],
                'shields' => ['nat', 1], 'experience' => ['nat', 1], 'captured' => ['strs', 0], 'power' => ['int', 0], 'hp' => ['int', 0],
                'keywords' => ['strs', 0], 'traits' => ['strs', 0], 'blank' => ['bool', 0], 'leaderUnit' => ['bool', 0], 'copyOf' => ['str', 0],
                'name' => ['str', 0], 'cost' => ['int', 0], 'aspects' => ['strs', 0], 'owner' => ['seat', 0]]);
            if (_SwuPgnIsZone($c['zone'] ?? null) && !SwuPgnIsArena($c['zone'])) $out[] = ['schema', "{$cp}zone \"{$c['zone']}\": keyframe cards are ground and space cards only"];
            if (!array_key_exists('statusTokens', $c)) $out[] = ['schema', "{$cp}statusTokens is missing"];
            else {
                $st = $c['statusTokens'];
                $ok = $st instanceof stdClass || (is_array($st) && $st !== [] && !array_is_list($st) && count(array_filter($st, function ($n) { return is_int($n) && $n > 0; })) === count($st));
                if (!$ok) $out[] = ['schema', "{$cp}statusTokens must be an object of positive counts"];
            }
            if (array_key_exists('upgradeControllers', $c)) {
                $uc = $c['upgradeControllers'];
                $ok = $uc instanceof stdClass || (is_array($uc) && $uc !== [] && !array_is_list($uc) && count(array_filter($uc, function ($n) { return $n === 1 || $n === 2; })) === count($uc));
                if (!$ok) $out[] = ['schema', "{$cp}upgradeControllers must be an object of seats"];
            }
            foreach (['upgrades', 'captured'] as $f) $attached += is_array($c[$f] ?? null) ? count($c[$f]) : 0;
        }
        if ($attached > 1000) $out[] = ['over-cap', "{$pre}cards hold $attached upgrades and captives (at most 1000)"];
    }
    return $out;
}

// ── ANNOTATIONS (§15), Result (§5.1) ─────────────────────────────────────────────────────────────────────────────

function _SwuPgnValidateAnnotations(array &$v, array $doc, array $seqs): void
{
    $notes = is_array($doc['annotations'] ?? null) ? $doc['annotations'] : [];
    $ids = [];
    foreach ($notes as $n) {
        if (is_array($n) && is_string($n['id'] ?? null)) $ids[$n['id']] = true;
    }
    foreach ($notes as $i => $n) {
        if (!_SwuPgnRecordIsObject($doc, 'annotations', $i, $n) || !is_array($n)) continue;
        $line = _SwuPgnLineOf($doc, 'annotations', $i);
        _SwuPgnShape($v, $n, ['ref' => ['str', 1], 'nag' => ['str', 0], 'text' => ['str', 0], 'by' => ['str', 0], 'id' => ['str', 0], 'parent' => ['str', 0], 'ts' => ['int', 0]], 'annotation', $line, null);
        if (is_string($n['ref'] ?? null) && !isset($seqs[$n['ref']])) _SwuPgnIssue($v, 'dangling-ref', '[A-02]: a note on ' . _SwuPgnShort($n['ref']) . ', which is no seq in the file', $line, $n['ref']);
        if (array_key_exists('line', $n) && (!is_array($n['line']) || !array_is_list($n['line']) || count(array_filter($n['line'], '_SwuPgnIsObject')) !== count($n['line']))) {
            _SwuPgnIssue($v, 'schema', 'annotation line must be an array of events', $line);
        }
        if (is_string($n['parent'] ?? null) && !isset($ids[$n['parent']])) _SwuPgnIssue($v, 'dangling-parent', 'parent ' . _SwuPgnShort($n['parent']) . ' names no note in the file (shown top-level)', $line);
    }
}

// §5.1 / §10.2: Result agrees with the last GAME_END.winner; Incomplete means no GAME_END. Skipped for an outcome or a
// winner this reader does not know.
function _SwuPgnValidateResult(array &$v, array $h, array $gameEnds, array $doc): void
{
    $result = $h['Result'] ?? null;
    if (!in_array($result, _SwuPgnClosedVocab('result'), true)) return;
    $want = ['P1' => 1, 'P2' => 2, 'Draw' => 'Draw', 'Incomplete' => null][$result];
    $last = $gameEnds ? end($gameEnds) : null;
    if ($gameEnds && !in_array($last, [1, 2, 'Draw'], true)) return;
    if ($want === null && $gameEnds) _SwuPgnIssue($v, 'result-mismatch', '[Result "Incomplete"] but the file has a GAME_END', $doc['headerLines']['Result'] ?? null);
    elseif ($want !== null && !$gameEnds) _SwuPgnIssue($v, 'result-mismatch', "[Result \"$result\"] but the file has no GAME_END", $doc['headerLines']['Result'] ?? null);
    elseif ($want !== null && $last !== $want) _SwuPgnIssue($v, 'result-mismatch', "[Result \"$result\"] disagrees with the last GAME_END winner " . json_encode($last), $doc['headerLines']['Result'] ?? null);
}
