<?php
// SWU-PGN/1.0 spec §2.1 levels + cross-record rules, §3 [V-01]…[V-09], §5 header formats, §6 vocabularies and the
// token grammar, §10 record shapes, §18 versions, §24 error model: what SwuPgnValidate reports, and at what level.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_validate.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$ids = ['P1Id' => 'sha256:' . str_repeat('a', 64), 'P2Id' => 'sha256:' . str_repeat('b', 64)];
$decks = [
    ['p' => 1, 'leader' => 'SOR#010', 'base' => 'SOR#028', 'deck' => [['SOR#108', 5]]],
    ['p' => 2, 'leader' => 'SOR#005', 'base' => 'SOR#020', 'deck' => [['SOR#045', 5]], 'sideboard' => [['SOR#046', 2]]],
];
$cardsIdx = [
    ['id' => 'SOR#005', 'name' => 'Darth Vader'], ['id' => 'SOR#010', 'name' => 'Luke'], ['id' => 'SOR#020', 'name' => 'Command Center'],
    ['id' => 'SOR#028', 'name' => 'Echo Base'], ['id' => 'SOR#045', 'name' => 'Cell Block Guard', 'kind' => 'unit'],
    ['id' => 'SOR#046', 'name' => 'Guard Two', 'kind' => 'unit'], ['id' => 'SOR#108', 'name' => 'Wampa', 'kind' => 'unit'],
];
$setup = [['seq' => 'R0.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#108'], 'p2DeckOrder' => ['SOR#045']]];
$baseEvents = [
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action', 'ms' => 10],
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2, 'ms' => 1500],
    ['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 2],
];
// $o: header (overrides), events (replace), extra (append), decks/cards/setup/annotations (replace; null omits), story,
// append (raw text), opts (validator options).
function T(array $o = []): string
{
    global $decks, $cardsIdx, $setup, $baseEvents, $ids;
    $opts = ['header' => ($o['header'] ?? []) + $ids];
    foreach (['decks' => $decks, 'cards' => $cardsIdx, 'setup' => $setup] as $k => $def) {
        $v = array_key_exists($k, $o) ? $o[$k] : $def;
        if ($v !== null) $opts[$k] = $v;
    }
    if (isset($o['annotations'])) $opts['annotations'] = $o['annotations'];
    if (isset($o['story'])) $opts['story'] = $o['story'];
    return SwuPgnTestFile(array_merge($o['events'] ?? $baseEvents, $o['extra'] ?? []), $opts) . ($o['append'] ?? '');
}
function V(array $o = []): array { return SwuPgnValidate(SwuPgnParse(T($o)), $o['opts'] ?? []); }
function codes(array $issues): array { return array_values(array_unique(array_column($issues, 'code'))); }
// $code is reported, in the list its §24 severity puts it in, and not in the other.
function expectIssue(array $r, string $code, string $what): void
{
    $sev = SwuPgnIssueCodes()[$code][0] ?? '?';
    $list = $sev === 'warning' ? 'warnings' : 'errors';
    $other = $sev === 'warning' ? 'errors' : 'warnings';
    $ok = in_array($code, codes($r[$list]), true) && !in_array($code, codes($r[$other]), true);
    SwuPgnTestCheck($ok, "$what → $code", ['level' => $r['level'], 'errors' => codes($r['errors']), 'warnings' => codes($r['warnings'])]);
}
// Exactly these codes and this level.
function expectExactly(array $r, array $errors, array $warnings, string $level, string $what): void
{
    $got = [codes($r['errors']), codes($r['warnings']), $r['level']];
    sort($got[0]); sort($got[1]); sort($errors); sort($warnings);
    SwuPgnTestEq($got, [$errors, $warnings, $level], $what);
}
function clean(array $r, string $what): void { expectExactly($r, [], [], 'complete', $what); }

// ── a clean file, the result shape, levels ───────────────────────────────────────────────────────────────────────
$r = V();
SwuPgnTestEq([$r['ok'], $r['level'], $r['errors'], $r['warnings'], $r['notices']], [true, 'complete', [], [], []], 'a clean hand-made file: ok, complete, nothing to say');
SwuPgnTestEq(array_keys($r), ['ok', 'level', 'errors', 'warnings', 'notices'], 'result shape: ok / level / errors / warnings / notices');
SwuPgnTestEq(SwuPgnLevels(), ['malformed', 'well-formed', 'valid', 'verified', 'complete'], '§2.1: five levels, lowest first');
$codes = SwuPgnIssueCodes();
SwuPgnTestEq([count($codes), count(array_filter($codes, fn($c) => $c[0] === 'fatal')), count(array_filter($codes, fn($c) => $c[0] === 'error')), count(array_filter($codes, fn($c) => $c[0] === 'warning'))],
    [43, 16, 17, 10], '§24: 43 codes — 16 fatal, 17 errors, 10 warnings');

