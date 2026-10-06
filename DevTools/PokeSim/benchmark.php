<?php
/** Seeded, headless performance probe. No session games or generated files are written.
 * php DevTools/PokeSim/benchmark.php --pairs=10 --repeat=3 --deck1=dhelmise-v2 --deck2=brisbane-lopunny
 * --without-opening-tracking isolates diagnostic overhead; it is not a gameplay setting.
 * Compare identical seeds, decks and PHP settings. Browser rendering, HTTP and
 * local storage are outside this measurement. State digests include telemetry.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__, 2).'/PokeSim/Runtime.php';
require dirname(__DIR__, 2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__, 2).'/PokeSim/Bot/Simulation.php';
$options = getopt('', ['pairs:', 'repeat:', 'seed:', 'deck1:', 'deck2:', 'without-opening-tracking']);
$pairs = (int)($options['pairs'] ?? 10); $repeat = (int)($options['repeat'] ?? 3);
$seed = (int)($options['seed'] ?? 1);
if ($pairs < 1 || $repeat < 1 || $seed < 1 || $seed > 2147483647-$pairs+1) {
    fwrite(STDERR, "Invalid pairs, repeat or seed range\n"); exit(1);
}
$deck1 = PokeNamedDeck($options['deck1'] ?? 'sinistcha');
$deck2 = PokeNamedDeck($options['deck2'] ?? 'brisbane-lopunny');
$runs = [];
for ($run = 0; $run < $repeat; ++$run) {
    $times = ['setup'=>0, 'observation'=>0, 'policy'=>0, 'apply'=>0];
    $digest = hash_init('sha256'); hash_update($digest, '[');
    $games = 0; $actions = 0; $started = hrtime(true);
    for ($i = 0; $i < $pairs; ++$i) foreach ([1, 2] as $first) {
        $t = hrtime(true); PokeCreateGame($deck1, $deck2, $seed+$i, $first);
        if (isset($options['without-opening-tracking'])) PokeSetVar('openingStats', []);
        $times['setup'] += hrtime(true)-$t; $steps = 0;
        while (!GetWinner() && $steps < 1500) {
            $t = hrtime(true); $view = PokeObservation(PokePendingPlayer());
            $times['observation'] += hrtime(true)-$t;
            $t = hrtime(true); $action = PokeBotChoose($view);
            $times['policy'] += hrtime(true)-$t;
            if ($action === null) throw new RuntimeException('No bot action');
            $t = hrtime(true); PokeApplyAction($action);
            $times['apply'] += hrtime(true)-$t; ++$steps;
        }
        if (!GetWinner()) throw new RuntimeException('Action cap reached');
        $actions += $steps;
        // Include full exported state to detect changes in choices, RNG and telemetry.
        if ($games++) hash_update($digest, ',');
        hash_update($digest, json_encode(['winner'=>GetWinner(), 'turns'=>GetTurnNumber(), 'actions'=>$steps,
            'state'=>PokeStateExport()], JSON_THROW_ON_ERROR));
    }
    hash_update($digest, ']');
    $seconds = (hrtime(true)-$started)/1e9;
    $runs[] = ['seconds'=>$seconds, 'gamesPerSecond'=>2*$pairs/$seconds, 'actions'=>$actions,
        'phaseSeconds'=>array_map(fn($ns)=>$ns/1e9, $times),
        'stateDigest'=>hash_final($digest)];
}
echo json_encode(['php'=>PHP_VERSION, 'openingTracking'=>!isset($options['without-opening-tracking']),
    'seed'=>$seed, 'pairs'=>$pairs, 'deck1'=>$options['deck1'] ?? 'sinistcha',
    'deck2'=>$options['deck2'] ?? 'brisbane-lopunny', 'runs'=>$runs,
    'peakMemoryBytes'=>memory_get_peak_usage(true)], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
