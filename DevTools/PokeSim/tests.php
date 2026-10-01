<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
set_error_handler(function($severity,$message,$file,$line) { if (error_reporting() & $severity) throw new ErrorException($message,0,$severity,$file,$line); });
$checks = 0;
function check($value, $message): void { global $checks; ++$checks; if (!$value) throw new RuntimeException($message); }
function fixture($own = 'me05-039', $enemy = 'me05-039'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN'); SetTurnNumber(4); SetTurnPlayer(1); SetFirstPlayer(2); SetRandomState(42);
    foreach ([1,2] as $seat) {
        AddPlayerTurns($seat,2); AddSetupReady($seat,true);
        $obj=PokeAdd($seat,'Active',$seat===1?$own:$enemy);$obj->Controller=$seat;$obj->Energy=['mee-005'];
        for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-005');
        for($i=0;$i<15;++$i)PokeAdd($seat,'Deck','mee-005');
    }
}
function play($id): void { $obj=PokeAdd(1,'Hand',$id);PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>PokeRef(1,'Hand',$obj->mzIndex)]); }
function answer($value, $player=1): void {
    // Every answer crosses a JSON snapshot, as it does in the local HTTP app.
    PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
    PokeApplyAction(['type'=>'decision','player'=>$player,'value'=>$value]);
}
function choice($id,$player=1): string { foreach (PokeDecisionOptions($player)['choices'] as $c) if ($c['card']===$id)return $c['value'];throw new RuntimeException("Missing choice $id"); }
$deck=PokeParseDeckText(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt'));
check(count($deck)===20 && !PokeValidateDeck($deck),'All 20 deck printings must be implemented');
fixture();$o=GetZoneObject('p1Active-0');$o->Damage=20;$o->Energy[]='me03-088';$o->Conditions=['Poisoned'=>10];$o->Evolutions=['me05-005'];
$state=PokeStateExport();PokeStateImport(json_decode(json_encode($state),true));check(PokeStateExport()===$state,'Snapshot must preserve zones, energies, evolutions and conditions');
fixture();$before=PokeStateExport();try{PokeApplyAction(['type'=>'attack','player'=>2,'source'=>'p2Active-0','index'=>0]);throw new RuntimeException('Illegal action accepted');}catch(InvalidArgumentException $e){}check(PokeStateExport()===$before,'Illegal action must not mutate');
fixture('me05-005','me05-005');PokePlaceDamageCounters('p1Active-0','p2Active-0',1);check(GetZoneObject('p2Active-0')->Damage===0,'Hide n Sneak stops attack effects');
PokeSetCondition('p1Active-0','p2Active-0','Poisoned','Ability');check(!GetZoneObject('p2Active-0')->Conditions,'Hide n Sneak stops ability effects');
PokeDealAttackDamage(1,'p1Active-0',20);check(GetZoneObject('p2Active-0')->Damage===20,'Hide n Sneak allows damage');
fixture('me05-005');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetZoneObject('p2Active-0')->Damage===10 && GetTurnPlayer()===2,'Furtive Drop places one counter and ends turn');
fixture('me05-006');for($i=0;$i<6;++$i)PokeAdd(1,'Discard','me05-005');$bench=PokeAdd(2,'Bench','me05-039');$bench->Controller=2;
PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetZoneObject('p2Active-0')->Damage===40&&GetZoneObject('p2Bench-0')->Damage===40,'Matcha Spin spreads counters');
fixture('me05-039','me05-005');for($i=0;$i<4;++$i)PokeAdd(1,'Discard','me05-006');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetWinner()===1,'Vengeful Anchor gains 140 damage and wins for no Pokémon');
fixture('me05-034');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(GetTurnPlayer()===1,'Puppet Pull waits for search before ending turn');answer(choice('mee-005'));check(PokeCount(1,'Hand')===1&&GetTurnPlayer()===2&&GetZoneObject('p2Active-0')->Damage===80,'Puppet Pull resolves across snapshot');
check(!array_filter(PokeObservation(2)['log'],fn($entry)=>$entry['event']==='reveal-search'),'Puppet Pull does not reveal its unrestricted search');
fixture('me05-034');PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);answer('PASS');check(GetTurnPlayer()===2,'Optional PASS normalizes to a resumable skip');
foreach (['me02.5-207'=>'sv08-164','me02.5-209'=>'me02.5-207','me03-072'=>'mee-005','me03-081'=>'me05-006','sv08-165'=>'me02.5-183'] as $trainer=>$target) {
    fixture(); if($trainer==='sv08-165') { SetFirstPlayer(2);AddPlayerTurns(1,1); }
    PokeAdd(1,'Deck',$target); play($trainer); answer(choice($target));check(PokeCount(1,'Hand')===1,"$trainer search resolves");
}
fixture();AddPlayerTurns(1,2);$bell=PokeAdd(1,'Hand','sv08-165');check(!PokeCanPlayTrainer(1,'p1Hand-0'),'Call Bell only works on second player first turn');
fixture();PokeAdd(1,'Hand','me05-005');PokeAdd(1,'Hand','me05-006');PokeAdd(1,'Deck','me05-039');play('me02.5-213');
check(PokeDecisionOptions(1)['min']===2,'Ultra Ball requires two discards');$cs=array_column(PokeDecisionOptions(1)['choices'],'value');answer(implode('&',$cs));answer(choice('me05-039'));check(PokeCountHideSneak(1)===2&&PokeCount(1,'Hand')===1,'Ultra Ball pays cost and searches across two awaits');
fixture();PokeAdd(1,'Hand','me02.5-213');PokeAdd(1,'Hand','me05-005');PokeAdd(1,'Hand','me05-006');PokeApplyAction(['type'=>'trainer','player'=>1,'source'=>'p1Hand-0']);$cs=array_column(PokeDecisionOptions(1)['choices'],'value');answer(implode('&',$cs));answer('-');check(PokeCountHideSneak(1)===2&&PokeCount(1,'Hand')===0,'Pending indices survive a removed card before candidates and optional skip resumes');
fixture();for($i=0;$i<5;++$i)PokeAdd(1,'Deck','me05-006');play('sv08-164');$cs=array_filter(PokeDecisionOptions(1)['choices'],fn($c)=>$c['card']==='me05-006');answer(implode('&',array_column($cs,'value')));check(PokeCountHideSneak(1)===5,'Brilliant Blender discards five');
fixture();PokeAdd(1,'Discard','me05-006');play('me02.5-196');answer(choice('me05-006'));check(PokeCount(1,'Hand')===1,'Night Stretcher recovers Pokémon');
fixture();PokeAdd(1,'Discard','mee-005');PokeAdd(1,'Discard','mee-005');play('sv10.5w-082');answer(implode('&',array_column(PokeDecisionOptions(1)['choices'],'value')));check(PokeCount(1,'Hand')===2,'Energy Retrieval recovers two basics');
fixture();$b=PokeAdd(2,'Bench','me05-005');$b->Controller=2;play('me02.5-183');answer(choice('me05-005'));check(GetZoneObject(PokeFirstRef(2,'Active'))->CardID==='me05-005','Boss orders switches opponent');
fixture();PokeAdd(1,'Hand','mee-005');play('me02.5-192');check(PokeCount(1,'Hand')===8,'Lillie draws eight at six prizes');
fixture();$prizes=&GetPrizes(2);$prizes=array_slice($prizes,0,3);PokeAdd(2,'Hand','me05-006');PokeAdd(2,'Hand','me05-005');play('me04-082');check(PokeCount(2,'Hand')===3&&GetZoneObject(PokeFirstRef(2,'Hand'))->CardID==='mee-005','Special Red Card puts hand on bottom before drawing');
fixture();play('sv09-144');check(PokeVar('blackBelt:1')===GetTurnNumber(),'Black Belt turn effect saved');
fixture('me05-039','sv01-086');PokeBlackBelt(1);PokeDealAttackDamage(1,'p1Active-0',30);check(GetZoneObject('p2Active-0')->Damage===70,"Black Belt adds 40 against Pokémon ex");
fixture();$d=&GetDeck(1);$d=[];PokeAdd(1,'Deck','me02.5-207');for($i=0;$i<10;++$i)PokeAdd(1,'Deck','mee-005');play('sv10.5b-084');check(PokeCount(1,'TempZone')===7,'Pokégear inspects top seven');answer(choice('me02.5-207'));check(PokeCount(1,'Hand')===1&&PokeCount(1,'Deck')===10&&PokeCount(1,'TempZone')===0,'Pokégear restores unchosen cards');
fixture();PokeAdd(1,'Deck','me05-039');PokeAdd(1,'Deck','me05-039');$e=PokeAdd(1,'Hand','me03-088');PokeApplyAction(['type'=>'attach','player'=>1,'source'=>'p1Hand-0','target'=>'p1Active-0']);$cs=array_filter(PokeDecisionOptions(1)['choices'],fn($c)=>$c['card']==='me05-039');answer(implode('&',array_column($cs,'value')));check(PokeCount(1,'Bench')===2&&GetZoneObject('p1Active-0')->Energy===['mee-005','me03-088'],'Telepathic attaches and benches two');
fixture('me05-005');$ev=PokeAdd(1,'Hand','me05-006');GetZoneObject('p1Active-0')->Damage=10;check(PokeCanEvolve(1,$ev,GetZoneObject('p1Active-0')),'Evolution is legal later turn');PokeApplyAction(['type'=>'evolve','player'=>1,'source'=>'p1Hand-0','target'=>'p1Active-0']);check(GetZoneObject('p1Active-0')->Damage===10&&GetZoneObject('p1Active-0')->Evolutions===['me05-005'],'Evolution preserves damage and lineage');
fixture();$b=PokeAdd(1,'Bench','me05-005');$b->Controller=1;GetZoneObject('p1Active-0')->Energy=['mee-005','mee-005','mee-005'];GetZoneObject('p1Active-0')->Conditions=['Poisoned'=>10];PokeApplyAction(['type'=>'retreat','player'=>1,'target'=>'p1Bench-0','payment'=>[0,1,2]]);check(PokeCount(1,'Discard')===3&&GetZoneObject(PokeFirstRef(1,'Active'))->CardID==='me05-005'&&!GetZoneObject(PokeFirstRef(1,'Bench'))->Conditions,'Retreat pays and clears conditions');
fixture();$b=PokeAdd(2,'Bench','me05-039');$b->Controller=2;GetZoneObject('p2Active-0')->Damage=130;PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);check(PokeDecisionOptions(1)['type']==='MZMULTICHOOSE','KO queues Prize claim');answer(PokeDecisionOptions(1)['choices'][0]['value']);answer(PokeDecisionOptions(2)['choices'][0]['value'],2);check(PokeCount(1,'Prizes')===5&&PokeCount(2,'Active')===1&&GetTurnPlayer()===2,'KO prizes and promotion finish before next turn');
fixture();$d=&GetDeck(2);$d=[];PokeApplyAction(['type'=>'end','player'=>1]);check(GetWinner()===1,'Mandatory turn draw loses on empty deck');
PokeCreateGame($deck,$deck,42,1);$a=PokeStateExport();PokeCreateGame($deck,$deck,42,1);check(PokeStateExport()===$a,'Seeded reset is deterministic');
echo "PASS $checks checks\n";
