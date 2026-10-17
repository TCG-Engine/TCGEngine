<?php
declare(strict_types=1);
require_once __DIR__ . '/GameLogic.php';

// Authored, linear practice. Every displayed transition runs the real rules engine.
// No session is opened and no live match is read or written.
function DFGTutorialLessons(): array {
    $lessons=[];
    $frame = static function(array $s, string $title, string $text, string $task, string $button, ?array $choice=null): array {
        return ['state'=>DFGObserve($s,0),'title'=>$title,'text'=>$text,'task'=>$task,'button'=>$button,'choice'=>$choice];
    };
    $pass = static function(array $s): array {
        while($s['phase']==='reactions') $s=DFGApply($s,$s['reactionTurn'],['type'=>'pass']);
        return $s;
    };
    $damage = static function(array $s): string {
        $stats=DFGComputeRound($s['commitments'],$s['reactions']);
        return "You dealt {$stats[0]['damage']} damage and took {$stats[1]['damage']}. Both attacks resolve together.";
    };

    $s=DFGNewGame('Red','Purple'); $frames=[];
    $frames[]=$frame($s,'One active hand','You own all 36 cards in six colored hands. Only the six cards in your active hand can be played. Start at 40 life; bring your opponent to zero to win.','Select Rushdown as your main. Its printed stats are 6 attack / 1 block.','Commit main',['kind'=>'card','value'=>2]);
    $s=DFGApply($s,0,['type'=>'commit','main'=>2]);$s=DFGApply($s,1,['type'=>'commit','main'=>35]);
    $frames[]=$frame($s,'Reveal, then react','In a match, both mains are committed face down and reveal together. Main abilities can change their printed stats. Trade Blows reads your high attack and rises to 8 attack. Reactions come after the reveal.','Pass without burning a reaction. The practice opponent will pass too.','Pass reaction');
    $s=$pass($s);
    $frames[]=$frame($s,'Damage, then a new hand',$damage($s).' Combat damage is attack minus opposing block, with a minimum of zero. Rushdown’s 6 attack meets 1 block; the opponent’s 8 attack meets your 1 block.','Select Orange, one of Rushdown’s printed destinations. Normal mains return to their hands.','Switch to Orange',['kind'=>'color','value'=>'Orange']);
    $s=DFGApply($s,0,['type'=>'switch','color'=>'Orange']);$s=DFGApply($s,1,['type'=>'switch','color'=>'Green']);
    $frames[]=$frame($s,'That is the round loop','You are now in Orange, with six different options. Rushdown is still available in Red. Each main’s bottom colors shape your next round.','Commit → reveal → alternate reactions → damage → switch.','Next lesson');
    $lessons[]=['name'=>'The round loop','frames'=>$frames];

    $s=DFGNewGame('Red','Purple');$frames=[];
    $frames[]=$frame($s,'Power now, fewer options later','A bonus burn is a second card committed with your main. It contributes its bonus effect, then leaves your collection permanently. This exercise uses Rushdown as main.','Select Combination Strike as your bonus burn: +2 attack.','Commit main + bonus',['kind'=>'card','value'=>5]);
    $s=DFGApply($s,0,['type'=>'commit','main'=>2,'bonuses'=>[5]]);$s=DFGApply($s,1,['type'=>'commit','main'=>35]);
    $frames[]=$frame($s,'React to what you can see','Your bonus raised Rushdown to 8 attack. Reaction burns are chosen after reveal, one at a time in alternating priority. They are permanent too; the main and bonus cannot also be reactions.','Select Guarded Strike to burn its reaction: +2 block.','Burn reaction',['kind'=>'card','value'=>3]);
    $s=DFGApply($s,0,['type'=>'react','card'=>3]);$s=DFGApply($s,1,['type'=>'pass']);
    $frames[]=$frame($s,'Passing is a real choice','A burn resets the pass count. Two consecutive passes close the reaction window. You can conserve your remaining cards here.','Your opponent passed. Pass now to resolve the exchange.','Pass reaction');
    $s=$pass($s);
    $frames[]=$frame($s,'The cost survives the round',$damage($s).' Combination Strike and Guarded Strike are now permanently burned. Rushdown returns. Every burn improves this exchange at the cost of future choices.','In a full match, select a main first, then a second card for an optional bonus. During reactions, clicking an eligible card burns it immediately.','Next lesson');
    $lessons[]=['name'=>'Bonus & reaction burns','frames'=>$frames];

    $s=DFGNewGame('Purple','Yellow');$frames=[];
    $frames[]=$frame($s,'Earn an advantage','Main cards can have objectives. Iron Guard earns Green advantage when it deals damage. With no bonus it becomes 2 attack / 5 block; taking no damage also carries +1 attack into the next round.','Select Iron Guard. The opponent will play Driving Kick.','Commit Iron Guard',['kind'=>'card','value'=>34]);
    $s=DFGApply($s,0,['type'=>'commit','main'=>34]);$s=DFGApply($s,1,['type'=>'commit','main'=>18]);$s=$pass($s);
    $frames[]=$frame($s,'Rewards follow you',$damage($s).' You earned Green advantage and +1 attack for next round. Advantage persists across hands; you can hold at most one token of each color.','Choose Green to find a card that can spend your new token.','Switch to Green',['kind'=>'color','value'=>'Green']);
    $s=DFGApply($s,0,['type'=>'switch','color'=>'Green']);$s=DFGApply($s,1,['type'=>'switch','color'=>'Orange']);
    $frames[]=$frame($s,'Spend before the reveal','This exercise selects Shield Bash as your main. It can spend Green for +2 block and permission to stay in Green. The carried attack also applies this round. Unused tokens stay with you.','Select your Green advantage. A spending main consumes it on reveal, even if its payoff fails.','Commit Shield Bash + Green',['kind'=>'token','value'=>'Green']);
    $s=DFGApply($s,0,['type'=>'commit','main'=>20,'advantages'=>['Green']]);$s=DFGApply($s,1,['type'=>'commit','main'=>9]);$s=$pass($s);
    $frames[]=$frame($s,'Objectives create future options',$damage($s).' Shield Bash used the token for +2 block. Green is now a legal destination alongside its printed routes. Carried attack lasts one round.','In a match, choose a main, open “Spend advantage”, select a held token, then commit. Only main cards can earn or spend advantage.','Next lesson');
    $lessons[]=['name'=>'Advantage & pressure','frames'=>$frames];

    $s=DFGNewGame('Red','Purple');$s['players'][0]['discarded']=[7,8,9,10,11,12];
    $s=DFGApply($s,0,['type'=>'commit','main'=>2]);$s=DFGApply($s,1,['type'=>'commit','main'=>35]);$s=$pass($s);$frames=[];
    $frames[]=$frame($s,'An empty hand is a trap','Practice setup: all six of your Orange cards have already been burned. Empty printed destinations remain legal. Entering one loses the fight, even with life left. Your opponent can sometimes force your destination.','Choose the empty Orange hand to see this loss condition. In a match, choose the nonempty Red route here.','Enter empty Orange',['kind'=>'color','value'=>'Orange']);
    $s=DFGApply($s,0,['type'=>'switch','color'=>'Orange']);$s=DFGApply($s,1,['type'=>'switch','color'=>'Green']);
    $frames[]=$frame($s,'Ready for a real fight','You lost by entering an empty hand with life still remaining. Reaching zero life or running out of all cards also loses; simultaneous losses are a draw. Watch the hand orbs before you burn or choose a route.','Start with Casual for varied choices, Tactical for resource management, or Pro for slower lookahead. Hover or focus cards for full text; tap to pin details.','Finish tutorial');
    $lessons[]=['name'=>'Routes & defeat','frames'=>$frames];
    return $lessons;
}
