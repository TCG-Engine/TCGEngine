<?php
// SWU-PGN conformance report (SWU-PGN/1.0 spec §4, §5, §6, §7, §8, §9, §10, §13, §15, §18).
//
// SwuPgnValidate($doc) never throws and never changes the doc. It returns
//   ['ok' => no errors, 'errors' => [...], 'warnings' => [...]]
// with each issue ['code', 'message', 'line' => ?int (1-based file line), 'seq' => ?string].
//
// ERROR   = the file breaks a MUST of the spec, or a record has a shape the fold dereferences
//           wrongly (§9: "a malformed file fails validate() instead of throwing inside a reader").
// WARNING = a SHOULD, a value the spec asks readers to surface (RecorderErrors, @unknown, unseeded,
//           DEFEAT reason "unknown"), an unknown event type (§18), or an early-writer trace (§22).
// Open vocabularies (§6.4) never produce an issue; DEFEAT.reason is closed and does.

function SwuPgnCheckVersion($game): string
{
    if (!is_string($game) || !preg_match('/^SWU-PGN\/(\d{1,9})\.(\d{1,9})\z/', $game, $m)) return 'unsupported';
    if ((int)$m[1] !== 1) return 'unsupported';            // §18: refuse a different MAJOR
    return (int)$m[2] === 0 ? 'ok' : 'newer-minor';        // §18: accept a higher MINOR
}

// §6.4 — the one closed vocabulary.
function SwuPgnDefeatReasons(): array
{
    return ['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect', 'unknown'];
}

function _SwuPgnIssue(array &$v, string $level, string $code, string $message, ?int $line = null, ?string $seq = null): void
{
    if ($v['count'] >= SWUPGN_MAX_ISSUES) {
        $v['truncated'] = true;
        return;
    }
    $v['count']++;
    $v[$level === 'error' ? 'errors' : 'warnings'][] = ['code' => $code, 'message' => $message, 'line' => $line, 'seq' => $seq];
}

function _SwuPgnIsObject($r): bool
{
    return $r instanceof stdClass || (is_array($r) && $r !== [] && !array_is_list($r));
}

function _SwuPgnTypeOk($v, string $type): bool
{
    switch ($type) {
        case 'seat': return $v === 1 || $v === 2;
        case 'str': return is_string($v);
        case 'int': return is_int($v);
        case 'nat': return is_int($v) && $v >= 0;
        case 'bool': return is_bool($v);
        case 'zone': return _SwuPgnIsZone($v);
        case 'arena': return SwuPgnIsArena($v);
        case 'kind': return $v === 'unit' || $v === 'upgrade';
        case 'winner': return $v === 1 || $v === 2 || $v === 'Draw';
        case 'strs': return is_array($v) && array_is_list($v) && count(array_filter($v, 'is_string')) === count($v);
        case 'obj': return _SwuPgnIsObject($v);
    }
    return true;
}

function _SwuPgnTypeName(string $type): string
{
    return ['seat' => 'seat 1 or 2', 'str' => 'a string', 'int' => 'an integer', 'nat' => 'a non-negative integer',
        'bool' => 'a boolean', 'zone' => 'a §6.2 zone', 'arena' => '"ground" or "space"', 'kind' => '"unit" or "upgrade"',
        'winner' => '1, 2 or "Draw"', 'strs' => 'an array of strings', 'obj' => 'an object'][$type] ?? $type;
}

