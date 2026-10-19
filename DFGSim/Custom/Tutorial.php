<?php
declare(strict_types=1);
require_once __DIR__ . '/GameLogic.php';

// Bump when checkpoints change so saved tutorials restart at a valid lesson.
const DFG_TUTORIAL_VERSION = 2;

function DFGTutorialLessons(): array {
    // Start, advance, and payload helpers share these immutable checkpoints for
    // this request. PHP's copy-on-write arrays keep caller changes isolated.
    static $cachedLessons = null;
    if ($cachedLessons !== null) return $cachedLessons;

    $lessons = [];
    $frames = [];
    $s = DFGNewGame('Red', 'Purple');
    // Each checkpoint is the actual engine state before its instruction. Player
    // inputs and the opponent's authored replies use the same rules as a match.
    $add = static function(string $title, string $text, string $task = '', ?array $action = null, array $script = [], ?string $target = null, string $button = 'Continue') use (&$s, &$frames): void {
        $frames[] = ['state'=>$s, 'title'=>$title, 'text'=>$text, 'task'=>$task,
            'action'=>$action, 'script'=>$script, 'target'=>$target, 'button'=>$button];
        if ($action !== null) $s = DFGApply($s, 0, $action);
        foreach ($script as [$seat, $move]) $s = DFGApply($s, $seat, $move);
    };
    $finishLesson = static function(string $name) use (&$lessons, &$frames): void {
        $lessons[] = ['name'=>$name, 'frames'=>$frames];
        $frames = [];
    };
    $damage = static function(array $state): string {
        $stats = DFGComputeRound($state['commitments'], $state['reactions']);
        return "You dealt {$stats[0]['damage']} damage and took {$stats[1]['damage']}.";
    };

    $add('Tired of losing to a bad hand?', 'DFG stands for Deterministic Fighting Game. There’s no randomness: no shuffling, dice, or lucky topdecks. Winning comes down to the moves you choose and how well you can read the opponent.', 'Let’s see how it works!');
    $add('Your cards', 'You and your opponent each start with the same 36 cards. They’re split into six colored hands, with six cards in each.', 'Those colored circles show how many cards you have in each hand.', null, [], 'your-orb-Red');
    $add('The unknown is your opponent', 'You can see which hand your opponent is using, but you don’t know which move they’ll pick. You both choose in secret, then reveal together. A great move against one card might be a terrible move against another.', 'What do you think they’ll play? Will it be the statistical best card against your current hand? Or will they try to counter your counter?');
    $add('How much is this hit worth?', 'You can burn cards to make a move stronger. Once burned, a card is gone for the rest of the fight. That leaves you with a choice: push for more damage now, or keep that card for later?', 'We’ll try both. First, let’s play a round without burning anything.', null, [], null, 'Show me');
    $finishLesson('Welcome to DFG');

    $s = DFGNewGame('Red', 'Purple');
    $add('Start with your Red hand', 'You have all six hands, but only one is active at a time. You’re starting in Red. The six cards below are the moves you can choose right now.', 'Both fighters start at 40 life. Bring your opponent to zero to win.', null, [], 'your-orb-Red');
    $add('Choose your first move', 'Let’s try Rushdown. It has 6 attack and 1 block: a hard hit, with very little protection. The card you choose for the round is called your main.', 'Click Rushdown, then press Commit. Leave the other cards alone for now.', ['type'=>'commit','main'=>2], [[1,['type'=>'commit','main'=>35]]]);
    $add('Now you can see their move', 'Your opponent chose Trade Blows. Its ability makes it hit harder against a move with high attack, so it’s now at 8 attack. The opponent had a successful read on your play, but that\'s ok! You both start on even footing next round.', 'Next comes a chance to burn cards in response.', null, [], 'played-main-1');
    $add('You can choose to pass', 'During reactions, you take turns either burning a card or passing. When both players pass in a row, the attacks resolve.', 'Press Pass reaction. Your opponent will pass too.', ['type'=>'pass'], [[1,['type'=>'pass']]]);
    $add('Both attacks land', $damage($s).' Your 6 attack got past their 1 block for 5 damage. Their 8 attack got past your 1 block for 7. Both attacks happen together.', 'You don’t lose Rushdown just for playing it. It goes back to your Red hand.');
    $add('Pick your next hand', 'The colored dots at the bottom of your main tell you where you can go next. Rushdown lets you choose Red or Orange. Changing hands gives you a different set of moves for the next round.', 'Choose Orange.', ['type'=>'switch','color'=>'Orange'], [[1,['type'=>'switch','color'=>'Green']]]);
    $add('One round down', 'You’re now in Orange, with six different moves to choose from. Rushdown is still waiting in Red for a later round. Your choice of main decides both how you fight now and which hands you can reach next.', 'Let’s go back to the same opening and try spending a card to change the result.', null, [], null, 'Try a bonus burn');
    $finishLesson('Your first round');

    $s = DFGNewGame('Red', 'Purple');
    $add('Give Rushdown a boost', 'We’re back at the opening, with all your cards restored. This time, add Combination Strike as a bonus burn. Its bonus gives your main +2 attack, but Combination Strike will be gone for the rest of this fight.', 'Click Rushdown first, then Combination Strike, then press Commit.', ['type'=>'commit','main'=>2,'bonuses'=>[5]], [[1,['type'=>'commit','main'=>35]]]);
    $add('That extra attack cost a card', 'Rushdown now has 8 attack. Combination Strike used its bonus effect, not its main ability. It’s already burned; you won’t be able to play it in a later round.', 'Now let’s use a reaction to protect you from Trade Blows.', null, [], 'played-main-0');
    $add('React after the reveal', 'You know what’s coming now, so you can decide whether another card is worth spending. Guarded Strike’s reaction gives you +2 block.', 'Click Guarded Strike. During reactions, clicking a playable card burns it straight away.', ['type'=>'react','card'=>3], [[1,['type'=>'pass']]]);
    $add('That’s enough for now', 'Your block has gone from 1 to 3. Your opponent has passed. You could burn again, but let’s save the rest of your cards. A burn starts the pass count over; two passes in a row still end the exchange.', 'Press Pass reaction to let the attacks land.', ['type'=>'pass']);
    $add('A better trade, at a price', $damage($s).' Compare that with the first attempt: you dealt 5 and took 7. The burns helped, but Combination Strike and Guarded Strike are both gone.', 'Burns can turn a round around. Just remember that the cards you spend now won’t be there when you return to this hand.', null, [], null, 'Try earning advantage');
    $finishLesson('Burning cards');

    $s = DFGNewGame('Purple', 'Yellow');
    $add('Some moves earn a reward', 'For this example, you’re starting in Purple with fresh cards. Iron Guard has an objective: deal damage to earn a Green advantage token. Playing it without a bonus also gives it +1 attack, bringing it to 2 attack and 5 block.', 'Choose Iron Guard and press Commit. We’ll have both players pass their reactions this time.', ['type'=>'commit','main'=>34], [[1,['type'=>'commit','main'=>18]], [0,['type'=>'pass']], [1,['type'=>'pass']]]);
    $add('You earned Green advantage', $damage($s).' Iron Guard dealt damage, so you earned a Green token. You also took no damage, which gives you +1 attack next round.', 'A token stays with you when you change hands. You can hold one of each color at a time.');
    $add('Take that token to Green', 'You don’t have to spend an advantage as soon as you earn it. Let’s use this one to try Forearm Check, a Green move with a payoff for spending Green.', 'Choose Green as your next hand.', ['type'=>'switch','color'=>'Green'], [[1,['type'=>'switch','color'=>'Orange']]]);
    $add('Spend it with your main', 'Forearm Check can spend your Green token for +2 block and the option to stay in Green afterward. Choose the token before you commit, while your move is still secret.', 'Click Forearm Check. Open Spend advantage, choose Green, then press Commit. We’ll pass the reactions again.', ['type'=>'commit','main'=>20,'advantages'=>['Green']], [[1,['type'=>'commit','main'=>9]], [1,['type'=>'pass']], [0,['type'=>'pass']]]);
    $add('The token is spent', $damage($s).' Your Green token paid for the extra block. It also added Green to your next-hand choices. Forearm Check wasn’t burned, so you can use the card again.', 'Spending advantage uses a token, not a card. If you choose to spend one, it’s used when the moves are revealed, even if the move doesn’t work out.');
    $add('Look one round ahead', 'The +1 attack from Iron Guard helped this round, then ran out. Unspent advantage tokens last until you use them. When you compare moves, look at what they could leave you for the next round as well as the damage they deal now.', 'There’s one more way to lose that you’ll want to watch for.', null, [], null, 'Show me');
    $finishLesson('Earning and spending advantage');

    $s = DFGNewGame('Red', 'Purple');
    $s['players'][0]['discarded'] = [7,8,9,10,11,12];
    $s = DFGApply($s,0,['type'=>'commit','main'=>2]);
    $s = DFGApply($s,1,['type'=>'commit','main'=>35]);
    while ($s['phase'] === 'reactions') $s = DFGApply($s,$s['reactionTurn'],['type'=>'pass']);
    $add('Keep an eye on your hands', 'We’ve jumped ahead to a fight where all six of your Orange cards have been burned. Look at the Orange circle; it’s empty! You still have life and cards in other hands, but entering an empty hand loses the fight.', 'In a real match, you’d choose Red here. For this example, choose Orange and watch what happens.', ['type'=>'switch','color'=>'Orange'], [[1,['type'=>'switch','color'=>'Green']]]);
    $add('Out of moves', 'You lost because Orange had no cards left to play. That’s why burning your last card in a hand can be dangerous. Some moves can even let your opponent choose where you go next.', 'You also lose at zero life or if every card is burned. If both players lose together, the fight is a draw.');
    $add('Time for your own fight', 'You’ve tried choosing a main, burning for attack and defense, changing hands, and spending advantage. You don’t need to memorize all 36 cards before you play. Hover or focus a card to read it.', 'Try a Casual bot for your first fight. You can replay a lesson whenever you want.', null, [], null, 'Finish tutorial');
    $finishLesson('Running out of moves');
    return $cachedLessons = $lessons;
}

