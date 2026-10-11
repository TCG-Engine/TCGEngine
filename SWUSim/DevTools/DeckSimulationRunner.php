<?php
/** Seeded SWUSim opening checkpoint or full game. Run with cwd set to the TCGEngine root. */
declare(strict_types=1);
$residualStartedAt = microtime(true);

function residualFail(string $message): never {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$options = ['deck' => '', 'deck2' => '', 'seed' => 'residual-poc-1', 'mode' => 'residual',
    'maxSteps' => null, 'memoryOnly' => false];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--deck=')) $options['deck'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--deck2=')) $options['deck2'] = substr($arg, 8);
    elseif (str_starts_with($arg, '--seed=')) $options['seed'] = substr($arg, 7);
    elseif (str_starts_with($arg, '--mode=')) $options['mode'] = substr($arg, 7);
    elseif ($arg === '--memory-only') $options['memoryOnly'] = true;
    elseif (str_starts_with($arg, '--max-steps=')) $options['maxSteps'] = intval(substr($arg, 12));
    else residualFail("Unknown argument: $arg");
}
foreach (['deck', 'deck2'] as $key) {
    if ($options[$key] === '' || !is_file($options[$key])) residualFail("Missing deck file: {$options[$key]}");
    $options[$key] = realpath($options[$key]);
}
if (!in_array($options['mode'], ['residual', 'full_game'], true)) residualFail('Mode must be residual or full_game.');
$options['maxSteps'] ??= $options['mode'] === 'full_game' ? 3000 : 250;
if ($options['seed'] === '' || $options['maxSteps'] < 1) residualFail('A seed and positive step cap are required.');
set_exception_handler(function (Throwable $error) use ($options): void {
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['schema' => 'swu-simulation-v1', 'mode' => $options['mode'], 'status' => 'engine_error', 'seed' => $options['seed'],
        'policy' => $options['mode'] === 'full_game' ? 'heuristic-normal' : 'residual-opening-v1', 'engineError' => $error->getMessage()],
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
    exit(2);
});

$root = realpath(getenv('TCGENGINE_ROOT') ?: dirname(__DIR__, 2));
if (!$root || !is_file($root . '/Core/EngineActionRunner.php')) residualFail('Set TCGENGINE_ROOT to the TCGEngine checkout.');
chdir($root);

// Match DevTools/SWUSimBotSelfPlayTest.php's single-process APCu fallback.
if (!function_exists('apcu_store')) {
    $GLOBALS['ResidualApcu'] = [];
    function apcu_store($key, $value, $ttl = 0) { $GLOBALS['ResidualApcu'][(string)$key] = $value; return true; }
    function apcu_fetch($key, &$success = null) { $success = array_key_exists((string)$key, $GLOBALS['ResidualApcu']); return $success ? $GLOBALS['ResidualApcu'][(string)$key] : false; }
    function apcu_exists($key) { return array_key_exists((string)$key, $GLOBALS['ResidualApcu']); }
    function apcu_delete($key) { $exists = apcu_exists($key); unset($GLOBALS['ResidualApcu'][(string)$key]); return $exists; }
    function apcu_inc($key, $step = 1, &$success = null, $ttl = 0) { $success = apcu_exists($key); if (!$success) return false; return $GLOBALS['ResidualApcu'][(string)$key] += $step; }
    $GLOBALS['ResidualApcuFallback'] = true;
}

require_once $root . '/Core/EngineActionRunner.php';
require_once $root . '/APIs/Lobbies/Classes/Player.php';
EngineLoadRootRuntime('SWUSim');
if (!empty($GLOBALS['ResidualApcuFallback'])) $GLOBALS['APCuEnabled'] = true;
require_once $root . '/SWUSim/CreateGame.php';

// Match the self-play harness's CLI-only persistence hook. Generated SWUSim
// writes continue to serialize into the APCu cache, but skip Gamestate.txt.
$GLOBALS['ResidualMemoryOnly'] = $options['memoryOnly'];
function EngineShouldPersistGamestateFile($rootName, $gameName): bool {
    return !(PHP_SAPI === 'cli' && $rootName === 'SWUSim' && !empty($GLOBALS['ResidualMemoryOnly']));
}
if ($options['memoryOnly']) {
    if (!GamestateUsesMemoryStorage() || !function_exists('SimGameWriteGamestateCache')) {
        throw new RuntimeException('Memory-only simulation requires the SWUSim APCu gamestate cache.');
    }
    $probe = 'residual-memory-probe-' . getmypid();
    if (!apcu_store($probe, 'ok', 10) || apcu_fetch($probe) !== 'ok') {
        throw new RuntimeException('Memory-only simulation could not write to the APCu cache.');
    }
    apcu_delete($probe);
}