// ── header (§5) ──────────────────────────────────────────────────────────────────────────────────────────────────
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.3']]), [], ['newer-minor'], 'complete', '§18: a higher MINOR is accepted and noted (no level cap)');
SwuPgnTestEq([SwuPgnCheckVersion('SWU-PGN/1.0'), SwuPgnCheckVersion('SWU-PGN/1.7'), SwuPgnCheckVersion('SWU-PGN/2.0'), SwuPgnCheckVersion('SWU-PGN/0.9'), SwuPgnCheckVersion(null)], ['ok', 'newer-minor', 'unsupported', 'unsupported', 'unsupported'], '§18 SwuPgnCheckVersion');
$doc = SwuPgnParse(T());
foreach ([['Game', 'SWU-PGN/2.0', 'unsupported-major'], ['Rounds', null, 'missing-tag'], ['Rounds', 'two', 'bad-count'], ['toString', 'x', 'reserved-key']] as [$tag, $val, $code]) {
    $d = $doc;
    if ($val === null) unset($d['headers'][$tag]); else $d['headers'][$tag] = $val;
    $r = SwuPgnValidate($d);
    SwuPgnTestCheck(in_array($code, codes($r['errors']), true) && $r['level'] === 'malformed' && !$r['ok'], "a doc built in code is held to the parser's rules: $tag → $code, malformed");
}
expectIssue(V(['header' => ['Engine' => 'nope']]), 'schema', '§5.1: Engine is <product>@<build>');
expectIssue(V(['header' => ['Engine' => '']]), 'schema', '§5.3: Engine is non-empty');
expectIssue(V(['header' => ['Seed' => '']]), 'schema', '§5.3: Seed is non-empty');
expectIssue(V(['header' => ['GameId' => '']]), 'schema', '§5.1: GameId is 1–128 characters');
expectIssue(V(['header' => ['GameId' => str_repeat('é', 129)]]), 'schema', '§5.1: … and no more than 128');
clean(V(['header' => ['GameId' => str_repeat('é', 128)]]), '§5.1: 128 characters (not bytes) is fine');
expectIssue(V(['header' => ['Date' => 'yesterday']]), 'schema', '§5.1: Date is RFC 3339');
expectIssue(V(['header' => ['Date' => '2026-09-04T15:58Z']]), 'schema', '§5: Date has a seconds field');
expectIssue(V(['header' => ['Date' => '2026-02-30T00:00:00Z']]), 'schema', 'RFC 3339: a real calendar date');
expectIssue(V(['header' => ['Date' => '2026-09-04T15:58:16']]), 'schema', '§5: Date has an explicit offset');
clean(V(['header' => ['Date' => '2026-09-04T15:58:16+02:00', 'EndDate' => '2026-09-04T16:30:00.695Z']]), '§5: any explicit offset, fractional seconds, EndDate');
expectIssue(V(['header' => ['EndDate' => '16:30']]), 'schema', '§5.2: EndDate is RFC 3339');
expectIssue(V(['header' => ['CardPool' => 'SOR,,JTL']]), 'schema', '§5.1: CardPool is set codes joined by ","');
expectIssue(V(['header' => ['CardPool' => 'sor']]), 'schema', '§6.1: set codes are upper case');
clean(V(['header' => ['CardPool' => 'SOR,SHD,TWI,JTL,LOF,IBH,SEC,LAW,TS26,ASH,HMW,IC27,ZZZ9']]), '§6.1: set codes with digits, and an unknown one (a registry is open)');
expectIssue(V(['header' => ['P1Id' => 'claudebot1']]), 'schema', '§17: P1Id is sha256: + 64 hex');
expectIssue(V(['header' => ['P2Id' => 'sha256:bbbb']]), 'schema', '§17: … all 64 of them');
expectIssue(V(['header' => ['P2Id' => 'sha256:' . str_repeat('B', 64)]]), 'schema', '§17: … lowercase');
expectIssue(V(['header' => ['Match' => 'lobby-17']]), 'schema', '§5.2: Match is sha256: + 64 hex');
expectIssue(V(['header' => ['P1Leader' => 'Luke']]), 'schema', '§5.1: P1Leader is a card id');
expectIssue(V(['header' => ['P2Base' => 'SOR#20']]), 'schema', '§6.1: NUM is zero-padded to three digits');
expectIssue(V(['header' => ['Result' => 'Win']]), 'schema', '§18: an unknown Result outcome in the file\'s own minor');
expectIssue(V(['header' => ['Result' => 'P3']]), 'schema', '§18: P3 is a seat, an error in any minor');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.7', 'Result' => 'Abandoned']]), [], ['newer-minor', 'unknown-value'], 'complete', '§18: an unknown outcome in a newer minor is a warning (read as Incomplete)');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.7', 'Result' => 'P3']]), ['schema'], ['newer-minor'], 'well-formed', '§18: … but P3 stays an error');
expectIssue(V(['header' => ['Perspective' => 'P3']]), 'schema', '§5.2: Perspective is P1 or P2');
expectIssue(V(['header' => ['WriterRevision' => '0']]), 'schema', '§5.2: WriterRevision is 1 or more');
expectIssue(V(['header' => ['GameNumber' => '0']]), 'schema', '§5.2: GameNumber is 1 or more');
expectExactly(V(['header' => ['RecorderErrors' => '2']]), [], ['recorder-errors'], 'verified', '§2.1: RecorderErrors above zero → at most verified');
clean(V(['header' => ['RecorderErrors' => '0', 'Undos' => '3', 'Format' => 'pReMiEr', 'Bogus' => 'x', 'Xmine' => 'y', 'P1' => 'Someone']]), '§5.2: zero RecorderErrors, Undos with no UNDO notes, any Format, unknown tags');

// [V-04] pre-release: the registry is EMPTY in this build (§25: the owner fills it in); a made-up product proves the rule.
SwuPgnTestEq(SWUPGN_PILOT_WRITER_PRODUCTS, [], '§25: the pilot writer registry constant ships empty');
$pilot = ['opts' => ['pilotProducts' => ['pilotwriter']]];
expectExactly(V(['header' => ['Engine' => 'pilotwriter@abc123']] + $pilot), [], ['pre-release'], 'well-formed', '[V-04]: no WriterRevision + a listed product → pre-release, at most well-formed');
clean(V(['header' => ['Engine' => 'pilotwriter@abc123', 'WriterRevision' => '6']] + $pilot), '[V-04]: with a WriterRevision it is an ordinary file');
clean(V(['header' => ['Engine' => 'PilotWriter@abc123']] + $pilot), '§25: the product compares exactly, case included');
clean(V(['header' => ['Engine' => 'pilotwriter-fork@abc123']] + $pilot), '§25: … the whole product before the first @');
clean(V(['header' => ['Engine' => 'pilotwriter@abc123']]), 'the default (empty) registry lists nobody');

// ── sections ─────────────────────────────────────────────────────────────────────────────────────────────────────
expectExactly(V(['append' => "\n%%% NOTES\nanything at all\n"]), [], ['unknown-section'], 'complete', '[V-03]: an unknown section warns');
clean(V(['append' => "\n%%% X_NOTES\nanything\n%%% x_more\n{}\n"]), '[V-03]: … not one with the X_ prefix (either case)');
clean(V(['decks' => null, 'setup' => null, 'cards' => null]), '§4: DECKS, CARDS and SETUP are optional');