// Per known `t`: field => [type, required]. §10.1 board records' required fields are errors when
// missing; §10.2 notes' listed fields only warn (nothing folds from them).
function _SwuPgnEventShapes(): array
{
    $pc = ['p' => ['seat', 1], 'card' => ['str', 1]];
    $play = $pc + ['zone' => ['zone', 0], 'cost' => ['nat', 0]];
    return [
        'MOVE' => ['card' => ['str', 1], 'from' => ['zone', 1], 'to' => ['zone', 1], 'p' => ['seat', 0], 'attachedTo' => ['str', 0], 'exhausted' => ['bool', 0], 'kind' => ['kind', 0]],
        'PLAY' => $play, 'PLAY_SMUGGLE' => $play, 'PLAY_EVENT' => $play,
        'PLAY_UPGRADE' => $play + ['target' => ['str', 0]],
        'DEPLOY_LEADER' => $play + ['epic' => ['bool', 0], 'kind' => ['kind', 0], 'target' => ['str', 0]],
        'ABILITY_ACTIVATE' => $pc + ['kind' => ['str', 0], 'title' => ['str', 0], 'ability' => ['str', 0], 'epic' => ['bool', 0]],
        'LEADER_FLIP' => $pc + ['onStartingSide' => ['bool', 1]],
        'TAKE_CONTROL' => $pc + ['zone' => ['zone', 0], 'from' => ['seat', 0], 'exhausted' => ['bool', 0]],
        'CAPTURE' => $pc + ['by' => ['str', 0]],
        'RESCUE' => $pc,
        'EXHAUST_RESOURCES' => ['p' => ['seat', 1], 'amount' => ['nat', 1]],
        'READY_RESOURCES' => ['p' => ['seat', 1], 'amount' => ['nat', 1]],
        'CREATE_TOKEN' => ['p' => ['seat', 1], 'token' => ['str', 1], 'zone' => ['zone', 1], 'power' => ['int', 0], 'hp' => ['int', 0], 'kind' => ['kind', 0]],
        'DAMAGE' => ['src' => ['str', 1], 'tgt' => ['str', 1], 'amt' => ['nat', 1], 'damageType' => ['str', 1], 'hp' => ['int', 1]],
        'OVERWHELM' => ['p' => ['seat', 1], 'tgt' => ['str', 1], 'amt' => ['nat', 1], 'hp' => ['int', 1]],
        'HEAL' => ['src' => ['str', 0], 'tgt' => ['str', 1], 'amt' => ['nat', 1], 'hp' => ['int', 1]],
        'DEFEAT' => ['card' => ['str', 1], 'reason' => ['str', 1], 'defeatedBy' => ['str', 0]],
        'EXHAUST' => ['card' => ['str', 1]], 'READY' => ['card' => ['str', 1]],
        'STATS' => ['card' => ['str', 1], 'power' => ['int', 1], 'hp' => ['int', 1], 'keywords' => ['strs', 0]],
        'DRAW' => ['p' => ['seat', 1], 'count' => ['nat', 1], 'cards' => ['strs', 1]],
        'DISCARD' => ['p' => ['seat', 1], 'cards' => ['strs', 1]],
        'RESOURCE' => $pc,
        'SHIELD_GAIN' => ['card' => ['str', 1], 'count' => ['int', 0]],
        'SHIELD_USE' => ['card' => ['str', 1], 'count' => ['int', 0]],
        'EXPERIENCE_GAIN' => ['card' => ['str', 1], 'count' => ['int', 1]],
        'STATUS_TOKEN' => ['card' => ['str', 1], 'token' => ['str', 1], 'count' => ['int', 1]],
        'ROUND_START' => ['round' => ['nat', 1]],
        'PHASE_START' => ['phase' => ['str', 1]],
        'CLAIM_INITIATIVE' => ['p' => ['seat', 1]],
        // §10.2 notes
        'ATTACK' => ['p' => ['seat', 1], 'atk' => ['str', 1], 'def' => ['str', 1], 'defenderType' => ['str', 1]],
        'PASS' => ['p' => ['seat', 1]], 'MULLIGAN' => ['p' => ['seat', 1]], 'KEEP_HAND' => ['p' => ['seat', 1]], 'SHUFFLE' => ['p' => ['seat', 1]],
        'CHOICE' => ['p' => ['seat', 1], 'offered' => ['strs', 1], 'chose' => ['nat', 1], 'prompt' => ['str', 0]],
        'MODAL_CHOICE' => ['p' => ['seat', 1], 'offered' => ['strs', 1], 'chose' => ['nat', 1]],
        'SEARCH' => ['p' => ['seat', 1], 'found' => ['strs', 0], 'zone' => ['zone', 0]],
        'REVEAL' => ['p' => ['seat', 1], 'zone' => ['zone', 1], 'cards' => ['strs', 1]],
        'TRIGGER' => ['card' => ['str', 1], 'p' => ['seat', 0]],
        'PHASE_END' => ['phase' => ['str', 1]],
        'ROUND_END' => ['round' => ['nat', 1]],
        'GAME_END' => ['winner' => ['winner', 1], 'reason' => ['str', 1]],
        'UNDO' => ['at' => ['str', 1], 'by' => ['seat', 0]],
    ];
}

function SwuPgnValidate(array $doc): array
{
    $v = ['errors' => [], 'warnings' => [], 'count' => 0, 'truncated' => false];
    $headers = is_array($doc['headers'] ?? null) ? $doc['headers'] : [];
    $sections = is_array($doc['sections'] ?? null) ? $doc['sections'] : null;

    _SwuPgnValidateHeaders($v, $headers);

    // §4 sections
    foreach ((is_array($doc['dropped'] ?? null) ? $doc['dropped'] : []) as $section => $n) {
        _SwuPgnIssue($v, 'error', 'section.dropped', "$n record(s) in $section exceed the reader's limits and were not read");
    }
    if ($sections !== null) {
        foreach (array_count_values(array_filter($sections, 'is_string')) as $name => $n) {
            if ($n > 1) _SwuPgnIssue($v, 'warning', 'section.duplicate', "%%% $name appears $n times");
        }
        foreach (['DECKS', 'SETUP', 'EVENTS'] as $name) {
            if (!in_array($name, $sections, true)) _SwuPgnIssue($v, 'warning', 'section.missing', "no %%% $name section");
        }
    }

    $seqs = [];
    foreach (($doc['events'] ?? []) as $e) {
        if (is_array($e) && is_string($e['seq'] ?? null)) $seqs[$e['seq']] = true;
    }

    $hasDecks = $sections === null ? !empty($doc['decks']) : in_array('DECKS', $sections, true);
    if ($hasDecks) _SwuPgnValidateDecks($v, $doc, $headers);
    $hasSetup = $sections === null ? !empty($doc['setup']) : in_array('SETUP', $sections, true);
    if ($hasSetup) _SwuPgnValidateSetup($v, $doc);
    _SwuPgnValidateCards($v, $doc, $headers);
    _SwuPgnValidateEvents($v, $doc, $headers, $seqs);
    _SwuPgnValidateAnnotations($v, $doc, $seqs);

    if (is_array($doc['story'] ?? null)) {
        $m = SwuPgnStoryMatches($doc);
        if (!$m['matches']) _SwuPgnIssue($v, 'warning', 'story.drift', "%%% STORY differs from the rendered events at story line {$m['line']} (§16: the events are the truth)");
    }

    if ($v['truncated']) {
        $v['warnings'][] = ['code' => 'issues.truncated', 'message' => 'more than ' . SWUPGN_MAX_ISSUES . ' issues; the rest were not listed', 'line' => null, 'seq' => null];
    }
    return ['ok' => !$v['errors'], 'errors' => $v['errors'], 'warnings' => $v['warnings']];
}