// Missing generated stats can make a zero-HP base lose on its first hit. A
// completed game on that state is not a meaningful simulation result.
$missingCardData = [];
foreach (['deck', 'deck2'] as $key) {
    preg_match_all('/^\s*\d+\s+([A-Z0-9]{2,5}_(?:T\d{2}|\d{2,3}))\s*$/m',
        (string)file_get_contents($options[$key]), $matches);
    foreach ($matches[1] as $cardID) {
        if (CardType($cardID) === null) $missingCardData[$cardID] = true;
    }
}
if ($missingCardData) {
    throw new RuntimeException('Local SWUSim generated card data is missing ' .
        implode(', ', array_slice(array_keys($missingCardData), 0, 8)) .
        (count($missingCardData) > 8 ? ' and ' . (count($missingCardData) - 8) . ' more' : '') .
        '. Regenerate TCGEngine SWUSim card dictionaries from a complete card database.');
}

function residualBoard(): array {
    $result = ['units' => [], 'baseHealth' => []];
    foreach ([1, 2] as $seat) {
        $result['units'][$seat] = ['Ground' => [], 'Space' => []];
        foreach (SWUBotUnits($seat) as $u) {
            $result['units'][$seat][$u['arena']][] = [
                'cardID' => $u['cardID'], 'uid' => $u['uid'], 'power' => $u['power'],
                'remainingHealth' => $u['remaining'], 'ready' => $u['ready'],
            ];
        }
        $base = GetBase($seat)[0] ?? null;
        $result['baseHealth'][$seat] = $base ? max(0, intval(CardHp($base->CardID)) - intval($base->Damage ?? 0)) : null;
    }
    return $result;
}

function residualBaseHealthPoint(int $round): array {
    $health = [];
    foreach ([1, 2] as $seat) {
        $base = GetBase($seat)[0] ?? null;
        $health[$seat] = $base ? max(0, intval(CardHp($base->CardID)) - intval($base->Damage ?? 0)) : null;
    }
    return ['round' => $round, 'ours' => $health[2], 'opponent' => $health[1]];
}

function residualScore(array $board): float {
    $count = $health = 0;
    foreach (['Ground', 'Space'] as $arena) {
        foreach ($board['units'][1][$arena] as $unit) { $count++; $health += $unit['remainingHealth']; }
        foreach ($board['units'][2][$arena] as $unit) { $count--; $health -= $unit['remainingHealth']; }
    }
    $baseSwing = intval($board['baseHealth'][1]) - intval($board['baseHealth'][2]);
    return $count * 1000000 + $health * 1000 + $baseSwing;
}

function residualUnitPlay(array $actions, int $seat): ?array {
    $plays = [];
    foreach ($actions as $action) {
        if (SWUBotActionKind($action) !== 'play') continue;
        $index = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        $card = GetHand($seat)[$index] ?? null;
        if (!$card || stripos((string)CardType((string)$card->CardID), 'unit') === false) continue;
        $plays[] = ['action' => $action, 'cost' => intval(CardCost((string)$card->CardID)), 'cardID' => (string)$card->CardID];
    }
    usort($plays, fn($a, $b) => ($b['cost'] <=> $a['cost']) ?: strcmp($a['cardID'], $b['cardID']));
    return $plays[0]['action'] ?? null;
}

function residualChosenCards(int $seat, array $action): array {
    $cards = [];
    if (preg_match_all('/myHand-(\d+)/', (string)($action['cardID'] ?? ''), $matches)) {
        foreach ($matches[1] as $index) {
            $obj = GetHand($seat)[intval($index)] ?? null;
            if ($obj) $cards[] = (string)$obj->CardID;
        }
    }
    return $cards;
}

