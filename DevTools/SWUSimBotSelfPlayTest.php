<?php
// Headless bot-vs-bot self-play test for SWUSim — no browser, no HTTP, no APCu-auth plumbing.
// Drives the bot exactly the way the real client's polling loop does (mode 10017 in
// Core/EngineActionRunner.php -> SWUSim/BotController.php's ProcessBotControllerStep(), which
// enumerates legal actions via SWUSim/BotLegalActions.php and executes the chosen one via
// EngineExecuteLoadedAction()), but calls that function directly in one CLI process — the same
// shape as DevTools/GABotSelfPlayTest.php, which this is modelled on.
//
// This is the FIRST thing that runs the Bot Practice machinery against a real SWU game. Its job is
// to find, empirically, which of SWUSim's decision types the DecisionQueue bridge does not cover,
// without auditing 1,608 card implementations. A failure here is the harness working.
//
// ⚠ WHAT A GREEN RUN DOES AND DOES NOT PROVE  (last verified: Phase 2a Task 6)
//
// Green means: THE MACHINERY TERMINATES, and the DecisionQueue bridge covers every decision type
// THESE FIXTURES RAISE, under the chooser THIS RUN USED. It does NOT mean the bridge is complete.
//
// As of Phase 2a, green has been reproduced on THREE fixture pairs x BOTH chooser profiles (6
// sweeps, 24 games each, all 24/24 gaps=0):
//   SOR pair             SWUSim/Tests/BotFixtures/premier_deck_a.txt / _b.txt
//   Modern pair          SWUSim/Tests/BotFixtures/premier_modern_deck_a.txt / _b.txt
//   Legacy probe deck    SWUSim/Tests/BotFixtures/decision_type_probe_deck.txt (mirrored)
// x chooser=first-legal and chooser=random (SWUSim/BotHeuristic.php profiles).
//
// Phase 1's original prediction here named four next gaps: SCRY, MZREARRANGE variants,
// TWOSIDEDSLIDER, and capture/rescue prompts. Three of those four are now resolved, NOT still open:
//   SCRY           — bridged (Task 2). The probe deck's SOR_031/SOR_236 exercise it under both
//                    choosers (e.g. 410 offered / 141 chosen under first-legal on the probe deck).
//   MZREARRANGE    — turned out to be a non-issue for SWUSim, not a latent gap: SWUSim has ZERO
//                    AddDecision emitters of this type (verified Task 6). The bridge case exists
//                    only because GrandArchiveSim, AzukiSim and HellbreakSim emit it — see the
//                    per-case note in DevTools/TestAutomationBridge.php.
//   TWOSIDEDSLIDER — same non-issue, one step further: NO root in the current tree has a live
//                    AddDecision emitter for this type (verified Task 6) — it exists only as a
//                    generic decision-type primitive the schema generator and a couple of goldfish/
//                    bot resolvers know how to answer IF something ever raises it. Nothing does.
// REVEALARRANGE (SOR_152, Task 3) and NAMETRAIT (HMW_108, Task 4) were also closed along the way —
// neither was on Phase 1's list because neither had been discovered yet.
//
// Genuinely still unproven, carried forward rather than closed:
//   capture / rescue prompts — CR 8.34 (CaptureUnit / RescueUnit); no fixture here raises them.
//   NUMBERCHOOSE             — bridge arm + validator arm exist, and SWUSim has 12 real
//                              AddDecision emitters (SEC_/LAW_/TWI_/ASH_/SOR_/HMW_ cards plus 2
//                              GameLogic.php sites), but NONE of them have fired in any of the six
//                              sweeps above — the emitting cards never reached a played/triggered
//                              state on these fixtures under either chooser.
//   MZSPLITASSIGN            — same shape: bridge + validator arm exist, 9 real SWUSim emitters,
//                              zero appearances in any sweep's [COVERAGE] table to date.
//   OPTIONCHOOSE:Heal0/Heal2 — DO appear (they are offered), but only 2-3 times across all six
//                              sweeps and never CHOSEN — the answer-application path for those two
//                              labels is offered-but-inert evidence, not exercised evidence.
// A family showing up in a [COVERAGE] table at all only proves the OFFER path; "!NEVER-CHOSEN"
// rows in that table are the same caveat the discovery report raised for unit Activate.
//
// The two PARTITION arms have their own version of that caveat under the DEFAULT chooser
// specifically: 'first-legal' always takes index 0, and both arms list their null answer first
// (SCRY's is "everything stays on top, order unchanged"; REVEALARRANGE's is "keep everything,
// discard nothing"). So a first-legal-only green on SCRY/REVEALARRANGE proves those arms are
// REACHABLE and their answer grammar round-trips the engine, NOT that a non-trivial split or
// arrangement does. The 'random' sweeps are what actually land on the other candidates for both.
//