// ── header (§5) ──────────────────────────────────────────────────────────────────────────────────

function _SwuPgnIsIsoUtc($v): bool
{
    return is_string($v) && (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{1,9})?)?(Z|[+-]00:?00)\z/', $v);
}

function _SwuPgnValidateHeaders(array &$v, array $h): void
{
    foreach (SwuPgnRequiredHeaderTags() as $tag) {
        if (!array_key_exists($tag, $h)) {
            _SwuPgnIssue($v, 'error', 'header.missing', "missing required header tag [$tag]");
        } elseif ($h[$tag] === '') {
            $hard = in_array($tag, ['Game', 'Engine', 'Seed'], true);
            _SwuPgnIssue($v, $hard ? 'error' : 'warning', 'header.empty', "[$tag] is empty");
        }
    }
    if (isset($h['Game']) && $h['Game'] !== '') {
        if (!preg_match('/^SWU-PGN\/\d{1,9}\.\d{1,9}\z/', $h['Game'])) {
            _SwuPgnIssue($v, 'error', 'header.game', '[Game] must be "SWU-PGN/MAJOR.MINOR"');
        } else {
            $ver = SwuPgnCheckVersion($h['Game']);
            if ($ver === 'unsupported') _SwuPgnIssue($v, 'error', 'version.major', "{$h['Game']} is a different major version; this reader reads SWU-PGN/1.x");
            elseif ($ver === 'newer-minor') _SwuPgnIssue($v, 'warning', 'version.minor', "{$h['Game']} is a newer minor version; unknown additions are ignored");
        }
    }
    $engine = $h['Engine'] ?? null;
    if (is_string($engine) && preg_match('/@unknown\z/', $engine)) _SwuPgnIssue($v, 'warning', 'header.engine-unknown', 'Engine is @unknown: this file is untraceable');
    if (is_string($engine) && preg_match('/-dirty\z/', $engine)) _SwuPgnIssue($v, 'warning', 'header.engine-dirty', 'Engine is a -dirty development build');
    if (($h['Seed'] ?? null) === 'unseeded') _SwuPgnIssue($v, 'warning', 'header.seed-unseeded', 'Seed is "unseeded": deterministic replay is impossible');
    foreach (['Date', 'EndDate'] as $tag) {
        if (array_key_exists($tag, $h) && $h[$tag] !== '' && !_SwuPgnIsIsoUtc($h[$tag])) _SwuPgnIssue($v, 'error', 'header.date', "[$tag] must be an ISO-8601 UTC time");
    }
    if (isset($h['CardPool']) && $h['CardPool'] !== '') {
        foreach (explode(',', $h['CardPool']) as $set) {
            if (!preg_match('/^[A-Za-z0-9]+\z/', $set)) {
                _SwuPgnIssue($v, 'error', 'header.cardpool', '[CardPool] must be a comma-separated list of set ids');
                break;
            }
        }
    }
    foreach (['P1Id', 'P2Id'] as $tag) {
        if (isset($h[$tag]) && !preg_match('/^sha256:[0-9a-fA-F]+\z/', $h[$tag])) _SwuPgnIssue($v, 'error', 'header.player-id', "[$tag] must look like sha256:<hex>");
    }
    foreach (['P1' => 'Player 1', 'P2' => 'Player 2'] as $tag => $generic) {
        if (isset($h[$tag]) && $h[$tag] !== $generic) _SwuPgnIssue($v, 'warning', 'header.player-name', "[$tag] should be the generic \"$generic\" (§17)");
    }
    foreach (['P1Leader', 'P1Base', 'P2Leader', 'P2Base'] as $tag) {
        if (isset($h[$tag]) && $h[$tag] !== '' && !preg_match('/^[A-Za-z0-9]+#[A-Za-z0-9]+\z/', $h[$tag])) _SwuPgnIssue($v, 'warning', 'header.card-id', "[$tag] is not a SET#NUM card id");
    }
    if (isset($h['Result']) && !in_array($h['Result'], ['P1', 'P2', 'Draw', 'Incomplete'], true)) _SwuPgnIssue($v, 'error', 'header.result', '[Result] must be P1, P2, Draw or Incomplete');
    if (isset($h['Rounds']) && !preg_match('/^\d+\z/', $h['Rounds'])) _SwuPgnIssue($v, 'error', 'header.rounds', '[Rounds] must be digits');
    if (isset($h['Perspective']) && !in_array($h['Perspective'], ['P1', 'P2'], true)) _SwuPgnIssue($v, 'error', 'header.perspective', '[Perspective] must be P1 or P2');
    foreach (['Undos', 'RecorderErrors', 'GameNumber'] as $tag) {
        if (isset($h[$tag]) && !preg_match('/^\d+\z/', $h[$tag])) _SwuPgnIssue($v, 'error', 'header.digits', "[$tag] must be digits");
    }
    if (isset($h['RecorderErrors']) && preg_match('/^\d+\z/', $h['RecorderErrors']) && (int)$h['RecorderErrors'] > 0) {
        _SwuPgnIssue($v, 'warning', 'header.recorder-errors', "the writer failed to record {$h['RecorderErrors']} handler(s): deltas between keyframes are incomplete");
    }
    if (isset($h['GameNumber']) && !isset($h['Match'])) _SwuPgnIssue($v, 'warning', 'header.game-number', '[GameNumber] is meaningless without [Match]');
}