function residualBestAttack(array $actions, int $seat): ?array {
    $started = microtime(true);
    $GLOBALS['ResidualAttackEvaluations'] = intval($GLOBALS['ResidualAttackEvaluations'] ?? 0) + 1;
    try {
    $best = null;
    foreach ($actions as $action) {
        if (SWUBotActionKind($action) !== 'attack') continue;
        $attacker = SWUBotViewForMz($seat, SWUBotActionMz($action));
        if (!$attacker || !SWUBotAttackTargets($seat, $attacker)['units']) continue;
        $candidate = SWUBotLookahead($seat, $action, function () use ($seat) {
            $legal = SWUBotLegalActions((string)($GLOBALS['gameName'] ?? ''), $seat);
            if (($legal['kind'] ?? '') === 'decision' && ($legal['decisionTooltip'] ?? '') === 'Choose_an_attack_target') {
                $bestTarget = null;
                foreach ($legal['actions'] as $target) {
                    if (!preg_match('/^their(Ground|Space)Arena-\d+$/', (string)$target['cardID'])) continue;
                    $line = SWUBotLookaheadBest($seat, $target, 'residualBoard', 'residualScore', 6, 48);
                    if ($line && ($bestTarget === null || $line['_score'] > $bestTarget['score'])) {
                        $bestTarget = ['action' => $target, 'score' => $line['_score'], 'path' => $line['_path']];
                    }
                }
                return $bestTarget ?? ['skip' => true];
            }
            if (SWUBotPendingDecisionSeat()) return ['skip' => true];
            return ['score' => residualScore(residualBoard()), 'path' => []];
        });
        if (!$candidate || isset($candidate['skip'])) continue;
        if ($best === null || $candidate['score'] > $best['score']) {
            $best = ['action' => $action, 'score' => $candidate['score'], 'target' => $candidate['action'] ?? null, 'path' => $candidate['path'] ?? []];
        }
    }
    return $best;
    } finally {
        $GLOBALS['ResidualAttackEvaluationMs'] = floatval($GLOBALS['ResidualAttackEvaluationMs'] ?? 0)
            + (microtime(true) - $started) * 1000;
    }
}

function residualHasUnitAttack(array $actions, int $seat): bool {
    foreach ($actions as $action) {
        if (SWUBotActionKind($action) !== 'attack') continue;
        $attacker = SWUBotViewForMz($seat, SWUBotActionMz($action));
        if ($attacker && SWUBotAttackTargets($seat, $attacker)['units']) return true;
    }
    return false;
}

function residualAttackAvailability(array $board, array $actions): array {
    $opponent = $board['units'][1];
    $ours = $board['units'][2];
    $attackers = count($opponent['Ground']) + count($opponent['Space']);
    $defenders = count($ours['Ground']) + count($ours['Space']);
    $sharedArena = (count($opponent['Ground']) && count($ours['Ground']))
        || (count($opponent['Space']) && count($ours['Space']));
    $readyInSharedArena = false;
    foreach (['Ground', 'Space'] as $arena) {
        if (!$ours[$arena]) continue;
        foreach ($opponent[$arena] as $unit) {
            if ($unit['ready']) { $readyInSharedArena = true; break; }
        }
    }
    $legal = residualHasUnitAttack($actions, 1);
    $reason = $legal ? 'legal' : (!$attackers ? 'opponent_no_unit'
        : (!$defenders ? 'our_no_unit'
        : (!$sharedArena ? 'different_arenas'
        : (!$readyInSharedArena ? 'attacker_unready' : 'no_legal_unit_attack'))));
    return ['reason' => $reason, 'legalUnitAttack' => $legal];
}

