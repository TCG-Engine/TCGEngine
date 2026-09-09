<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/tdd-regression/test_swusim_decision_validators.php
//
// SWUValidateDecisionAnswer() is the server-side gate on a DecisionQueue answer
// (SWUSim/Custom/GameLogic.php). It exists because an unvalidated answer is not an error — it is a
// SILENT NO-OP: the continuation runs, finds nothing matching, and returns, so a test that submits
// garbage looks green while asserting nothing. Every arm in that function was added to close one of
// those false-green holes (MZMULTICHOOSE first, then PASSPARAMETER / OPTIONCHOOSE / SCRY).
//
// This file guards the NAMECARD, REVEALARRANGE and NAMETRAIT arms and their matching
// BridgeEnumerateDecisionActions() encoders, plus the SCRY encoder (whose validator arm already
// existed), plus a thin control set over the arms that predate all of them so a future edit beside
// them cannot quietly widen one.
//
// ── WHY THE BRIDGE SECTIONS SEED REAL STATE ──────────────────────────────────────────────────────
// Three of the four encoders synthesise their candidate list from the board rather than from the
// decision's Param, so an unseeded run would exercise the degenerate "offer only the decline" path
// and every assertion over it would be vacuously green. Worse, a vacuous run stays green when the
// candidate helper reads the WRONG SEAT'S zones — a bot naming a card out of the enemy deck, or a
// trait off a card in the enemy hand, is CHEATING, and a cheat validates and resolves exactly like a
// legitimate answer. So each bridge section seeds distinct, disjoint state per seat and asserts the
// information boundary explicitly, in both directions.
//
// ── WHY THE TEST MUST ENQUEUE A REAL DECISION ────────────────────────────────────────────────────
// The signature is SWUValidateDecisionAnswer(int $player, string $answer): bool — TWO parameters. It
// finds the queue head itself, by walking GetDecisionQueue($player) for the first non-`removed`
// entry, and reads $head->Type / $head->Param from it. There is no way to hand it a synthetic head,
// so each case builds a real queue row via DecisionQueueController::AddDecision() and clears the
// queue afterwards.
//
// READ-ONLY on disk: the SWUSim runtime is loaded library-style and the DecisionQueue globals are
// driven directly. No game is created, no gamestate is written.

header('Content-Type: text/plain');
if (!defined('TCGENGINE_BRIDGE_LIBRARY_ONLY')) define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once __DIR__ . '/../TestAutomationBridge.php';
EngineLoadRootRuntime('SWUSim');

$checks = [];

$checks['SWUValidateDecisionAnswer exists'] = function_exists('SWUValidateDecisionAnswer');
$checks['titleData is populated']           = is_array($GLOBALS['titleData'] ?? null)
                                              && count($GLOBALS['titleData']) > 1000;

// enqueue -> validate -> clear. The queue globals are the generated ZoneAccessors' backing store
// (SWUSim/ZoneAccessors.php GetDecisionQueue), so resetting them between cases is the whole cleanup.
$validate = function (int $player, string $type, string $param, string $answer) : bool {
    foreach ([1, 2, 3, 4] as $seat) $GLOBALS["p{$seat}DecisionQueue"] = [];
    DecisionQueueController::AddDecision($player, $type, $param, 1, 'Test_prompt');
    $result = SWUValidateDecisionAnswer($player, $answer);
    foreach ([1, 2, 3, 4] as $seat) $GLOBALS["p{$seat}DecisionQueue"] = [];
    return $result;
};

// Sanity: the helper really does put the row it claims in front of the validator. Without this a
// broken enqueue would make every NAMECARD case below pass for the wrong reason (an empty queue
// returns true unconditionally).
foreach ([1, 2, 3, 4] as $seat) $GLOBALS["p{$seat}DecisionQueue"] = [];
DecisionQueueController::AddDecision(1, 'NAMECARD', '', 1, 'Name_a_card');
$queued = GetDecisionQueue(1);
$checks['helper enqueues exactly one row']   = count($queued) === 1;
$checks['queued row is a NAMECARD']          = (string)($queued[0]->Type ?? '') === 'NAMECARD';
$checks['queued NAMECARD Param is empty']    = (string)($queued[0]->Param ?? 'x') === '';
foreach ([1, 2, 3, 4] as $seat) $GLOBALS["p{$seat}DecisionQueue"] = [];

