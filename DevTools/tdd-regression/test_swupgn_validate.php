<?php
// SWU-PGN/1.0 spec conformance report: validate() errors vs warnings for header tags (§5),
// sections (§4, §7, §8, §15), records (§9, §10), closed vocabularies (§6.4), versions (§18).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_validate.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$decks = [
    ['p' => 1, 'leader' => 'SOR#010', 'base' => 'SOR#028', 'deck' => [['SOR#108', 5]]],
    ['p' => 2, 'leader' => 'SOR#005', 'base' => 'SOR#020', 'deck' => [['SOR#045', 5]], 'sideboard' => [['SOR#046', 2]]],
];
$cardsIdx = [
    ['id' => 'SOR#005', 'name' => 'Darth Vader'], ['id' => 'SOR#010', 'name' => 'Luke'], ['id' => 'SOR#020', 'name' => 'Command Center'],
    ['id' => 'SOR#028', 'name' => 'Echo Base'], ['id' => 'SOR#045', 'name' => 'Cell Block Guard', 'kind' => 'unit'],
    ['id' => 'SOR#046', 'name' => 'Guard Two', 'kind' => 'unit'], ['id' => 'SOR#108', 'name' => 'Wampa', 'kind' => 'unit'],
];
$setup = [['seq' => 'R1.S.0', 't' => 'INIT', 'p1DeckOrder' => ['SOR#108'], 'p2DeckOrder' => ['SOR#045']]];
$baseEvents = [
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action', 'ms' => 10],
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit', 'for' => 'R1.A.1'],
    ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2, 'ms' => 1500],
    ['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 2],
];
// $o: header overrides, events (replace), extra events (append), decks/cards/setup/annotations (replace or null to omit)
function V(array $o = []): array
{
    global $decks, $cardsIdx, $setup, $baseEvents;
    $opts = ['header' => $o['header'] ?? []];
    foreach (['decks' => $decks, 'cards' => $cardsIdx, 'setup' => $setup] as $k => $def) {
        $v = array_key_exists($k, $o) ? $o[$k] : $def;
        if ($v !== null) $opts[$k] = $v;
    }
    if (isset($o['annotations'])) $opts['annotations'] = $o['annotations'];
    if (isset($o['story'])) $opts['story'] = $o['story'];
    $events = array_merge($o['events'] ?? $baseEvents, $o['extra'] ?? []);
    $text = SwuPgnTestFile($events, $opts) . ($o['append'] ?? '');
    return SwuPgnValidate(SwuPgnParse($text));
}
function codes(array $issues): array { return array_values(array_unique(array_column($issues, 'code'))); }
function expectIssue(array $r, string $level, string $code, string $what): void
{
    $other = $level === 'error' ? 'warnings' : 'errors';
    $in = in_array($code, codes($r[$level === 'error' ? 'errors' : 'warnings']), true);
    $notOther = !in_array($code, codes($r[$other]), true);
    SwuPgnTestCheck($in && $notOther, "$what → $level $code", ['errors' => codes($r['errors']), 'warnings' => codes($r['warnings'])]);
}

