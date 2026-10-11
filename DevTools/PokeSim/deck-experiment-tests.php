<?php
require __DIR__.'/deck-experiment.php';
$checks=0;
function experimentCheck($ok,$message){global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
$original=file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt');
foreach(PokeExperimentVariants() as $name=>$changes){
    $deck=PokeExperimentDeck($changes);
    foreach(['me04-082','sv09-144'] as $id)experimentCheck(array_sum(array_column(array_filter($deck,fn($e)=>$e['id']===$id),'count'))===1,$name.' preserves protected singleton');
    experimentCheck(array_sum(array_column($deck,'count'))===60&&PokeValidateDeck($deck)===[],$name.' is a legal implemented 60-card deck');
}
experimentCheck(PokeSimulateGame(42,2)===PokeSimulateGame(42,2,1500,'sinistcha','sinistcha',PokeExperimentDeck([])),'Explicit baseline override reproduces existing simulation');
$variant=PokeSimulateGame(42,2,1500,'sinistcha','brisbane-lopunny',PokeExperimentDeck(PokeExperimentVariants()['retrieval-to-lillie']));
experimentCheck($variant['status']==='complete'&&$variant['openingStats'][0]['goal']==='Vengeful Anchor','Variant retains the Dhelmise policy and opening profile');
experimentCheck(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt')===$original,'Registered deck untouched');
try{PokeExperimentDeck(['me05-039'=>-5,'mee-005'=>5]);throw new RuntimeException('Negative count accepted');}catch(InvalidArgumentException $e){++$checks;}
foreach(['me04-082','sv09-144'] as $id){try{PokeExperimentDeck([$id=>-1,'mee-005'=>1]);throw new RuntimeException('Protected cut accepted');}catch(InvalidArgumentException $e){++$checks;}}
$v2=PokeNamedDeck('dhelmise-v2');
experimentCheck(PokeExperimentDeck([],'dhelmise-v2')===PokeExperimentDeck(PokeExperimentVariants()['retrieval-to-lillie']),'Historical v2 experiment baseline remains the Retrieval to Lillie list');
experimentCheck($v2===PokeExperimentDeck(PokeExperimentVariants('dhelmise-v2')['belt-gear-two-ultra-pad'],'dhelmise-v2'),'Saved v2 is the confirmed Belt to Gear and two Ultras to Pads list');
experimentCheck(PokeDetectDeck(array_reverse($v2))==='dhelmise-v2','Deck detection ignores entry order');
experimentCheck(PokeDeckName('dhelmise-v2')==='dhelmise v2','Selector label');
experimentCheck(PokeOpeningProfile('dhelmise-v2')['name']==='Vengeful Anchor','V2 retains opening goal');
foreach(PokeExperimentVariants('dhelmise-v2') as $name=>$changes){
    $deck=PokeExperimentDeck($changes,'dhelmise-v2');
    experimentCheck(PokeValidateDeck($deck)===[],$name.' v2 legal');
    experimentCheck(array_sum(array_column(array_filter($deck,fn($e)=>$e['id']==='me04-082'),'count'))===1,$name.' preserves Red Card');
}
foreach([1,2] as $first){
    $game=PokeSimulateGame(43,$first,1500,'dhelmise-v2','brisbane-lopunny');
    experimentCheck($game['status']==='complete'&&$game['openingStats'][0]['deck']==='dhelmise-v2'&&$game['openingStats'][0]['goal']==='Vengeful Anchor','V2 completes with correct telemetry in each order');
}
experimentCheck(PokeExperimentBlenderMilestones([['type'=>'trainer','source'=>'sv08-164','turn'=>1]])===['blenderTurn1Played'=>true,'blenderByOpportunityPlayed'=>true],'Turn one Blender action captured');
experimentCheck(PokeExperimentBlenderMilestones([['type'=>'trainer','source'=>'sv08-164','turn'=>2]])===['blenderTurn1Played'=>false,'blenderByOpportunityPlayed'=>true],'Second turn Blender not counted as turn one');
experimentCheck(PokeExperimentBlenderMilestones([['type'=>'decision','source'=>'sv08-164','turn'=>1]])===['blenderTurn1Played'=>false,'blenderByOpportunityPlayed'=>false],'Decisions do not count as Blender plays');
$meowthDeck=PokeExperimentDeck(PokeExperimentVariants('dhelmise-v2')['belt-to-meowth'],'dhelmise-v2');
PokeCreateGame($meowthDeck,PokeNamedDeck('brisbane-lopunny'),44,1);
for($i=0;$i<100&&GetCurrentPhase()==='SETUP';++$i)PokeApplyAction(PokeBotChoose(PokeObservation(PokePendingPlayer())));
experimentCheck(GetCurrentPhase()==='MAIN','Meowth variant completes setup');
$bellBaseline=PokeExperimentDeck([],'dhelmise-v2','call-bell');
experimentCheck($bellBaseline===$v2,'Call Bell suite starts from the updated selector v2');
foreach(PokeExperimentVariants('dhelmise-v2','call-bell') as $name=>$changes){
    $deck=PokeExperimentDeck($changes,'dhelmise-v2','call-bell');
    experimentCheck(PokeValidateDeck($deck)===[],$name.' Call Bell deck legal');
    experimentCheck(array_sum(array_column($deck,'count'))===60,$name.' Call Bell deck has 60 cards');
    experimentCheck(array_sum(array_column(array_filter($deck,fn($e)=>$e['id']==='me04-082'),'count'))===1,$name.' Call Bell suite preserves Red Card');
}
$usage=PokeExperimentAccessMilestones([
    ['type'=>'trainer','source'=>'sv08-165','turn'=>1],
    ['type'=>'decision','turn'=>1,'choices'=>["Team Rocket's Petrel"]],
    ['type'=>'trainer','source'=>'sv10.5b-084','turn'=>1],
    ['type'=>'decision','turn'=>1,'choices'=>[]],
    ['type'=>'bench','source'=>'me03-062','turn'=>2],
    ['type'=>'decision','turn'=>2,'choices'=>["Lillie's Determination"]],
]);
experimentCheck($usage['callBellTurn1Plays']===1,'Call Bell accepted plays tracked');
experimentCheck($usage['openingSearches']['sv08-165'][0]['choices']===["Team Rocket's Petrel"],'Call Bell search target tracked');
experimentCheck($usage['openingSearches']['sv10.5b-084'][0]['choices']===[],'Search whiff retained');
experimentCheck($usage['openingSearches']['me03-062'][0]['turn']===2,'Meowth turn two search tracked');
echo "PASS $checks experiment checks\n";