// ── NAMECARD ─────────────────────────────────────────────────────────────────────────────────────
// Param is ALWAYS the empty string at all 10 SWUSim emitters (SOR_062, SOR_185, ASH_077, LAW_243,
// LOF_204, SEC_046, SEC_186, SEC_210, SEC_260, and GameLogic.php's Foresight regroup grant), so
// there is no offered pool to check an answer against — the pool is the whole title universe. The
// answer is a card TITLE WITH SPACES INTACT: every consumer compares it with CardTitle() /
// SWUObjectTitle() and does its own str_replace(' ','_') when it needs to store the name in a
// space-delimited GlobalEffects flag.
//
// A title that no card has is the false-green case: with no arm the validator fell through to the
// blanket `return true` and the continuation quietly matched nothing.
$checks['NAMECARD accepts a real title (no subtitle)']  = $validate(1, 'NAMECARD', '', 'Vanquish') === true;
$checks['NAMECARD accepts a real title with spaces']    = $validate(1, 'NAMECARD', '', 'Luke Skywalker') === true;
$checks['NAMECARD accepts an apostrophe title']         = $validate(1, 'NAMECARD', '', "Emperor's Royal Guard") === true;
$checks['NAMECARD rejects a non-title']                 = $validate(1, 'NAMECARD', '', 'Qqxzy Not A Card') === false;
$checks['NAMECARD rejects a bare CardID']               = $validate(1, 'NAMECARD', '', 'SOR_078') === false;
// The underscored form is what a CONSUMER writes into its own flag, never what it is answered with.
// Accepting it here would let a bridge/test pre-underscore the answer and silently whiff downstream.
$checks['NAMECARD rejects the underscored form']        = $validate(1, 'NAMECARD', '', 'Luke_Skywalker') === false;
// A card whose title is shared by several printings is still ONE name (SOR_078 / TWI_077 Vanquish).
$checks['NAMECARD accepts a reprinted title once']      = $validate(1, 'NAMECARD', '', 'Vanquish') === true;
// A deployed leader matches on its deployed-side name (SWUObjectTitle -> CardLeaderUnitTitle), which
// lives in its own dictionary, so naming it must be legal.
$checks['NAMECARD accepts a deployed-leader title']     = $validate(1, 'NAMECARD', '', 'The Death Star') === true;
// Case-insensitive, matching the client picker's own lookup (Core/NameCardUI.js normalizeName()).
$checks['NAMECARD is case-insensitive']                 = $validate(1, 'NAMECARD', '', 'vanquish') === true;
// Consumers all do trim($lastDecision) before comparing, so surrounding whitespace is not an error.
$checks['NAMECARD tolerates surrounding whitespace']    = $validate(1, 'NAMECARD', '', '  Vanquish  ') === true;

// Declining is genuinely legal: every consumer opens with
//   if (SWUDecisionDeclined($lastDecision)) return;
// (SWUSim/Custom/CardHelpers.php). NAMECARD is not MZCHOOSE, so the validator's own decline gate
// (which refuses PASS/'-'/'' only for the MANDATORY MZCHOOSE type) lets it through ahead of any arm.
$checks['NAMECARD accepts the "-" decline']  = $validate(1, 'NAMECARD', '', '-') === true;
$checks['NAMECARD accepts a PASS decline']   = $validate(1, 'NAMECARD', '', 'PASS') === true;
$checks['NAMECARD accepts an empty decline'] = $validate(1, 'NAMECARD', '', '') === true;
// ⚠ 'NO' is a decline to SWUDecisionDeclined() but NOT to this validator (it is the YESNO button's
// literal, and the NAMECARD client never sends it). Pinned so nobody "fixes" the bridge to emit it.
$checks['NAMECARD does not accept "NO"']     = $validate(1, 'NAMECARD', '', 'NO') === false;

// ── CONTROLS over the pre-existing arms ──────────────────────────────────────────────────────────
// Not the NAMECARD work; they exist so an edit beside them fails here rather than in a live game.
$checks['MZCHOOSE still refuses a decline']       = $validate(1, 'MZCHOOSE', 'myGroundArena-0', 'PASS') === false;
$checks['MZMAYCHOOSE still allows a decline']     = $validate(1, 'MZMAYCHOOSE', 'myGroundArena-0', '-') === true;
$checks['OPTIONCHOOSE still pool-checks labels']  = $validate(1, 'OPTIONCHOOSE', 'Ready&Exhaust', 'Ready') === true
                                                 && $validate(1, 'OPTIONCHOOSE', 'Ready&Exhaust', 'Nope') === false;
$checks['SCRY still pool-checks peeked CardIDs']  = $validate(1, 'SCRY', 'SOR_123,SOR_031', 'SOR_123|SOR_031') === true
                                                 && $validate(1, 'SCRY', 'SOR_123,SOR_031', 'SOR_111|') === false;
$checks['PASSPARAMETER still echoes its Param']   = $validate(1, 'PASSPARAMETER', 'myGroundArena-0', 'myGroundArena-0') === true
                                                 && $validate(1, 'PASSPARAMETER', 'myGroundArena-0', 'myGroundArena-1') === false;

// ── BRIDGE SIDE: the encoder must not pre-underscore, and must offer a decline ───────────────────
// BridgeEnumerateDecisionActions() is the bot's answer encoder. It is loaded at RUNTIME by AzukiSim's
// live in-game bot, so the NAMECARD case is additive and unreachable from any root that never queues
// the type. GrandArchiveSim is the only other root that emits NAMECARD (from
// GrandArchiveSim/Custom/GameLogic.php, with a non-empty Param) and no GA source file requires this
// bridge — the only path that loads it under root=GrandArchiveSim is
// DevTools/rl/train_selfplay_php.php. There the arm is not unreachable, just inert:
// BridgeSWUOwnCardTitles() short-circuits on `!function_exists('CardTitle')`, which GA has none of,
// so a GA NAMECARD yields only the decline — an accident of a missing function, not a guarantee that
// holds for arms added later.
$nameCardDecision = new stdClass();
$nameCardDecision->Type  = 'NAMECARD';
$nameCardDecision->Param = '';
$enumerate = fn(int $player) => array_column(BridgeEnumerateDecisionActions($nameCardDecision, $player), 'cardID');