$GLOBALS['ResidualPhase'] = $options['mode'] === 'full_game' ? 'full_game' : 'opening';
$GLOBALS['ResidualPlanned'] = [];
$GLOBALS['ResidualLastChoice'] = null;
$GLOBALS['ResidualAttackPlan'] = null;
SWUBotRegisterChooser('residual-opening-v1', function (array $actions, array $legal): ?array {
    $seat = intval($legal['playerID'] ?? 0);
    $phase = $GLOBALS['ResidualPhase'];
    $planned = $GLOBALS['ResidualPlanned'][0] ?? null;
    if ($planned !== null) {
        foreach ($actions as $action) {
            if (($action['cardID'] ?? '') === $planned) {
                array_shift($GLOBALS['ResidualPlanned']);
                return $GLOBALS['ResidualLastChoice'] = ['action' => $action, 'rule' => 'best-trade-continuation', 'context' => $legal['decisionTooltip'] ?? ''];
            }
        }
        $GLOBALS['ResidualPlanned'] = [];
    }
    $choice = null; $rule = '';
    if ($phase === 'opening' && ($legal['kind'] ?? '') !== 'decision') {
        $choice = residualUnitPlay($actions, $seat);
        if ($choice) $rule = 'play-highest-cost-unit';
        else {
            foreach ($actions as $action) {
                if (SWUBotActionKind($action) === ($seat === 1 ? 'initiative' : 'pass')) { $choice = $action; break; }
            }
            $rule = $seat === 1 ? 'claim-initiative' : 'pass';
        }
    } elseif ($phase === 'attack' && ($legal['kind'] ?? '') !== 'decision' && $seat === 1) {
        $best = residualBestAttack($actions, $seat);
        if ($best) {
            $choice = $best['action']; $rule = 'best-resolved-unit-trade';
            $GLOBALS['ResidualAttackPlan'] = $best;
            $GLOBALS['ResidualPlanned'] = array_merge(
                $best['target'] ? [(string)$best['target']['cardID']] : [],
                array_column($best['path'], 'answer')
            );
        } else throw new RuntimeException('A unit attack was legal, but its card decisions could not be evaluated.');
    }
    if (!$choice) {
        $choice = SWUBotHeuristicChoose('normal', $actions, $legal);
        $rule = $rule ?: 'heuristic-decision';
    }
    if (!$choice) return null;
    $found = false;
    foreach ($actions as $candidate) if (($candidate['cardID'] ?? '') === ($choice['cardID'] ?? '')) { $found = true; break; }
    if (!$found) throw new RuntimeException('Policy returned an action outside the legal set.');
    return $GLOBALS['ResidualLastChoice'] = ['action' => $choice, 'rule' => $rule,
        'context' => $legal['decisionTooltip'] ?? '', 'chosenCards' => residualChosenCards($seat, $choice)];
});

// The bot controller requires the chooser callback to return an action, not its trace wrapper.
$registered = $GLOBALS['SWUBotChoosers']['residual-opening-v1'];
SWUBotRegisterChooser('residual-opening-v1', function (array $actions, array $legal) use ($registered): ?array {
    $record = $registered($actions, $legal);
    return $record['action'] ?? null;
});
SWUBotSetForcedChooserProfileForSeat(1, 'residual-opening-v1');
SWUBotSetForcedChooserProfileForSeat(2, 'residual-opening-v1');

