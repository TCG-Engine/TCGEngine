<?php
// Offline only: restore a replay prefix, verify public evidence, then branch.
define('RL_TRAINER_LIBRARY_ONLY', true);
require_once __DIR__ . '/../../rl/train_selfplay_php.php';
$options = getopt('', ['replay:', 'step:', 'choice:', 'horizon:', 'seed:']);
$game = 'ga_position_' . bin2hex(random_bytes(8));
$dir = dirname(__DIR__, 3) . '/GrandArchiveSim/Games/' . $game;
try {
    $replay = json_decode(file_get_contents($options['replay'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
    $step = intval($options['step'] ?? 0);
    if ($step < 1 || !isset($replay['actions'][$step-1])) throw new RuntimeException('Invalid one-based step');
    $initial = $replay['initialGamestateText'] ?? '';
    if ($initial === '' || hash('sha256', RegressionNormalizeNewlines($initial)) !== ($replay['initialSnapshotHash'] ?? '')) throw new RuntimeException('Initial snapshot hash missing or mismatched; capture a new replay');
    EngineLoadRootRuntime('GrandArchiveSim');
    mkdir($dir, 0700, true);
    file_put_contents($dir . '/Gamestate.txt', $initial);
    $GLOBALS['playerID'] = 1;
    BridgeLoadRuntimeGame('GrandArchiveSim', $game);
    for ($i=0; $i<$step; ++$i) {
        GaRlResumeStack();
        $legal = GaRlLegalActions($game, 1);
        $seat = intval($legal['playerID']);
        $recorded = $replay['actions'][$i];
        if (intval($recorded['turnPlayer']) !== $seat) throw new RuntimeException('Acting seat mismatch at step '.($i+1));
        if ($recorded['observation'] !== null) {
            if (GaRlFeatureState($seat, $legal) !== $recorded['observation']) throw new RuntimeException('Public observation mismatch at step '.($i+1));
        }
        if ($i === $step-1) break;
        $result = RlStepLoaded('GrandArchiveSim', $game, $recorded['action'], true);
        if (empty($result['applyResult']['success'])) throw new RuntimeException('Prefix action rejected at step '.($i+1));
    }
    if (empty($recorded['observation'])) throw new RuntimeException('Selected step has no public evidence');
    $actions = $legal['actions'];
    // Include heuristic synthesized multi-choice/modal answers as legal candidates.
    $heuristic = GABotChooseActionHeuristic($actions, $legal);
    if ($heuristic !== null && !in_array($heuristic, $actions, true)) $actions[] = $heuristic;
    // Review identity preserves distinct copies that share a learned semantic key.
    // Keep only executable fields; never include metadata or hidden zone contents.
    $choices = array_map(fn($a) => array_intersect_key($a, array_flip(
        ['playerID', 'mode', 'cardID', 'buttonInput', 'inputText', 'chkInput'])), $actions);
    $keys = array_map(fn($a) => GaRlActionKey($a, $legal), $actions);
    $result = ['step'=>$step, 'seat'=>$seat, 'positionHash'=>GABotComparableGamestateHash($game),
        'observation'=>$recorded['observation'], 'actionKeys'=>$keys, 'choices'=>$choices, 'verifiedPrefixSteps'=>$step-1];
    if (isset($options['choice'])) {
        $choice = intval($options['choice']);
        if (!isset($actions[$choice])) throw new RuntimeException('Invalid choice index');
        $horizon = intval($options['horizon'] ?? 100);
        if ($horizon < 1 || $horizon > 2000) throw new RuntimeException('Horizon must be 1..2000');
        mt_srand(intval($options['seed'] ?? 1));
        $trace = [];
        $noOps = []; $noOpAttempts = 0;
        for ($i=0; $i<$horizon; ++$i) {
            $stateHash = GABotComparableGamestateHash($game);
            $available = array_values(array_filter($legal['actions'],
                fn($candidate) => !isset($noOps[$stateHash][RlActionSignature($candidate)])));
            if ($i > 0 && !$available) throw new RuntimeException('All continuation actions made no progress');
            $action = $i === 0 ? $actions[$choice] : GABotChooseActionHeuristic($available, $legal);
            if (is_array($action) && isset($noOps[$stateHash][RlActionSignature($action)])) {
                throw new RuntimeException('Heuristic synthesized a previously rejected continuation');
            }
            if (!is_array($action)) throw new RuntimeException('No continuation action');
            $trace[] = GaRlActionKey($action, $legal);
            $next = RlStepLoaded('GrandArchiveSim', $game, $action, true);
            if (empty($next['applyResult']['success'])) throw new RuntimeException('Continuation action rejected: '.json_encode($next['applyResult']));
            if ($next['preHash'] === $next['postHash']) {
                $noOps[$stateHash][RlActionSignature($action)] = true;
                ++$noOpAttempts;
            }
            $terminal = $next['snapshot']['terminal'] ?? [];
            if (!empty($terminal['isTerminal'])) break;
            $legal = GaRlLegalActions($game, $seat);
        }
        $result['branch'] = ['choice'=>$choice, 'steps'=>count($trace), 'terminal'=>$terminal, 'noOpAttempts'=>$noOpAttempts,
            'finalPositionHash'=>GABotComparableGamestateHash($game), 'actions'=>$trace,
            'note'=>'Bounded current-heuristic continuation with seeded RNG; not an action-quality label.'];
    }
    echo json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL); $failed = true;
} finally {
    // Only the unique scratch game created by this worker is removed.
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        rmdir($dir);
    }
}

if (!empty($failed)) exit(1);