// ── The zones must be SEEDED, or this whole section is vacuous ───────────────────────────────────
// With no gamestate parsed the seat's hand/discard/deck are all NULL, BridgeSWUOwnCardTitles()
// returns [], and the arm degenerates to "append a decline" — which every check below would pass
// while proving nothing. Worse, an unseeded file stays GREEN if the helper regresses to reading the
// OPPONENT's zones: a bot that names a card out of the enemy deck is CHEATING, which is a worse and
// far less visible failure than the stall this arm replaced. So both seats get distinct, disjoint
// title sets and seat 1's offer is asserted against both.
$seed = function (string $zoneGlobal, array $cardIDs) {
    $GLOBALS[$zoneGlobal] = array_map(function ($cardID) {
        $card = new stdClass();
        $card->CardID = $cardID;
        return $card;
    }, $cardIDs);
};
$seed('p1Hand',    ['SOR_078']);              // Vanquish
$seed('p1Discard', ['SOR_005']);              // Luke Skywalker
$seed('p1Deck',    ['SOR_082', 'SOR_082']);   // Emperor's Royal Guard, twice — dedupes to one title
$seed('p2Hand',    ['SOR_234']);              // Maximum Firepower
$seed('p2Discard', ['SOR_233']);              // I Am Your Father
$seed('p2Deck',    ['SOR_135']);              // Emperor Palpatine

$bridgeCardIDs = $enumerate(1);
$seat2CardIDs  = $enumerate(2);

$checks['bridge answers NAMECARD at all']      = is_array($bridgeCardIDs) && count($bridgeCardIDs) > 0;
$checks['bridge offers a decline']             = in_array('-', $bridgeCardIDs, true);
// The seat's OWN cards, across all three zones, with spaces and exact CardTitle() casing.
$checks['bridge offers a hand title']          = in_array('Vanquish', $bridgeCardIDs, true);
$checks['bridge offers a discard title']       = in_array('Luke Skywalker', $bridgeCardIDs, true);
$checks['bridge offers a deck title']          = in_array("Emperor's Royal Guard", $bridgeCardIDs, true);
$checks['bridge dedupes repeated titles']      = count(array_keys($bridgeCardIDs, "Emperor's Royal Guard", true)) === 1;
// ⚠ THE CHEATING CHECK. Seat 1 must not see a single one of seat 2's titles, and vice versa.
$seat2Titles = ['Maximum Firepower', 'I Am Your Father', 'Emperor Palpatine'];
$seat1Titles = ['Vanquish', 'Luke Skywalker', "Emperor's Royal Guard"];
$checks['seat 1 never sees seat 2 cards']      = array_intersect($seat2Titles, $bridgeCardIDs) === [];
$checks['seat 2 never sees seat 1 cards']      = array_intersect($seat1Titles, $seat2CardIDs) === [];
$checks['seat 2 sees its own cards']           = count(array_intersect($seat2Titles, $seat2CardIDs)) === 3;
// Titles carry spaces; underscoring them here would break every consumer's CardTitle() comparison.
$underscored = array_values(array_filter($bridgeCardIDs, fn($v) => $v !== '-' && strpos($v, '_') !== false));
$checks['bridge never underscores a title']    = $underscored === [];
// The list is SORTED, so which titles survive the cap is a function of the decklist and not of the
// unrevealed deck order. (Cap not bound here; the ordering is the observable part.)
$sortedTitles = array_values(array_filter($bridgeCardIDs, fn($v) => $v !== '-'));
$expectedSort = $sortedTitles; sort($expectedSort, SORT_STRING);
$checks['bridge sorts its titles']             = $sortedTitles === $expectedSort;
$checks['bridge caps its title list']          = count($sortedTitles) <= BridgeNameCardActionCap();
// Whatever it offers must pass the validator it is answering — bridge and engine agree or the bot
// burns steps on refused answers.
$bridgeAnswersValidate = count($bridgeCardIDs) > 1;   // not vacuous over a lone decline
foreach ($bridgeCardIDs as $cardID) {
    if ($validate(1, 'NAMECARD', '', (string)$cardID) !== true) $bridgeAnswersValidate = false;
}
$checks['every bridge answer passes the validator'] = $bridgeAnswersValidate;
// The decline is the answer of LAST resort: the bot's chooser is 'first-legal', so a decline sitting
// at index 0 would make it decline every NAMECARD it ever sees and the arm would prove nothing.
$checks['decline is listed last'] = (end($bridgeCardIDs) === '-');

// EMPTY-SEAT CONTROL: a seat with nothing to name still gets an answer. Zero actions IS the stall.
foreach (['p1Hand', 'p1Discard', 'p1Deck'] as $zoneGlobal) $GLOBALS[$zoneGlobal] = [];
$emptySeat = $enumerate(1);
$checks['bridge never returns zero actions'] = $emptySeat === ['-'];
foreach (['p1Hand', 'p1Discard', 'p1Deck', 'p2Hand', 'p2Discard', 'p2Deck'] as $zoneGlobal) $GLOBALS[$zoneGlobal] = [];