// ── DECKS (§7), SETUP (§8), CARDS (§6.5) ─────────────────────────────────────────────────────────

function _SwuPgnLineOf(array $doc, string $section, int $i): ?int
{
    $l = $doc['lines'][$section][$i] ?? null;
    return is_int($l) ? $l : null;
}

function _SwuPgnDeckListOk($list): bool
{
    if (!is_array($list) || !array_is_list($list)) return false;
    foreach ($list as $pair) {
        if (!is_array($pair) || !array_is_list($pair) || count($pair) !== 2 || !is_string($pair[0]) || !is_int($pair[1]) || $pair[1] < 1) return false;
    }
    return true;
}

function _SwuPgnValidateDecks(array &$v, array $doc, array $h): void
{
    $decks = is_array($doc['decks'] ?? null) ? $doc['decks'] : [];
    if (count($decks) !== 2) _SwuPgnIssue($v, 'error', 'decks.count', '%%% DECKS has ' . count($decks) . ' lines; it must have one per player (two)');
    $seen = [];
    foreach ($decks as $i => $d) {
        $line = _SwuPgnLineOf($doc, 'decks', $i);
        if (!_SwuPgnIsObject($d)) {
            _SwuPgnIssue($v, 'error', 'record.not-object', 'a DECKS record is not a JSON object', $line);
            continue;
        }
        if (!is_array($d)) $d = [];
        $extra = array_diff(array_keys($d), ['p', 'leader', 'base', 'deck', 'sideboard']);
        if ($extra) _SwuPgnIssue($v, 'error', 'decks.extra', 'DECKS allows no other fields (found ' . implode(', ', $extra) . ')', $line);
        $p = $d['p'] ?? null;
        if (!_SwuPgnTypeOk($p, 'seat')) _SwuPgnIssue($v, 'error', 'decks.p', 'DECKS p must be 1 or 2', $line);
        elseif (isset($seen[$p])) _SwuPgnIssue($v, 'error', 'decks.p', "two DECKS lines for seat $p", $line);
        else $seen[$p] = true;
        foreach (['leader', 'base'] as $f) {
            if (!is_string($d[$f] ?? null)) _SwuPgnIssue($v, 'error', 'decks.field', "DECKS $f must be a string", $line);
        }
        if (!_SwuPgnDeckListOk($d['deck'] ?? null)) _SwuPgnIssue($v, 'error', 'decks.entry', 'DECKS deck must be an array of [id, count]', $line);
        if (array_key_exists('sideboard', $d) && !_SwuPgnDeckListOk($d['sideboard'])) _SwuPgnIssue($v, 'error', 'decks.entry', 'DECKS sideboard must be an array of [id, count]', $line);
        if ($p === 1 || $p === 2) {
            foreach (['leader' => "P{$p}Leader", 'base' => "P{$p}Base"] as $f => $tag) {
                if (is_string($d[$f] ?? null) && isset($h[$tag]) && $d[$f] !== $h[$tag]) _SwuPgnIssue($v, 'warning', 'decks.header-mismatch', "DECKS seat $p $f {$d[$f]} disagrees with [$tag]", $line);
            }
        }
    }
}

function _SwuPgnValidateSetup(array &$v, array $doc): void
{
    $inits = 0;
    foreach ((is_array($doc['setup'] ?? null) ? $doc['setup'] : []) as $i => $r) {
        $line = _SwuPgnLineOf($doc, 'setup', $i);
        if (!_SwuPgnIsObject($r)) {
            _SwuPgnIssue($v, 'error', 'record.not-object', 'a SETUP record is not a JSON object', $line);
            continue;
        }
        if (!is_array($r) || ($r['t'] ?? null) !== 'INIT') {
            _SwuPgnIssue($v, 'warning', 'setup.extra', 'SETUP holds one INIT and nothing else', $line);
            continue;
        }
        if (++$inits > 1) {
            _SwuPgnIssue($v, 'warning', 'setup.extra', 'more than one INIT', $line);
            continue;
        }
        if (($r['seq'] ?? null) !== 'R1.S.0') _SwuPgnIssue($v, 'error', 'setup.init', 'INIT seq must be "R1.S.0"', $line);
        foreach (['p1DeckOrder', 'p2DeckOrder'] as $f) {
            if (!_SwuPgnTypeOk($r[$f] ?? null, 'strs')) _SwuPgnIssue($v, 'error', 'setup.init', "INIT $f must be an array of card ids", $line);
        }
    }
    if ($inits === 0) _SwuPgnIssue($v, 'error', 'setup.init', '%%% SETUP has no INIT record');
}

