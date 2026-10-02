<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function openingCheck($ok,$message){global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function openingFixture(int $first=2, string $deck='sinistcha'): void {
    InitializeGamestate(); $GLOBALS['playerID']=1; $GLOBALS['currentPlayer']=1;
    SetFirstPlayer($first); SetTurnPlayer(1); SetCurrentPhase('MAIN'); SetTurnNumber(1); AddPlayerTurns(1,1);
    PokeSetVar('deckKey:1',$deck); PokeSetVar('deckKey:2','custom'); PokeOpeningInit();
    PokeDamageStartTurn(1);
    foreach([1,2] as $seat){$obj=PokeAdd($seat,'Active','me05-005');$obj->Controller=$seat;$obj->Energy=['mee-005'];}
}
openingFixture();
$attacker=PokeAdd(1,'Bench','me05-039'); $attacker->Controller=1;
PokeOpeningObserve();
$snapshot=PokeVar('openingStats')[1]['snapshot'];
openingCheck(in_array('energy_shortfall',$snapshot['blockers'])&&in_array('goal_prerequisite_unmet',$snapshot['blockers']),'Simultaneous Energy and setup blockers');
$attacker->Energy=['mee-005']; PokeOpeningObserve();
openingCheck(in_array('access_to_active',PokeVar('openingStats')[1]['snapshot']['blockers']),'Powered Benched attacker is distinguished');
PokeOpeningAction(['type'=>'end','player'=>1]); PokeOpeningFinishTurn();
$row=PokeOpeningResults()[0];
openingCheck($row['reached']&&!$row['attackDeclared']&&$row['anyLegalSeen'],'Legal fallback available during failed goal');
openingCheck(in_array('legal_attack_unused',$row['blockers']),'Passing a legal attack is tracked');
$before=PokeOpeningResults(); PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
openingCheck(PokeOpeningResults()===$before,'Diagnostics and traces survive serialization');
openingCheck(!array_key_exists('openingStats',PokeObservation(2)),'Private traces never enter opponent observations');
openingFixture(1); PokeOpeningObserve(); PokeOpeningFinishTurn();
openingCheck(PokeVar('openingStats')[1]['status']==='pending'&&!PokeVar('openingStats')[1]['reached'],'First player turn one is not an eligible opportunity');
AddPlayerTurns(1,2); SetTurnNumber(3); PokeOpeningObserve(); PokeOpeningFinishTurn();
openingCheck(PokeOpeningResults()[0]['status']==='complete'&&PokeOpeningResults()[0]['eligibleTurn']===2,'First player own second turn measured');
openingFixture(1); PokeWin(2,'test');
openingCheck(PokeOpeningResults()[0]['status']==='game_ended_before_opportunity','Early game end kept separate from missed reached turn');
openingFixture(2,'custom'); PokeOpeningObserve();
PokeOpeningAction(['type'=>'attack','source'=>'p1Active-0','index'=>0,'player'=>1]);
PokeOpeningResolved(1); PokeRecordDamage(1,2,10); PokeOpeningFinishTurn();
$row=PokeOpeningResults()[0];
openingCheck($row['fullyEnabledAttack']&&$row['attackResolved']&&$row['damage']===10,'Custom deck defaults to any attack with explicit resolution and damage');
openingFixture(); $active=GetZoneObject('p1Active-0');$active->CardID='me05-039';$active->Energy=['mee-005'];$active->Conditions=['Asleep'=>true];
PokeOpeningObserve();
openingCheck(in_array('special_condition',PokeVar('openingStats')[1]['snapshot']['blockers']),'Conditions block powered Active');
openingFixture(2,'custom'); PokeOpeningObserve(); PokeOpeningAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]); PokeOpeningFinishTurn();
openingCheck(!PokeOpeningResults()[0]['attackResolved']&&in_array('attack_failed_to_resolve',PokeOpeningResults()[0]['blockers']),'Declaration is separate from resolution');
openingFixture(); $active=GetZoneObject('p1Active-0'); $active->CardID='me05-039';
for($i=0;$i<4;++$i)PokeAdd(1,'Discard','me05-006');
for($i=0;$i<6;++$i)PokeAdd(1,'Prizes','mee-005');
PokeApplyAction(['type'=>'attack','player'=>1,'source'=>'p1Active-0','index'=>0]);
$row=PokeOpeningResults()[0];
openingCheck(GetWinner()===1&&$row['fullyEnabledAttack']&&$row['attackResolved']&&$row['damage']>0,'Winning attack resolves before final opening capture');
openingFixture(2,'brisbane-lopunny');$active=GetZoneObject('p1Active-0');$active->CardID='30th-066';
$bench=PokeAdd(1,'Bench','me02-084');$bench->Controller=1;
PokeOpeningObserve();$snapshot=PokeVar('openingStats')[1]['snapshot'];
openingCheck($snapshot['goalLegal']&&!in_array('access_to_active',$snapshot['blockers']),'Copied goal uses the Active copier Energy and access');
openingFixture();$before=PokeVar('openingStats');
try{PokeApplyAction(['type'=>'attack','player'=>2,'source'=>'p2Active-0','index'=>0]);throw new RuntimeException('Illegal action accepted');}catch(InvalidArgumentException $e){}
openingCheck(PokeVar('openingStats')===$before,'Invalid actions do not mutate telemetry');
openingFixture();PokeSetVar('decks',[PokeNamedDeck('sinistcha'),PokeNamedDeck('sinistcha')]);
PokeOpeningObserve();
foreach([1,2] as $seat)GetZoneObject(PokeFirstRef($seat,'Active'))->Damage=(int)CardHp('me05-005');
PokeResolveKnockouts();$before=PokeOpeningResults();
openingCheck(GetCurrentPhase()==='SETUP'&&$before[0]['status']==='complete'&&$before[1]['status']==='game_ended_before_opportunity','Sudden death closes original opportunities before restart');
openingCheck($before[0]['order']==='second'&&$before[1]['order']==='first','Original opening order preserved across sudden death');
$rows=PokeSimulatePairs(42,2,1500,'sinistcha','brisbane-lopunny');
foreach($rows as $game){
    openingCheck($game['status']==='complete'&&count($game['openingStats'])===2,'Both decks produce opening results');
    foreach($game['openingStats'] as $row){
        openingCheck($row['eligibleTurn']===($row['player']===$game['firstPlayer']?2:1),'Both seats use correct eligible turn');
        openingCheck(in_array($row['status'],['complete','game_ended_before_opportunity'],true),'Finished games have terminal opening outcomes');
        openingCheck(!$row['attackResolved']||$row['attackDeclared'],'Resolution requires a declaration');
    }
}
$capped=PokeSimulateGame(42,1,1);
$summary=PokeOpeningSummary(array_merge($rows,[$capped], [['status'=>'complete','winner'=>1]]));
openingCheck(array_sum(array_column($summary,'incomplete'))===2,'Capped games excluded for both seats; older results omitted');
openingCheck(array_sum(array_column($summary,'games'))===8,'Only completed tracked games enter denominators');
openingCheck(PokeSimulateGame(42,1,1500,'sinistcha','brisbane-lopunny')===$rows[0],'Replaying a seed reproduces telemetry');
echo "PASS $checks opening stats checks\n";