// ── mbstring must be OPTIONAL, not assumed ──────────────────────────────────────────────────────
// Prod LAMPP's PHP is built without extensions the dev container has (the same trap CLAUDE.md
// records for GD/WebP), and an undefined-function fatal on every "Name a card" answer would be
// strictly worse than the stall this task fixed. Every other mb_* call in Core/ / AppCore/ / SWUSim/
// is function_exists-guarded; _SWUNameCardKey() is too. Of the ~1,963 distinct titles exactly one
// (Chirrut Îmwe) folds differently without mbstring, and even that one still matches its printed form.
$checks['_SWUNameCardKey exists']              = function_exists('_SWUNameCardKey');
$checks['no unguarded mb_ in the arm']         = (function () {
    $src = @file_get_contents(__DIR__ . '/../../SWUSim/Custom/GameLogic.php');
    if (!is_string($src)) return false;
    // Every mb_* call in this file must be preceded on the same line by a function_exists guard.
    foreach (explode("\n", $src) as $line) {
        if (strpos($line, '//') === 0 || !preg_match('/(?<![A-Za-z_])mb_[a-z_]+\s*\(/', $line)) continue;
        if (strpos($line, "function_exists('mb_") === false) return false;
    }
    return true;
})();
$checks['key folds a real title']              = _SWUNameCardKey('  Luke Skywalker  ') === 'luke skywalker';
$checks['Chirrut Imwe validates either way']   = $validate(1, 'NAMECARD', '', 'Chirrut Îmwe') === true;


// ════════════════════════════════════════════════════════════════════════════════════════════════
// REVEALARRANGE — validator arm and encoder
// ════════════════════════════════════════════════════════════════════════════════════════════════
// SOR_152 For a Cause I Believe In is the only emitter repo-wide (verified by grep, not assumed).
// Param is the comma-joined revealed CardIDs; the answer is "keptIDs|discardIDs".
//
// ⚠ THE GRAMMAR LOOKS LIKE SCRY'S AND THE SECOND HALF MEANS SOMETHING ELSE. SCRY's is
// "top|BOTTOM"; this one is "kept|DISCARDED". An encoder that copied the sibling case would mill
// the cards it meant to bury, and REVEALARRANGE_FINALIZE's forgiving tail (an unaccounted revealed
// card goes back on top) means neither the engine nor a test would say a word about it. The
// behavioural pin below runs the real finalizer and asserts a card in the second half lands in the
// DISCARD PILE, which is the only assertion that can tell the two grammars apart.
$checks['REVEALARRANGE accepts a full partition']    = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A,C|B') === true;
$checks['REVEALARRANGE accepts keeping all']         = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A,B,C|') === true;
$checks['REVEALARRANGE accepts discarding all']      = $validate(1, 'REVEALARRANGE', 'A,B,C', '|A,B,C') === true;
// Under-naming is legal: the finalizer puts every unaccounted revealed card back on top, so a short
// answer is a real (if lazy) play rather than a malformed one.
$checks['REVEALARRANGE accepts a partial answer']    = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A|') === true;
// The false-green case the arm exists for: naming something that was never revealed used to fall
// through the blanket `return true` and then silently match nothing inside the finalizer.
$checks['REVEALARRANGE rejects an unrevealed ID']    = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A|Z') === false;
$checks['REVEALARRANGE rejects overcounting an ID']  = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A,A|B') === false;
// ... but a CardID revealed TWICE may legitimately be named twice.
$checks['REVEALARRANGE allows a real duplicate']     = $validate(1, 'REVEALARRANGE', 'A,A,B', 'A,A|B') === true;
// A third '|' section is malformed. The explode limit of 2 leaves it inside the second half, where
// it fails membership — deliberately, so it is refused rather than silently dropped.
$checks['REVEALARRANGE rejects a third section']     = $validate(1, 'REVEALARRANGE', 'A,B,C', 'A|B|C') === false;
$checks['REVEALARRANGE accepts a decline']           = $validate(1, 'REVEALARRANGE', 'A,B,C', '-') === true;

// ── NAMETRAIT — validator arm ────────────────────────────────────────────────────────────────────
// HMW_108 The First Legion is the only emitter repo-wide and it is in the HMW preview set. Param is
// the empty string, so the pool is the whole trait universe: SWUAllTraits(), 117 entries derived
// from the generated trait dictionaries.
//
// ⚠ HMW_108 already validates its own answer (strcasecmp against SWUAllTraits() in its handler), so
// this arm is not what stands between a garbage answer and a wrong board today. It is here so a
// second emitter inherits the check, and so the bot encoder has a server-side contract to be tested
// against rather than only the one card that consumes its answers.
$checks['NAMETRAIT accepts a real trait']            = $validate(1, 'NAMETRAIT', '', 'Imperial') === true;
// 14 of the 117 traits are multi-word, and SPACES STAY for the same reason NAMECARD titles keep
// theirs: the answer travels in $lastDecision, and HMW_108's handler does the str_replace(' ','_')
// itself when it arms the SWU_HMW108 flag.
$checks['NAMETRAIT accepts a multi-word trait']      = $validate(1, 'NAMETRAIT', '', 'Bounty Hunter') === true;
$checks['NAMETRAIT rejects the underscored form']    = $validate(1, 'NAMETRAIT', '', 'Bounty_Hunter') === false;
// Case-insensitive, matching the consumer's strcasecmp exactly.
$checks['NAMETRAIT is case-insensitive']             = $validate(1, 'NAMETRAIT', '', 'imperial') === true
                                                     && $validate(1, 'NAMETRAIT', '', 'BOUNTY HUNTER') === true;