function DFGTutorialStart(int $lesson = 0, int $step = 0): array {
    $lessons = DFGTutorialLessons();
    if (!isset($lessons[$lesson]['frames'][$step])) throw new InvalidArgumentException('Unknown lesson step');
    return ['version'=>DFG_TUTORIAL_VERSION, 'lesson'=>$lesson, 'step'=>$step, 'complete'=>false, 'revision'=>0,
        'matchId'=>bin2hex(random_bytes(12)), 'state'=>$lessons[$lesson]['frames'][$step]['state']];
}
function DFGTutorialAdvance(array $tutorial, ?array $action): array {
    $lessons = DFGTutorialLessons();
    $frame = $lessons[$tutorial['lesson']]['frames'][$tutorial['step']];
    if ($tutorial['complete']) throw new LogicException('Tutorial complete');
    $expected = $frame['action'];
    if ($expected !== null) {
        $normalized = $action;
        if (($normalized['type'] ?? '') === 'commit') {
            if (($normalized['bonuses'] ?? null) === []) unset($normalized['bonuses']);
            if (($normalized['advantages'] ?? null) === []) unset($normalized['advantages']);
        }
        if ($normalized != $expected) throw new InvalidArgumentException('Follow the lesson: ' . $frame['task']);
        $tutorial['state'] = DFGApply($tutorial['state'], 0, $action);
        foreach ($frame['script'] as [$seat, $move]) $tutorial['state'] = DFGApply($tutorial['state'], $seat, $move);
    } elseif ($action !== null) throw new InvalidArgumentException('Use Continue to move to the next part of this lesson.');
    if ($tutorial['step'] + 1 < count($lessons[$tutorial['lesson']]['frames'])) $tutorial['step']++;
    elseif ($tutorial['lesson'] + 1 < count($lessons)) {
        $tutorial['lesson']++; $tutorial['step'] = 0;
        $tutorial['state'] = $lessons[$tutorial['lesson']]['frames'][0]['state'];
    } else $tutorial['complete'] = true;
    $tutorial['revision']++;
    return $tutorial;
}
function DFGTutorialPayload(array $tutorial): array {
    $lessons = DFGTutorialLessons();
    $frame = $lessons[$tutorial['lesson']]['frames'][$tutorial['step']];
    return ['lesson'=>$tutorial['lesson'], 'step'=>$tutorial['step'], 'complete'=>$tutorial['complete'],
        'names'=>array_column($lessons, 'name'), 'steps'=>count($lessons[$tutorial['lesson']]['frames']),
        'title'=>$frame['title'], 'text'=>$frame['text'], 'task'=>$frame['task'],
        'action'=>$frame['action'], 'target'=>$frame['target'], 'button'=>$frame['button'],
        'totalSteps'=>array_sum(array_map(static fn($lesson) => count($lesson['frames']), $lessons))];
}
