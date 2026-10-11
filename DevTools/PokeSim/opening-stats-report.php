<?php
/** Generic opening report for both seats and starting orders, including full-game conversion. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
try {
    $options = getopt('', ['games:','seed:','trace:','deck1:','deck2:']);
    $games = filter_var($options['games'] ?? 100, FILTER_VALIDATE_INT);
    $seed = filter_var($options['seed'] ?? 1, FILTER_VALIDATE_INT);
    if ($games === false || $games < 2 || $games > 10000 || $games % 2 || $seed === false || $seed < 1
        || $seed > 2147483647-intdiv($games,2)+1) throw new InvalidArgumentException('Use an even 2–10000 games and a valid positive seed range');
    $rows = PokeSimulatePairs($seed, intdiv($games,2), 1500, $options['deck1'] ?? 'sinistcha',
        $options['deck2'] ?? 'brisbane-lopunny');
    $misses = []; $traces = [];
    foreach ($rows as $game) {
        foreach ($game['openingStats'] as $row) if (!$row['fullyEnabledAttack']) {
            unset($row['trace']);
            $misses[] = ['seed'=>$game['seed'], 'firstPlayer'=>$game['firstPlayer'], 'gameStatus'=>$game['status']] + $row;
        }
        if (isset($options['trace']) && $game['seed']===(int)$options['trace']) $traces[] = $game;
    }
    echo json_encode(['policy'=>PokeSimulationPolicy(), 'summary'=>PokeSimulationSummary($rows),
        'openingMisses'=>$misses, 'traces'=>$traces], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
    exit(count(array_filter($rows, fn($r)=>$r['status']!=='complete')) ? 2 : 0);
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); }