$checks['NAMETRAIT tolerates whitespace']            = $validate(1, 'NAMETRAIT', '', '  Imperial  ') === true;
$checks['NAMETRAIT rejects a non-trait']             = $validate(1, 'NAMETRAIT', '', 'Qqxzy Not A Trait') === false;
$checks['NAMETRAIT rejects a bare CardID']           = $validate(1, 'NAMETRAIT', '', 'HMW_108') === false;
// A card TITLE is not a trait — the two free-text pickers must not be interchangeable.
$checks['NAMETRAIT rejects a card title']            = $validate(1, 'NAMETRAIT', '', 'Vanquish') === false;
// Declining is legal at the transport level (NAMETRAIT is not MZCHOOSE) and HMW_108#0 opens by
// returning on it. 'NO' is the YESNO button literal and is not a decline here.
$checks['NAMETRAIT accepts the "-" decline']         = $validate(1, 'NAMETRAIT', '', '-') === true;
$checks['NAMETRAIT accepts a PASS decline']          = $validate(1, 'NAMETRAIT', '', 'PASS') === true;
$checks['NAMETRAIT does not accept "NO"']            = $validate(1, 'NAMETRAIT', '', 'NO') === false;
// The universe really is loaded — otherwise every check above passes for the wrong reason (the arm
// stays permissive when the dictionaries are missing, by design).
$checks['SWUAllTraits is populated']                 = function_exists('SWUAllTraits') && count(SWUAllTraits()) > 50;

// ════════════════════════════════════════════════════════════════════════════════════════════════
// BRIDGE SIDE — SCRY, REVEALARRANGE and NAMETRAIT encoders
// ════════════════════════════════════════════════════════════════════════════════════════════════
$enumerateType = function (string $type, string $param, int $player = 1) : array {
    $decision = new stdClass();
    $decision->Type  = $type;
    $decision->Param = $param;
    return array_column(BridgeEnumerateDecisionActions($decision, $player), 'cardID');
};

// ── SCRY ─────────────────────────────────────────────────────────────────────────────────────────
// Param is the comma-joined PEEKED CardIDs; the answer is "top|bottom", top listed topmost-first.
// The answer space is n!*(n+1) arrangements, and DoScry() has exactly two callers repo-wide —
// SOR_236 R2-D2 (n=1) and SOR_031 Inferno Four (n=2) — so the enumeration is exhaustive rather than
// sampled, and the counts below are the WHOLE space, not a floor.
$scry1 = $enumerateType('SCRY', 'SOR_111');
$scry2 = $enumerateType('SCRY', 'SOR_123,SOR_031');
$checks['SCRY n=1 is the whole space (2)']     = count($scry1) === 2;
$checks['SCRY n=1 offers both extremes']       = $scry1 === ['SOR_111|', '|SOR_111'];
$checks['SCRY n=2 is the whole space (6)']     = count($scry2) === 6;
// EXTREMES FIRST, in the peeked order, so they survive any future truncation — and so the
// 'first-legal' chooser's pick is the identifiable "keep the peek where it is" answer.
$checks['SCRY leads with all-to-top']          = ($scry2[0] ?? '') === 'SOR_123,SOR_031|';
$checks['SCRY follows with all-to-bottom']     = ($scry2[1] ?? '') === '|SOR_123,SOR_031';
$checks['SCRY answers are distinct']           = count(array_unique($scry2)) === count($scry2);
// A CardID peeked twice makes distinct index arrangements collapse to the same answer string; the
// engine's check is multiplicity-based, so they are genuinely interchangeable and offering both
// would just be duplicate actions.
$checks['SCRY dedupes duplicate CardIDs']      = count($enumerateType('SCRY', 'SOR_111,SOR_111')) === 3;
$checks['SCRY stays under its cap']            = count($enumerateType('SCRY', 'A,B,C')) <= BridgeScryActionCap();
// ⚠ The cap has to reach the PERMUTATION GENERATOR, not just the answer list: the generator
// materialises n! arrays, so a hypothetical DoScry(10) would build 3.6M of them before any
// downstream truncation could bite. Identity stays first, so the extremes are still the early answers.
$checks['permutations honour their limit']     = count(BridgeIndexPermutations(5, 4)) === 4
                                               && BridgeIndexPermutations(3, 0)[0] === [0, 1, 2]
                                               && count(BridgeIndexPermutations(3, 0)) === 6;
// Zero actions IS the stall this arm was added to fix; an empty Param is unreachable (DoScry returns
// early on an empty deck) but the floor is kept anyway.
$checks['SCRY never returns zero actions']     = $enumerateType('SCRY', '') === [''];
// Bridge and engine must agree, or the bot burns steps on refused answers.
$scryAnswersValidate = count($scry2) > 1;
foreach (array_merge($scry1, $scry2) as $answer) {
    if ($validate(1, 'SCRY', strpos($answer, 'SOR_111') !== false ? 'SOR_111' : 'SOR_123,SOR_031', (string)$answer) !== true) {
        $scryAnswersValidate = false;
    }
}
$checks['every SCRY answer passes the validator'] = $scryAnswersValidate;

