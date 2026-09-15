<?php
// The swappable "chooser" half of the SWUSim bot seam:
//   SWUBotLegalActions() -> chooser($actions, $legal) -> one action -> EngineExecuteLoadedAction()
//
// Phase 1 registered ONE profile, 'first-legal', which does no evaluation at all. Its job is to
// prove the enumerate -> choose -> execute loop terminates on a real game before any scoring
// exists. Phase 2a adds 'random', which does no evaluation either but selects UNIFORMLY, so the
// enumerator's rarely-index-0 arms actually execute before a scorer starts selecting into them.
// Phase 2b is now the three heuristic profiles — 'heuristic-aggro', 'heuristic-normal',
// 'heuristic-control' — built by the RL bots spec (docs/superpowers/specs/2026-09-13-swusim-rl-bots-design.md,
// Section 2): the style filter, the confident rules, then the fallback scorer. A model-backed chooser can
// register later the same way, without touching enumeration or execution.

require_once __DIR__ . '/Custom/BotFeatures.php';   // per-seat feature switches (the strength test's variants)
require_once __DIR__ . '/Custom/BotEvaluator.php';
require_once __DIR__ . '/Rl/CardTags.php';
require_once __DIR__ . '/Custom/BotFlavours.php';
require_once __DIR__ . '/Custom/BotStyles.php';
require_once __DIR__ . '/Custom/BotResourcing.php';
require_once __DIR__ . '/Custom/BotGuides.php';
require_once __DIR__ . '/Custom/BotFallback.php';
require_once __DIR__ . '/Custom/BotRules.php';
require_once __DIR__ . '/Custom/BotLookahead.php';   // the fallback judges Actions by applying them (BotFallback.php)
require_once __DIR__ . '/Rl/SwuKeys.php';            // RL Phase 3: swu-v1 state and move keys
require_once __DIR__ . '/Rl/SwuPolicy.php';          // RL Phase 3: the learned layer ('@rl' variant)

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

// A PER-SEAT override, so a harness can pit two profiles against each other in one game (the self-play
// harness's --chooser2=). Same standing as the process-wide override — null unless a harness sets it.
function SWUBotSetForcedChooserProfileForSeat(int $seat, ?string $profileName) {
    if ($profileName === null || $profileName === '') unset($GLOBALS['SWUBotForcedChooserProfileBySeat'][$seat]);
    else $GLOBALS['SWUBotForcedChooserProfileBySeat'][$seat] = $profileName;
}

// Per-seat override > process-wide override > the SWUBotProfile DQ variable > 'first-legal'.
function SWUBotActiveChooserProfile(int $seat = 0) {
    $bySeat = $GLOBALS['SWUBotForcedChooserProfileBySeat'][$seat] ?? null;
    if ($seat > 0 && $bySeat !== null && strval($bySeat) !== '') return strval($bySeat);
    $forced = $GLOBALS['SWUBotForcedChooserProfile'] ?? null;
    if ($forced !== null && strval($forced) !== '') return strval($forced);
    $value = class_exists('DecisionQueueController')
        ? DecisionQueueController::GetVariable('SWUBotProfile') : null;
    return ($value !== null && $value !== '') ? strval($value) : 'first-legal';
}

function SWUBotChooseAction(array $actions, array $legal) {
    if (empty($actions)) return null;
    $profile = SWUBotActiveChooserProfile(intval($legal['playerID'] ?? 0));
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

// ── 'heuristic-aggro' / 'heuristic-normal' / 'heuristic-control' ─────────────────────────────────
// The RL bots spec's decision stack (Section 2): rules 1–2 on the full candidate set (lethal outranks the
// style), then the style filter (layer 1) and the resourcing-floor constraint, then the remaining layer-2
// rules, then the fallback scorer with its guides (layer 4). A rule's answer must be one of the candidates; anything else is ignored and recorded as
// "invalid:<rule>" (a Task 10 gate is that this never happens). Deterministic — no RNG anywhere.
//
// Coverage: $GLOBALS['SWUBotCoverage'][$seat]["rule:<name>" | "invalid:<name>" | "fallback"] => count,
// read by the self-play harness's metrics. $GLOBALS['SWUBotTestExtraRules'] is a TEST-ONLY hook
// (SWUSim/DevTools/tests/bot_stack_test.php): extra rules appended after layer 2. Nothing in the running
// product sets it.
// $variant selects the feature switches for THIS decision (BotFeatures.php); the previous set is restored on return,
// so code outside a decision always sees everything on. $GLOBALS['SWUBotLastDecisionDisabled'] records the set used.
function SWUBotHeuristicChoose(string $style, array $actions, array $legal, string $variant = ''): ?array {
    $prev = $GLOBALS['SWUBotDisabledFeatures'] ?? [];
    $prevRl = $GLOBALS['SWURlOn'] ?? false;
    SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []);
    $GLOBALS['SWUBotLastDecisionDisabled'] = $GLOBALS['SWUBotDisabledFeatures'];
    $GLOBALS['SWURlOn'] = ($variant === 'rl');   // the learned layer (SWUSim/Rl/SwuPolicy.php), fallback decisions only
    try {
        return _SWUBotHeuristicChooseStack($style, $actions, $legal);
    } finally {
        SWUBotSetDisabledFeatures($prev);
        $GLOBALS['SWURlOn'] = $prevRl;
    }
}