// ── every record: not-object, number-range, reserved keys ────────────────────────────────────────────────────────
expectIssue(V(['extra' => [[1, 2]]]), 'not-object', '§4: a [ line in a section reaches the JSON path and is not an object');
expectIssue(V(['decks' => [$decks[0], 'deck']]), 'not-object', '§24: … in any section');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'X_NOTE', '0' => 'x']]]), 'a record with numeric-looking keys is still an object');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PASS', 'p' => 1, 'x_score' => 1.5]]]), 'number-range', '§2.1 rule 5: a fraction, even in an extension field');
expectIssue(V(['decks' => [$decks[0], ['x_rank' => 9007199254740992] + $decks[1]]]), 'number-range', 'rule 5: 2^53 in DECKS');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#300', 'name' => 'X', 'x_n' => -9007199254740992]])]), 'number-range', 'rule 5: −2^53 in CARDS');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'ts' => 1e300]]]), 'number-range', 'rule 5: 1e300 in ANNOTATIONS');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PASS', 'p' => 1, 'x_n' => 9007199254740991, 'x_m' => -9007199254740991]]]), 'rule 5: ±(2^53−1) is in range');
$d = SwuPgnParse(T());
$d['events'][0]['constructor'] = 1;
expectIssue(SwuPgnValidate($d), 'reserved-key', '[P-06]: a doc built in code with a reserved key');

// ── DECKS (§7), CARDS (§6.5), SETUP (§8) ─────────────────────────────────────────────────────────────────────────
expectIssue(V(['decks' => [$decks[0], ['leader' => 'SOR#005']]]), 'schema', '§7: p is required');
expectIssue(V(['decks' => [$decks[0], ['p' => 3] + $decks[1]]]), 'schema', '§7: p is 1 or 2');
expectIssue(V(['decks' => [$decks[0], $decks[0]]]), 'schema', '§7: one line per player');
expectIssue(V(['decks' => [$decks[0], ['deck' => [['SOR#045', '5']]] + $decks[1]]]), 'schema', '§7: deck is [id, count]');
expectIssue(V(['decks' => [$decks[0], ['leader' => 5] + $decks[1]]]), 'schema', '§7: leader is a string');
clean(V(['decks' => [$decks[0], ['color' => 'red', 'x_note' => 'y'] + $decks[1]]]), '§7: DECKS is open — unknown fields are ignored');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#200']])]), 'card-index', '§24: a CARDS entry with no name');
expectIssue(V(['cards' => array_merge($cardsIdx, [['name' => 'X']])]), 'card-index', '§24: … or no id');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#200', 'name' => 'X', 'kind' => 'event']])]), 'schema', '§25: CARDS kind is closed');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.2'], 'cards' => array_merge($cardsIdx, [['id' => 'SOR#200', 'name' => 'X', 'kind' => 'event']])]), [], ['newer-minor', 'unknown-value'], 'complete', '[V-02]: … an unknown kind in a newer minor is a warning');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#108:2', 'name' => 'Wampa']])]), 'schema', '§6.5: a CARDS id is a base id');
clean(V(['cards' => array_slice($cardsIdx, 1)]), '§6.5: CARDS need not cover every id');
clean(V(['setup' => [['seq' => 'R1.S.0'] + $setup[0]]]), 'Appendix C: INIT at R1.S.0 is accepted');
expectIssue(V(['setup' => [['seq' => 'R2.S.0'] + $setup[0]]]), 'schema', '§8: INIT seq is R0.S.0');
expectIssue(V(['setup' => [['p1DeckOrder' => 'SOR#108'] + $setup[0]]]), 'schema', '§8: p1DeckOrder is string[]');
expectIssue(V(['setup' => [$setup[0], ['seq' => 'R0.S.9'] + $setup[0]]]), 'schema', '§8: one INIT');
expectIssue(V(['setup' => [['seq' => 'R0.S.1', 't' => 'SHUFFLE', 'p' => 1]]]), 'schema', '§8: a SETUP section holds an INIT');
expectIssue(V(['setup' => [$setup[0], ['seq' => 'R1.A.1', 't' => 'SHUFFLE', 'p' => 1]]]), 'duplicate-seq', '§2.1 rule 1: seqs are unique across SETUP and EVENTS');

// ── EVENTS: seq, t, for, ms, unknown types ───────────────────────────────────────────────────────────────────────
expectIssue(V(['extra' => [['t' => 'PASS', 'p' => 1]]]), 'schema', '§9: every event has a seq');
expectIssue(V(['extra' => [['seq' => 'R1.A.3']]]), 'schema', '§9: every event has a t');
expectIssue(V(['extra' => [['seq' => 'round1-3', 't' => 'PASS', 'p' => 1]]]), 'schema', '§9.1: seq matches the schema pattern');
expectIssue(V(['extra' => [['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 1]]]), 'duplicate-seq', '§2.1 rule 1: a seq used twice');
clean(V(['extra' => [['seq' => 'R1.A.game-end', 't' => 'GAME_END', 'winner' => 1, 'reason' => 'Base Destroyed'], ['seq' => 'R1.A.end', 't' => 'PHASE_END', 'phase' => 'action'], ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'active' => 1], ['seq' => 'R0.S.5', 't' => 'SHUFFLE', 'p' => 1], ['seq' => 'R2.end-undo', 't' => 'UNDO', 'at' => 'R2.end']], 'header' => ['Result' => 'P1']]),
    '§9.1: game-end, edges, setup steps and -undo are valid seqs');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'EXHAUST', 'card' => 'SOR#108', 'for' => 'R9.A.9']]]), 'dangling-ref', '§2.1 rule 2: for names a seq in the file');
clean(V(['extra' => [['seq' => 'R1.A.3a', 't' => 'EXHAUST', 'card' => 'SOR#108', 'for' => 'R1.A.4'], ['seq' => 'R1.A.4', 't' => 'PASS', 'p' => 1]]]), '§9.1: for may point forward');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'EXHAUST', 'card' => 'SOR#108', 'for' => 4]]]), 'schema', '§9.1: for is a string');
clean(V(['extra' => [['seq' => 'R1.A.9-undo', 't' => 'UNDO', 'at' => 'R1.A.9', 'by' => 1]], 'header' => ['Undos' => '1']]), '§2.1: an UNDO\'s at is exempt from rule 2');
expectIssue(V(['extra' => [['seq' => 'R1.A.2-undo', 't' => 'UNDO', 'at' => 'R1.A.3']]]), 'schema', '§10.2: an UNDO\'s seq is its at plus -undo');
expectIssue(V(['extra' => [['seq' => 'R1.A.2a', 't' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat', 'hp' => 26, 'ms' => 5]]]), 'schema', '§5.2.1: a lettered consequence carries no ms');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PASS', 'p' => 1, 'ms' => '5s']]]), 'schema', '§5.2.1: ms is an integer');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'action', 'ms' => 9], ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'active' => 2, 'ms' => 99]]]), '§5.2.1: ms on an action ability and a ROUND_START');
expectExactly(V(['extra' => [['seq' => 'R1.A.3', 't' => 'FROBNICATE', 'p' => 1], ['seq' => 'R1.A.4', 't' => 'FROBNICATE']]]), [], ['unknown-type'], 'complete', '[V-03]: an unknown type is a warning (once), never an error');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'X_FROB', 'p' => 9]]]), '[V-03]: … and none for the X_ prefix');