$lobby = new stdClass();
$lobby->numPlayers = 2; $lobby->maxPlayers = 2; $lobby->format = 'botpractice';
$lobby->isPrivate = true; $lobby->botPlayers = [1, 2];
$lobby->players = [new Player(1, file_get_contents($options['deck']), ''), new Player(2, file_get_contents($options['deck2']), '')];
$setupStartedAt = microtime(true);
ob_start();
try { $gameName = SWUSetupGame($lobby, ['forcedFirstPlayer' => 1, 'rngSeed' => $options['seed']]); }
finally { ob_end_clean(); }
$setupMs = (microtime(true) - $setupStartedAt) * 1000;
$swuDir = $root . '/SWUSim/';
$trace = []; $status = 'step_cap'; $before = null; $after = null; $attackAction = null; $attackStarted = false;
$attackAvailability = null; $winner = 0; $rounds = 0; $appliedSteps = 0;
$openingHands = null; $baseHealthTimeline = [];
$parseMs = $actionMs = 0.0;
for ($step = 0; $step < $options['maxSteps']; $step++) {
    $parseStartedAt = microtime(true);
    ParseGamestate($swuDir);
    $parseMs += (microtime(true) - $parseStartedAt) * 1000;
    if ($openingHands === null) {
        $openingHands = [];
        foreach ([1, 2] as $seat) $openingHands[$seat] = array_values(array_map(
            fn($card) => (string)$card->CardID, array_filter(GetHand($seat), fn($card) => $card && empty($card->removed))));
    }
    $round = intval(GetTurnNumber());
    $rounds = $round;
    if ($options['mode'] === 'full_game') {
        if (!$baseHealthTimeline) $baseHealthTimeline[0] = residualBaseHealthPoint(0);
        $baseHealthTimeline[$round] = residualBaseHealthPoint($round);
    }
    $winner = intval(SWUGetGameWinner());
    if ($winner) { $status = 'completed'; break; }
    if ($options['mode'] === 'residual' && $round > 2) { $status = 'missed_checkpoint'; break; }
    if ($options['mode'] === 'residual' && $round === 2 && GetCurrentPhase() === 'MAIN' && !$attackStarted && !SWUBotPendingDecisionSeat()) {
        $GLOBALS['ResidualPhase'] = 'attack';
        if (intval(GetTurnPlayer()) !== 1) { $status = 'opponent_lost_initiative'; break; }
        $before = residualBoard();
        $legal = SWUBotLegalActions($gameName, 1);
        $attackAvailability = residualAttackAvailability($before, $legal['actions'] ?? []);
        if (!$attackAvailability['legalUnitAttack']) { $status = 'no_unit_attack'; $after = $before; break; }
    }
    $GLOBALS['ResidualLastChoice'] = null;
    $actionStartedAt = microtime(true);
    ob_start();
    try { $result = ProcessBotControllerStep(0, 'SWUSim', $gameName); }
    finally { ob_end_clean(); }
    $actionMs += (microtime(true) - $actionStartedAt) * 1000;
    if (empty($result['success']) || empty($result['applied'])) { $status = 'engine_error'; break; }
    $appliedSteps++;
    if ($options['mode'] === 'full_game') {
        $observedRound = intval(GetTurnNumber());
        $baseHealthTimeline[$observedRound] = residualBaseHealthPoint($observedRound);
    }
    $choice = $GLOBALS['ResidualLastChoice'];
    if ($choice) {
        $action = $choice['action'];
        $trace[] = ['step' => $step, 'round' => $round, 'seat' => intval($action['playerID'] ?? 0),
            'mode' => intval($action['mode'] ?? 0), 'action' => (string)($action['cardID'] ?? ''),
            'rule' => $choice['rule'], 'context' => $choice['context'],
            'chosenCards' => $choice['chosenCards'] ?? []];
        if ($options['mode'] === 'full_game' && count($trace) > 30) array_shift($trace);
        if ($GLOBALS['ResidualPhase'] === 'attack' && $choice['rule'] === 'best-resolved-unit-trade') {
            $attackStarted = true; $attackAction = $action['cardID'];
        }
    }
    if ($options['mode'] === 'residual' && $attackStarted) {
        ParseGamestate($swuDir);
        if (!SWUBotPendingDecisionSeat() && empty($GLOBALS['ResidualPlanned'])) {
            $after = residualBoard(); $status = 'resolved_attack'; break;
        }
    }
}
if ($options['mode'] === 'full_game') {
    ParseGamestate($swuDir);
    $winner = intval(SWUGetGameWinner());
    $rounds = intval(GetTurnNumber());
    $baseHealthTimeline[$rounds] = residualBaseHealthPoint($rounds);
    if ($winner) $status = 'completed';
}
if ($after === null) $after = residualBoard();
$replay = null;
if ($options['mode'] === 'full_game' && $status === 'completed') {
    $candidate = MatchReplayBuildDownloadPayload('SWUSim', $gameName);
    if (($candidate['initialGamestate'] ?? '') !== '' && count($candidate['actions'] ?? []) > 0) {
        $replay = $candidate;
    }
}
$summary = ['unitCount' => [], 'remainingUnitHealth' => []];
foreach ([1, 2] as $seat) {
    foreach (['Ground', 'Space'] as $arena) {
        $units = $after['units'][$seat][$arena];
        $summary['unitCount'][$seat][$arena] = count($units);
        $summary['remainingUnitHealth'][$seat][$arena] = array_sum(array_column($units, 'remainingHealth'));
    }
}
echo json_encode(['schema' => 'swu-simulation-v1', 'mode' => $options['mode'], 'status' => $status, 'seed' => $options['seed'],
    'memoryOnly' => $options['memoryOnly'],
    'policy' => $options['mode'] === 'full_game' ? 'heuristic-normal' : 'residual-opening-v1',
    'winner' => $winner, 'rounds' => $rounds, 'appliedSteps' => $appliedSteps,
    'opponentDeck' => basename($options['deck']),
    'ourDeck' => basename($options['deck2']), 'openingHands' => $openingHands,
    'steps' => $appliedSteps, 'trace' => $trace, 'replay' => $replay,
    'baseHealthTimeline' => array_values($baseHealthTimeline),
    'selectedAttack' => $attackAction, 'attackAvailability' => $attackAvailability,
    'beforeAttack' => $before, 'afterAttack' => $after,
    'summary' => $summary, 'engineError' => $result['message'] ?? null,
    'timingMs' => ['total' => round((microtime(true) - $residualStartedAt) * 1000, 1),
        'setup' => round($setupMs, 1), 'parse' => round($parseMs, 1), 'action' => round($actionMs, 1),
        'attackEvaluation' => round(floatval($GLOBALS['ResidualAttackEvaluationMs'] ?? 0), 1)]],
    JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
exit(in_array($status, ['resolved_attack', 'no_unit_attack', 'completed'], true) ? 0 : 2);