// ── a clean file ────────────────────────────────────────────────────────────────────────────────
$r = V();
SwuPgnTestEq([$r['ok'], $r['errors'], $r['warnings']], [true, [], []], 'a clean hand-made file: ok, no errors, no warnings');
$a = SwuPgnValidate(SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/appendix-a.swupgn')));
SwuPgnTestEq([$a['errors'], $a['warnings']], [[], []], 'Appendix A: no errors and no warnings');
SwuPgnTestCheck(isset($r['errors']) && isset($r['warnings']) && array_key_exists('ok', $r), 'result shape: ok / errors / warnings');

// ── header (§5) ─────────────────────────────────────────────────────────────────────────────────
expectIssue(V(['header' => ['Game' => 'SWU-PGN/2.0']]), 'error', 'version.major', '§18: a different MAJOR version is refused');
expectIssue(V(['header' => ['Game' => 'SWU-PGN/1.3']]), 'warning', 'version.minor', '§18: a higher MINOR version is accepted (noted)');
expectIssue(V(['header' => ['Game' => 'PGN']]), 'error', 'header.game', '§5.1: Game must be SWU-PGN/MAJOR.MINOR');
SwuPgnTestEq([SwuPgnCheckVersion('SWU-PGN/1.0'), SwuPgnCheckVersion('SWU-PGN/1.7'), SwuPgnCheckVersion('SWU-PGN/2.0'), SwuPgnCheckVersion('SWU-PGN/0.9'), SwuPgnCheckVersion(null)], ['ok', 'newer-minor', 'unsupported', 'unsupported', 'unsupported'], '§18 SwuPgnCheckVersion');
expectIssue(V(['header' => ['Engine' => '']]), 'error', 'header.empty', '§5.3: Engine must be non-empty');
expectIssue(V(['header' => ['Seed' => '']]), 'error', 'header.empty', '§5.3: Seed must be non-empty');
expectIssue(V(['header' => ['Engine' => 'petranaki@unknown']]), 'warning', 'header.engine-unknown', '§5.3: an @unknown Engine is accepted and surfaced');
expectIssue(V(['header' => ['Engine' => 'petranaki@7dd39a09-dirty']]), 'warning', 'header.engine-dirty', '§5.3: a -dirty build is surfaced');
expectIssue(V(['header' => ['Seed' => 'unseeded']]), 'warning', 'header.seed-unseeded', '§5.3: an unseeded file is accepted and surfaced');
expectIssue(V(['header' => ['Date' => 'yesterday']]), 'error', 'header.date', '§5.1: Date is ISO-8601 UTC');
expectIssue(V(['header' => ['Date' => '2026-09-04T15:58:16+02:00']]), 'error', 'header.date', '§5.1: Date must be UTC');
SwuPgnTestEq(V(['header' => ['Date' => '2026-09-04T15:58:16.695Z', 'EndDate' => '2026-09-04T16:30:00Z']])['errors'], [], '§5.1/§5.2: fractional seconds and EndDate accepted');
expectIssue(V(['header' => ['EndDate' => '16:30']]), 'error', 'header.date', '§5.2: EndDate is ISO-8601 UTC');
expectIssue(V(['header' => ['CardPool' => 'SOR,,JTL']]), 'error', 'header.cardpool', '§5.1: CardPool is a comma-separated list of set ids');
SwuPgnTestEq(V(['header' => ['CardPool' => 'ASH,HMW,IBH,IC27,JTL,LAW,LOF,SEC,SHD,SOR,TS26,TWI']])['errors'], [], '§5.1: a long CardPool with digit-bearing set ids is fine');
expectIssue(V(['header' => ['P1Id' => 'claudebot1']]), 'error', 'header.player-id', '§17: P1Id must look like sha256:<hex>');
expectIssue(V(['header' => ['P2Id' => 'sha256:xyz']]), 'error', 'header.player-id', '§17: P2Id hex only');
expectIssue(V(['header' => ['P1' => 'claudebot1']]), 'warning', 'header.player-name', '§5.1/§17: P1 should be the generic "Player 1"');
expectIssue(V(['header' => ['Result' => 'Win']]), 'error', 'header.result', '§5.1: Result is P1/P2/Draw/Incomplete');
expectIssue(V(['header' => ['Rounds' => 'seven']]), 'error', 'header.rounds', '§5.1: Rounds is digits');
expectIssue(V(['header' => ['Perspective' => 'P3']]), 'error', 'header.perspective', '§5.2: Perspective is P1 or P2');
expectIssue(V(['header' => ['Undos' => '-1']]), 'error', 'header.digits', '§5.2: Undos is digits');
expectIssue(V(['header' => ['RecorderErrors' => '2']]), 'warning', 'header.recorder-errors', '§5.2: RecorderErrors present → surfaced');
expectIssue(V(['header' => ['GameNumber' => '2']]), 'warning', 'header.game-number', '§5.2: GameNumber is meaningless without Match');
expectIssue(V(['header' => ['P1Leader' => 'Luke']]), 'warning', 'header.card-id', '§6.1: leader/base ids look like SET#NUM');
SwuPgnTestEq(V(['header' => ['Format' => 'pReMiEr', 'Bogus' => 'x']])['warnings'], [], '§5.2: Format in any case and unknown tags are accepted silently');
$doc = SwuPgnParse(SwuPgnTestFile($baseEvents));
unset($doc['headers']['Rounds']);
expectIssue(SwuPgnValidate($doc), 'error', 'header.missing', '§5.1: a doc missing a required tag (built without the parser) is an error');

// ── sections (§4, §7, §8) ───────────────────────────────────────────────────────────────────────
expectIssue(V(['decks' => null]), 'warning', 'section.missing', '§4: DECKS is "required in practice"');
expectIssue(V(['setup' => null]), 'warning', 'section.missing', '§4: SETUP is "required in practice"');
expectIssue(V(['append' => "\n%%% DECKS\n"]), 'warning', 'section.duplicate', 'a banner that appears twice');
expectIssue(V(['extra' => [[1, 2]]]), 'error', 'record.not-object', '§4: every record is one JSON OBJECT (a [ line reaches validate and is rejected)');
expectIssue(V(['decks' => [$decks[0]]]), 'error', 'decks.count', '§7: two DECKS lines');
expectIssue(V(['decks' => [$decks[0], $decks[0]]]), 'error', 'decks.p', '§7: one line per seat');
expectIssue(V(['decks' => [$decks[0], ['color' => 'red'] + $decks[1]]]), 'error', 'decks.extra', '§7: no other fields (additionalProperties: false)');
expectIssue(V(['decks' => [$decks[0], ['deck' => [['SOR#045', '5']]] + $decks[1]]]), 'error', 'decks.entry', '§7: deck is an array of [id, count]');
expectIssue(V(['decks' => [$decks[0], ['leader' => 5] + $decks[1]]]), 'error', 'decks.field', '§7: leader is a string');
expectIssue(V(['decks' => [$decks[0], ['leader' => 'SOR#999'] + $decks[1]]]), 'warning', 'decks.header-mismatch', '§7: DECKS leader disagrees with P2Leader');
expectIssue(V(['setup' => [['seq' => 'R0.S.0'] + $setup[0]]]), 'error', 'setup.init', '§8: INIT seq is always "R1.S.0"');
expectIssue(V(['setup' => [['p1DeckOrder' => 'SOR#108'] + $setup[0]]]), 'error', 'setup.init', '§8: p1DeckOrder is string[]');
expectIssue(V(['setup' => [$setup[0], ['seq' => 'R1.S.1', 't' => 'NOTE']]]), 'warning', 'setup.extra', '§8: SETUP holds one INIT and nothing else');

// ── CARDS (§6.5) ────────────────────────────────────────────────────────────────────────────────
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#200']])]), 'error', 'cards.field', '§6.5: id and name are required strings');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#200', 'name' => 'X', 'kind' => 'event']])]), 'error', 'cards.kind', '§6.5: kind is "unit" or "upgrade" (absent means neither)');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#108:2', 'name' => 'Wampa']])]), 'warning', 'cards.copy-suffix', '§6.5: index ids are base ids (no :N)');
expectIssue(V(['cards' => array_merge($cardsIdx, [['id' => 'SOR#108', 'name' => 'Wampa again']])]), 'warning', 'cards.duplicate', 'an id listed twice');
expectIssue(V(['cards' => array_slice($cardsIdx, 1)]), 'warning', 'cards.coverage', '§6.5: the index SHOULD cover every id the file mentions');

// ── EVENTS: seq, t, shapes (§9, §10) ────────────────────────────────────────────────────────────
expectIssue(V(['extra' => [['t' => 'PASS', 'p' => 1]]]), 'error', 'event.seq', '§9: every event has a seq');
expectIssue(V(['extra' => [['seq' => 'R1.A.3']]]), 'error', 'event.t', '§9: every event has a t');
expectIssue(V(['extra' => [['seq' => 'round1-3', 't' => 'PASS', 'p' => 1]]]), 'error', 'event.seq-pattern', '§9.1: seq matches the pattern');
expectIssue(V(['extra' => [['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 1]]]), 'error', 'event.seq-duplicate', '§9: seq is unique in the file');
SwuPgnTestEq(V(['extra' => [['seq' => 'R1.A.game-end', 't' => 'GAME_END', 'winner' => 1, 'reason' => 'Base Destroyed'], ['seq' => 'R1.A.end', 't' => 'PHASE_END', 'phase' => 'action'], ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2], ['seq' => 'R0.S.5', 't' => 'SHUFFLE', 'p' => 1]]])['errors'], [], '§9.1: game-end, phase/round edges and setup steps are valid seqs');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'FROBNICATE', 'p' => 1]]]), 'warning', 'event.unknown-type', '§18: an unknown type is a warning, never an error');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@2', 'amt' => '4', 'damageType' => 'combat', 'hp' => 26]]]), 'error', 'event.field', '§9: amt is an integer');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DRAW', 'p' => 1, 'count' => 1, 'cards' => 'SOR#108']]]), 'error', 'event.field', '§9: cards is an array');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PASS', 'p' => 3]]]), 'error', 'event.field', '§10: p is 1 or 2');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat']]]), 'error', 'event.required', '§10.1: a board record missing a required field (DAMAGE.hp)');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108']]]), 'warning', 'event.required', '§10.2: a note missing a listed field is only a warning');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => -2]]]), 'error', 'event.field', 'EXHAUST_RESOURCES amount is not negative');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'groundArena']]]), 'error', 'event.zone', '§6.2: zone outside the vocabulary');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@3', 'amt' => 4, 'damageType' => 'combat', 'hp' => 26]]]), 'error', 'base-ref', '§6.3: base@N names seat 1 or 2');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:weakness#weakness-id', 'zone' => 'ground']]]), 'warning', 'token-id', '§6.1: a TOKEN id with a non-numeric card id');