// Do NOT pre-emptively add arms for what's still unproven above. The rule this harness runs on is
// "fix only what the harness reports", because an unverifiable encoder is worse than a missing one
// — it turns a loud stall into a silently wrong answer. Closing NUMBERCHOOSE/MZSPLITASSIGN needs a
// fixture whose emitting cards actually get played and triggered (a deck built to force the play,
// not just include the card); closing capture/rescue needs a fixture pair from a set with those
// keywords (JTL/LOF/SEC).
//
// Usage (inside the SWUSim web container):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/SWUSimBotSelfPlayTest.php \
//     [--deck=<path>] [--deck2=<path>] [--max-steps=3000] [--verbose] [--first-player=1|2]
//     [--seed=<string>|random] [--games=N] [--chooser=first-legal|random|heuristic-aggro|...]
//     [--chooser2=<profile>]
//
// --chooser= selects a profile registered in SWUSim/BotHeuristic.php. The DEFAULT is 'first-legal',
// so every pre-existing invocation of this file is byte-for-byte unchanged. 'random' selects
// uniformly instead of always taking index 0, which is the only way the enumerator's rarely-first
// arms (unit activated abilities, the second label of an OPTIONCHOOSE, the non-trivial SCRY /
// REVEALARRANGE / NAMETRAIT answers) ever get executed — see the [COVERAGE] table below for the
// per-family evidence, which is what measured them at 0% under 'first-legal' in the first place.
//
// --chooser2= gives SEAT 2 its own profile (default: --chooser's value), through the per-seat override
// SWUBotSetForcedChooserProfileForSeat() — how the RL bots spec's style pairings are played
// (heuristic-aggro vs heuristic-control, …). Without it every line of output is unchanged.
//
// Every game also prints one `SWUBOT_METRICS {json}` line (seed, firstPlayer, winner, rounds,
// baseDamageDealt per seat, the heuristic stack's per-seat rule coverage, chooser per seat), and a sweep
// aggregates them into [SWEEP METRICS] / [SWEEP RULES] lines: median rounds, mean base damage dealt per
// seat, rule firings per seat, and the rules that never fired. An `invalid:<rule>` coverage entry — a
// rule that answered outside the candidate set — fails the sweep.
//
// --games=N runs a SWEEP of N games instead of one. Use it: a single game is not sufficient
// evidence for this harness. Of the three infinite loops Task 8 found, the third (a refused upgrade
// play committing SWU_CARDS_PLAYED, so the no-op detector could never fire) did NOT occur on the
// default seed and was found only because a multi-game sweep was run. A one-game green is a weaker
// signal than it looks.
//
// `apc.enable_cli=1` is required: SWUSetupGame() stores auth metadata via APCu
// (SimGameWriteAuthKeysFromLobby) and throws if that store fails; the CLI SAPI disables APCu by
// default. Everything here is one continuous process, so that APCu state never needs to outlive
// this invocation.

// Homebrew/dev PHP does not always ship APCu. Same in-memory compatibility layer GA's harness
// uses; it has exactly the lifetime a single-process run needs. Web requests use the real one.
if (!function_exists('apcu_store')) {
  $GLOBALS['SWUBotTestUsingApcuFallback'] = true;
  $GLOBALS['SWUBotTestApcu'] = [];
  function apcu_store($key, $value, $ttl = 0) { $GLOBALS['SWUBotTestApcu'][strval($key)] = $value; return true; }
  function apcu_fetch($key, &$success = null) {
    $key = strval($key);
    $success = array_key_exists($key, $GLOBALS['SWUBotTestApcu']);
    return $success ? $GLOBALS['SWUBotTestApcu'][$key] : false;
  }
  function apcu_exists($key) { return array_key_exists(strval($key), $GLOBALS['SWUBotTestApcu']); }
  function apcu_delete($key) {
    $key = strval($key);
    $existed = array_key_exists($key, $GLOBALS['SWUBotTestApcu']);
    unset($GLOBALS['SWUBotTestApcu'][$key]);
    return $existed;
  }
  function apcu_inc($key, $step = 1, &$success = null, $ttl = 0) {
    $key = strval($key);
    if (!array_key_exists($key, $GLOBALS['SWUBotTestApcu'])) { $success = false; return false; }
    $GLOBALS['SWUBotTestApcu'][$key] += $step;
    $success = true;
    return $GLOBALS['SWUBotTestApcu'][$key];
  }
}

require_once __DIR__ . '/../Core/EngineActionRunner.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
// Same runtime a real SWUSim request has (Core/NetworkingLibraries.php in particular, which
// EngineExecuteLoadedAction's cache bookkeeping needs).
EngineLoadRootRuntime('SWUSim');
if (!empty($GLOBALS['SWUBotTestUsingApcuFallback'])) $GLOBALS['APCuEnabled'] = true;
// No ambient $lobby in scope here, so CreateGame.php's auto-run guard stays quiet; this just
// defines SWUSetupGame() (and pulls in GameLogic.php and the card ability files).
require_once __DIR__ . '/../SWUSim/CreateGame.php';

$swuDir = __DIR__ . '/../SWUSim/';

function SWUBotTestParseArgs($argv) {
  $args = ['deck' => null, 'deck2' => null, 'maxSteps' => 3000, 'maxRounds' => 0, 'verbose' => false, 'firstPlayer' => 1,
           'seed' => 'swusimbotselfplay00000000000000', 'games' => 1, 'chooser' => 'first-legal', 'chooser2' => null];
  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--deck=')) $args['deck'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--deck2=')) $args['deck2'] = substr($arg, 8);
    elseif (str_starts_with($arg, '--max-steps=')) $args['maxSteps'] = intval(substr($arg, 12));
    // RL training's round cap (spec Section 4: 1.5x the top of the pairing's range). 0 = no cap.
    elseif (str_starts_with($arg, '--max-rounds=')) $args['maxRounds'] = max(0, intval(substr($arg, 13)));
    elseif (str_starts_with($arg, '--first-player=')) $args['firstPlayer'] = intval(substr($arg, 15));
    // A FIXED seed by default, so a stall this harness finds can be re-entered and diagnosed instead
    // of vanishing on the next run. --seed=random asks for a fresh unpredictable game.
    elseif (str_starts_with($arg, '--seed=')) $args['seed'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--games=')) $args['games'] = max(1, intval(substr($arg, 8)));
    elseif (str_starts_with($arg, '--chooser=')) $args['chooser'] = substr($arg, 10);
    elseif (str_starts_with($arg, '--chooser2=')) $args['chooser2'] = substr($arg, 11);
    elseif ($arg === '--verbose') $args['verbose'] = true;
  }
  return $args;
}

$args = SWUBotTestParseArgs($argv);
$fixtureDir = __DIR__ . '/../SWUSim/Tests/BotFixtures';
$deckPath1 = $args['deck'] ?? ($fixtureDir . '/premier_deck_a.txt');
$deckPath2 = $args['deck2'] ?? ($fixtureDir . '/premier_deck_b.txt');
if (!is_file($deckPath1)) { fwrite(STDERR, "deck file not found: $deckPath1\n"); exit(1); }
if (!is_file($deckPath2)) { fwrite(STDERR, "deck file not found: $deckPath2\n"); exit(1); }

