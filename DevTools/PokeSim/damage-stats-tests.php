<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function damageCheck($ok,$message){global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
SetCurrentPhase('MAIN');SetTurnPlayer(1);SetFirstPlayer(1);SetTurnNumber(1);AddPlayerTurns(1,1);
$source=PokeAdd(1,'Active','me02-084');$source->Controller=1;
$target=PokeAdd(2,'Active','me02-084');$target->Controller=2;
PokeDamageStartTurn(1);
PokeDealAttackDamage(1,'p1Active-0',30);
PokePlaceDamageCounters('p1Active-0','p2Active-0',4,'Ability');
damageCheck(PokeVar('damageTurns')[0]['damage']===70,'Attack and counters combine into actual damage');
PokePlaceDamageCounters('p1Active-0','p1Active-0',2);
damageCheck(PokeVar('damageTurns')[0]['damage']===70,'Self damage excluded');
$target->Energy=['sv05-161'];
PokePlaceDamageCounters('p1Active-0','p2Active-0',4);
damageCheck(PokeVar('damageTurns')[0]['damage']===70&&$target->Damage===70,'Protected counters contribute no damage');
SetCurrentPhase('CHECKUP');PokeRecordDamage(1,2,20);
damageCheck(PokeVar('damageTurns')[0]['damage']===70,'Checkup excluded');
SetCurrentPhase('MAIN');PokeDamageFinishTurn();SetTurnPlayer(2);SetTurnNumber(2);AddPlayerTurns(2,1);PokeDamageStartTurn(2);PokeDamageFinishTurn();
damageCheck(PokeVar('damageTurns')[1]['damage']===0&&PokeVar('damageTurns')[1]['order']==='second','Finished zero damage turns retained');
$before=PokeVar('damageTurns');PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
damageCheck(PokeVar('damageTurns')===$before,'Tracking survives serialized reload');
foreach([1,2] as $first){
    $game=PokeSimulateGame(42,$first);
    damageCheck($game['status']==='complete'&&count($game['damageTurns'])===$game['turns'],'Every turn, including final winning turn, is tracked');
    foreach($game['damageTurns'] as $row)damageCheck($row['complete']&&$row['order']===($row['player']===$first?'first':'second'),'Finished turn attributed to original starting order');
}
echo "PASS $checks damage stats checks\n";