function _SWUBotHeuristicChooseStack(string $style, array $actions, array $legal): ?array {
    $seat = intval($legal['playerID'] ?? 0);
    $ctx = ['style' => $style, 'seat' => $seat, 'opp' => SWUBotOpponent($seat),
            'kind' => strval($legal['kind'] ?? ''), 'type' => strval($legal['decisionType'] ?? ''),
            'param' => strval($legal['decisionParam'] ?? ''), 'tooltip' => strval($legal['decisionTooltip'] ?? ''),
            'following' => (array)($legal['following'] ?? []), 'actions' => array_values($actions)];
    $inSet = function ($pick) use (&$ctx) {
        foreach ($ctx['actions'] as $a) { if (($a['cardID'] ?? null) === ($pick['cardID'] ?? '')) return true; }
        return false;
    };
    $run = function (array $rules) use (&$ctx, $inSet, $seat) {
        foreach ($rules as $name => $fn) {
            $pick = $fn($ctx);
            if ($pick === null) continue;
            if (!$inSet($pick)) { SWUBotRecordCoverage($seat, "invalid:$name"); continue; }
            SWUBotRecordCoverage($seat, "rule:$name");
            $GLOBALS['SWUBotLastRule'] = $name;
            return $pick;
        }
        return null;
    };
    $all = $ctx['actions'];
    if (($p = $run(SWUBotRulesBeforeFilter())) !== null) return SWUBotTrace($ctx, $all, $p, 'rule');
    $ctx['actions'] = SWUBotStyleFilter($ctx);
    // Rule 10, the resourcing floor, as a fixed constraint: it removes PASS below the leader's deploy threshold
    // and leaves WHICH card to the fallback's keep values (and to the learned layer in training).
    $floored = SWUBotResourceFloorFilter($ctx);
    if (count($floored) !== count($ctx['actions'])) { SWUBotRecordCoverage($seat, 'filter:resource-floor'); $ctx['actions'] = $floored; }
    if (($p = $run(array_merge(SWUBotRulesAfterFilter(), $GLOBALS['SWUBotTestExtraRules'] ?? []))) !== null) return SWUBotTrace($ctx, $all, $p, 'rule');
    SWUBotRecordCoverage($seat, 'fallback');
    $pick = SWUBotFallbackChoose($ctx);
    // Layer 3 — the learned layer replaces ONLY the fallback's choice (spec Section 2).
    if (!empty($GLOBALS['SWURlOn'])) $pick = SWURlChoose($ctx, $pick);
    // Which guide (BotGuides.php) favoured the pick, if any — so sweeps can report how often each one decides.
    $g = _SWUBotGuides($ctx); $pc = strval($pick['cardID'] ?? '');
    if (in_array($pc, $g['attackFirst'], true)) SWUBotRecordCoverage($seat, 'guide:attack-first');
    elseif ($pc !== '' && $g['maxUnits'] === $pc) SWUBotRecordCoverage($seat, 'guide:max-units');
    elseif ($pc === 'PASS' && SWUBotAtResourceStop($ctx)) SWUBotRecordCoverage($seat, 'guide:stop');
    return SWUBotTrace($ctx, $all, $pick, 'fallback');
}