// Validated in the PARENT too, before any child is spawned: SWUBotChooseAction() silently falls
// back to 'first-legal' for an unregistered profile name, so a typo in --chooser= would otherwise
// produce a fully green 24-game sweep that proves nothing about the profile the operator asked for.
$registeredChoosers = array_keys($GLOBALS['SWUBotChoosers'] ?? []);
if (!in_array($args['chooser'], $registeredChoosers, true)) {
  fwrite(STDERR, "unknown --chooser='{$args['chooser']}'; registered profiles: "
    . implode(', ', $registeredChoosers) . "\n");
  exit(1);
}
if ($args['chooser2'] !== null && !in_array($args['chooser2'], $registeredChoosers, true)) {
  fwrite(STDERR, "unknown --chooser2='{$args['chooser2']}'; registered profiles: "
    . implode(', ', $registeredChoosers) . "\n");
  exit(1);
}

// ── Offered-vs-chosen coverage accounting ────────────────────────────────────────────────────────
// The instrument that produced this phase's motivating number: "myGroundArena-N!CustomInput!Activate
// was offered 260 times across 24 games and chosen 0 times". A green sweep says the machinery
// terminates; it says NOTHING about how much of the enumerator the run actually executed, and under
// 'first-legal' the answer was "whatever landed at index 0". That measurement was previously taken
// by hand-instrumenting the enumerator in a throwaway session, which is why it had to be retaken
// from scratch every time somebody wanted it. It lives here now.
//
// FAMILY GROUPING (as specified by the task, one deviation, called out below):
//   "<mz>!CustomInput!<verb>"  -> the verb            ("Activate", "Pass", "DeployLeader:Unit")
//   "<mz>-N!FSM!"              -> "FSM:<zone>"        ("FSM:myHand", "FSM:myGroundArena")
//   a mode-100 decision answer -> "decision:<Type>"   (the pending DecisionQueue entry's Type)
//
// DEVIATION: YESNO and OPTIONCHOOSE additionally carry the chosen/offered LABEL
// ("decision:OPTIONCHOOSE:Pilot"). Whether the bot ever takes the SECOND label — the Pilot half of
// "Unit&Pilot" — is one of the two questions this task exists to answer, and a bare
// "decision:OPTIONCHOOSE" row cannot answer it. Both types have a small, fixed, human-readable
// answer vocabulary, so the subdivision stays readable; every other type's answers are mzIDs,
// comma-joined CardID lists or whole assignment strings and would make the table useless.
//
// COUNTING RULES, so the numbers are not over-read:
//   offered — one per candidate, per chooser invocation. ProcessBotControllerStep() re-invokes the
//             chooser against a SHRUNKEN candidate set after a no-op, so a step with retries counts
//             its surviving candidates more than once. That is the honest reading of "was on the
//             table when the chooser was asked"; retries are separately reported as maxRetries.
//   chosen  — one per chooser invocation, for the action returned.
//   applied — the subset of `chosen` that actually moved the comparable gamestate hash. This column
//             is not in the task brief and is the point of the exercise: "Activate chosen 41 times,
//             applied 0 times" is a bug report, while "chosen 41, applied 41" is coverage.
//
// ⚠ WHAT THE TABLE STILL CANNOT SEE, so it is not over-read: for a DECISION family, `applied` only
// means the answer was accepted and the queue advanced — popping the decision moves the gamestate by
// itself. A SCRY answered "keep everything where it is" therefore counts as applied exactly like a
// SCRY that rearranged the top of the deck. The family grouping is the grouping the task specified
// and it measures SELECTION PRESSURE (was this arm ever picked?), not answer richness inside one
// decision type. Distinguishing a trivial answer from an interesting one within a type needs
// per-answer accounting, which is deliberately only done for the two label-vocabulary types above.
function SWUBotTestActionFamily($cardID, $mode, $kind, $decisionType) {
  $cardID = strval($cardID);
  if ($kind === 'decision' || intval($mode) === 100) {
    $type = ($decisionType !== '' && $decisionType !== null) ? strval($decisionType) : 'UNKNOWN';
    if ($type === 'YESNO' || $type === 'OPTIONCHOOSE') {
      return 'decision:' . $type . ':' . ($cardID === '' ? '(empty)' : $cardID);
    }
    return 'decision:' . $type;
  }
  $parts  = explode('!', $cardID);
  $mz     = strval($parts[0] ?? '');
  $widget = strval($parts[1] ?? '');
  $verb   = strval($parts[2] ?? '');
  if ($widget === 'CustomInput') return $verb === '' ? 'CustomInput:(no verb)' : $verb;
  if ($widget === 'FSM')         return 'FSM:' . preg_replace('/-\d+$/', '', $mz);
  return 'other:' . preg_replace('/-\d+/', '-N', $cardID);
}

function SWUBotTestRecordCoverage($family, $column, $count = 1) {
  if (!isset($GLOBALS['SWUBotTestCoverage'])) $GLOBALS['SWUBotTestCoverage'] = [];
  if (!isset($GLOBALS['SWUBotTestCoverage'][$family])) {
    $GLOBALS['SWUBotTestCoverage'][$family] = ['offered' => 0, 'chosen' => 0, 'applied' => 0];
  }
  $GLOBALS['SWUBotTestCoverage'][$family][$column] += intval($count);
}

// The pending decision's Type, for family naming. SWUBotLegalActions() collapses the bridge's
// result to {kind, playerID, actions} and drops the entry, so it has to be re-read here — from the
// SAME "first non-removed entry" rule SWUBotPendingDecisionSeat() and the bridge both use, so the
// label can never describe a different decision from the one being answered.
function SWUBotTestPendingDecisionType($seat) {
  if (!function_exists('GetDecisionQueue')) return '';
  foreach (GetDecisionQueue(intval($seat)) as $entry) {
    if ($entry === null || !empty($entry->removed)) continue;
    return strval($entry->Type ?? '');
  }
  return '';
}