// Ids that look like card ids (not base@N, not prompt labels), copy suffix stripped.
function _SwuPgnCollectCardIds($v, array &$out): void
{
    if (is_string($v)) {
        if (count($out) < 5000 && (strncmp($v, 'TOKEN:', 6) === 0 || preg_match('/^[A-Za-z0-9]+#[A-Za-z0-9-]+(:\d+)?\z/', $v))) $out[SwuPgnBaseId($v)] = true;
    } elseif (is_array($v)) {
        foreach ($v as $x) if (is_string($x)) _SwuPgnCollectCardIds($x, $out);
    }
}

function _SwuPgnValidateCards(array &$v, array $doc, array $h): void
{
    $cards = is_array($doc['cards'] ?? null) ? $doc['cards'] : [];
    $index = [];
    foreach ($cards as $i => $c) {
        $line = _SwuPgnLineOf($doc, 'cards', $i);
        if (!_SwuPgnIsObject($c)) {
            _SwuPgnIssue($v, 'error', 'record.not-object', 'a CARDS record is not a JSON object', $line);
            continue;
        }
        if (!is_array($c)) $c = [];
        if (!is_string($c['id'] ?? null) || !is_string($c['name'] ?? null)) {
            _SwuPgnIssue($v, 'error', 'cards.field', 'a CARDS entry needs a string id and a string name', $line);
            continue;
        }
        if (array_key_exists('kind', $c) && !_SwuPgnTypeOk($c['kind'], 'kind')) _SwuPgnIssue($v, 'error', 'cards.kind', 'CARDS kind is "unit" or "upgrade" (omit it for anything else)', $line);
        if (SwuPgnCopyNumber($c['id']) !== null) _SwuPgnIssue($v, 'warning', 'cards.copy-suffix', "CARDS id {$c['id']} carries a :N copy suffix", $line);
        if (isset($index[$c['id']])) _SwuPgnIssue($v, 'warning', 'cards.duplicate', "CARDS lists {$c['id']} twice", $line);
        $index[$c['id']] = true;
    }

    $present = isset($doc['sections']) && is_array($doc['sections']) ? in_array('CARDS', $doc['sections'], true) : (bool)$cards;
    if (!$present) return;
    $ids = [];
    foreach (['P1Leader', 'P1Base', 'P2Leader', 'P2Base'] as $tag) _SwuPgnCollectCardIds($h[$tag] ?? null, $ids);
    foreach ((is_array($doc['decks'] ?? null) ? $doc['decks'] : []) as $d) {
        if (!is_array($d)) continue;
        _SwuPgnCollectCardIds($d['leader'] ?? null, $ids);
        _SwuPgnCollectCardIds($d['base'] ?? null, $ids);
        foreach (['deck', 'sideboard'] as $k) {
            foreach ((is_array($d[$k] ?? null) ? $d[$k] : []) as $pair) if (is_array($pair)) _SwuPgnCollectCardIds($pair[0] ?? null, $ids);
        }
    }
    foreach ((is_array($doc['events'] ?? null) ? $doc['events'] : []) as $e) {
        if (!is_array($e)) continue;
        foreach (['card', 'token', 'atk', 'def', 'tgt', 'src', 'defeatedBy', 'by', 'target', 'attachedTo', 'cards', 'found'] as $k) _SwuPgnCollectCardIds($e[$k] ?? null, $ids);
    }
    $missing = array_keys(array_diff_key($ids, $index));
    if ($missing) {
        _SwuPgnIssue($v, 'warning', 'cards.coverage', count($missing) . ' id(s) the file mentions are not in %%% CARDS (e.g. ' . implode(', ', array_slice($missing, 0, 5)) . ')');
    }
}

// ── EVENTS (§9, §10) ─────────────────────────────────────────────────────────────────────────────

