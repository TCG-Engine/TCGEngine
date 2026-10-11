<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($severity,$message,$file,$line) { if (error_reporting() & $severity) throw new ErrorException($message,0,$severity,$file,$line); });
$checks=0;
function meowthCheck(bool $ok,string $message): void { global $checks; ++$checks; if (!$ok) throw new RuntimeException($message); }
function meowthBoard(string $own='30th-101',string $enemy='sv08-130'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN'); SetTurnNumber(4); SetTurnPlayer(1); SetFirstPlayer(2); SetRandomState(42);
    foreach ([1,2] as $p) {
        AddPlayerTurns($p,2); AddSetupReady($p,true);
        $o=PokeAdd($p,'Active',$p===1?$own:$enemy); $o->Controller=$p; $o->EnteredTurn=0;
        $o->Energy=['mee-008','mee-008','mee-008'];
        for ($i=0;$i<6;++$i) PokeAdd($p,'Prizes','mee-008');
        for ($i=0;$i<20;++$i) PokeAdd($p,'Deck','mee-008');
        PokeSetVar('deckKey:'.$p,'galarian-meowth');
    }
}
function meowthAnswer(string $value): void {
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
    PokeApplyAction(['type'=>'decision','player'=>1,'value'=>$value]);
}
function meowthPlay(string $id): void {
    $o=PokeAdd(1,'Hand',$id); PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>PokeRef(1,'Hand',$o->mzIndex)]);
}
$deck=PokeNamedDeck('galarian-meowth');
meowthCheck(!PokeValidateDeck($deck)&&array_sum(array_column($deck,'count'))===60,'60-card deck is fully implemented');
meowthCheck(PokeDetectDeck(array_reverse($deck))==='galarian-meowth','Deck identity survives text import');
$totals=['Pokemon'=>0,'Trainer'=>0,'Energy'=>0];
foreach ($deck as $entry) { $totals[CardType($entry['id'])]+=$entry['count']; meowthCheck(!str_contains(CardName($entry['id']),'Genesect'),'No Genesect'); }
meowthCheck($totals===['Pokemon'=>16,'Trainer'=>32,'Energy'=>12],'Exact category totals');
foreach ([0,7,10] as $n) {
    meowthBoard(); for ($i=0;$i<$n;++$i) PokeAdd(1,'Hand','mee-008');
    PokeApplyAction(['player'=>1,'type'=>'attack','source'=>'p1Active-0','index'=>1]);
    meowthCheck(GetZoneObject('p2Active-0')->Damage===$n*10,'Treasure Rush uses hand size');
}
meowthBoard(); PokeApplyAction(['player'=>1,'type'=>'attack','source'=>'p1Active-0','index'=>0]);
meowthCheck(PokeCount(1,'Hand')===1&&GetZoneObject('p2Active-0')->Damage===10,'Pay Day draws one');
meowthBoard(); meowthPlay('me02.5-258'); meowthCheck(PokeCount(1,'Hand')===3,'Cheren draws three');
meowthBoard(); PokeAdd(1,'Hand','sv09-120'); PokeAdd(1,'Hand','sv07-106'); PokeAdd(1,'Hand','me01-094'); meowthPlay('me05-078');
$d=PokeDecisionOptions(1); meowthCheck(count($d['choices'])===2,'Gwynn excludes Rule Boxes');
meowthAnswer(implode('&',array_column($d['choices'],'value')));
meowthCheck(PokeCount(1,'Hand')===7,'Gwynn discards two and draws six across serialization');
meowthBoard('sv07-106'); PokeAdd(1,'Discard','mee-008'); PokeAdd(1,'Discard','mee-008'); PokeAdd(1,'Discard','mee-005');
$mawile=PokeAdd(1,'Bench','me01-094'); $mawile->Controller=1;
PokeAdd(1,'Hand','sv08-130'); PokeApplyAction(['player'=>1,'type'=>'evolve','source'=>'p1Hand-0','target'=>'p1Active-0']);
$d=PokeDecisionOptions(1); meowthCheck(count($d['choices'])===2,'Assemble Alloy selects only basic Metal');
meowthAnswer(implode('&',array_column($d['choices'],'value')));
meowthAnswer('p1Active-0'); meowthAnswer('p1Bench-0');
meowthCheck(count(GetZoneObject('p1Active-0')->Energy)===4&&GetZoneObject('p1Bench-0')->Energy===['mee-008'],'Alloy splits Energy between Metal Pokemon across await frames');
meowthCheck(PokeCount(1,'Discard')===1&&!GetEnergyUsed(1),'Ability preserves manual attachment');
meowthBoard('sv07-106'); PokeAdd(1,'Discard','mee-008'); PokeAdd(1,'Hand','sv08-130');
PokeApplyAction(['player'=>1,'type'=>'evolve','source'=>'p1Hand-0','target'=>'p1Active-0']); meowthAnswer('-');
meowthCheck(PokeCount(1,'Discard')===1&&PokeDecisionOptions(1)===null,'Alloy can be declined');
meowthBoard('sv08-130'); PokeApplyAction(['player'=>1,'type'=>'attack','source'=>'p1Active-0','index'=>0]);
PokeStateImport(PokeStateExport()); SetTurnNumber(5);
$fire=PokeAdd(2,'Bench','sv03-026'); $fire->Controller=2;
// A Fire source must not double its damage into Metal Defender's protected target.
$before=GetZoneObject('p1Active-0')->Damage; PokeDealAttackDamage(2,'p2Bench-0',20);
meowthCheck(GetZoneObject('p1Active-0')->Damage-$before===20,'Metal Defender suppresses Weakness on next turn');
SetTurnNumber(6); $before=GetZoneObject('p1Active-0')->Damage; PokeDealAttackDamage(2,'p2Bench-0',20);
meowthCheck(GetZoneObject('p1Active-0')->Damage-$before===40,'Metal Defender expires');
foreach ([6,3,1] as $prizes) {
    meowthBoard('me01-094'); foreach (array_slice(PokeObjects(1,'Prizes'),$prizes) as $o) $o->Remove();
    PokeDealAttackDamage(1,'p1Active-0',0); PokeMacro('Attack',1,'me01-094',0,['mzID'=>'p1Active-0']); PokeDrain();
    meowthCheck(GetZoneObject('p2Active-0')->Damage===80*(6-$prizes),'Gobble Down scales with prizes taken');
}
foreach ([0,10] as $damage) {
    meowthBoard('me01-094'); GetZoneObject('p2Active-0')->Damage=$damage;
    PokeMacro('Attack',1,'me01-094',1,['mzID'=>'p1Active-0']); PokeDrain();
    meowthCheck(GetZoneObject('p2Active-0')->Damage===$damage+($damage?30:260),'Huge Bite uses target damage');
}
meowthBoard('sv08.5-004'); PokeApplyAction(['player'=>1,'type'=>'attack','source'=>'p1Active-0','index'=>0]);
$item=PokeAdd(2,'Hand','me03-072');
meowthCheck(!PokeCanPlayTrainer(2,PokeRef(2,'Hand',$item->mzIndex)),'Budew blocks Items on the next turn');
SetTurnNumber(6); meowthCheck(PokeCanPlayTrainer(2,PokeRef(2,'Hand',$item->mzIndex)),'Budew lock expires');
meowthBoard(); for ($i=0;$i<7;++$i) PokeAdd(1,'Hand','mee-008');
$a=PokeBotChoose(PokeObservation(1)); meowthCheck($a['type']==='attack'&&$a['index']===1,'Bot chooses Treasure Rush over Pay Day');
meowthCheck(PokeOpeningProfile('galarian-meowth')['ready'](1,GetZoneObject('p1Active-0')),'Seven cards meets opening goal');
meowthBoard(); AddPlayerTurns(1,1);
for ($i=0;$i<6;++$i) PokeAdd(1,'Hand','mee-008'); PokeAdd(1,'Hand','sv09-120'); AddSupporterUsed(1,true);
$a=PokeBotChoose(PokeObservation(1)); meowthCheck($a['type']==='attack'&&$a['index']===1,'First-turn bot preserves seven-card hand over optional benching');
meowthBoard('sv09-120'); PokeAdd(1,'Hand','30th-101');
GetZoneObject('p1Active-0')->Remove(); AddSetupReady(1,false);
SetCurrentPhase('SETUP'); PokeAdd(1,'Hand','sv07-106');
$a=PokeBotChoose(PokeObservation(1)); meowthCheck($a['type']==='setup-active'&&GetZoneObject($a['source'])->CardID==='30th-101','Bot starts Meowth when available');
meowthBoard(); $arch=PokeAdd(1,'Bench','sv08-130'); $arch->Controller=1; $arch->Energy=['mee-008','mee-008','mee-008'];
PokeAdd(1,'Hand','me01-130'); $a=PokeBotChoose(PokeObservation(1));
meowthCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me01-130','Bot switches from weak Meowth to powered Archaludon');
meowthBoard('sv08-130'); foreach(array_slice(PokeObjects(1,'Prizes'),2) as $o)$o->Remove();
$mawile=PokeAdd(1,'Bench','me01-094'); $mawile->Controller=1; $mawile->Energy=['mee-008','mee-008']; PokeAdd(1,'Hand','me01-130');
$a=PokeBotChoose(PokeObservation(1)); meowthCheck($a['type']==='trainer','Bot switches to Mawile for a late knockout');
meowthCheck(PokePrizeValue('me01-094')===3,'Mega Mawile awards three prizes');
foreach($deck as $entry) meowthCheck(is_file(dirname(__DIR__,2).'/PokeSim/WebpImages/'.$entry['id'].'.webp'),'Deck artwork available: '.$entry['id']);
foreach ([1,2] as $first) {
    $game=PokeSimulateGame(42,$first,1500,'galarian-meowth','dhelmise-v2');
    meowthCheck($game['status']==='complete','Meowth matchup completes: '.$game['reason']);
}
echo "Meowth: $checks checks passed\n";