function SWUBotTestPrintCoverage($coverage, $heading) {
  if (empty($coverage)) { echo "{$heading}\n  (no chooser invocations recorded)\n"; return; }
  uasort($coverage, function ($a, $b) {
    return ($b['offered'] <=> $a['offered']) ?: ($b['chosen'] <=> $a['chosen']);
  });
  $width = strlen('family');
  foreach (array_keys($coverage) as $family) $width = max($width, strlen($family));
  $width = min($width, 60);
  echo "{$heading}\n";
  echo '  ' . str_pad('family', $width) . str_pad('offered', 10, ' ', STR_PAD_LEFT)
     . str_pad('chosen', 9, ' ', STR_PAD_LEFT) . str_pad('applied', 10, ' ', STR_PAD_LEFT) . "\n";
  foreach ($coverage as $family => $counts) {
    // A family with offers and zero picks is the whole point of the table; mark it so it cannot be
    // lost in a long list. '!' = never chosen, '~' = chosen but never actually applied.
    $flag = intval($counts['chosen']) === 0 ? ' !NEVER-CHOSEN'
          : (intval($counts['applied']) === 0 ? ' ~NEVER-APPLIED' : '');
    echo '  ' . str_pad(substr($family, 0, $width), $width)
       . str_pad(strval($counts['offered']), 10, ' ', STR_PAD_LEFT)
       . str_pad(strval($counts['chosen']),   9, ' ', STR_PAD_LEFT)
       . str_pad(strval($counts['applied']), 10, ' ', STR_PAD_LEFT) . $flag . "\n";
  }
}

// ── Sweep mode (--games=N) ───────────────────────────────────────────────────────────────────────
// Runs N games and fails if ANY of them fails. Game k gets seed "sNN" (NN = ceil(k/2)) and first
// player 1 or 2 alternately, so --games=24 is exactly the 12-seed x both-first-players sweep that
// found the third infinite loop. The default is still a single game, so the quick path stays quick.
//
// Each game is a SEPARATE PHP PROCESS rather than a loop in this one, deliberately. A game leaves
// state behind in three places this process cannot fully reset — the engine's promoted globals
// (EngineLoadRootRuntime hoists every root registry into $GLOBALS), APCu (the gamestate cache and
// the auth record), and DecisionQueueController's static variable store. Looping in-process would
// let game 7 inherit game 6's residue, and a sweep whose games are not independent cannot be used
// as evidence that a game is reproducible from its seed — which is the whole reason the seed exists.
// The child re-invocation costs ~1s of runtime load per game; independence is worth it.
function SWUBotTestRunSweep(array $args, $selfPath) {
  $games = intval($args['games']);
  $seeds = intdiv($games + 1, 2);
  echo "[SWEEP] {$games} game(s) — {$seeds} seed(s) x both first players, max-steps={$args['maxSteps']}"
     . " chooser={$args['chooser']}" . ($args['chooser2'] !== null ? " chooser2={$args['chooser2']}" : '') . "\n";

  $passed = 0; $failed = 0; $totalGaps = 0; $gapLines = []; $signals = []; $coverage = []; $metrics = [];
  for ($game = 1; $game <= $games; ++$game) {
    $seed = 's' . str_pad(strval(intdiv($game + 1, 2)), 2, '0', STR_PAD_LEFT);
    $firstPlayer = (($game - 1) % 2) + 1;

    $cmd = escapeshellarg(PHP_BINARY)
      . ' -d apc.enable_cli=1 -d xdebug.mode=off -d xdebug.start_with_request=no '
      . escapeshellarg($selfPath)
      . ' --games=1'
      . ' --seed=' . escapeshellarg($seed)
      . ' --first-player=' . intval($firstPlayer)
      . ' --max-steps=' . intval($args['maxSteps'])
      . ' --max-rounds=' . intval($args['maxRounds']);
    if ($args['deck']  !== null) $cmd .= ' --deck='  . escapeshellarg($args['deck']);
    if ($args['deck2'] !== null) $cmd .= ' --deck2=' . escapeshellarg($args['deck2']);
    // Forwarded the same way --deck/--deck2 are: the sweep's children are separate PHP processes
    // and inherit NOTHING from this one, so a flag that is not on the child command line does not
    // reach the game. Always passed, not just when non-default, so the child's own [SWEEP]/[RESULT]
    // reporting states the profile it actually ran under.
    $cmd .= ' --chooser=' . escapeshellarg($args['chooser']);
    if ($args['chooser2'] !== null) $cmd .= ' --chooser2=' . escapeshellarg($args['chooser2']);
    // stderr carries the bot's own error_log chatter (the "excluding and retrying" lines), which is
    // per-step diagnostic noise, not a result. Everything a verdict depends on is on stdout.
    $cmd .= ' 2>/dev/null';

    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    // The child emits one machine-readable [RESULT] line; fall back to the exit code if it is
    // missing (a fatal before the report, for example), so a crashed child can never read as a pass.
    $result = null;
    foreach ($output as $line) {
      if (strpos($line, 'SWUBOT_METRICS ') === 0) {
        $m = json_decode(substr($line, 15), true);
        if (is_array($m)) $metrics[] = $m;
        continue;
      }
      if (strpos($line, '[RESULT] ') !== 0) continue;
      $decoded = json_decode(substr($line, 9), true);
      if (is_array($decoded)) $result = $decoded;
    }

    $label = sprintf('[GAME %02d] seed=%s fp=%d', $game, $seed, $firstPlayer);
    if ($result === null) {
      ++$failed;
      echo "{$label}  FAIL  (no [RESULT] line; child exit={$exitCode})\n";
      foreach ($output as $line) echo "    | {$line}\n";
      continue;
    }

    $totalGaps += intval($result['gaps'] ?? 0);
    foreach ((array)($result['coverage'] ?? []) as $family => $counts) {
      if (!isset($coverage[$family])) $coverage[$family] = ['offered' => 0, 'chosen' => 0, 'applied' => 0];
      $coverage[$family]['offered'] += intval($counts['offered'] ?? 0);
      $coverage[$family]['chosen']  += intval($counts['chosen']  ?? 0);
      $coverage[$family]['applied'] += intval($counts['applied'] ?? 0);
    }
    if (!empty($result['failureSignal'])) $signals[] = $seed . '/fp' . $firstPlayer . ':' . $result['failureSignal'];
    foreach (($result['gapList'] ?? []) as $gap) $gapLines[] = $seed . '/fp' . $firstPlayer . ': ' . $gap;

    $ok = ($exitCode === 0) && empty($result['fail']);
    if ($ok) ++$passed; else ++$failed;
    echo sprintf("%s  %s  %d/%d checks  steps=%-4d winner=P%s gaps=%d maxRetries=%d nonPass=%d/%d\n",
      $label, $ok ? 'PASS' : 'FAIL',
      intval($result['pass'] ?? 0), intval($result['total'] ?? 0),
      intval($result['steps'] ?? 0), strval($result['winner'] ?? '?'),
      intval($result['gaps'] ?? 0), intval($result['maxRetries'] ?? 0),
      intval($result['nonPass1'] ?? 0), intval($result['nonPass2'] ?? 0));
    // A failing game's own output is the diagnosis (the [FAIL] lines, the [STALL]/[TIMEOUT] board
    // dump, the GAP lines). Print it rather than making someone re-run that seed by hand.
    if (!$ok) foreach ($output as $line) echo "    | {$line}\n";
  }

  foreach (array_unique($gapLines) as $gap) echo "  GAP {$gap}\n";
  foreach (array_unique($signals) as $signal) echo "  SIGNAL {$signal}\n";
  SWUBotTestPrintCoverage($coverage, "[SWEEP COVERAGE] offered/chosen/applied by action family, all {$games} game(s), chooser={$args['chooser']}");
  echo "[SWEEP SUMMARY] {$passed}/{$games} games passed | gaps={$totalGaps} | controller failure signals="
     . count(array_unique($signals)) . "\n";
  $invalid = SWUBotTestPrintSweepMetrics($metrics);
  return ($failed === 0 && $invalid === 0) ? 0 : 1;
}