// ── REVEALARRANGE ────────────────────────────────────────────────────────────────────────────────
// 2^n keep/discard SPLITS with the kept half left in the revealed order. The kept half's ORDER is a
// real choice, but the ordered space is Σ C(n,k)·k! (65 at n=4) and a cap-truncated slice of a
// factorial list would make *which* orders are offered an artifact of the enumeration order — so
// ordering is deliberately left to a chooser that can evaluate one order against another. That is a
// bounded design decision, and these checks pin it so a later change is a deliberate one.
//
// EXTREMES FIRST, exactly like SCRY: keep-all and discard-all are emitted before the ascending mask
// walk, so both survive truncation once n is large enough for the cap (BridgeTopDeckSearchActionCap(),
// 40) to bind. n=4's own 16 splits never reach that cap, so the n=6 block further below is the one
// that actually exercises the ordering.
$revealParam = 'SOR_250,SOR_031,SOR_098,SOR_116';   // the Param the probe deck actually produced
$reveal      = $enumerateType('REVEALARRANGE', $revealParam);
$checks['REVEALARRANGE offers 2^n splits']       = count($reveal) === 16;
$checks['REVEALARRANGE leads with keep-all']     = ($reveal[0] ?? '') === $revealParam . '|';
$checks['REVEALARRANGE offers discard-all']      = in_array('|' . $revealParam, $reveal, true);
$checks['REVEALARRANGE answers are distinct']    = count(array_unique($reveal)) === count($reveal);
$checks['REVEALARRANGE respects its cap']        = count($reveal) <= BridgeTopDeckSearchActionCap();
$checks['REVEALARRANGE never returns zero']      = $enumerateType('REVEALARRANGE', '') === [''];
// Every kept half is a SUBSEQUENCE of the revealed order (never a re-ordering), and kept+discarded
// together account for every revealed card exactly once — no card may be silently dropped into the
// finalizer's forgiving tail.
$revealedIDs   = explode(',', $revealParam);
$revealShapeOk = count($reveal) > 1;
foreach ($reveal as $answer) {
    $halves    = explode('|', (string)$answer, 2);
    $kept      = array_values(array_filter(explode(',', $halves[0] ?? '')));
    $discarded = array_values(array_filter(explode(',', $halves[1] ?? '')));
    $merged    = array_merge($kept, $discarded);
    sort($merged);
    $expected  = $revealedIDs;
    sort($expected);
    if ($merged !== $expected) $revealShapeOk = false;
    $inOrder = array_values(array_filter($revealedIDs, fn($id) => in_array($id, $kept, true)));
    if ($inOrder !== $kept) $revealShapeOk = false;
    if ($validate(1, 'REVEALARRANGE', $revealParam, (string)$answer) !== true) $revealShapeOk = false;
}
$checks['REVEALARRANGE answers are well-formed'] = $revealShapeOk;

// n=6 is the smallest reveal count where the cap actually binds (2^6 = 64 > 40): before the
// extremes-first fix, discard-all was the LAST mask generated by the ascending walk and was silently
// dropped once the cap truncated it. SOR_152 never reveals this many, so n=4 above cannot catch a
// regression here — this is the check that can. (Mutation-proven: reverting to the ascending-only
// walk turns 'REVEALARRANGE n=6 offers discard-all' red; see the phase2a final-fix report.)
$revealParam6 = 'A1,A2,A3,A4,A5,A6';
$reveal6      = $enumerateType('REVEALARRANGE', $revealParam6);
$checks['REVEALARRANGE n=6 hits its cap']        = count($reveal6) === BridgeTopDeckSearchActionCap();
$checks['REVEALARRANGE n=6 leads with keep-all'] = ($reveal6[0] ?? '') === $revealParam6 . '|';
$checks['REVEALARRANGE n=6 offers discard-all']  = in_array('|' . $revealParam6, $reveal6, true);

// ── THE GRAMMAR PIN: run the REAL finalizers and watch where the cards land ──────────────────────
// This is the only assertion that can tell "top|bottom" from "kept|discarded" apart, and it is the
// reason it exists: both finalizers accept both grammars without complaint, so a copied encoder is
// invisible everywhere else. The handlers are called directly out of $customDQHandlers with a
// hand-built deck — no game, no gamestate write.
$deckOf = function (array $cardIDs) {
    return array_map(function ($cardID) {
        $card = new stdClass();
        $card->CardID = $cardID;
        return $card;
    }, $cardIDs);
};
$idsOf = fn(array $zone) => implode(',', array_map(fn($c) => (string)($c->CardID ?? ''), $zone));
global $customDQHandlers;
$checks['both finalizers are registered'] = isset($customDQHandlers['SCRY_FINALIZE'])
                                          && isset($customDQHandlers['REVEALARRANGE_FINALIZE']);

// SCRY: the SECOND half is the BOTTOM OF THE DECK. Nothing is discarded, ever.
$GLOBALS['p1Deck']    = $deckOf(['A', 'B', 'C', 'D', 'E']);
$GLOBALS['p1Discard'] = [];
$customDQHandlers['SCRY_FINALIZE'](1, ['2'], '|A,B');            // the bridge's all-to-bottom answer
$checks['SCRY second half goes to the DECK BOTTOM'] = $idsOf($GLOBALS['p1Deck']) === 'C,D,E,A,B'
                                                    && $GLOBALS['p1Discard'] === [];