function _SwuPgnValidateEvents(array &$v, array $doc, array $h, array $seqs): void
{
    $shapes = _SwuPgnEventShapes();
    $boardTypes = ['MOVE', 'PLAY', 'PLAY_SMUGGLE', 'PLAY_EVENT', 'PLAY_UPGRADE', 'DEPLOY_LEADER', 'ABILITY_ACTIVATE', 'LEADER_FLIP',
        'TAKE_CONTROL', 'CAPTURE', 'RESCUE', 'EXHAUST_RESOURCES', 'READY_RESOURCES', 'CREATE_TOKEN', 'DAMAGE', 'OVERWHELM', 'HEAL',
        'DEFEAT', 'EXHAUST', 'READY', 'STATS', 'DRAW', 'DISCARD', 'RESOURCE', 'SHIELD_GAIN', 'SHIELD_USE', 'EXPERIENCE_GAIN',
        'STATUS_TOKEN', 'ROUND_START', 'PHASE_START', 'CLAIM_INITIATIVE'];
    $seen = [];
    $unknownSeen = [];
    $undos = 0;
    $newerMinor = SwuPgnCheckVersion($h['Game'] ?? null) === 'newer-minor';

    foreach ((is_array($doc['events'] ?? null) ? $doc['events'] : []) as $i => $e) {
        $line = _SwuPgnLineOf($doc, 'events', $i);
        if (!_SwuPgnIsObject($e)) {
            _SwuPgnIssue($v, 'error', 'record.not-object', 'an EVENTS record is not a JSON object', $line);
            continue;
        }
        if (!is_array($e)) $e = [];
        $seq = is_string($e['seq'] ?? null) ? $e['seq'] : null;
        $issue = function (string $level, string $code, string $msg) use (&$v, $line, $seq) { _SwuPgnIssue($v, $level, $code, $msg, $line, $seq); };

        // §9 / §9.1 seq
        if ($seq === null) {
            $issue('error', 'event.seq', 'every event needs a string seq');
        } else {
            if (!preg_match('/^R\d+\.(S|A|G)(\.[A-Za-z0-9-]+)?\z|^R\d+\.(start|end)\z/', $seq)) $issue('error', 'event.seq-pattern', "seq \"$seq\" does not match the §9.1 pattern");
            if (isset($seen[$seq])) $issue('error', 'event.seq-duplicate', "seq $seq is used twice");
            $seen[$seq] = true;
        }
        $t = $e['t'] ?? null;
        if (!is_string($t)) {
            $issue('error', 'event.t', 'every event needs a string t');
            continue;
        }

        // §5.2.1 ms
        if (array_key_exists('ms', $e)) {
            if (!is_int($e['ms']) || $e['ms'] < 0) $issue('error', 'event.ms', 'ms is a non-negative integer duration from Date');
            elseif (!_SwuPgnIsNumbered($e) && $t !== 'ROUND_START' && $t !== 'PHASE_START') $issue('error', 'event.ms', "ms is only allowed on numbered actions, ROUND_START and PHASE_START (not $t)");
        }
        // §9.1 for
        if (array_key_exists('for', $e)) {
            if (!is_string($e['for'])) $issue('error', 'event.field', 'for must be a string seq');
            elseif (!isset($seqs[$e['for']])) $issue('warning', 'event.for', "for names {$e['for']}, which is not in the file");
        }

        if (!isset($shapes[$t])) {
            if (!isset($unknownSeen[$t]) && count($unknownSeen) < 100) {
                $unknownSeen[$t] = true;
                $issue('warning', 'event.unknown-type', "unknown event type $t (ignored by the fold)");
            }
            continue;
        }
        $board = in_array($t, $boardTypes, true);
        foreach ($shapes[$t] as $field => [$type, $required]) {
            if (!array_key_exists($field, $e)) {
                if ($required) $issue($board ? 'error' : 'warning', 'event.required', "$t is missing $field");
                continue;
            }
            if (_SwuPgnTypeOk($e[$field], $type)) continue;
            if ($type === 'zone' && is_string($e[$field])) $issue('error', 'event.zone', "$t.$field \"{$e[$field]}\" is not a §6.2 zone");
            else $issue('error', 'event.field', "$t.$field must be " . _SwuPgnTypeName($type));
        }

        // §6.3 base refs, §6.1 token ids
        foreach (['tgt', 'def', 'by', 'card', 'src', 'atk', 'target', 'attachedTo', 'token', 'defeatedBy'] as $f) {
            $ref = $e[$f] ?? null;
            if (!is_string($ref)) continue;
            if (strncmp($ref, 'base@', 5) === 0 && !in_array(SwuPgnSeatOfBaseRef($ref), [1, 2], true)) $issue('error', 'base-ref', "$t.$f \"$ref\" is not base@1 or base@2");
            $tok = SwuPgnTokenParts($ref);
            if ($tok !== null && $tok['cardId'] !== null && !$tok['resolvable']) $issue('warning', 'token-id', "$t.$f \"$ref\" has a non-numeric card id (use the degraded TOKEN:<name> form)");
        }
        if (is_array($e['offered'] ?? null)) {
            foreach ($e['offered'] as $ref) {
                if (is_string($ref) && strncmp($ref, 'base@', 5) === 0 && !in_array(SwuPgnSeatOfBaseRef($ref), [1, 2], true)) $issue('error', 'base-ref', "$t.offered \"$ref\" is not base@1 or base@2");
            }
        }

        _SwuPgnValidateEventRules($issue, $t, $e, $seq, $newerMinor);
        if ($t === 'UNDO') $undos++;
    }

    // §5.2 Undos is authoritative and never below the UNDO notes (absent = none).
    $declared = isset($h['Undos']) && preg_match('/^\d+\z/', $h['Undos']) ? (int)$h['Undos'] : 0;
    if ($undos > $declared) _SwuPgnIssue($v, 'error', 'undo.count', "the file has $undos UNDO record(s) but [Undos] says $declared");
}