// MOVE (§10.1)
$mv = function (array $x) { return ['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'discard', 'p' => 1] + $x; };
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'ground', 'to' => 'ground', 'p' => 1]]]), 'error', 'move.same-zone', '§10.1: from MUST NOT equal to');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => '', 'to' => 'ground', 'p' => 1]]]), 'error', 'event.zone', '§10.1: "" is not a zone');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'SOR#108', 'from' => 'outsideTheGame', 'to' => 'deck', 'p' => 1]]]), 'warning', 'move.deck-build', '§10.1: deck construction is not a move');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade']]]), 'error', 'move.attached-to', '§10.1: an attaching move into an arena REQUIRES attachedTo');
expectIssue(V(['extra' => [$mv(['attachedTo' => 'SOR#095'])]]), 'error', 'move.attached-to', '§10.1: exits are host-less — no attachedTo on a move out of an arena');
expectIssue(V(['extra' => [$mv(['exhausted' => true])]]), 'warning', 'move.exhausted', '§10.1: exhausted belongs on a move out of resource only');
expectIssue(V(['extra' => [$mv(['kind' => 'event'])]]), 'error', 'event.field', '§10.1: MOVE.kind is "unit" or "upgrade"');
SwuPgnTestEq(V(['extra' => [['seq' => 'R1.A.3', 't' => 'MOVE', 'card' => 'LOF#215', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'upgrade', 'attachedTo' => 'SOR#108'], ['seq' => 'R1.A.4', 't' => 'MOVE', 'card' => 'SOR#200', 'from' => 'resource', 'to' => 'hand', 'p' => 1, 'exhausted' => true]], 'cards' => array_merge($cardsIdx, [['id' => 'LOF#215', 'name' => 'Cable', 'kind' => 'upgrade'], ['id' => 'SOR#200', 'name' => 'Res']])])['errors'], [], '§10.1: a proper attaching move and an exhausted exit from resource are fine');

