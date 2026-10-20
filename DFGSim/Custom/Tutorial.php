<?php
declare(strict_types=1);
require_once __DIR__ . '/GameLogic.php';

// Bump when checkpoints change so saved tutorials restart at a valid lesson.
const DFG_TUTORIAL_VERSION = 3;

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

    $add('Tired of losing to a bad hand?', 'DFG stands for Deterministic Fighting Game. There’s no randomness: no shuffling, dice, or lucky topdecks. Read your opponent and choose what to spend.', 'Let’s play a round.');
    $add('Six hands, secret moves', 'Both fighters have the same 36 cards, split into six colored hands. Only one hand is active at a time. You can see their active hand, but you choose your moves in secret and reveal together.', 'The colored circles show how many cards remain in each hand.', null, [], 'your-orb-Red');
    $add('How to win', 'Both fighters start at 40 life. Reduce your opponent to zero to win. A fighter also loses by entering an empty hand or burning all their cards. If both lose together, it’s a draw.', 'Burned cards are gone for the fight. Spend them for a stronger round, or save them for later.', null, [], null, 'Try a round');
    $finishLesson('Welcome to DFG');

    $s = DFGNewGame('Red', 'Purple');
    $add('Choose a main and a bonus', 'Your main is the move you play this round. Rushdown has 6 attack and 1 block. Add Combination Strike as a bonus burn for +2 attack; only its bonus effect applies, and that card is spent permanently.', 'Click Rushdown, then Combination Strike, then press Commit.', ['type'=>'commit','main'=>2,'bonuses'=>[5]], [[1,['type'=>'commit','main'=>35]]]);
    $add('The reveal', 'Your Rushdown has 8 attack. The opponent chose Trade Blows, which gains attack against a high-attack move and also reaches 8. You can now burn reactions with both moves visible.', 'Unlike your bonus, Rushdown stays available after this round.', null, [], 'played-main-1');
    $add('Burn a reaction', 'Guarded Strike’s reaction adds +2 block, bringing yours from 1 to 3. Reactions spend cards permanently, just like bonuses.', 'Click Guarded Strike to burn it now. The opponent will pass.', ['type'=>'react','card'=>3], [[1,['type'=>'pass']]]);
    $add('Pass to resolve', 'Players take turns burning or passing. A burn resets the pass count; two passes in a row resolve both attacks together.', 'Your opponent has passed. Press Pass reaction.', ['type'=>'pass']);
    $add('Damage and the cost of burns', $damage($s).' Attack minus block determines damage: 8 − 1 to them, 8 − 3 to you. Without either burn, you would have dealt 5 and taken 7.', 'Combination Strike and Guarded Strike are gone. Rushdown returns to your hand.');
    $add('Choose your next hand', 'The dots at the bottom of your main show your next-hand options. Rushdown lets you stay in Red or switch to Orange for different moves.', 'Choose Orange. Next, we’ll try earning and spending advantage.', ['type'=>'switch','color'=>'Orange'], [[1,['type'=>'switch','color'=>'Green']]]);
    $finishLesson('Your first round and burns');

    $s = DFGNewGame('Purple', 'Yellow');
    $add('Earn advantage', 'We’re starting a fresh example in Purple. Iron Guard earns a Green advantage token by dealing damage. With no bonus burn, it gains +1 attack for 2 attack and 5 block.', 'Choose Iron Guard and press Commit. We’ll pass both reactions.', ['type'=>'commit','main'=>34], [[1,['type'=>'commit','main'=>18]], [0,['type'=>'pass']], [1,['type'=>'pass']]]);
    $add('Take your reward to Green', $damage($s).' You earned Green advantage for dealing damage and +1 attack next round for taking none. Tokens last until spent; you can hold one of each color.', 'Choose Green to try a move that spends your token.', ['type'=>'switch','color'=>'Green'], [[1,['type'=>'switch','color'=>'Orange']]]);
    $add('Spend with your main', 'Forearm Check can spend Green for +2 block and the option to stay in Green afterward. Declare spending before committing, while your move is secret.', 'Click Forearm Check. Open Spend advantage, choose Green, then press Commit. We’ll pass both reactions.', ['type'=>'commit','main'=>20,'advantages'=>['Green']], [[1,['type'=>'commit','main'=>9]], [1,['type'=>'pass']], [0,['type'=>'pass']]]);
    $add('Tokens and cards are different resources', $damage($s).' Your token paid for extra block and a Green destination. Forearm Check is still available: spending advantage uses a token, not a card.', 'Declared tokens are spent on reveal, even if the move fails. Iron Guard’s carried attack lasts just one round.');
    $add('Time for your own fight', 'You’ve chosen a main, burned bonuses and reactions, changed hands, and spent advantage. Hover or focus any card to read it as you play.', 'Try a Casual bot first. You can replay any lesson.', null, [], null, 'Finish tutorial');
    $finishLesson('Earning and spending advantage');
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