// Rules specific to one event type.
function _SwuPgnValidateEventRules(callable $issue, string $t, array $e, ?string $seq, bool $newerMinor = false): void
{
    switch ($t) {
        case 'MOVE':
            $from = $e['from'] ?? null;
            $to = $e['to'] ?? null;
            if (is_string($from) && $from === $to) $issue('error', 'move.same-zone', 'a MOVE\'s from must differ from its to');
            if ($from === 'outsideTheGame' && $to === 'deck') $issue('warning', 'move.deck-build', 'deck construction is not recorded as MOVEs');
            $attaching = ($e['kind'] ?? null) === 'upgrade' && SwuPgnIsArena($to);
            if ($attaching && !array_key_exists('attachedTo', $e)) $issue('error', 'move.attached-to', 'an attaching MOVE into an arena must name its host with attachedTo');
            if (!$attaching && array_key_exists('attachedTo', $e)) $issue('error', 'move.attached-to', 'attachedTo belongs only on the attaching move into an arena (exits are host-less)');
            if (array_key_exists('exhausted', $e) && $from !== 'resource') $issue('warning', 'move.exhausted', 'exhausted belongs only on a MOVE out of resource');
            break;
        case 'DEPLOY_LEADER':
            $pilot = ($e['kind'] ?? null) === 'upgrade';
            if ($pilot && !is_string($e['target'] ?? null)) $issue('error', 'deploy.target', 'a pilot deploy (kind "upgrade") must name its target');
            if (!$pilot && array_key_exists('target', $e)) $issue('warning', 'deploy.target', 'target without kind "upgrade"');
            break;
        case 'ABILITY_ACTIVATE':
            $kind = $e['kind'] ?? null;
            if (is_string($kind) && !in_array($kind, ['action', 'epic', 'triggered', 'keyword', 'replacement', 'constant', 'event', 'delayed'], true)) $issue('warning', 'ability.kind', "ABILITY_ACTIVATE kind \"$kind\" is not one of the eight");
            if ($kind === 'epic' && ($e['epic'] ?? null) !== true) $issue('warning', 'ability.epic', 'an Epic Action must carry epic: true');
            break;
        case 'DEFEAT':
            $reason = $e['reason'] ?? null;
            // Closed for this version; a newer minor may add values, which a reader treats as "unknown".
            if (is_string($reason) && !in_array($reason, SwuPgnDefeatReasons(), true)) {
                $issue($newerMinor ? 'warning' : 'error', 'defeat.reason', "DEFEAT reason \"$reason\" is outside the closed set" . ($newerMinor ? ' (read as "unknown")' : ''));
            }
            if ($reason === 'unknown') $issue('warning', 'defeat.unknown', 'DEFEAT reason "unknown": the writer could not resolve a cause');
            break;
        case 'DAMAGE':
            if (($e['damageType'] ?? null) === 'overwhelm') $issue('error', 'damage.overwhelm', 'overwhelm onto a base is its own OVERWHELM record');
            break;
        case 'CREATE_TOKEN':
            if (_SwuPgnIsZone($e['zone'] ?? null) && !SwuPgnIsArena($e['zone'])) $issue('error', 'create-token.zone', 'CREATE_TOKEN zone must name the destination arena');
            if (is_string($e['token'] ?? null) && strncmp($e['token'], 'TOKEN:', 6) !== 0) $issue('warning', 'token-id', 'a token id must start with TOKEN:');
            break;
        case 'STATS':
            $kw = $e['keywords'] ?? null;
            if (_SwuPgnTypeOk($kw, 'strs')) {
                $sorted = $kw;
                sort($sorted, SORT_STRING);
                if ($sorted !== $kw) $issue('warning', 'stats.keywords-order', 'STATS keywords should be sorted');
            }
            break;
        case 'STATUS_TOKEN':
            if (in_array($e['token'] ?? null, ['shield', 'experience'], true)) $issue('warning', 'status-token.name', 'shields and experience have their own records');
            break;
        case 'UNDO':
            if (is_string($e['at'] ?? null) && $seq !== null && $seq !== $e['at'] . '-undo') $issue('error', 'undo.seq', "an UNDO's seq must be its at plus -undo ({$e['at']}-undo)");
            break;
        case 'GAME_END':
            if ($seq !== null && !preg_match('/\.game-end\z/', $seq)) $issue('warning', 'game-end.seq', 'GAME_END takes its own .game-end step');
            break;
        case 'ROUND_START':
        case 'ROUND_END':
            if (!array_key_exists('keyframe', $e)) break;
            $problem = SwuPgnKeyframeProblem($e['keyframe']);
            if ($problem !== null) {
                $issue('error', 'keyframe.damaged', "damaged keyframe: $problem (readers ignore it)");
                break;
            }
            foreach (array_slice(_SwuPgnKeyframeFieldProblems($e['keyframe']), 0, 20) as $msg) $issue('error', 'keyframe.field', $msg);
            break;
    }
}