// other closed / pinned things
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat']]]), 'error', 'defeat.reason', '§6.4: DEFEAT.reason is CLOSED');
expectIssue(V(['header' => ['Game' => 'SWU-PGN/1.1'], 'extra' => [['seq' => 'R1.A.3', 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat']]]), 'warning', 'defeat.reason', '§6.4/§18: a reason from a NEWER minor version is read as "unknown" — a warning, not an error');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'unknown']]]), 'warning', 'defeat.unknown', '§6.4: reason "unknown" is valid but surfaced');
foreach (['attack', 'ability', 'nonCombatDamage', 'uniqueRule', 'frameworkEffect'] as $reason) {
    SwuPgnTestEq(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => $reason]]])['errors'], [], "§6.4: DEFEAT.reason $reason is in the set");
}
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'overwhelm', 'hp' => 26]]]), 'error', 'damage.overwhelm', '§6.4: overwhelm is its own OVERWHELM record');
SwuPgnTestEq(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'laser', 'hp' => 26], ['seq' => 'R1.A.4', 't' => 'PHASE_START', 'phase' => 'overtime']]])['errors'], [], '§6.4: open vocabularies (damageType, phase) accept new values');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:x-wing#1', 'zone' => 'outsideTheGame']]]), 'error', 'create-token.zone', '§10.1: CREATE_TOKEN names the destination ARENA');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'upgrade']]]), 'error', 'deploy.target', '§10.1: a pilot deploy carries kind + target together');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'magic']]]), 'warning', 'ability.kind', '§10.1: an ABILITY_ACTIVATE kind outside the eight');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'epic']]]), 'warning', 'ability.epic', '§3: an Epic Action MUST carry epic: true');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'STATS', 'card' => 'SOR#108', 'power' => 4, 'hp' => 5, 'keywords' => ['sentinel', 'overwhelm']]]]), 'warning', 'stats.keywords-order', '§10.1: keywords are sorted');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'shield', 'count' => 1]]]), 'warning', 'status-token.name', 'token contract: shields/experience have their own records');