// ── record shapes (§10) ──────────────────────────────────────────────────────────────────────────────────────────
$x = function (array $e) { return ['extra' => [['seq' => 'R1.A.3'] + $e]]; };
expectIssue(V($x(['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => '4', 'damageType' => 'combat', 'hp' => 26])), 'schema', '§10.1: amt is an integer');
expectIssue(V($x(['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat'])), 'schema', '§10.1: DAMAGE.hp is required');
clean(V($x(['t' => 'DAMAGE', 'tgt' => 'base@2', 'amt' => 4, 'hp' => 26])), '§9: DAMAGE.damageType (and src) are not schema-required');
expectIssue(V($x(['t' => 'DAMAGE', 'src' => 'Framework effect', 'tgt' => 'base@2', 'amt' => 4, 'hp' => 26])), 'schema', '[W-32]: src is a card id or base@N');
expectIssue(V($x(['t' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => 'SOR#108'])), 'schema', '§10.1: DRAW cards is an array');
expectIssue(V($x(['t' => 'PASS', 'p' => 3])), 'schema', '§18: p is 1 or 2');
expectIssue(V($x(['t' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108'])), 'schema', '§10.2: ATTACK def is listed (not `?`)');
expectIssue(V($x(['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => -2])), 'schema', 'EXHAUST_RESOURCES amount is not negative');
expectIssue(V($x(['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@3', 'amt' => 4, 'hp' => 26])), 'schema', '§6.3/§18: base@3 is no seat');
expectIssue(V($x(['t' => 'CHOICE', 'p' => 1, 'offered' => ['base@0'], 'chose' => 0])), 'schema', '§6.3: in CHOICE.offered too');
expectIssue(V($x(['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'overwhelm', 'hp' => 26])), 'schema', '§6.4: overwhelm is its own OVERWHELM record');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'laser', 'hp' => 26], ['seq' => 'R1.A.4', 't' => 'PHASE_START', 'phase' => 'overtime'], ['seq' => 'R1.A.5', 't' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108', 'def' => 'base@2', 'defenderType' => 'fortress']]]), '§25: damageType, phase and defenderType are open');
expectIssue(V($x(['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'battle droid', 'zone' => 'ground'])), 'schema', '§10.1: CREATE_TOKEN token is a TOKEN: id');
expectIssue(V($x(['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'upgrade'])), 'schema', '§10.1: a pilot deploy carries kind + target together');
expectIssue(V($x(['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'target' => 'SOR#108'])), 'schema', '§10.1: … target alone is not one');
expectIssue(V($x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'magic'])), 'schema', '§25: ABILITY_ACTIVATE.kind is closed');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.1']] + $x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'magic'])), [], ['newer-minor', 'unknown-value'], 'complete', '[V-02]: … unknown in a newer minor is a warning');
expectIssue(V(['header' => ['Game' => 'SWU-PGN/1.1']] + $x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 7])), 'schema', '§18: a number where a name belongs is an error in any minor');
expectIssue(V($x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'triggered', 'limit' => ['max' => 1, 'per' => 'round']])), 'schema', '[W-49]: limit requires ability');
expectIssue(V($x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'ability' => 'a', 'limit' => ['max' => 1, 'per' => 'turn']])), 'schema', '§6.4: limit.per is closed');
expectIssue(V($x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'ability' => 'a', 'limit' => ['max' => 0, 'per' => 'game']])), 'schema', '§10.1: limit.max is ≥ 1');
clean(V($x(['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'base@1', 'ability' => 'a', 'kind' => 'triggered', 'limit' => ['max' => 1, 'per' => 'phase'], 'usedBy' => 'SOR#108', 'grantedBy' => 'SOR#200'])), '§10.1: a limited use with usedBy and grantedBy');
expectIssue(V($x(['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'resource'])), 'schema', '[W-56]: a resource TAKE_CONTROL without from names the owner');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'resource', 'owner' => 2], ['seq' => 'R1.A.4', 't' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#109', 'zone' => 'resource', 'from' => 1, 'exhausted' => true]]]), '§10.1: … with owner, or with from');
expectIssue(V($x(['t' => 'STATS', 'card' => 'SOR#108', 'power' => 4, 'hp' => 5, 'name' => 'Wampa'])), 'schema', '§10.1: STATS name/cost/aspects only with copyOf');
expectIssue(V($x(['t' => 'STATS', 'card' => 'SOR#108', 'power' => 4, 'hp' => 5, 'traits' => 'creature'])), 'schema', '§10.1: STATS traits is string[]');
clean(V($x(['t' => 'STATS', 'card' => 'TWI#116', 'power' => 3, 'hp' => 3, 'keywords' => [], 'traits' => ['clone'], 'blank' => true, 'leaderUnit' => true, 'copyOf' => 'SOR#095:2', 'name' => 'Battlefield Marine', 'cost' => 2, 'aspects' => ['command', 'heroism']])), '§10.1: a full STATS');
foreach (['LOOK' => ['p' => 1, 'owner' => 2, 'zone' => 'hand', 'cards' => ['SOR#095:7'], 'count' => 1], 'NAME' => ['p' => 1, 'name' => 'Battlefield Marine'], 'ABILITY_RESET' => ['card' => 'SHD#163:2'],
    'SEARCH' => ['p' => 1, 'owner' => 1, 'seen' => ['LAW#204:2'], 'found' => [], 'zone' => 'deck'], 'CHOICE' => ['p' => 1, 'offered' => ['a'], 'chose' => 0, 'blind' => 'R1.A.1', 'simul' => 'R1.A.1']] as $t => $e) {
    clean(V($x(['t' => $t] + $e)), "§10.2: a well-formed $t");
}
expectIssue(V($x(['t' => 'LOOK', 'p' => 1, 'owner' => 3, 'zone' => 'hand', 'cards' => [], 'count' => 0])), 'schema', '§10.2: LOOK owner is a seat');
expectIssue(V($x(['t' => 'LOOK', 'p' => 1, 'owner' => 2, 'zone' => 'hand', 'cards' => []])), 'schema', '§10.2: LOOK count is listed');
expectIssue(V($x(['t' => 'NAME', 'p' => 1, 'name' => 5])), 'schema', '§10.2: NAME name is text');
expectIssue(V($x(['t' => 'ABILITY_RESET', 'ability' => 'a'])), 'schema', '§10.1: ABILITY_RESET card is required');
expectIssue(V($x(['t' => 'SEARCH', 'p' => 1, 'owner' => 'me'])), 'schema', '§10.2: SEARCH owner is a seat');
expectIssue(V($x(['t' => 'SEARCH', 'p' => 1, 'seen' => 'SOR#1'])), 'schema', '§10.2: SEARCH seen is string[]');
expectIssue(V($x(['t' => 'CHOICE', 'p' => 1, 'offered' => ['a'], 'chose' => 0, 'blind' => 1])), 'schema', '§10.2: CHOICE blind is a seq');
expectIssue(V($x(['t' => 'PASS', 'p' => 1, 'simul' => true])), 'schema', '§10.2: simul is a seq');
expectIssue(V($x(['t' => 'GAME_END', 'winner' => 3, 'reason' => 'x'])), 'schema', '§10.2: winner is 1, 2 or "Draw"');
expectIssue(V($x(['t' => 'GAME_END', 'winner' => 'P1', 'reason' => 'x'])), 'schema', '§10.2: … never "P1"');

// ── zones (§6.2, rule 3) and the MOVE rules ──────────────────────────────────────────────────────────────────────
$mv = function (array $m) { return ['extra' => [$m + ['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'p' => 1]]]; };
expectIssue(V($x(['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'groundArena'])), 'bad-zone', 'rule 3: a zone outside the vocabulary');
expectExactly(V($mv(['from' => 'hand', 'to' => 'sideboard'])), ['bad-zone'], [], 'well-formed', '§20 bad-move: a MOVE to sideboard');
expectIssue(V($mv(['from' => '', 'to' => 'ground'])), 'bad-zone', '[W-07]: an empty from');
expectIssue(V($x(['t' => 'LOOK', 'p' => 1, 'owner' => 1, 'zone' => 'library', 'cards' => [], 'count' => 0])), 'bad-zone', 'rule 3: LOOK.zone');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.4']] + $mv(['from' => 'hand', 'to' => 'exile'])), ['bad-zone'], ['newer-minor'], 'well-formed', '§18: zones are closed MAJOR — never unknown-value');
expectExactly(V($mv(['from' => 'discard', 'to' => 'discard'])), ['same-zone-move'], [], 'well-formed', 'rule 4: a MOVE from a zone to itself that is not an arena');
clean(V($mv(['from' => 'ground', 'to' => 'ground', 'kind' => 'unit'])), 'rule 4: an attachment changing in place in its arena');
expectExactly(V($mv(['from' => 'outsideTheGame', 'to' => 'deck'])), ['deck-build-move'], [], 'well-formed', '[W-41] deck-build-move');
expectExactly(V($mv(['card' => 'TOKEN:credit#8015500527:2', 'from' => 'outsideTheGame', 'to' => 'resource'])), ['credit-zone'], [], 'well-formed', '[W-42] credit-zone: a Credit token into resource');
expectIssue(V($mv(['card' => 'TOKEN:credit', 'from' => 'resource', 'to' => 'outsideTheGame'])), 'credit-zone', '[W-42]: … or out of it (degraded form)');
expectExactly(V($mv(['card' => 'TOKEN:credit:1', 'from' => 'outsideTheGame', 'to' => 'resource'])), ['bad-token-name'], [], 'well-formed', '§6.1: TOKEN:credit:1 breaks the grammar, so it is no Credit token');
clean(V($mv(['card' => 'TOKEN:credit#8015500527', 'from' => 'outsideTheGame', 'to' => 'base'])), '§6.1: a Credit into base');
expectExactly(V($mv(['card' => 'TOKEN:battle-droid#3463348370:3', 'from' => 'ground', 'to' => 'hand'])), ['token-zone'], [], 'well-formed', '[V-07] token-zone: a token leaving play for hand');
expectIssue(V($mv(['card' => 'TOKEN:shield#8752877738', 'from' => 'base', 'to' => 'discard'])), 'token-zone', '[V-07]: … or out of base');
clean(V($mv(['card' => 'TOKEN:xwing#9415311381', 'from' => 'ground', 'to' => 'space'])), '[V-07]: a token moving with its unit to the other arena stays in play');
expectIssue(V($mv(['card' => 'TOKEN:the-force#4571900905', 'from' => 'ground', 'to' => 'space'])), 'token-zone', '[V-07]: … except the Force');
expectIssue(V($mv(['card' => 'TOKEN:credit', 'from' => 'base', 'to' => 'ground'])), 'token-zone', '[V-07]: … and a Credit');
clean(V($mv(['card' => 'TOKEN:beast#6445363199:2', 'from' => 'ground', 'to' => 'outsideTheGame'])), '[W-47]: a token leaves play to outsideTheGame');
expectIssue(V($mv(['card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'kind' => 'upgrade'])), 'schema', '[W-15]: an attaching move names its host');
expectIssue(V($mv(['from' => 'ground', 'to' => 'discard', 'attachedTo' => 'SOR#095'])), 'schema', '[W-15]: exits are host-less');
expectIssue(V($mv(['from' => 'ground', 'to' => 'discard', 'exhausted' => true])), 'schema', '§10.1: exhausted only into or out of resource');
expectIssue(V($mv(['from' => 'ground', 'to' => 'discard', 'kind' => 'event'])), 'schema', '§25: MOVE.kind is closed');
clean(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#108'],
    ['seq' => 'R1.A.4', 't' => 'MOVE', 'card' => 'SOR#200', 'from' => 'resource', 'to' => 'hand', 'p' => 1, 'exhausted' => true],
    ['seq' => 'R1.A.5', 't' => 'MOVE', 'card' => 'SOR#201', 'from' => 'hand', 'to' => 'resource', 'p' => 1, 'exhausted' => true]]]), '§10.1: an attaching move; exhausted out of and into resource');
expectExactly(V($mv(['card' => 'HMW#140', 'from' => 'hand', 'to' => 'base', 'kind' => 'upgrade', 'attachedTo' => 'base@2'])), ['fortify-host'], [], 'well-formed', '[V-06] fortify-host: a MOVE onto the other seat\'s base');
expectIssue(V($x(['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'HMW#140', 'target' => 'base@2'])), 'fortify-host', '[V-06]: … or a PLAY_UPGRADE');
clean(V(['extra' => [['seq' => 'R1.A.2a', 't' => 'MOVE', 'card' => 'HMW#140', 'from' => 'hand', 'to' => 'base', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'base@1', 'for' => 'R1.A.3'], ['seq' => 'R1.A.3', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'HMW#140', 'target' => 'base@1', 'zone' => 'base', 'cost' => 3]]]), '[W-30]: Fortify on your own base');

// ── token names (§6.1, [V-05]) ───────────────────────────────────────────────────────────────────────────────────
foreach (['TOKEN:weakness#weakness-id', 'TOKEN:X-Wing', 'TOKEN:tie_fighter', 'TOKEN:-a', 'TOKEN:a--b', 'TOKEN:', 'TOKEN:a#1:01', 'TOKEN:a:0', 'TOKEN:a#'] as $bad) {
    expectExactly(V($x(['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => $bad, 'zone' => 'ground'])), ['bad-token-name'], [], 'well-formed', "[V-05]: \"$bad\" breaks the grammar");
}
foreach (['TOKEN:credit', 'TOKEN:the-force#4571900905:2', 'TOKEN:xwing#9415311381:10', 'TOKEN:tie-fighter#7268926664', 'TOKEN:a1:19'] as $good) {
    clean(V($x(['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => $good, 'zone' => 'ground'])), "§6.1: \"$good\" is a token id");
}
SwuPgnTestEq([_SwuPgnReservedToken('TOKEN:credit'), _SwuPgnReservedToken('TOKEN:credit:2'), _SwuPgnReservedToken('TOKEN:credit#8015500527:2'), _SwuPgnReservedToken('TOKEN:credit:1'), _SwuPgnReservedToken('TOKEN:the-force#4571900905'), _SwuPgnReservedToken('TOKEN:credits'), _SwuPgnReservedToken('TOKEN:credit#x')],
    ['credit', 'credit', 'credit', null, 'the-force', null, null], '§6.1: the two reserved token names are recognised by the name part');
expectIssue(V($x(['t' => 'CHOICE', 'p' => 1, 'offered' => ['TOKEN:Bad Name'], 'chose' => 0])), 'bad-token-name', '§6.1: any TOKEN: string anywhere in an event record');
expectIssue(V(['decks' => [$decks[0], ['deck' => [['TOKEN:Credit', 1]]] + $decks[1]]]), 'bad-token-name', '§6.1: … in DECKS');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'TOKEN:X', 'name' => 'X']])]), 'bad-token-name', '§6.1: … in CARDS');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'line' => [['seq' => 'R1.A.2', 't' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:Bad', 'zone' => 'ground']]]]]), 'bad-token-name', '§6.1: … in an annotation\'s what-if line');
clean(V(['annotations' => [['ref' => 'R1.A.1', 'text' => 'TOKEN:Bad is prose', 'by' => 'TOKEN:Who']]]), '§6.1: an annotation\'s text is prose, not checked');
expectExactly(V($x(['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'Advantage', 'count' => 1])), ['bad-token-name'], [], 'well-formed', '[V-05]: a STATUS_TOKEN.token breaking the grammar');
expectIssue(V($x(['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'tie_fighter', 'count' => 1])), 'bad-token-name', '§22: no _ in a token name');
clean(V($x(['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'weakness', 'count' => -1])), '§6.4: weakness is a status token name');
clean(V($x(['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'count' => 1])), '§9: STATUS_TOKEN.token is not schema-required');

// ── DEFEAT, effects, ROUND_START, Result ─────────────────────────────────────────────────────────────────────────
expectExactly(V($x(['t' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat'])), ['schema'], ['unknown-defeat'], 'well-formed', '§18: an unknown DEFEAT.reason in the own minor is an error (and reads as unknown)');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.7']] + $x(['t' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat'])), [], ['newer-minor', 'unknown-value', 'unknown-defeat'], 'verified', '§20 higher-minor: newer-minor, unknown-value, unknown-defeat → verified');
expectExactly(V($x(['t' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'unknown'])), [], ['unknown-defeat'], 'verified', '§2.1: a DEFEAT reason "unknown" → at most verified');
foreach (['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect'] as $reason) clean(V($x(['t' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => $reason, 'defeatedBy' => 'base@1'])), "§6.4: DEFEAT.reason $reason");
expectIssue(V($x(['t' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'ability', 'defeatedBy' => 'unknown'])), 'schema', '[W-32]: defeatedBy is a card id or base@N');

$fx = ['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154:2', 'p' => 1, 'targets' => ['IC27#097:2', 'IC27#097:3'], 'duration' => 'phase'];
$fxs = function (array ...$es) { $o = []; foreach ($es as $n => $e) $o[] = ['seq' => 'R1.A.' . (3 + $n)] + $e; return ['extra' => $o]; };
clean(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['IC27#097:2']], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'])), '§10.1: start, partial end, end');
clean(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['IC27#097:2']], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['IC27#097:3']])), '§10.1: an effect whose last target leaves ends');
expectExactly(V($fxs(['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'])), ['effect-end-unknown'], [], 'well-formed', '§20 effect-end-unknown: fx@1 never started');
expectIssue(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'])), 'effect-end-unknown', '[V-09]: … or already ended');
expectIssue(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#108']])), 'effect-end-unknown', '[V-09]: … or names a target the effect does not have');
expectIssue(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['IC27#097:2', 'IC27#097:3']], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'])), 'effect-end-unknown', '[V-09]: the last target leaving ended it');
expectIssue(V($fxs(['seat' => 'both'] + array_diff_key($fx, ['targets' => 1]), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#108']])), 'effect-end-unknown', '[V-09]: a seat effect has no targets to end');
clean(V($fxs(['seat' => 'both'] + array_diff_key($fx, ['targets' => 1]), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'cancelled'])), '§10.1: a seat effect for both players');
expectIssue(V($fxs(array_diff_key($fx, ['targets' => 1]))), 'schema', '§10.1: exactly one of targets and seat — neither');
expectIssue(V($fxs(['seat' => 1] + $fx)), 'schema', '§10.1: … both');
expectIssue(V($fxs(['targets' => []] + $fx)), 'schema', '§10.1: targets names at least one');
expectIssue(V($fxs(['seat' => 'all'] + array_diff_key($fx, ['targets' => 1]))), 'schema', '§10.1: seat is 1, 2 or "both"');
expectIssue(V($fxs(['duration' => 'forever'] + $fx)), 'schema', '§10.1: duration is closed');
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.2']] + $fxs(['duration' => 'forever'] + $fx)), [], ['newer-minor', 'unknown-value'], 'complete', '[V-02]: … unknown in a newer minor is a warning');
expectIssue(V($fxs(['duration' => 'until'] + $fx)), 'schema', '[W-48]: until requires untilText');
expectIssue(V($fxs(['duration' => 'until', 'untilText' => ''] + $fx)), 'schema', '[W-48]: … never empty');
clean(V($fxs(['duration' => 'until', 'untilText' => 'the phase ends or an action is taken'] + $fx)), '[W-48]: until with its untilText');
foreach (['fx@0', 'fx@01', 'effect1', 'fx@', 'FX@1'] as $bad) expectIssue(V($fxs(['id' => $bad] + $fx)), 'schema', "§6.1: \"$bad\" is no effect id");
expectIssue(V($fxs($fx, ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'gone'])), 'schema', '§10.1: EFFECT_END.reason is closed');
clean(V($fxs($fx, $fx + ['seq' => 'R1.A.9'], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'])), 'a restated effect id is still one active effect');

expectExactly(V($x(['t' => 'ROUND_START', 'round' => 2])), ['round-start-active'], [], 'well-formed', '§20 round-start-no-active: [V-08]');
expectIssue(V($x(['t' => 'ROUND_START', 'round' => 2, 'active' => 3])), 'round-start-active', '[V-08]: active is 1 or 2');
clean(V($x(['t' => 'PHASE_START', 'phase' => 'regroup'])), '§10.1: a PHASE_START need not state active');

$ge = function (string $result, ...$winners) { $o = ['header' => ['Result' => $result], 'extra' => []]; foreach ($winners as $n => $w) $o['extra'][] = ['seq' => "R1.A.game-end$n", 't' => 'GAME_END', 'winner' => $w, 'reason' => 'x']; return $o; };
expectExactly(V($ge('P1')), ['result-mismatch'], [], 'well-formed', '§20 result-mismatch: [Result "P1"] with no GAME_END');
expectIssue(V($ge('Incomplete', 1)), 'result-mismatch', '§10.2: an Incomplete game has no GAME_END');
expectIssue(V($ge('P2', 1)), 'result-mismatch', '§10.2: GAME_END.winner agrees with Result');
expectIssue(V($ge('P2', 2, 1)), 'result-mismatch', '§24: … the LAST GAME_END');
foreach ([['P1', 1], ['P2', 2], ['Draw', 'Draw']] as [$res, $w]) clean(V($ge($res, $w)), "§5.1: [Result \"$res\"] with winner " . json_encode($w));
expectExactly(V(['header' => ['Game' => 'SWU-PGN/1.7']] + $ge('Abandoned', 'Abandoned')), [], ['newer-minor', 'unknown-value'], 'complete', '§18: an unknown outcome is not checked for agreement');

// ── keyframes (§11, §13; rules 3 and 6) ──────────────────────────────────────────────────────────────────────────
$kfe = function ($kf) { return ['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'active' => 1, 'keyframe' => $kf]]]; };
SwuPgnTestCheck(V($kfe(SwuPgnTestKeyframe()))['errors'] === [], 'a complete keyframe is well-shaped');
expectIssue(V($kfe(['players' => ['1' => SwuPgnTestSeatKeyframe(1)]])), 'schema', '§13: a keyframe missing a seat');
expectIssue(V($kfe(['players' => []] + SwuPgnTestKeyframe())), 'schema', '§13: … or both');
expectIssue(V($kfe('snapshot')), 'schema', '§13: a keyframe that is not an object');
expectIssue(V($kfe(SwuPgnTestKeyframe(['handSize' => '3']))), 'schema', '§11: handSize is a number');
expectIssue(V($kfe(SwuPgnTestKeyframe(['leader' => ['id' => 'SOR#010', 'deployed' => 'no']]))), 'schema', '§11: leader fields are typed');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'discard')]]))), 'schema', '§13: keyframe cards are ground/space only');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'sideboard')]]))), 'bad-zone', '§2.1 rule 3: a keyframe card zone outside the vocabulary');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'ground', ['statusTokens' => []])]]))), 'schema', '§13: statusTokens is an object, not an array');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'ground', ['upgradeControllers' => []])]]))), 'schema', '§13: upgradeControllers is an object, not an array');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'ground', ['upgrades' => ['SOR#300'], 'upgradeControllers' => ['SOR#300' => 3]])]]))), 'schema', '§11: upgradeControllers maps to seats');
expectIssue(V($kfe(SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'ground', ['shields' => null])]]))), 'schema', '§13: a card needs shields');
SwuPgnTestEq(V($kfe(SwuPgnTestKeyframe(['foreignResources' => [], 'abilityUses' => [['card' => 'SOR#108', 'ability' => 'a', 'count' => 1, 'per' => 'round', 'max' => 1]],
    'cards' => [SwuPgnTestCard('SOR#108', 'ground', ['traits' => ['creature'], 'owner' => 2, 'upgrades' => ['SOR#300'], 'upgradeControllers' => ['SOR#300' => 2], 'statusTokens' => ['advantage' => 1]])]], [], ['effects' => [['id' => 'fx@1', 'src' => 'SOR#108', 'p' => 1, 'seat' => 'both', 'duration' => 'game']]]))
    + ['header' => []])['errors'], [], '§11: the new keyframe fields are well-shaped (no errors; folding aside)');
expectIssue(V($kfe(SwuPgnTestKeyframe(['abilityUses' => [['card' => 'SOR#108', 'ability' => 'a', 'count' => 1, 'per' => 'turn', 'max' => 1]]]))), 'schema', '§6.4: abilityUses per is closed');
expectIssue(V($kfe(SwuPgnTestKeyframe([], [], ['effects' => [['id' => 'fx1', 'src' => 'SOR#108', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'game']]]))), 'schema', '§11: an effect id is fx@N');
expectExactly(V($kfe(SwuPgnTestKeyframe(['handSize' => 1001, 'hand' => array_map(fn($i) => "SOR#1$i", range(1, 1001))]))), ['over-cap'], [], 'well-formed', '§2.1 rule 6: a keyframe list over 1000');
$loaded = array_map(fn($i) => SwuPgnTestCard("SOR#2$i", 'ground', ['upgrades' => array_map(fn($u) => "U#$i-$u", range(1, 100)), 'captured' => ['C#' . $i]]), range(1, 10));
expectExactly(V($kfe(SwuPgnTestKeyframe(['cards' => $loaded]))), ['over-cap'], [], 'well-formed', 'rule 6: a seat\'s cards holding 1010 upgrades and captives');

// Folding (§14) only when there are no errors: a mismatching keyframe → keyframe-mismatch, at most valid.
$bad = ['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'active' => 1, 'keyframe' => SwuPgnTestKeyframe(['handSize' => 3, 'hand' => ['A#001', 'A#002', 'A#003']])]]];
$r = V($bad);
expectExactly($r, [], ['keyframe-mismatch'], 'valid', '§2.1: a keyframe the events do not fold to → keyframe-mismatch, at most valid');
SwuPgnTestEq([$r['ok'], $r['warnings'][0]['seq'] ?? null], [true, 'R2.start'], 'a mismatch is a warning: still ok, and it names the keyframe\'s seq');
$bad['extra'][] = ['seq' => 'R2.A.1', 't' => 'PASS', 'p' => 9];
expectExactly(V($bad), ['schema'], [], 'well-formed', '§2.1: with an error the file is not folded (no keyframe-mismatch)');

// ── ANNOTATIONS (§15) ────────────────────────────────────────────────────────────────────────────────────────────
clean(V(['annotations' => [['ref' => 'R1.A.1', 'nag' => '??', 'text' => 'hm', 'by' => 'Reviewer A', 'id' => 'n1', 'ts' => 1], ['ref' => 'R1.A.1', 'nag' => '$$', 'parent' => 'n1', 'line' => [['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 2]], 'colour' => 'red']]]),
    '§15: notes, threads, unknown glyphs, what-if lines and other fields');
expectExactly(V(['annotations' => [['ref' => 'R9.A.9', 'text' => 'x']]]), ['dangling-ref'], [], 'well-formed', '§20 dangling-ref: a note on a missing seq');
expectIssue(V(['annotations' => [['text' => 'x']]]), 'schema', '§15: ref is required');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'ts' => 'now']]]), 'schema', '§15: ts is an integer');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'line' => [5]]]]), 'schema', '§15: line is GameEvent[]');
expectExactly(V(['annotations' => [['ref' => 'R1.A.1', 'parent' => 'nope']]]), [], ['dangling-parent'], 'complete', '§15: a parent that names no note is a warning');

// ── notices (§5.3, §16) ──────────────────────────────────────────────────────────────────────────────────────────
$r = V(['header' => ['Engine' => 'petranaki@unknown', 'Seed' => 'unseeded']]);
SwuPgnTestEq([$r['level'], codes($r['notices'])], ['complete', ['engine-unknown', 'unseeded']], '§5.3: @unknown and unseeded are surfaced as notices, not issues');
SwuPgnTestEq(codes(V(['header' => ['Engine' => 'petranaki@7dd39a09-dirty']])['notices']), ['engine-dirty'], '§5.3: a -dirty build is surfaced');
$r = V(['story' => ['something else']]);
SwuPgnTestEq([$r['level'], codes($r['notices'])], ['complete', ['story-drift']], '§16: a STORY that differs from the render is a notice, never invalid');
SwuPgnTestEq(V(['story' => [' ── action ──', '  1. Player 1 plays Wampa to ground (cost 2)', '  2. Player 2 passes']])['notices'], [], '§16: a STORY that matches the render is silent');

// ── issue shape, cap, SwuPgnValidateText ─────────────────────────────────────────────────────────────────────────
$text = T($x(['t' => 'PASS', 'p' => 9]));
$r = SwuPgnValidate(SwuPgnParse($text));
$i = $r['errors'][0];
$want = 1 + array_search(true, array_map(fn($l) => strpos($l, '"p":9') !== false, explode("\n", $text)), true);
SwuPgnTestEq([array_keys($i), $i['code'], $i['line'], $i['seq']], [['code', 'message', 'line', 'seq'], 'schema', $want, 'R1.A.3'], 'issue shape: code, message, line (1-based), seq');
SwuPgnTestEq(V(['header' => ['Date' => 'x']])['errors'][0]['line'] ?? null, 3, 'a header issue carries its tag\'s line');
$many = [];
for ($k = 0; $k < SWUPGN_MAX_ISSUES + 50; $k++) $many[] = ['seq' => 'R1.A.' . (10 + $k), 't' => 'PASS', 'p' => 9];
$r = V(['extra' => $many]);
SwuPgnTestCheck(SWUPGN_MAX_ISSUES >= 1000 && count($r['errors']) === SWUPGN_MAX_ISSUES && codes($r['warnings']) === ['too-many-issues'] && count($r['warnings']) === 1, '§24: the list stops at a cap of at least 1000 and says so once');
$many[] = ['seq' => 'R1.A.zz', 't' => 'DEFEAT', 'card' => 'X#001', 'reason' => 'unknown'];
SwuPgnTestEq(V(['extra' => $many, 'events' => []])['level'], 'well-formed', '§24: the level still counts every issue past the cap');
$r = SwuPgnValidateText("[Game \"SWU-PGN/2.0\"]\n");
SwuPgnTestEq([$r['ok'], $r['level'], codes($r['errors']), $r['errors'][0]['line']], [false, 'malformed', ['unsupported-major'], 1], 'SwuPgnValidateText: a file that does not parse is malformed, with its fatal code and line');
SwuPgnTestEq(SwuPgnValidateText(T()), V(), 'SwuPgnValidateText = parse + validate');
SwuPgnTestEq(SwuPgnValidateText(T(['header' => ['Engine' => 'pilotwriter@x']]), ['pilotProducts' => ['pilotwriter']])['level'], 'well-formed', 'SwuPgnValidateText passes the options through');
SwuPgnTestEq(SwuPgnValidateText(T(), ['caps' => ['records' => 2]])['errors'][0]['code'] ?? null, 'too-many-records', 'SwuPgnValidateText takes parse caps');

SwuPgnTestFinish();