// Aggregates the per-game SWUBOT_METRICS lines. Returns the number of invalid:<rule> coverage entries (a
// rule answered outside the candidate set), which fails the sweep.
function SWUBotTestPrintSweepMetrics(array $metrics) {
  if (empty($metrics)) return 0;
  $rounds = array_map(fn($m) => intval($m['rounds'] ?? 0), $metrics);
  sort($rounds);
  $n = count($rounds);
  $median = $n % 2 ? $rounds[intdiv($n, 2)] : ($rounds[$n / 2 - 1] + $rounds[$n / 2]) / 2;
  $dealt = [1 => 0, 2 => 0]; $wins = [1 => 0, 2 => 0]; $rules = []; $invalid = 0;
  foreach ($metrics as $m) {
    foreach ([1, 2] as $s) {
      $dealt[$s] += intval($m['baseDamageDealt'][$s] ?? 0);
      foreach ((array)($m['coverage'][$s] ?? []) as $key => $count) {
        $rules[$key][$s] = ($rules[$key][$s] ?? 0) + intval($count);
        if (str_starts_with(strval($key), 'invalid:')) $invalid += intval($count);
      }
    }
    $w = intval($m['winner'] ?? 0);
    if (isset($wins[$w])) $wins[$w]++;
  }
  $c = $metrics[0]['chooser'] ?? [];
  echo sprintf("[SWEEP METRICS] chooser1=%s chooser2=%s games=%d rounds median=%s min=%d max=%d | wins P1=%d P2=%d"
    . " | mean base damage dealt P1=%.1f P2=%.1f | invalid=%d\n",
    strval($c[1] ?? '?'), strval($c[2] ?? '?'), $n, strval($median), $rounds[0], $rounds[$n - 1],
    $wins[1], $wins[2], $dealt[1] / $n, $dealt[2] / $n, $invalid);
  ksort($rules);
  foreach ($rules as $key => $bySeat) {
    echo sprintf("[SWEEP RULES] %-34s P1=%-5d P2=%d\n", $key, intval($bySeat[1] ?? 0), intval($bySeat[2] ?? 0));
  }
  // Name the rules the fixtures never exercised (a report, not a gate).
  if (function_exists('SWUBotRulesBeforeFilter') && !empty($rules)) {
    $never = [];
    foreach (array_keys(array_merge(SWUBotRulesBeforeFilter(), SWUBotRulesAfterFilter())) as $name) {
      if (!isset($rules["rule:$name"])) $never[] = $name;
    }
    echo "[SWEEP RULES] never fired: " . (empty($never) ? '(none)' : implode(', ', $never)) . "\n";
  }
  return $invalid;
}

if ($args['games'] > 1) exit(SWUBotTestRunSweep($args, __FILE__));

$checks = [];
function SWUBotTestCheck(&$checks, $label, $passed, $detail = '') { $checks[] = [$label, $passed, $detail]; }

// ── Observation seam: wrap the registered chooser ────────────────────────────────────────────────
// ProcessBotControllerStep() does not report WHICH action it applied, and its internal
// exclude-and-retry loop count is not returned either. Both are needed for assertions 3 and 4.
// Rather than instrument production code, re-register 'first-legal' as a recording wrapper around
// the ORIGINAL callable — identical selection semantics, plus a per-step log of every candidate the
// chooser handed back. The last entry of a step whose result reports applied=true is the action
// that actually stuck; every earlier entry in that step was a no-op that got excluded and retried.
//
// The wrapper is installed over the SELECTED profile (--chooser=, default 'first-legal') and
// re-registered under that same name, so SWUBotActiveChooserProfile() resolves to it unchanged and
// selection semantics are exactly the wrapped profile's. It also carries the offered-vs-chosen
// accounting, because the chooser call is the ONLY place in the pipeline that sees the full
// candidate set and the pick together.
$GLOBALS['SWUBotTestChoiceLog'] = [];
$GLOBALS['SWUBotTestCoverage'] = [];
$swuBotChooserProfile = strval($args['chooser']);
// --chooser2= puts seat 2 on its own profile. BOTH profiles get the recording wrapper — an unwrapped
// seat-2 profile would log nothing, and the applied/non-pass accounting below reads that log.
$swuBotChooserProfile2 = $args['chooser2'] !== null ? strval($args['chooser2']) : $swuBotChooserProfile;
foreach (array_unique([$swuBotChooserProfile, $swuBotChooserProfile2]) as $swuBotWrapProfile) {
$swuBotOriginalChooser = $GLOBALS['SWUBotChoosers'][$swuBotWrapProfile] ?? null;
if (!is_callable($swuBotOriginalChooser)) {
  fwrite(STDERR, "SWUBotHeuristic did not register a '{$swuBotWrapProfile}' chooser; cannot run.\n");
  exit(1);
}
SWUBotRegisterChooser($swuBotWrapProfile, function (array $actions, array $legal) use ($swuBotOriginalChooser) {
  $kind = strval($legal['kind'] ?? '');
  // Read ONCE per invocation, before the answer is chosen: the queue is untouched at this point, so
  // every candidate in $actions and the pick itself belong to this same decision.
  $decisionType = $kind === 'decision'
    ? SWUBotTestPendingDecisionType(intval($legal['playerID'] ?? 0)) : '';
  foreach ($actions as $candidate) {
    SWUBotTestRecordCoverage(SWUBotTestActionFamily(
      $candidate['cardID'] ?? '', $candidate['mode'] ?? 0, $kind, $decisionType), 'offered');
  }
  $chosen = call_user_func($swuBotOriginalChooser, $actions, $legal);
  $family = SWUBotTestActionFamily($chosen['cardID'] ?? '', $chosen['mode'] ?? 0, $kind, $decisionType);
  if ($chosen !== null) SWUBotTestRecordCoverage($family, 'chosen');
  $GLOBALS['SWUBotTestChoiceLog'][] = [
    'kind'     => $kind,
    'playerID' => intval($legal['playerID'] ?? 0),
    'mode'     => intval($chosen['mode'] ?? 0),
    'cardID'   => strval($chosen['cardID'] ?? ''),
    'inSet'    => in_array($chosen, $actions, true),
    'family'   => $family,
  ];
  return $chosen;
});
}
SWUBotSetForcedChooserProfile($swuBotChooserProfile);
if ($swuBotChooserProfile2 !== $swuBotChooserProfile) SWUBotSetForcedChooserProfileForSeat(2, $swuBotChooserProfile2);