// Debug trace, OFF unless the SWUBOT_TRACE environment variable names a file. Pass it through the container
// with `docker exec -e SWUBOT_TRACE=/tmp/trace.jsonl …`. Appends one JSON line per decision the stack answers:
// round, seat, style, decision, the candidates (hand plays show their card and current play cost), the pick,
// and the layer that answered. Read-only; changes no behaviour.
//
// SWUBOT_TRACE_MODE=combo (the sweep retros, SWUSim/DevTools/rl/sweep_fixtures.sh) records only the decisions
// that mark a COMBO — a trigger-ordering prompt, a decision the non-turn player makes mid-action, a Plot
// prompt — and adds 'stack': each ordering candidate's [CardID, TriggerType, Controller]. That keeps a sweep's
// traces small enough to keep for every game, so the retro can mine them for interactions no test covers.
function SWUBotTrace(array $ctx, array $all, ?array $pick, string $layer): ?array {
    $path = getenv('SWUBOT_TRACE');
    if (!$path) return $pick;
    $seat = intval($ctx['seat']);
    $turn = intval(GetTurnPlayer());
    $isOrdering = ($ctx['tooltip'] ?? '') === 'Choose_trigger_to_resolve';
    if (getenv('SWUBOT_TRACE_MODE') === 'combo'
        && !$isOrdering && !($ctx['kind'] === 'decision' && $seat !== $turn) && stripos(strval($ctx['tooltip']), 'plot') === false) return $pick;
    $stack = null;
    if ($isOrdering) {
        $stack = [];
        foreach ($all as $a) {
            if (!preg_match('/EffectStack-(\d+)$/', strval($a['cardID'] ?? ''), $m)) continue;
            $e = GetEffectStack()[intval($m[1])] ?? null;
            if ($e !== null) $stack[] = [strval($e->CardID), strval($e->TriggerType), intval($e->Controller)];
        }
    }
    $show = function ($a) use ($seat) {
        $c = strval($a['cardID'] ?? '');
        if (SWUBotActionKind($a) === 'play') {
            $o = GetHand($seat)[intval(substr(SWUBotActionMz($a), strlen('myHand-')))] ?? null;
            if ($o !== null) $c .= ' ' . $o->CardID . '$' . SWUComputePlayCost($seat, $o);
        }
        return $c;
    };
    $hand = array_map(fn($o) => $o->CardID . '$' . intval(CardCost($o->CardID)), array_values(array_filter(GetHand($seat), fn($o) => $o !== null && empty($o->removed))));
    $rec = ['round' => intval(GetTurnNumber()), 'phase' => strval(GetCurrentPhase()), 'seat' => $seat, 'style' => $ctx['style'],
            'kind' => $ctx['kind'], 'type' => $ctx['type'], 'tooltip' => $ctx['tooltip'], 'next' => strval($ctx['following'][0] ?? ''),
            'ready' => SWUResourceCount($seat, true), 'capacity' => SWUTotalPaymentCapacity($seat), 'hand' => $hand,
            'turn' => $turn, 'stack' => $stack,
            'candidates' => array_map($show, $all), 'pick' => $pick === null ? null : $show($pick), 'layer' => $layer,
            'rule' => $layer === 'rule' ? ($GLOBALS['SWUBotLastRule'] ?? null) : null];
    @file_put_contents($path, json_encode($rec, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
    return $pick;
}

function SWUBotRecordCoverage(int $seat, string $key): void {
    $GLOBALS['SWUBotCoverage'][$seat][$key] = ($GLOBALS['SWUBotCoverage'][$seat][$key] ?? 0) + 1;
}
function SWUBotResetCoverage(): void { $GLOBALS['SWUBotCoverage'] = []; }

foreach (['aggro', 'normal', 'control'] as $style) {
    SWUBotRegisterChooser("heuristic-$style", fn(array $actions, array $legal) => SWUBotHeuristicChoose($style, $actions, $legal));
    // Variant profiles for the strength test (BotFeatures.php): "heuristic-<style>@base", "heuristic-<style>@no-<feature>".
    foreach (SWUBotVariants() as $v) {
        SWUBotRegisterChooser("heuristic-$style@$v", fn(array $actions, array $legal) => SWUBotHeuristicChoose($style, $actions, $legal, $v));
    }
    // The learned layer (RL Phase 3): "heuristic-<style>@rl", configured by SWU_RL_* (SWUSim/Rl/SwuPolicy.php).
    SWUBotRegisterChooser("heuristic-$style@rl", fn(array $actions, array $legal) => SWUBotHeuristicChoose($style, $actions, $legal, 'rl'));
}
