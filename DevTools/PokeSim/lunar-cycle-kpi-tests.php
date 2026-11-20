<?php
require __DIR__.'/opening-stats-tests.php';
foreach([1,2] as $first){
 openingFixture($first,'ceruledge-fighting');
 GetZoneObject('p1Active-0')->CardID='me01-074';
 $sol=PokeAdd(1,'Bench','me01-075');$sol->Controller=1;
 for($i=0;$i<12;++$i)PokeAdd(1,'Deck','mee-002');
 PokeAdd(1,'Hand','base1-97');
 PokeOpeningObserve();$m=PokeVar('openingStats')[1]['lunarCycleTurnOne'];
 openingCheck($m['reached']&&$m['available']&&!$m['activated'],'Legal on own turn one in either starting order');
 PokeApplyAction(['type'=>'ability','player'=>1,'source'=>'p1Active-0','index'=>0]);
 PokeOpeningObserve();
 openingCheck(!PokeVar('openingStats')[1]['lunarCycleTurnOne']['activated'],'Unresolved payment does not count');
 $v=PokeObservation(1);PokeApplyAction(['type'=>'decision','player'=>1,'value'=>$v['decision']['choices'][0]['value']]);
 $m=PokeVar('openingStats')[1]['lunarCycleTurnOne'];
 openingCheck($m['activated']&&PokeCount(1,'Deck')===9,'Resolved discard/draw counts exactly on own turn one');
 PokeOpeningFinishTurn();$before=PokeOpeningResults();PokeStateImport(PokeStateExport());
 $completed=PokeVar('openingStats')[1]['lunarCycleTurnOne'];
 openingCheck($before===PokeOpeningResults(),'Lunar metric survives serialization');
 AddPlayerTurns(1,2);SetTurnNumber(3);PokeOpeningObserve();
 openingCheck(PokeVar('openingStats')[1]['lunarCycleTurnOne']===$completed,'Later turns retain first-turn activation');
}
openingFixture(1,'ceruledge-fighting');GetZoneObject('p1Active-0')->CardID='me01-074';
PokeAdd(1,'Hand','base1-97');PokeAdd(1,'Deck','mee-002');PokeOpeningObserve();PokeOpeningFinishTurn();
openingCheck(in_array('solrock_missing',PokeVar('openingStats')[1]['lunarCycleTurnOne']['blockers']),'Missing partner is recorded');
AddPlayerTurns(1,2);SetTurnNumber(3);PokeSetVar('lunarCycle:1',3);PokeOpeningObserve();
openingCheck(!PokeVar('openingStats')[1]['lunarCycleTurnOne']['activated'],'Turn-two use does not become a turn-one success');
$row=PokeOpeningResults()[0];$row['status']='game_ended_before_opportunity';
$success=$row;$success['lunarCycleTurnOne']['activated']=true;$success['lunarCycleTurnOne']['available']=true;$success['lunarCycleTurnOne']['blockers']=[];
$legacy=$row;unset($legacy['lunarCycleTurnOne']);
$games=[['status'=>'complete','winner'=>1,'openingStats'=>[$success]],['status'=>'complete','winner'=>2,'openingStats'=>[$row]],['status'=>'complete','winner'=>1,'openingStats'=>[$legacy]],['status'=>'capped','winner'=>0,'openingStats'=>[$success]]];
$s=PokeOpeningSummary($games)[0]['lunarCycleTurnOne'];
openingCheck($s['tracked']===2&&$s['activated']===1&&$s['reached']===2,'Legacy and capped data excluded; first-turn setup counted before first-player attack opportunity');
openingCheck($s['conversion']['activated']===['games'=>1,'wins'=>1]&&$s['conversion']['missed']===['games'=>1,'wins'=>0],'Win conversion split by actual activation');
openingFixture(2,'ceruledge-fighting');GetZoneObject('p1Active-0')->CardID='me02-019';GetZoneObject('p1Active-0')->Energy=['mee-002'];
foreach(['me01-074','me01-075'] as $id){$c=PokeAdd(1,'Bench',$id);$c->Controller=1;}
for($i=0;$i<10;++$i){PokeAdd(1,'Deck','base1-97');PokeAdd(2,'Deck','mee-002');}
PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
$decision=PokeObservation(1)['decision'];
PokeApplyAction(['type'=>'decision','player'=>1,'value'=>$decision['choices'][0]['value']]);
$metric=PokeVar('openingStats')[1]['lunarCycleTurnOne'];
openingCheck($metric['reached']&&!$metric['available']&&!$metric['activated'],'Gather Strength fuel after attacking is not a usable Lunar window');
file_put_contents(__DIR__.'/fixtures/lunar-cycle-kpi-test-data.json',json_encode($games,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo "PASS $checks combined opening/Lunar KPI checks\n";