// A "pass" is either the free-play end-of-action Pass wire form or a mode-100 decline of an
// optional decision. Assertion 4 needs the bot to do something that is NEITHER.
function SWUBotTestIsPassLike($entry) {
  $cardID = strval($entry['cardID'] ?? '');
  if ($cardID === 'myHealth-0!CustomInput!Pass') return true;
  if (intval($entry['mode'] ?? 0) === 100 && ($cardID === 'PASS' || $cardID === '-' || $cardID === '')) return true;
  return false;
}

function SWUBotTestStallDiagnostic($swuDir, $gameName, $reason) {
  ParseGamestate($swuDir);
  $queues = [];
  $seatCount = function_exists('GetSeatOrderArray') ? max(2, count(GetSeatOrderArray())) : 2;
  for ($seat = 1; $seat <= $seatCount; $seat++) {
    foreach (GetDecisionQueue($seat) as $entry) {
      if ($entry === null || !empty($entry->removed)) continue;
      $queues[] = [
        'seat' => $seat,
        'type' => strval($entry->Type ?? ''),
        'param' => substr(strval($entry->Param ?? ''), 0, 200),
        'tooltip' => substr(strval($entry->Tooltip ?? ''), 0, 120),
      ];
    }
  }
  echo '[' . strtoupper($reason) . '] ' . json_encode([
    'gameName'       => strval($gameName),
    'turnPlayer'     => intval(GetTurnPlayer()),
    'phase'          => strval(GetCurrentPhase()),
    'turnNumber'     => intval(GetTurnNumber()),
    'pendingSeat'    => function_exists('SWUBotPendingDecisionSeat') ? SWUBotPendingDecisionSeat() : -1,
    'botPlayers'     => function_exists('GetSWUBotPlayers') ? GetSWUBotPlayers() : [],
    'flash'          => substr(strval(GetFlashMessage()), 0, 200),
    'baseDamage'     => [1 => intval((GetBase(1)[0]->Damage ?? -1)), 2 => intval((GetBase(2)[0]->Damage ?? -1))],
    'handCount'      => [1 => count(GetHand(1)), 2 => count(GetHand(2))],
    'decisionQueues' => $queues,
  ], JSON_UNESCAPED_SLASHES) . "\n";
}

// ── Build a bot-vs-bot botpractice game in-process (mirrors JoinQueue's local-mode branch) ───────
$lobby = new stdClass();
$lobby->numPlayers = 2;
$lobby->maxPlayers = 2;
$lobby->format = 'botpractice';
$lobby->isPrivate = true;
$lobby->botPlayers = [1, 2];   // SWUSetupGame() forwards this to SetSWUBotPlayers()
$lobby->players = [
  new Player(1, file_get_contents($deckPath1), ''),
  new Player(2, file_get_contents($deckPath2), ''),
];

$setupOpts = ['forcedFirstPlayer' => $args['firstPlayer']];
if ($args['seed'] !== '' && $args['seed'] !== 'random') $setupOpts['rngSeed'] = $args['seed'];
$gameName = SWUSetupGame($lobby, $setupOpts);
if ($args['verbose']) echo "[INFO] rngSeed: " . ($setupOpts['rngSeed'] ?? '(random)') . "\n";
if ($args['verbose']) echo "[INFO] game created: {$gameName}\n";

ParseGamestate($swuDir);
SWUBotTestCheck($checks, 'game mode is botpractice', SWUGameMode() === 'botpractice', SWUGameMode());
$botSeats = GetSWUBotPlayers();
SWUBotTestCheck($checks, 'both seats are bot-controlled', $botSeats === [1, 2], json_encode($botSeats));
$flash = strval(GetFlashMessage());
SWUBotTestCheck($checks, 'both decks loaded without error',
  stripos($flash, 'could not load') === false && stripos($flash, 'no deck link') === false, $flash);

// ── Drive the bot to completion ──────────────────────────────────────────────────────────────────
$maxConsecutiveNoOps = 20;
$steps = 0;
$consecutiveNoOps = 0;
$stalled = false;
$gameOver = false;
$capped = false;   // --max-rounds reached (RL training scores it -0.25; not a stall)
$winner = 0;
$maxRetriesInOneStep = 0;
$maxRetriesStepDetail = '';
$nonPassActions = [1 => 0, 2 => 0];
$appliedActions = [1 => 0, 2 => 0];
$actionHistogram = [];
$failureSignal = '';
$stepError = '';
if (function_exists('SWUBotResetCoverage')) SWUBotResetCoverage();   // the heuristic stack's per-seat rule log