// ms, for, UNDO, GAME_END
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat', 'hp' => 26, 'ms' => 5]]]), 'error', 'event.ms', '§5.2.1: ms only on numbered actions, ROUND_START, PHASE_START');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'PASS', 'p' => 1, 'ms' => '5s']]]), 'error', 'event.ms', '§5.2.1: ms is an integer duration');
SwuPgnTestEq(V(['extra' => [['seq' => 'R1.A.3', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'action', 'ms' => 9], ['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'ms' => 99]]])['errors'], [], '§5.2.1: ms on an action ability and a ROUND_START is fine');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'EXHAUST', 'card' => 'SOR#108', 'for' => 'R9.A.9']]]), 'warning', 'event.for', '§9.1: for names a seq that is not in the file');
expectIssue(V(['extra' => [['seq' => 'R1.A.3', 't' => 'EXHAUST', 'card' => 'SOR#108', 'for' => 4]]]), 'error', 'event.field', '§9.1: for is a string');
expectIssue(V(['extra' => [['seq' => 'R1.A.2-undo', 't' => 'UNDO', 'at' => 'R1.A.3']]]), 'error', 'undo.seq', '§9.1: an UNDO\'s seq is its `at` plus -undo');
expectIssue(V(['extra' => [['seq' => 'R1.A.3-undo', 't' => 'UNDO', 'at' => 'R1.A.3', 'by' => 1]]]), 'error', 'undo.count', '§5.2: Undos (absent = none) can never be below the UNDO records');
SwuPgnTestEq(V(['header' => ['Undos' => '3'], 'extra' => [['seq' => 'R1.A.3-undo', 't' => 'UNDO', 'at' => 'R1.A.3', 'by' => 1]]])['errors'], [], '§10.2: Undos MAY exceed the UNDO records');
expectIssue(V(['extra' => [['seq' => 'R1.A.end', 't' => 'GAME_END', 'winner' => 2, 'reason' => 'Concede']]]), 'warning', 'game-end.seq', '§22: a GAME_END outside its own .game-end step is an early-writer file');
expectIssue(V(['extra' => [['seq' => 'R1.A.game-end', 't' => 'GAME_END', 'winner' => 3, 'reason' => 'x']]]), 'error', 'event.field', '§10.2: winner is 1, 2 or "Draw"');

// keyframes
$kf = SwuPgnTestKeyframe();
SwuPgnTestEq(V(['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => $kf]]])['errors'], [], 'a complete keyframe validates');
expectIssue(V(['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['players' => ['1' => SwuPgnTestSeatKeyframe(1)]]]]]), 'error', 'keyframe.damaged', '§13: a keyframe missing a seat is non-conformant');
expectIssue(V(['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => SwuPgnTestKeyframe(['handSize' => '3'])]]]), 'error', 'keyframe.field', '§11: keyframe field types (handSize is a number)');
expectIssue(V(['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#108', 'discard')]])]]]), 'error', 'keyframe.field', '§13: keyframe cards are ground/space only');
expectIssue(V(['extra' => [['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => SwuPgnTestKeyframe(['leader' => ['id' => 'SOR#010', 'deployed' => 'no']])]]]), 'error', 'keyframe.field', '§11: leader fields are typed');

// ── ANNOTATIONS (§15) ───────────────────────────────────────────────────────────────────────────
SwuPgnTestEq(V(['annotations' => [['ref' => 'R1.A.1', 'nag' => '??', 'text' => 'hm', 'by' => 'Reviewer A', 'id' => 'n1', 'ts' => 1], ['ref' => 'R1.A.1', 'nag' => '$$', 'parent' => 'n1', 'line' => [['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 2]]]]])['errors'], [], '§15: notes, threads, unknown glyphs and what-if lines are fine');
expectIssue(V(['annotations' => [['ref' => 'R9.A.9', 'text' => 'x']]]), 'error', 'annotation.ref', '§3/§15: a note points at a seq that really exists');
expectIssue(V(['annotations' => [['text' => 'x']]]), 'error', 'annotation.ref', '§15: ref is required');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'author' => 'x']]]), 'error', 'annotation.extra', '§15: no other fields');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'ts' => 'now']]]), 'error', 'annotation.field', '§15: ts is an integer');
expectIssue(V(['annotations' => [['ref' => 'R1.A.1', 'parent' => 'nope']]]), 'warning', 'annotation.parent', '§15: a parent that names no note → shown top-level (warned)');

// ── STORY ───────────────────────────────────────────────────────────────────────────────────────
expectIssue(V(['story' => ['something else']]), 'warning', 'story.drift', '§16: a STORY that differs from the render is a warning, never invalid');
SwuPgnTestEq(V(['story' => [' ── action ──', '  1. Player 1 plays Wampa to ground (cost 2)', '  2. Player 2 passes', '']])['warnings'], [], '§16: a STORY that matches the render is silent');

// ── issue shape, limits ─────────────────────────────────────────────────────────────────────────
$r = V(['extra' => [['seq' => 'R1.A.3', 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat']]]);
$i = $r['errors'][0];
SwuPgnTestEq([array_keys($i), $i['line'], $i['seq']], [['code', 'message', 'line', 'seq'], 40, 'R1.A.3'], 'issue shape: code, message, line (1-based), seq');
$many = [];
for ($k = 0; $k < SWUPGN_MAX_ISSUES + 50; $k++) $many[] = ['seq' => 'R1.A.' . (10 + $k), 't' => 'DEFEAT', 'card' => 'SOR#108', 'reason' => 'combat'];
$r = V(['extra' => $many]);
SwuPgnTestCheck(count($r['errors']) + count($r['warnings']) <= SWUPGN_MAX_ISSUES + 1 && in_array('issues.truncated', codes($r['warnings']), true), 'the issue list is capped and says so');
$doc = SwuPgnParse(SwuPgnTestHeaderText(SwuPgnTestHeaderTags()) . "%%% EVENTS\n" . str_repeat('{"seq":"R1.A.1","t":"PASS","p":1}' . "\n", 3));
$doc['dropped'] = ['events' => 4];
expectIssue(SwuPgnValidate($doc), 'error', 'section.dropped', 'records over the reader\'s limits are an error, not silently lost');

SwuPgnTestFinish();
