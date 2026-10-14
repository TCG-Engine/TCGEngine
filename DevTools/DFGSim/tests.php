<?php
declare(strict_types=1);
require_once __DIR__ . '/../../DFGSim/Bot/HeuristicBot.php';
$checks = 0; $failures = 0;
function check(bool $condition, string $label): void {
    global $checks, $failures; ++$checks;
    echo ($condition ? 'PASS' : 'FAIL') . ': ' . $label . "\n";
    if (!$condition) ++$failures;
}
function rejects(callable $operation): bool {
    try { $operation(); return false; } catch (LogicException | RuntimeException $error) { return true; }
}
function revealed(int $human, int $bot, array $humanBonus = [], array $botBonus = [], int $first = 0): array {
    $cards = DFGCatalog();
    $state = DFGNewGame($cards[$human]['color'], $cards[$bot]['color'], 17, $first);
    $state = DFGApply($state, 0, ['type' => 'commit', 'main' => $human, 'bonuses' => $humanBonus]);
    return DFGApply($state, 1, ['type' => 'commit', 'main' => $bot, 'bonuses' => $botBonus]);
}
function finishWindow(array $state): array {
    $state = DFGApply($state, $state['reactionTurn'], ['type' => 'pass']);
    return DFGApply($state, $state['reactionTurn'], ['type' => 'pass']);
}
check(count(DFGCatalog()) === 36, 'all 36 exported cards and their effects compile');
$stats = DFGComputeRound([['main'=>2,'bonuses'=>[],'advantages'=>['Red']],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 8 && $stats[0]['block'] === 1, 'Rushdown gains one base block while retaining its Red attack payoff');
$stats = DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 2, 'Feint has four fallback attack against low printed block');
$stats = DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>24,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6, 'Feint retains six attack at the printed block four boundary');
$stats = DFGComputeRound([['main'=>6,'bonuses'=>[]],['main'=>23,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 5, 'Covering Step reads the printed attack five boundary');
$stats = DFGComputeRound([['main'=>6,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[1]['attack'] === 5 && $stats[0]['block'] === 3, 'Covering Step ignores modified attack when printed attack is four');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[]]]);
check($stats[0]['block'] === 2 && $stats[1]['attack'] === 6, 'Firm Counter has two base block and exposes the printed-block read to Catch the Opening');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[29]]]);
check($stats[0]['block'] === 5 && $stats[1]['attack'] === 7, 'Firm Counter reaches five block against a bonus while Catch the Opening still reads printed block');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[]]], [['seat'=>1,'card'=>29]]);
check($stats[0]['block'] === 2, 'an opposing reaction burn cannot activate Firm Counter bonus-commitment protection');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[]],['main'=>31,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 3, 'Shield Bash starts at three attack and three block without either buff');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[],'advantages'=>['Green']],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 5, 'Shield Bash Green spending improves block without granting the low-attack buff');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[],'advantages'=>['Green']],['main'=>16,'bonuses'=>[]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 5, 'Shield Bash combines its printed low-attack read with Green spending');
$state = DFGNewGame();
check(array_sum(array_map(fn($color) => count(DFGAvailable($state['players'][0], $color)), DFG_COLORS)) === 36, 'six hands have six cards each');
check(rejects(fn() => DFGCompileEffect('Execute PHP: <?php echo 1;')), 'unsupported card text fails closed, never executes code');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => []], ['main' => 3, 'bonuses' => []]]);
check($stats[0]['attack'] === 5 && $stats[0]['damage'] === 0, 'Quick Jab gains its no-bonus attack and damage floors at zero');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => [5]], ['main' => 3, 'bonuses' => []]]);
check($stats[0]['attack'] === 6, 'bonus burn disables Quick Jab condition but applies its bonus');
$stats = DFGComputeRound([['main' => 4, 'bonuses' => []], ['main' => 19, 'bonuses' => []]], [['seat' => 1, 'card' => 22]]);
check($stats[0]['attack'] === 6 && $stats[1]['block'] === 9, 'conditions use printed block while reactions change effective block');
$stats = DFGComputeRound([['main' => 22, 'bonuses' => []], ['main' => 2, 'bonuses' => []]], [['seat' => 0, 'card' => 19]]);
check($stats[0]['block'] === 8, 'reaction burns do not count as pre-reveal bonus burns');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => [2]], ['main' => 31, 'bonuses' => [32]]]);
check($stats[0]['attack'] === 7 && $stats[1]['attack'] === 10, 'bonuses stack, including effects benefiting the opponent');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 1, 'Charged Fist gains one attack when conserving its bonus burn');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[36]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 7, 'Charged Fist with a bonus receives only the burned card effect');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[],'advantages'=>['Purple']],['main'=>1,'bonuses'=>[]]], [['seat'=>0,'card'=>35]]);
check($stats[0]['attack'] === 7 && $stats[0]['block'] === 3, 'Charged Fist conservation stacks with Purple spending and remains active during reaction burns');
$stats = DFGComputeRound([['main'=>31,'bonuses'=>[34]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['block'] === 3, 'Iron Guard retains its three-block bonus');
$stats = DFGComputeRound([['main' => 33, 'bonuses' => []], ['main' => 3, 'bonuses' => []]], [['seat' => 1, 'card' => 6]]);
check($stats[0]['damage'] === 1 && $stats[1]['block'] === 6 && $stats[0]['effectiveOpponentBlock'] === 4, 'block reduction applies to final block only for the attacker’s damage');
$stats = DFGComputeRound([['main' => 33, 'bonuses' => []], ['main' => 2, 'bonuses' => []]]);
check($stats[0]['damage'] === 5, 'block reduction cannot make block negative');
$stats = DFGComputeRound([['main' => 25, 'bonuses' => []], ['main' => 2, 'bonuses' => []]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 6, 'Blue conditional attack and block both apply');
check(array_keys(array_filter(DFGCatalog(), fn($card) => in_array($card['color'], $card['switches'], true))) === [2, 11, 14, 23, 30, 32], 'exactly the six designated cards permit staying in the same hand');
foreach (DFG_COLORS as $color) check(count(array_filter(DFGCatalog(), fn($card) => $card['color'] === $color && in_array($color, $card['switches'], true))) === 1, 'each hand has exactly one stay option');
foreach ([2, 11, 14, 23, 30, 32] as $id) {
    $stayState = finishWindow(revealed($id, 1));
    $stayState = DFGApply($stayState, 0, ['type' => 'switch', 'color' => DFGCatalog()[$id]['color']]);
    check($stayState['switches'][0] === DFGCatalog()[$id]['color'], 'the designated stay card legally selects its own hand after damage');
}
foreach (DFGCatalog() as $card) check(count($card['switches']) >= 2 && count($card['switches']) <= 3 && count($card['switches']) === count(array_unique($card['switches'])), 'every card has two or three distinct printed destinations');
foreach (DFG_COLORS as $start) {
    $reached = [$start];
    do {
        $before = $reached;
        foreach (DFGCatalog() as $card) if (in_array($card['color'], $reached, true)) $reached = array_values(array_unique(array_merge($reached, $card['switches'])));
    } while ($before !== $reached);
    check(count($reached) === 6, 'all six hands remain reachable from every opening color');
}
$stats = DFGComputeRound([['main' => 27, 'bonuses' => [30]], ['main' => 3, 'bonuses' => []]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 5, 'Counterpunch bonus adds exactly two attack and no block');
$stats = DFGComputeRound([['main' => 27, 'bonuses' => [26]], ['main' => 3, 'bonuses' => []]], [['seat' => 0, 'card' => 25]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 6, 'failed conditional reaction uses its Otherwise effect');
$stats = DFGComputeRound([['main' => 12, 'bonuses' => [7]], ['main' => 27, 'bonuses' => []]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 3 && $stats[1]['attack'] === 4, 'Counter Throw gains nothing from its own bonus commitment; Watchful still reads it');
$stats = DFGComputeRound([['main'=>12,'bonuses'=>[]],['main'=>32,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 5, 'Counter Throw reads the printed attack 5 boundary without needing a burn');
$stats = DFGComputeRound([['main'=>12,'bonuses'=>[]],['main'=>14,'bonuses'=>[18]]]);
check($stats[1]['attack'] >= 5 && $stats[0]['attack'] === 3 && $stats[0]['block'] === 3, 'Counter Throw ignores bonus-modified attack when printed attack is below 5');
$stats = DFGComputeRound([['main'=>7,'bonuses'=>[12]],['main'=>32,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 3, 'Counter Throw bonus supplies two block, not attack');
check(DFGCatalog()[12]['name'] === 'Counter Throw' && DFGCatalog()[12]['switches'] === ['Red','Blue','Purple'] && DFGCatalog()[12]['reaction'] === '', 'replacement keeps card ID, switches, and no reaction');

$state = DFGNewGame(); $before = $state;
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 31])), 'main card must be in active hand');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [1]])), 'main card cannot also be a bonus burn');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [2, 2]])), 'duplicate bonus burns rejected');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [3]])), 'a card without a bonus effect cannot be burned as a bonus');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [32]])), 'inactive hand cannot supply bonus burns');
check($state === $before, 'invalid actions leave original state unchanged');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [2, 5]])), 'two distinct bonus burns rejected without mutating state');
check($state === $before, 'rejected multiple burns leave cards and commitments untouched');
check(!array_filter(DFGEnumeratePlans(DFGObserve($state, 1), 1), fn($plan) => count($plan['bonuses']) > 1), 'bot enumerates only zero or one bonus burn');
$state = DFGApply($state, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [2]]);
check(count($state['commitments'][0]['bonuses']) === 1, 'one bonus burn is accepted');
$view = DFGObserve($state, 1);
check($view['commitments'][0] === ['hidden' => true] && !$view['players'][0]['discarded'] && $view['stats'] === null, 'hidden main, bonus identities, bonus count, and damage remain private before reveal');
check(!str_contains(json_encode($view['log']), 'Quick Jab') && !str_contains(json_encode($view['log']), 'Rushdown'), 'commitment log does not reveal hidden cards');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'commit', 'main' => 2])), 'committed main cannot be changed');
$state = revealed(1, 31, [2], [32]);
check($state['phase'] === 'reactions' && $state['players'][0]['discarded'] === [2] && $state['players'][1]['discarded'] === [32], 'both commitments reveal together and bonus burns become permanent');
check(rejects(fn() => DFGApply($state, 1, ['type' => 'pass'])), 'reaction priority enforced');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'react', 'card' => 2])), 'discarded bonus card cannot be reacted with');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'react', 'card' => 19])), 'inactive hand cannot supply reactions');
$reserved = revealed(3, 31);
check(rejects(fn() => DFGApply($reserved, 0, ['type' => 'react', 'card' => 3])), 'main card is reserved until damage and cannot be reaction burned');
$state = DFGApply($state, 0, ['type' => 'pass']);
$state = DFGApply($state, 1, ['type' => 'react', 'card' => 34]);
check($state['passes'] === 0 && $state['reactionTurn'] === 0, 'reaction burn resets consecutive passes');
$state = DFGApply($state, 0, ['type' => 'react', 'card' => 3]);
check(rejects(fn() => DFGApply($state, 0, ['type' => 'react', 'card' => 3])), 'reaction cannot be repeated or taken out of turn');
$state = finishWindow($state);
check($state['phase'] === 'switching' && $state['players'][0]['life'] === 34 && $state['players'][1]['life'] === 34, 'two passes close window and apply exact simultaneous damage');
check(in_array(1, DFGAvailable($state['players'][0]), true) && !in_array(2, DFGAvailable($state['players'][0]), true) && !in_array(3, DFGAvailable($state['players'][0]), true), 'normal main returns while bonus and reaction burns stay discarded');
check(rejects(fn() => DFGApply($state, 0, ['type' => 'switch', 'color' => 'Green'])), 'unlisted next hand rejected');
$state = DFGApply($state, 1, ['type' => 'switch', 'color' => 'Red']);
check(DFGObserve($state, 0)['switches'][1] === 'hidden', 'opponent next hand stays hidden until both choose');
$state = DFGApply($state, 0, ['type' => 'switch', 'color' => 'Yellow']);
check($state['round'] === 2 && $state['phase'] === 'planning' && $state['players'][0]['color'] === 'Yellow', 'both listed hand choices start next round');
$state = DFGApply($state, 0, ['type' => 'commit', 'main' => 13]);
$state = DFGApply($state, 1, ['type' => 'commit', 'main' => 2]);
check($state['reactionTurn'] === 1, 'first reaction priority alternates next round');
$forced = finishWindow(revealed(3, 31));
check(rejects(fn() => DFGApply($forced, 0, ['type' => 'switch', 'color' => 'Red'])), 'staying in active color requires it to be listed on the main card');
$draw = revealed(2, 31); $draw['players'][0]['life'] = 1; $draw['players'][1]['life'] = 1;
$draw = finishWindow($draw);
check($draw['winner'] === 'draw' && $draw['phase'] === 'finished' && $draw['players'][0]['life'] === 0 && $draw['players'][1]['life'] === 0, 'simultaneous knockout is a draw, with no first-player kill advantage');
check(rejects(fn() => DFGApply($draw, 0, ['type' => 'pass'])), 'finished match accepts no further gameplay');

