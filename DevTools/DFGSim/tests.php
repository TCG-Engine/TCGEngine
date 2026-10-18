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
$stats=DFGComputeRound([['main'=>1,'bonuses'=>[6]],['main'=>19,'bonuses'=>[]]]);
check($stats[0]['attack']===3 && $stats[0]['block']===4, 'Quick Jab gives up its conservation reward when burning a defensive bonus');
$stats=DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>29,'bonuses'=>[27]]],[['seat'=>1,'card'=>25]]);
check($stats[1]['block']===6 && $stats[0]['attack']===2 && $stats[0]['pierce']===0, 'Feint penetration cannot trigger from bonus or reaction block on a printed-three guard');
$stats=DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>19,'bonuses'=>[]]]);
check($stats[0]['damage']===1 && $stats[1]['block']===6, 'Feint gets around Rooted Guard for one without reducing its actual block');
$stats=DFGComputeRound([['main'=>5,'bonuses'=>[]],['main'=>19,'bonuses'=>[]]]);
check($stats[0]['attack']===3 && $stats[0]['block']===1 && $stats[0]['pierce']===0, 'uncharged Combination Strike has no free spending payoff');
$stats = DFGComputeRound([['main'=>9,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 1 && $stats[1]['damage'] === 4, 'Driving Tackle is 6/1 and its printed block prevents one damage');
$stats = DFGComputeRound([['main'=>30,'bonuses'=>[]],['main'=>23,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 2, 'Counterpunch is 4/2 against printed attack five');
$stats = DFGComputeRound([['main'=>30,'bonuses'=>[]],['main'=>2,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 5, 'Counterpunch reaches 4/5 at printed attack six');
$stats = DFGComputeRound([['main'=>30,'bonuses'=>[]],['main'=>23,'bonuses'=>[20]]]);
check($stats[1]['attack'] >= 6 && $stats[0]['block'] === 2, 'Counterpunch ignores modified attack when printed attack is below six');
$stats = DFGComputeRound([['main'=>30,'bonuses'=>[],'advantages'=>['Blue']],['main'=>2,'bonuses'=>[]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 6, 'Counterpunch Blue spend stacks with its high-attack read to reach 5/6');
$stats = DFGComputeRound([['main'=>18,'bonuses'=>[]],['main'=>26,'bonuses'=>[]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 4, 'Driving Kick reaches 5/4 at opposing printed block two');
$stats = DFGComputeRound([['main'=>18,'bonuses'=>[]],['main'=>16,'bonuses'=>[]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 0, 'Driving Kick remains 5/0 at opposing printed block three');
$stats = DFGComputeRound([['main'=>18,'bonuses'=>[]],['main'=>26,'bonuses'=>[27]]]);
check($stats[1]['block'] > 2 && $stats[0]['block'] === 4, 'Driving Kick reads printed block despite an opposing block bonus');
$stats = DFGComputeRound([['main'=>19,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 0 && $stats[0]['block'] === 6 && $stats[1]['attack'] === 5, 'Rooted Guard remains 0/6 against printed attack four despite modified attack five');
$stats = DFGComputeRound([['main'=>19,'bonuses'=>[]],['main'=>23,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 6, 'Rooted Guard reaches 3/6 at printed attack five');
$stats = DFGComputeRound([['main'=>23,'bonuses'=>[]],['main'=>19,'bonuses'=>[]]]);
check($stats[1]['attack'] === 3 && $stats[1]['block'] === 6, 'Rooted Guard high-attack read works for either player');
$stats = DFGComputeRound([['main'=>26,'bonuses'=>[]],['main'=>24,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 2, 'Catch the Opening is 3/2 against a strong printed guard');
$stats = DFGComputeRound([['main'=>26,'bonuses'=>[]],['main'=>21,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 2, 'Catch the Opening retains two block when its low-block reward activates');
$stats = DFGComputeRound([['main'=>14,'bonuses'=>[]],['main'=>23,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 0, 'Darting Strike is 6/0 against printed attack five');
$stats = DFGComputeRound([['main'=>14,'bonuses'=>[]],['main'=>2,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 0, 'Darting Strike has no free defense against printed attack six');
$stats = DFGComputeRound([['main'=>14,'bonuses'=>[]],['main'=>23,'bonuses'=>[24]]]);
check($stats[1]['attack'] >= 6 && $stats[0]['block'] === 0, 'Darting Strike has no automatic block against modified attack');
$stats = DFGComputeRound([['main'=>2,'bonuses'=>[],'advantages'=>['Red']],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 1, 'Rushdown spending rewards a hit next round instead of increasing current attack');
$stats = DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 2 && $stats[0]['block'] === 3 && $stats[0]['pierce'] === 0, 'Feint has a 2/3 fallback without penetration against low printed block');
$stats = DFGComputeRound([['main'=>4,'bonuses'=>[]],['main'=>24,'bonuses'=>[]]]);
check($stats[0]['attack'] === 4 && $stats[0]['block'] === 3 && $stats[0]['pierce'] === 3, 'Feint gains attack and three penetration at printed block four');
$stats = DFGComputeRound([['main'=>6,'bonuses'=>[]],['main'=>23,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 5, 'Covering Step reads the printed attack five boundary');
$stats = DFGComputeRound([['main'=>6,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[1]['attack'] === 5 && $stats[0]['block'] === 3, 'Covering Step ignores modified attack when printed attack is four');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[]]]);
check($stats[0]['attack'] === 2 && $stats[0]['block'] === 2 && $stats[1]['attack'] === 6, 'Firm Counter is 2/2 without an opposing bonus and exposes its printed block to Catch the Opening');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[29]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 5 && $stats[1]['attack'] === 7, 'Firm Counter reaches 5/5 against a bonus while Catch the Opening still reads printed block');
$stats = DFGComputeRound([['main'=>21,'bonuses'=>[]],['main'=>26,'bonuses'=>[]]], [['seat'=>1,'card'=>29]]);
check($stats[0]['attack'] === 2 && $stats[0]['block'] === 2, 'an opposing reaction burn cannot activate either Firm Counter reward');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[]],['main'=>31,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 3, 'Shield Bash starts at three attack and three block without either buff');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[],'advantages'=>['Green']],['main'=>31,'bonuses'=>[]]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 5, 'Shield Bash Green spending adds two block without granting the low-attack buff');
$stats = DFGComputeRound([['main'=>20,'bonuses'=>[],'advantages'=>['Green']],['main'=>16,'bonuses'=>[]]]);
check($stats[0]['attack'] === 5 && $stats[0]['block'] === 5, 'Shield Bash combines its printed low-attack read with Green spending');
$stats = DFGComputeRound([['main'=>28,'bonuses'=>[]],['main'=>24,'bonuses'=>[]]]);
check($stats[0]['attack'] === 7 && $stats[0]['block'] === 2, 'Find the Gap reaches 7/2 at printed block four without an opposing bonus');
$stats = DFGComputeRound([['main'=>28,'bonuses'=>[]],['main'=>29,'bonuses'=>[27]]], [['seat'=>1,'card'=>25]]);
check($stats[1]['block'] === 6 && $stats[0]['attack'] === 4 && $stats[0]['block'] === 2, 'Find the Gap stays 4/2 against printed block three despite bonus and reaction block');
$stats = DFGComputeRound([['main'=>26,'bonuses'=>[28]],['main'=>24,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6, 'Find the Gap bonus grants three attack against a strong printed guard');
$stats = DFGComputeRound([['main'=>26,'bonuses'=>[28]],['main'=>29,'bonuses'=>[27]]]);
check($stats[0]['attack'] === 4, 'Find the Gap bonus grants only one attack against modified block five with printed block three');
$stats = DFGComputeRound([['main'=>26,'bonuses'=>[]],['main'=>29,'bonuses'=>[27]]], [['seat'=>0,'card'=>28]]);
check($stats[0]['attack'] === 4, 'Find the Gap reaction grants one attack even against an opposing bonus burn');
$state = DFGNewGame();
check(array_sum(array_map(fn($color) => count(DFGAvailable($state['players'][0], $color)), DFG_COLORS)) === 36, 'six hands have six cards each');
check(rejects(fn() => DFGCompileEffect('Execute PHP: <?php echo 1;')), 'unsupported card text fails closed, never executes code');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => []], ['main' => 3, 'bonuses' => []]]);
check($stats[0]['attack'] === 5 && $stats[0]['damage'] === 1, 'Quick Jab gains its no-bonus attack and gets through Guarded Strike for one');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => [5]], ['main' => 3, 'bonuses' => []]]);
check($stats[0]['attack'] === 5, 'bonus burn disables Quick Jab condition but applies its bonus');
$stats = DFGComputeRound([['main' => 4, 'bonuses' => []], ['main' => 19, 'bonuses' => []]], [['seat' => 1, 'card' => 22]]);
check($stats[0]['attack'] === 4 && $stats[0]['pierce'] === 3 && $stats[1]['block'] === 8, 'conditions use printed block while reactions change effective block');
$stats = DFGComputeRound([['main' => 22, 'bonuses' => []], ['main' => 2, 'bonuses' => []]], [['seat' => 0, 'card' => 19]]);
check($stats[0]['block'] === 7, 'Brace stacks its five base block with a two-block reaction');
$stats = DFGComputeRound([['main' => 1, 'bonuses' => [2]], ['main' => 31, 'bonuses' => [32]]]);
check($stats[0]['attack'] === 6 && $stats[1]['attack'] === 10, 'bonuses stack, including effects benefiting the opponent');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 6 && $stats[0]['block'] === 1, 'Charged Fist gains one attack when conserving its bonus burn');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[36]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['attack'] === 7, 'Charged Fist with a bonus receives only the burned card effect');
$stats = DFGComputeRound([['main'=>32,'bonuses'=>[],'advantages'=>['Purple']],['main'=>1,'bonuses'=>[]]], [['seat'=>0,'card'=>35]]);
check($stats[0]['attack'] === 7 && $stats[0]['block'] === 1, 'Charged Fist Purple spending adds no attack while conservation and reactions still apply');
$stats = DFGComputeRound([['main'=>31,'bonuses'=>[34]],['main'=>1,'bonuses'=>[]]]);
check($stats[0]['block'] === 3, 'Iron Guard retains its three-block bonus');
$stats = DFGComputeRound([['main' => 33, 'bonuses' => []], ['main' => 3, 'bonuses' => []]], [['seat' => 1, 'card' => 6]]);
check($stats[0]['damage'] === 2 && $stats[1]['block'] === 5 && $stats[0]['effectiveOpponentBlock'] === 3, 'block reduction applies to final block only for the attacker’s damage');
$stats = DFGComputeRound([['main' => 33, 'bonuses' => []], ['main' => 2, 'bonuses' => []]]);
check($stats[0]['damage'] === 5, 'block reduction cannot make block negative');
$stats = DFGComputeRound([['main' => 25, 'bonuses' => []], ['main' => 2, 'bonuses' => []]]);
check($stats[0]['attack'] === 3 && $stats[0]['block'] === 6, 'Blue conditional attack and block both apply');
check(array_keys(array_filter(DFGCatalog(), fn($card) => in_array($card['color'], $card['switches'], true))) === [2, 11, 14, 21, 30, 32], 'exactly the six designated cards permit staying in the same hand');
foreach (DFG_COLORS as $color) check(count(array_filter(DFGCatalog(), fn($card) => $card['color'] === $color && in_array($color, $card['switches'], true))) === 1, 'each hand has exactly one stay option');
foreach ([2, 11, 14, 21, 30, 32] as $id) {
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
$stats = DFGComputeRound([['main'=>12,'bonuses'=>[]],['main'=>28,'bonuses'=>[26]]]);
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
check($state['phase'] === 'switching' && $state['players'][0]['life'] === 34 && $state['players'][1]['life'] === 35, 'two passes close window and apply exact simultaneous damage');
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