for (; $steps < $args['maxSteps']; $steps++) {
  // Re-parse before every step, mirroring what a real HTTP request does on every poll. See
  // GABotSelfPlayTest.php's long comment: a single continuous CLI process otherwise carries stale
  // per-object Location/PlayerID/mzIndex fields that a real per-request reparse rebuilds.
  ParseGamestate($swuDir);

  $winner = function_exists('SWUGetGameWinner') ? intval(SWUGetGameWinner()) : 0;
  if ($winner !== 0) { $gameOver = true; break; }
  if ($args['maxRounds'] > 0 && intval(GetTurnNumber()) > $args['maxRounds']) { $capped = true; break; }

  $GLOBALS['SWUBotTestChoiceLog'] = [];
  $result = ProcessBotControllerStep(0, 'SWUSim', $gameName);
  $log = $GLOBALS['SWUBotTestChoiceLog'];

  // Retries within this single poll = every chosen candidate except the last one.
  $retries = max(0, count($log) - 1);
  if ($retries > $maxRetriesInOneStep) {
    $maxRetriesInOneStep = $retries;
    $maxRetriesStepDetail = "step {$steps}: " . json_encode(array_slice($log, 0, 8), JSON_UNESCAPED_SLASHES);
  }

  if (empty($result['success'])) {
    // ProcessBotControllerStep has three distinguishable failure signals, all of which are bugs:
    // cap-tripped, all-candidates-no-op, and (via applied=false with a chooser return of null) a
    // chooser that produced nothing. Report which one fired verbatim.
    $stepError = strval($result['message'] ?? 'unknown engine error');
    if (stripos($stepError, 'retry loop exceeded') !== false)          $failureSignal = 'cap-tripped';
    else if (stripos($stepError, 'only no-op actions') !== false)      $failureSignal = 'all-candidates-no-op';
    else                                                              $failureSignal = 'engine-error';
    SWUBotTestCheck($checks, 'bot step succeeds', false, "step {$steps} [{$failureSignal}]: {$stepError}");
    break;
  }
  if (strpos(strval($result['message'] ?? ''), 'chooser returned no action') !== false) {
    $failureSignal = 'chooser-returned-null';
    SWUBotTestCheck($checks, 'chooser always returns an action', false, "step {$steps}: " . $result['message']);
    break;
  }

  if (!empty($result['applied']) && !empty($log)) {
    $applied = $log[count($log) - 1];
    $seat = intval($applied['playerID']) ?: 0;
    if ($seat === 0) $seat = intval(GetTurnPlayer());
    if (isset($appliedActions[$seat])) {
      $appliedActions[$seat]++;
      if (!SWUBotTestIsPassLike($applied)) $nonPassActions[$seat]++;
    }
    $key = $applied['kind'] . '|' . $applied['mode'] . '|' . preg_replace('/-\d+/', '-N', $applied['cardID']);
    $actionHistogram[$key] = ($actionHistogram[$key] ?? 0) + 1;
    // The last logged choice of a step whose result reports applied=true is the one that stuck.
    if (isset($applied['family'])) SWUBotTestRecordCoverage($applied['family'], 'applied');
  }

  if (empty($result['applied'])) {
    $consecutiveNoOps++;
    if ($consecutiveNoOps >= $maxConsecutiveNoOps) { $stalled = true; break; }
  } else {
    $consecutiveNoOps = 0;
  }

  if ($args['verbose'] && $steps > 0 && $steps % 50 === 0) {
    echo "[INFO] step {$steps} phase=" . GetCurrentPhase() . " turn=" . GetTurnPlayer()
       . " round=" . GetTurnNumber()
       . " baseDmg=" . intval(GetBase(1)[0]->Damage ?? -1) . "/" . intval(GetBase(2)[0]->Damage ?? -1) . "\n";
  }
}

// ── 1. TERMINATION ───────────────────────────────────────────────────────────────────────────────
SWUBotTestCheck($checks, 'game completed', $gameOver,
  $gameOver ? "in {$steps} bot steps, winner=P{$winner}"
    : ($stalled ? "stalled after {$consecutiveNoOps} consecutive no-op steps (around step {$steps})"
      : ($stepError !== '' ? "aborted at step {$steps}: {$stepError}"
        : "hit --max-steps={$args['maxSteps']} without a winner")));
SWUBotTestCheck($checks, 'step budget not exhausted', $steps < $args['maxSteps'], "steps={$steps} budget={$args['maxSteps']}");
// Game-end metrics, read NOW — the hash probes below write to the game. The round counter is
// $gTurnNumber (GetTurnNumber): 1 at the start, +1 at each round's regroup (GameLogic.php ~:6772), so it
// is the round the game ended in. Base damage DEALT by a seat is the damage on the OTHER seat's base.
$swuBotMetrics = [
  'seed'            => $args['seed'],
  'firstPlayer'     => $args['firstPlayer'],
  'winner'          => $winner,
  'rounds'          => intval(GetTurnNumber()),
  'baseDamageDealt' => [1 => intval(GetBase(2)[0]->Damage ?? 0), 2 => intval(GetBase(1)[0]->Damage ?? 0)],
  'coverage'        => [1 => $GLOBALS['SWUBotCoverage'][1] ?? (object)[], 2 => $GLOBALS['SWUBotCoverage'][2] ?? (object)[]],
  'chooser'         => [1 => $swuBotChooserProfile, 2 => $swuBotChooserProfile2],
  'capped'          => $capped,
];
if (!$gameOver && !$capped) SWUBotTestStallDiagnostic($swuDir, $gameName, $stalled ? 'stall' : ($stepError !== '' ? 'error' : 'timeout'));