$base = DFGNewGame();
$left = DFGApply($base, 0, ['type' => 'commit', 'main' => 1, 'bonuses' => [2]]);
$right = DFGApply($base, 0, ['type' => 'commit', 'main' => 2, 'bonuses' => [5]]);
$rngA = 123; $rngB = 123;
check(DFGBotChoose(DFGObserve($left, 1), 1, $rngA) === DFGBotChoose(DFGObserve($right, 1), 1, $rngB), 'bot decision is identical for different hidden opponent commitments');
check(DFGRunBot(DFGNewGame('Red', 'Purple', 123)) === DFGRunBot(DFGNewGame('Red', 'Purple', 123)), 'same seed produces reproducible bot commitments');
$danger = revealed(2, 32, [], [], 1); $danger['players'][1]['life'] = 5;
check(DFGBotReaction(DFGObserve($danger, 1), 1) === ['type' => 'react', 'card' => 34], 'bot spends a defensive reaction to prevent lethal damage');
$botState = DFGRunBot(DFGNewGame());
check($botState['commitments'][1] !== null && DFGObserve($botState, 0)['commitments'][1] === ['hidden' => true], 'bot commits first and its choice is hidden from human');

// Full legal bot-vs-bot matches exercise every phase and persistent resource depletion.
$finished = 0; $maxRounds = 0;
foreach ([['Red', 'Purple', 11], ['Orange', 'Green', 23], ['Yellow', 'Blue', 37]] as [$human, $bot, $seed]) {
    $state = DFGRunBot(DFGNewGame($human, $bot, $seed)); $rng = $seed + 123;
    for ($steps = 0; $steps < 500 && $state['phase'] !== 'finished'; ++$steps) {
        $action = DFGBotChoose(DFGObserve($state, 0), 0, $rng);
        $state = DFGRunBot(DFGApply($state, 0, $action));
    }
    $maxRounds = max($maxRounds, $state['round']);
    if ($state['phase'] === 'finished') ++$finished;
    foreach ($state['players'] as $player) check(count($player['discarded']) === count(array_unique($player['discarded'])) && count(DFGAvailable($player)) + count($player['discarded']) === 36, 'full match never duplicates or loses cards outside permanent burns');
}
check($finished === 3, 'three seeded full matches finish within the simulation budget');
echo "$checks checks, $failures failures. Full-match maximum rounds: $maxRounds.\n";
exit($failures ? 1 : 0);