$GLOBALS['p1Deck'] = $deckOf(['A', 'B', 'C', 'D', 'E']);
$customDQHandlers['SCRY_FINALIZE'](1, ['2'], 'A,B|');            // the bridge's all-to-top answer
$checks['SCRY first half stays on top, in order'] = $idsOf($GLOBALS['p1Deck']) === 'A,B,C,D,E';

// REVEALARRANGE: the SECOND half is the DISCARD PILE. Same shape, different destination.
$GLOBALS['p1Deck']    = $deckOf(['A', 'B', 'C', 'D', 'E']);
$GLOBALS['p1Discard'] = [];
$customDQHandlers['REVEALARRANGE_FINALIZE'](1, ['3'], 'B,C|A');
$checks['REVEALARRANGE second half is DISCARDED'] = $idsOf($GLOBALS['p1Discard']) === 'A';
$checks['REVEALARRANGE kept half is first-on-top'] = $idsOf($GLOBALS['p1Deck']) === 'B,C,D,E';
// And the two grammars really do disagree on the same string: 'B,C|A' buries A under SCRY and mills
// it under REVEALARRANGE. If this ever passes, the finalizers have been unified and the encoders can be.
$GLOBALS['p1Deck']    = $deckOf(['A', 'B', 'C', 'D', 'E']);
$GLOBALS['p1Discard'] = [];
$customDQHandlers['SCRY_FINALIZE'](1, ['3'], 'B,C|A');
$checks['the two finalizers are NOT interchangeable'] = $idsOf($GLOBALS['p1Deck']) === 'B,C,D,E,A'
                                                      && $GLOBALS['p1Discard'] === [];
$GLOBALS['p1Deck'] = [];
$GLOBALS['p1Discard'] = [];

// ── NAMETRAIT ────────────────────────────────────────────────────────────────────────────────────
// Param is empty, so the candidate list is synthesised — from the traits actually on ENEMY CARDS IN
// PLAY. Two boundaries are being asserted here and both matter:
//
//   1. THE INFORMATION BOUNDARY. HMW_108 strips the named trait from enemy cards *including those
//      not in play*, which makes the enemy hand/deck/discard look like fair game for the candidate
//      list. They are not: a real player cannot see them, and a trait sourced from one validates and
//      resolves exactly like a legitimate answer, so the cheat is invisible. Only the public board
//      (arenas, leader, base) may be read. This is the same failure mode BridgeSWUOwnCardTitles
//      guards, one axis over — there the seat's OWN zones are the boundary, here it is IN PLAY.
//   2. THE OWNERSHIP BOUNDARY. "Enemy" is by TEAM and by CONTROLLER, agreeing with HMW_108's own
//      read side (_SWUHmw108TraitSuppressed spares a teammate through SWUTeamOf). A trait that only
//      the deciding seat's own units carry is not a candidate — naming it strips nothing.
foreach ([1, 2, 3, 4] as $seat) {
    foreach (['GroundArena', 'SpaceArena', 'Leader', 'Base', 'Hand', 'Deck', 'Discard', 'GlobalEffects'] as $zone) {
        $GLOBALS["p{$seat}{$zone}"] = [];
    }
}
$inPlay = function (string $cardID, int $controller) {
    $card = new stdClass();
    $card->CardID     = $cardID;
    $card->Controller = $controller;
    return $card;
};
// Seat 2's board: Imperial/Trooper on the ground, Underworld/Bounty Hunter in the arena (a multi-word
// trait), Rebel/Official leader, Eadu base.
$GLOBALS['p2GroundArena'] = [$inPlay('HMW_108', 2), $inPlay('SOR_204', 2)];   // Imperial,Trooper / Underworld,Bounty Hunter
$GLOBALS['p2Leader']      = [$inPlay('SOR_009', 2)];                          // Rebel,Official
$GLOBALS['p2Base']        = [$inPlay('SOR_022', 2)];                          // Eadu
// HIDDEN zones, deliberately stocked with traits that appear nowhere on either board.
$GLOBALS['p2Hand']        = [$inPlay('SOR_233', 2)];                          // Gambit
$GLOBALS['p2Deck']        = [$inPlay('SOR_052', 2)];                          // Rebel,Vehicle,Capital Ship
// Seat 1's OWN board carries a trait seat 2's does not, so "my traits leak into my own offer" fails here.
$GLOBALS['p1SpaceArena']  = [$inPlay('SOR_031', 1)];                          // Imperial,Vehicle,Fighter

$nameTrait1 = $enumerateType('NAMETRAIT', '', 1);
$nameTrait2 = $enumerateType('NAMETRAIT', '', 2);