// ── 2. NO ENUMERATION GAPS ───────────────────────────────────────────────────────────────────────
$gaps = $GLOBALS['SWUBotUnrecognizedDecisions'] ?? [];
$gapSummaries = [];
SWUBotTestCheck($checks, 'no unrecognized decision types', empty($gaps), 'gaps=' . count($gaps));
if (!empty($gaps)) {
  $unique = [];
  foreach ($gaps as $g) {
    $k = $g['type'] . '|' . $g['param'];
    if (!isset($unique[$k])) { $unique[$k] = ['g' => $g, 'n' => 0]; }
    $unique[$k]['n']++;
  }
  foreach ($unique as $u) {
    $g = $u['g'];
    $gapSummaries[] = "type={$g['type']} param=" . substr($g['param'], 0, 120);
    echo "  GAP (x{$u['n']}): type={$g['type']} param=" . substr($g['param'], 0, 240) . " seat={$g['seat']}\n";
  }
}

// ── 3. NO NO-OP LOOP ─────────────────────────────────────────────────────────────────────────────
SWUBotTestCheck($checks, 'no excessive no-op retries', $maxRetriesInOneStep <= 5,
  "maxRetriesInOneStep={$maxRetriesInOneStep}" . ($maxRetriesStepDetail !== '' ? " — {$maxRetriesStepDetail}" : ''));

// ── 4. BOTH SEATS ACT ────────────────────────────────────────────────────────────────────────────
SWUBotTestCheck($checks, 'seat 1 took a non-pass action', $nonPassActions[1] > 0,
  "nonPass={$nonPassActions[1]} applied={$appliedActions[1]}");
SWUBotTestCheck($checks, 'seat 2 took a non-pass action', $nonPassActions[2] > 0,
  "nonPass={$nonPassActions[2]} applied={$appliedActions[2]}");

// ── 5. HASH EXCLUSION DERIVATION ─────────────────────────────────────────────────────────────────
// A deliberately illegal action must not move the comparable hash. If it does, some gamestate block
// mutates on a rejected write and is missing from SWUBotExcludedHashBlocks() — which would strand
// the bot's no-op detector and, through it, the exclude-and-retry loop.
//
// These probes WRITE to the game. For a game that did not complete, the saved state is the stall point the
// sweep keeps for diagnosis (SWUSim/DevTools/rl/sweep_fixtures.sh), and the probes were overwriting it: a
// stalled prompt could vanish from the kept folder. So snapshot the file here and restore it after the probes.
// The checks run exactly as before.
$swuBotGamestatePath = $swuDir . "Games/{$gameName}/Gamestate.txt";
$swuBotStallSnapshot = $gameOver ? null : @file_get_contents($swuBotGamestatePath);
ParseGamestate($swuDir);
$before = SWUBotComparableGamestateHash($gameName);
EngineExecuteLoadedAction(
  ['playerID' => 1, 'mode' => 10002, 'cardID' => 'myHand-9999'],
  'SWUSim', $gameName, ['updateCache' => true]
);
$after = SWUBotComparableGamestateHash($gameName);
SWUBotTestCheck($checks, 'illegal action does not move the hash', $before !== null && $before === $after,
  $before === null ? 'hash unavailable (Regression* helpers not loaded)' : (substr(strval($before), 0, 12) . ' vs ' . substr(strval($after), 0, 12)));

// Companion probe, reported separately so it can never weaken the assertion above: the brief's
// 'myHand-9999' has no '!' at all, so mode 10002's dispatcher hits its default no-op branch before
// reaching ActionMap. This variant carries the real '!FSM!' wire shape, so it goes all the way into
// SWUSim's own play path with a nonexistent index — the case the bot's retry loop actually meets.
ParseGamestate($swuDir);
$beforeFsm = SWUBotComparableGamestateHash($gameName);
EngineExecuteLoadedAction(
  ['playerID' => 1, 'mode' => 10002, 'cardID' => 'myHand-9999!FSM!'],
  'SWUSim', $gameName, ['updateCache' => true]
);
$afterFsm = SWUBotComparableGamestateHash($gameName);
SWUBotTestCheck($checks, 'illegal FSM play does not move the hash', $beforeFsm !== null && $beforeFsm === $afterFsm,
  substr(strval($beforeFsm), 0, 12) . ' vs ' . substr(strval($afterFsm), 0, 12));
if (is_string($swuBotStallSnapshot) && $swuBotStallSnapshot !== '') @file_put_contents($swuBotGamestatePath, $swuBotStallSnapshot);

// ── Report ───────────────────────────────────────────────────────────────────────────────────────
if ($args['verbose'] || !$gameOver) {
  arsort($actionHistogram);
  echo "[ACTIONS] " . json_encode(array_slice($actionHistogram, 0, 25, true), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
}
if ($failureSignal !== '') echo "[SIGNAL] ProcessBotControllerStep failure signal: {$failureSignal}\n";
SWUBotTestPrintCoverage($GLOBALS['SWUBotTestCoverage'] ?? [],
  "[COVERAGE] offered/chosen/applied by action family, chooser={$swuBotChooserProfile}");

$failures = 0;
foreach ($checks as [$label, $passed, $detail]) {
  echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . ($detail !== '' ? " — {$detail}" : '') . "\n";
  if (!$passed) $failures++;
}
echo "[SUMMARY] Total: " . count($checks) . " | Pass: " . (count($checks) - $failures) . " | Fail: {$failures}\n";

// Machine-readable single line for --games=N sweep mode to parse. Emitted on EVERY run (a sweep
// child is just a normal single-game run), so there is one code path and the sweep can never report
// a different verdict from the one printed above.
echo '[RESULT] ' . json_encode([
  'chooser'       => $swuBotChooserProfile,
  'coverage'      => $GLOBALS['SWUBotTestCoverage'] ?? [],
  'total'         => count($checks),
  'pass'          => count($checks) - $failures,
  'fail'          => $failures,
  'steps'         => $steps,
  'winner'        => $winner,
  'gaps'          => count($gaps),
  'gapList'       => array_values(array_unique($gapSummaries)),
  'maxRetries'    => $maxRetriesInOneStep,
  'nonPass1'      => $nonPassActions[1],
  'nonPass2'      => $nonPassActions[2],
  'failureSignal' => $failureSignal,
], JSON_UNESCAPED_SLASHES) . "\n";
echo 'SWUBOT_METRICS ' . json_encode($swuBotMetrics, JSON_UNESCAPED_SLASHES) . "\n";

exit($failures > 0 ? 1 : 0);