// §11 types for a keyframe that is otherwise complete.
function _SwuPgnKeyframeFieldProblems(array $kf): array
{
    $out = [];
    $chk = function (array $obj, string $prefix, array $spec) use (&$out) {
        foreach ($spec as $f => [$type, $required]) {
            if (!array_key_exists($f, $obj)) {
                if ($required) $out[] = "$prefix$f is missing";
                continue;
            }
            $ok = $type === 'initiative' ? ($obj[$f] === null || $obj[$f] === 1 || $obj[$f] === 2) : _SwuPgnTypeOk($obj[$f], $type);
            if (!$ok) $out[] = "$prefix$f must be " . ($type === 'initiative' ? '1, 2 or null' : _SwuPgnTypeName($type));
        }
    };
    $chk($kf, '', ['round' => ['nat', 0], 'phase' => ['str', 0], 'initiative' => ['initiative', 0], 'initiativeTaken' => ['bool', 0], 'active' => ['seat', 0]]);
    foreach ([1, 2] as $s) {
        $pl = $kf['players'][$s];
        $pre = "players.$s.";
        $chk($pl, $pre, ['seat' => ['seat', 0], 'baseHp' => ['int', 1], 'baseMaxHp' => ['int', 1], 'handSize' => ['nat', 1],
            'hand' => ['strs', 1], 'deckSize' => ['nat', 0], 'resourcesReady' => ['nat', 1], 'resourcesExhausted' => ['nat', 1],
            'resources' => ['strs', 0], 'baseEpicActionUsed' => ['bool', 0], 'credits' => ['nat', 1], 'hasForce' => ['bool', 1],
            'discard' => ['strs', 1], 'leader' => ['obj', 0]]);
        if (is_array($pl['leader'] ?? null)) {
            $chk($pl['leader'], $pre . 'leader.', ['id' => ['str', 1], 'deployed' => ['bool', 1], 'exhausted' => ['bool', 1], 'epicActionUsed' => ['bool', 1], 'onStartingSide' => ['bool', 0]]);
        }
        foreach ($pl['cards'] as $i => $c) {
            $c = is_array($c) ? $c : [];
            $cp = $pre . 'cards[' . (is_string($c['id'] ?? null) ? $c['id'] : $i) . '].';
            $chk($c, $cp, ['id' => ['str', 1], 'zone' => ['arena', 1], 'damage' => ['nat', 1], 'exhausted' => ['bool', 1],
                'upgrades' => ['strs', 0], 'shields' => ['nat', 0], 'experience' => ['nat', 0], 'captured' => ['strs', 0],
                'power' => ['int', 0], 'hp' => ['int', 0], 'keywords' => ['strs', 0]]);
            if (array_key_exists('statusTokens', $c)) {
                $st = $c['statusTokens'];
                $ok = $st instanceof stdClass || (is_array($st) && !array_is_list($st) && count(array_filter($st, function ($n) { return is_int($n) && $n > 0; })) === count($st));
                if (!$ok) $out[] = "{$cp}statusTokens must be an object of positive counts";
            }
        }
    }
    return $out;
}

// ── ANNOTATIONS (§15) ────────────────────────────────────────────────────────────────────────────

function _SwuPgnValidateAnnotations(array &$v, array $doc, array $seqs): void
{
    $notes = is_array($doc['annotations'] ?? null) ? $doc['annotations'] : [];
    $ids = [];
    foreach ($notes as $n) {
        if (is_array($n) && is_string($n['id'] ?? null)) $ids[$n['id']] = true;
    }
    foreach ($notes as $i => $n) {
        $line = _SwuPgnLineOf($doc, 'annotations', $i);
        if (!_SwuPgnIsObject($n)) {
            _SwuPgnIssue($v, 'error', 'record.not-object', 'an ANNOTATIONS record is not a JSON object', $line);
            continue;
        }
        if (!is_array($n)) $n = [];
        $extra = array_diff(array_keys($n), ['ref', 'nag', 'text', 'by', 'line', 'id', 'parent', 'ts']);
        if ($extra) _SwuPgnIssue($v, 'error', 'annotation.extra', 'ANNOTATIONS allows no other fields (found ' . implode(', ', $extra) . ')', $line);
        $ref = $n['ref'] ?? null;
        if (!is_string($ref)) _SwuPgnIssue($v, 'error', 'annotation.ref', 'a note needs a string ref', $line);
        elseif (!isset($seqs[$ref])) _SwuPgnIssue($v, 'error', 'annotation.ref', "a note points at $ref, which is not a seq in the file", $line, $ref);
        foreach (['nag', 'text', 'by', 'id', 'parent'] as $f) {
            if (array_key_exists($f, $n) && !is_string($n[$f])) _SwuPgnIssue($v, 'error', 'annotation.field', "annotation $f must be a string", $line);
        }
        if (array_key_exists('ts', $n) && !is_int($n['ts'])) _SwuPgnIssue($v, 'error', 'annotation.field', 'annotation ts must be an integer (epoch ms)', $line);
        if (array_key_exists('line', $n) && (!is_array($n['line']) || !array_is_list($n['line']) || count(array_filter($n['line'], '_SwuPgnIsObject')) !== count($n['line']))) {
            _SwuPgnIssue($v, 'error', 'annotation.field', 'annotation line must be an array of events', $line);
        }
        if (is_string($n['parent'] ?? null) && !isset($ids[$n['parent']])) _SwuPgnIssue($v, 'warning', 'annotation.parent', "parent {$n['parent']} names no note in the file (shown top-level)", $line);
    }
}
