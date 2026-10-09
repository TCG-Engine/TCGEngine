<?php
// SWU-PGN: parse a .swupgn text into a document (spec §4–§8). See SwuPgn.php.

class SwuPgnParseException extends RuntimeException {}

// Sections whose lines are NDJSON records. STORY is prose and deliberately not one of them.
const SWUPGN_JSON_SECTIONS = ['DECKS', 'CARDS', 'SETUP', 'EVENTS', 'ANNOTATIONS'];
// Every %%% banner this version knows; validate() warns on anything else.
const SWUPGN_KNOWN_SECTIONS = ['STORY', 'DECKS', 'CARDS', 'SETUP', 'EVENTS', 'ANNOTATIONS'];

// String.prototype.trim(): strips Unicode whitespace AND the byte-order mark, which PHP's trim()
// does not. A file saved with a BOM must not lose its first header line.
function _SwuPgnTrim(string $s): string {
  $t = preg_replace('/^[\s\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+|[\s\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+$/u', '', $s);
  return $t ?? trim($s);   // invalid UTF-8: fall back rather than lose the line
}

// The card id without its `:N` copy suffix (spec §6.1). Strip this FIRST, before splitting on `#`.
function SwuPgnBaseId($ref): string {
  return preg_replace('/:\d+$/', '', (string)$ref);
}

// Number("…"): a header number that is not finite reads as $fallback, never NaN.
function _SwuPgnFiniteOr(string $value, $fallback) {
  $v = _SwuPgnTrim($value);
  if ($v === '') return 0;
  if (!is_numeric($v)) return $fallback;
  $n = $v + 0;
  if (is_float($n) && !is_finite($n)) return $fallback;
  return (is_float($n) && floor($n) === $n && abs($n) < PHP_INT_MAX) ? (int)$n : $n;
}

// A header COUNT that is not base-10 digits is corrupt, not zero: it stays absent.
function _SwuPgnStrictCount(?string $value): ?int {
  if ($value === null || preg_match('/^\s*\d+\s*$/', $value) !== 1) return null;
  $n = trim($value) + 0;
  return is_int($n) ? $n : null;   // 1e309-sized digit strings overflow to float: not a count
}

function _SwuPgnParseHeaderLine(string $line, array &$raw): void {
  // A line may hold several [Tag "Value"] pairs; a backslash takes the next character literally.
  if (preg_match_all('/\[([A-Za-z0-9]+)\s+"((?:[^"\\\\]|\\\\.)*)"\]/', $line, $m, PREG_SET_ORDER)) {
    foreach ($m as $tag) $raw[$tag[1]] = preg_replace('/\\\\(.)/', '$1', $tag[2]);
  }
}

function _SwuPgnBuildHeader(array $raw): array {
  $req = function (string $k) use ($raw): string {
    if (!array_key_exists($k, $raw)) throw new SwuPgnParseException("SWU-PGN: missing required header tag [$k]");
    return $raw[$k];
  };
  $header = [
    'game' => $req('Game'), 'gameId' => $req('GameId'), 'date' => $req('Date'),
    'format' => $raw['Format'] ?? null, 'cardPool' => $req('CardPool'), 'engine' => $req('Engine'),
    'seed' => $req('Seed'),
    'perspective' => $raw['Perspective'] ?? null,
    'p1Id' => $req('P1Id'), 'p2Id' => $req('P2Id'), 'p1' => $req('P1'), 'p2' => $req('P2'),
    'p1Leader' => $req('P1Leader'), 'p1Base' => $req('P1Base'),
    'p2Leader' => $req('P2Leader'), 'p2Base' => $req('P2Base'),
    'result' => $req('Result'), 'reason' => $req('Reason'),
    'rounds' => _SwuPgnFiniteOr($req('Rounds'), 0),
  ];
  if ($header['format'] === null) unset($header['format']);
  foreach (['RecorderErrors' => 'recorderErrors', 'Undos' => 'undos', 'GameNumber' => 'gameNumber'] as $tag => $key) {
    $n = _SwuPgnStrictCount($raw[$tag] ?? null);
    if ($n !== null) $header[$key] = $n;
  }
  if (array_key_exists('EndDate', $raw)) $header['endDate'] = $raw['EndDate'];
  if (array_key_exists('Match', $raw)) $header['match'] = $raw['Match'];
  return $header;
}

// Drop the leading/trailing blank lines a section banner's spacing leaves around the prose.
function _SwuPgnTrimBlankEdges(array $lines): array {
  $start = 0; $end = count($lines);
  while ($start < $end && _SwuPgnTrim($lines[$start]) === '') $start++;
  while ($end > $start && _SwuPgnTrim($lines[$end - 1]) === '') $end--;
  return array_slice($lines, $start, $end - $start);
}

/**
 * Parse a .swupgn text. Throws SwuPgnParseException on a missing required header tag, a line that
 * is not valid JSON, or a record before any %%% banner. A section this version does not know is a
 * LATER version's and is skipped (spec §18). Records are returned exactly as decoded: shape
 * checking is validate()'s job, and the fold tolerates anything.
 */
function SwuPgnParse(string $text): array {
  return _SwuPgnParse($text, true);
}

// $assoc = false decodes records to stdClass, keeping {} and [] apart — what validate() needs to
// check a record's TYPE the way a JSON Schema does. Everything else reads the assoc form.
function _SwuPgnParse(string $text, bool $assoc): array {
  $raw = [];
  $story = []; $decks = []; $cards = []; $setup = []; $events = []; $annotations = [];
  $section = 'NONE';

  $lines = explode("\n", $text);
  foreach ($lines as $i => $rawLine) {
    $line = _SwuPgnTrim($rawLine);

    // %%% STORY is prose: every line verbatim, blank lines included, until the next banner.
    if ($section === 'STORY' && !str_starts_with($line, '%%%')) {
      $story[] = preg_replace('/\r$/', '', $rawLine);
      continue;
    }
    if ($line === '') continue;
    // Header lines exist only before the first banner. Inside a section a `[` line is a record.
    if ($section === 'NONE' && str_starts_with($line, '[')) {
      _SwuPgnParseHeaderLine($line, $raw);
      continue;
    }
    if (str_starts_with($line, '%%%')) {
      $name = strtoupper(_SwuPgnTrim(substr($line, 3)));
      $section = ($name === 'STORY' || in_array($name, SWUPGN_JSON_SECTIONS, true)) ? $name : 'UNKNOWN';
      continue;
    }
    try {
      $rec = json_decode($line, $assoc, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
      throw new SwuPgnParseException('SWU-PGN: invalid JSON on line ' . ($i + 1));
    }
    switch ($section) {
      case 'DECKS': $decks[] = $rec; break;
      case 'CARDS': $cards[] = $rec; break;
      case 'SETUP': $setup[] = $rec; break;
      case 'EVENTS': $events[] = $rec; break;
      case 'ANNOTATIONS': $annotations[] = $rec; break;
      case 'UNKNOWN': break;
      default: throw new SwuPgnParseException('SWU-PGN: record before any %%% section on line ' . ($i + 1));
    }
  }

  return [
    'header' => _SwuPgnBuildHeader($raw), 'story' => _SwuPgnTrimBlankEdges($story),
    'decks' => $decks, 'cards' => $cards, 'setup' => $setup, 'events' => $events, 'annotations' => $annotations,
  ];
}