$checks['bridge answers NAMETRAIT at all']       = count($nameTrait1) > 1;
$checks['NAMETRAIT offers a decline']            = in_array('-', $nameTrait1, true);
// The decline is the answer of LAST resort: 'first-legal' would decline every NAMETRAIT it ever saw
// if this sat at index 0, and the arm would prove nothing.
$checks['NAMETRAIT decline is listed last']      = end($nameTrait1) === '-';
$checks['NAMETRAIT offers an enemy unit trait']  = in_array('Trooper', $nameTrait1, true);
$checks['NAMETRAIT offers an enemy leader trait']= in_array('Official', $nameTrait1, true);
$checks['NAMETRAIT offers an enemy base trait']  = in_array('Eadu', $nameTrait1, true);
// A multi-word trait is offered WITH ITS SPACE — the consumer underscores it itself.
$checks['NAMETRAIT offers a multi-word trait']   = in_array('Bounty Hunter', $nameTrait1, true);
$checks['NAMETRAIT never underscores a trait']   = array_values(array_filter(
                                                       $nameTrait1,
                                                       fn($v) => $v !== '-' && strpos($v, '_') !== false)) === [];
// ⚠ THE CHEATING CHECK. Nothing from an enemy's HIDDEN zones may appear.
$checks['NAMETRAIT never reads the enemy hand']  = !in_array('Gambit', $nameTrait1, true);
$checks['NAMETRAIT never reads the enemy deck']  = !in_array('Capital Ship', $nameTrait1, true);
// ⚠ THE OWNERSHIP CHECK. Seat 1's own Fighter is not an enemy trait; seat 2 sees it and seat 1 does not.
$checks['NAMETRAIT skips your own board']        = !in_array('Fighter', $nameTrait1, true);
$checks['the seats see different traits']        = in_array('Fighter', $nameTrait2, true)
                                                 && !in_array('Trooper', $nameTrait2, true)
                                                 && !in_array('Eadu', $nameTrait2, true);
$checks['NAMETRAIT is sorted']                   = (function () use ($nameTrait1) {
    $traits = array_values(array_filter($nameTrait1, fn($v) => $v !== '-'));
    $sorted = $traits; sort($sorted, SORT_STRING);
    return $traits === $sorted;
})();
$checks['NAMETRAIT respects its cap']            = count($nameTrait1) - 1 <= BridgeNameTraitActionCap();
$checks['NAMETRAIT answers are distinct']        = count(array_unique($nameTrait1)) === count($nameTrait1);
$nameTraitValidates = count($nameTrait1) > 1;
foreach ($nameTrait1 as $answer) {
    if ($validate(1, 'NAMETRAIT', '', (string)$answer) !== true) $nameTraitValidates = false;
}
$checks['every NAMETRAIT answer validates']      = $nameTraitValidates;

// A unit sitting in the enemy's arena that the DECIDING SEAT now controls is not an enemy card.
// Control changes are why the helper classifies by ->Controller instead of by whose zone holds it.
$GLOBALS['p2GroundArena'] = [$inPlay('HMW_108', 1)];
$GLOBALS['p2Leader']      = [];
$GLOBALS['p2Base']        = [];
$checks['NAMETRAIT follows CONTROL, not the zone'] = $enumerateType('NAMETRAIT', '', 1) === ['-'];

// EMPTY-BOARD CONTROL: with no enemy card in play there is still an answer. Zero actions IS the stall.
foreach ([1, 2, 3, 4] as $seat) {
    foreach (['GroundArena', 'SpaceArena', 'Leader', 'Base', 'Hand', 'Deck', 'Discard'] as $zone) {
        $GLOBALS["p{$seat}{$zone}"] = [];
    }
}
$checks['NAMETRAIT never returns zero actions']  = $enumerateType('NAMETRAIT', '', 1) === ['-'];

// ── TEAM SUNS: a TEAMMATE's board is not an enemy board ──────────────────────────────────────────
// Four seats, teams by parity (1,3 Red / 2,4 Blue), which is exactly how HMW_108's own read side
// decides who loses the trait (_SWUHmw108TraitSuppressed spares a teammate through SWUTeamOf). If the
// encoder disagreed with it the bot would name traits its own effect does not strip — a wasted On
// Attack that no 2-player run could ever show, since SWUTeamOf() returns the seat itself there and
// every "is this mine" test degenerates to the same answer.
$teamsMode = new stdClass();
$teamsMode->CardID = 'SWU_MODE_TEAMS';
$GLOBALS['p1GlobalEffects'] = [$teamsMode];
SetSeatOrder('1234');
SetLiveSeats('1234');
$GLOBALS['p2GroundArena'] = [$inPlay('HMW_108', 2)];   // enemy   — Imperial, Trooper
$GLOBALS['p4GroundArena'] = [$inPlay('SOR_204', 4)];   // enemy   — Underworld, Bounty Hunter
$GLOBALS['p3Base']        = [$inPlay('SOR_022', 3)];   // TEAMMATE — Eadu
$teamOffer = $enumerateType('NAMETRAIT', '', 1);
$checks['NAMETRAIT sees both enemy seats']   = in_array('Trooper', $teamOffer, true)
                                             && in_array('Bounty Hunter', $teamOffer, true);
$checks['NAMETRAIT spares a teammate']       = !in_array('Eadu', $teamOffer, true);
$GLOBALS['p1GlobalEffects'] = [];
SetSeatOrder('12');
SetLiveSeats('12');
foreach ([1, 2, 3, 4] as $seat) {
    foreach (['GroundArena', 'SpaceArena', 'Leader', 'Base', 'Hand', 'Deck', 'Discard'] as $zone) {
        $GLOBALS["p{$seat}{$zone}"] = [];
    }
}


$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
