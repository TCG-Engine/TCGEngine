<?php
// The swappable "chooser" half of the SWUSim bot seam:
//   SWUBotLegalActions() -> chooser($actions, $legal) -> one action -> EngineExecuteLoadedAction()
//
// Phase 1 registered ONE profile, 'first-legal', which does no evaluation at all. Its job is to
// prove the enumerate -> choose -> execute loop terminates on a real game before any scoring
// exists. Phase 2a adds 'random', which does no evaluation either but selects UNIFORMLY, so the
// enumerator's rarely-index-0 arms actually execute before a scorer starts selecting into them.
// Phase 2b registers a scoring chooser under a new profile name; a model-backed chooser can
// register later the same way, without touching enumeration or execution.

if (!isset($GLOBALS['SWUBotChoosers'])) $GLOBALS['SWUBotChoosers'] = [];

function SWUBotRegisterChooser($profileName, callable $fn) {
    $GLOBALS['SWUBotChoosers'][strval($profileName)] = $fn;
}

// A PROCESS-LEVEL profile override, for callers that have no persisted per-game bot config.
// Today that is DevTools/SWUSimBotSelfPlayTest.php's --chooser= flag.
//
// It deliberately OUTRANKS the SWUBotProfile DecisionQueue variable. That variable lives inside the
// gamestate's DecisionQueueVariables block, which every engine write re-serializes and every
// ParseGamestate re-reads from disk — so a CLI process cannot hold a value there across a game
// without writing it into the saved game, and an operator-typed flag must not be silently
// overridden by whatever a loaded gamestate happened to carry. Nothing in the running product sets
// this; it is null unless a harness sets it, so the DQ-variable path is unchanged for real games.
function SWUBotSetForcedChooserProfile($profileName) {
    $GLOBALS['SWUBotForcedChooserProfile'] =
        ($profileName === null || strval($profileName) === '') ? null : strval($profileName);
}

function SWUBotActiveChooserProfile() {
    $forced = $GLOBALS['SWUBotForcedChooserProfile'] ?? null;
    if ($forced !== null && strval($forced) !== '') return strval($forced);
    $value = class_exists('DecisionQueueController')
        ? DecisionQueueController::GetVariable('SWUBotProfile') : null;
    return ($value !== null && $value !== '') ? strval($value) : 'first-legal';
}

function SWUBotChooseAction(array $actions, array $legal) {
    if (empty($actions)) return null;
    $profile = SWUBotActiveChooserProfile();
    $chooser = $GLOBALS['SWUBotChoosers'][$profile] ?? $GLOBALS['SWUBotChoosers']['first-legal'] ?? null;
    if ($chooser === null) return $actions[0];
    return call_user_func($chooser, $actions, $legal);
}

SWUBotRegisterChooser('first-legal', function (array $actions, array $legal) {
    return $actions[0] ?? null;
});

// ── 'random' ─────────────────────────────────────────────────────────────────────────────────────
// Uniform over the candidate set. Its job is COVERAGE, not strength: 'first-legal' only ever
// executes whatever happens to sit at index 0 of the enumerator's output, so entire families —
// measured, not guessed — were offered thousands of times and chosen zero times (the unit
// activated-ability arm "…!CustomInput!Activate", 260 offers / 0 picks across 24 games; the second
// label of every OPTIONCHOOSE, including the Pilot half of "Unit&Pilot"). This profile is what
// makes them execute before a scoring chooser starts selecting into them.
//
// ⚠ EngineRandomInt() ONLY — never rand()/mt_rand(). Core/DeterministicRNG.php derives its stream
// from the gamestate plus the per-game RNG_SEED, so a seeded sweep reproduces exactly and undo
// (whose snapshot payload carries $gRandomCounter — Custom/GameLogic.php:19671) restores the same
// stream. A libc-random bot would make every stall this harness finds unreproducible.
//
// ⚠ AND THE DRAW IS COUNTER-NEUTRAL. EngineRandomInt() -> EngineDeterministicBytes() calls
// IncrementDeterministicRandomCounter(), and $gRandomCounter is SERIALIZED into the SWUSim
// gamestate (SWUSim/GamestateParser.php:277) as a trailing line AFTER the last schema-declared
// block — so it is outside every block SWUBotExcludedHashBlocks() is able to name, and it is not
// masked. Consuming a draw here would therefore move SWUBotComparableGamestateHash() across an
// action that changed nothing else, which is precisely the condition that disables
// ProcessBotControllerStep()'s no-op detector and leaves its exclude-and-retry loop re-picking a
// rejected candidate forever (three separate instances of that failure are recorded in
// SWUSim/BotController.php's header).
//
// Measured directly, not theorised — a rejected "myHand-9999!FSM!" against a live modern-fixture
// game, hashing either side of the write exactly the way ProcessBotControllerStep() does:
//   no draw at all           -> before === after   (noOp correctly detected)
//   one CONSUMING draw       -> before !== after   (noOp MISSED — the detector is dead)
//   this chooser's draw      -> before === after   (noOp correctly detected)
// Note the failure is LATENT, not loud: the modern sweep still went 6/6 with a consuming draw,
// because those games never needed the retry loop (maxRetries=0 throughout). Losing a safety net
// silently while the sweep stays green is precisely how the earlier infinite loops got in, which is
// why this is fixed at the source rather than left to be caught by a future red sweep.
//
// Restoring costs nothing in variety, because the hash MATERIAL is the live gamestate and
// EngineSnapshotState() includes $updateNumber, which Core/EngineActionRunner.php:1067 increments
// on every engine write — including the rejected ones inside a single retry loop. Successive draws
// therefore hash different material even at an identical counter: measured over 12 consecutive
// writes against a fixed 4-candidate set, the restored-counter chooser returned all 4 candidates.
SWUBotRegisterChooser('random', function (array $actions, array $legal) {
    $actions = array_values($actions);
    $count = count($actions);
    if ($count === 0) return null;
    if ($count === 1) return $actions[0];
    // Fail SAFE, not open: with no deterministic RNG available, degrade to 'first-legal' rather
    // than reaching for mt_rand() and silently making the run unreproducible.
    if (!function_exists('EngineRandomInt') || !function_exists('GetDeterministicRandomCounter')
        || !function_exists('SetDeterministicRandomCounter')) {
        return $actions[0];
    }
    $counter = GetDeterministicRandomCounter();
    try {
        $index = EngineRandomInt(0, $count - 1);
    } finally {
        SetDeterministicRandomCounter($counter);
    }
    return $actions[$index] ?? $actions[0];
});
