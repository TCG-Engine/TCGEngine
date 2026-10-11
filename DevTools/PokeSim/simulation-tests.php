<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function simCheck($condition,$message){global $checks;++$checks;if(!$condition)throw new RuntimeException($message);}
$rows=PokeSimulatePairs(42,3);
simCheck(count($rows)===6,'Three pairs produce six games');
foreach($rows as $index=>$row){
    simCheck($row['seed']===42+intdiv($index,2)&&$row['firstPlayer']===$index%2+1,'Every seed plays both starting orders');
    simCheck($row['status']==='complete'&&$row['actions']>0,'Both bots finish using normal engine decisions');
    simCheck($row['winnerOrder']===($row['winner']===$row['firstPlayer']?'first':'second'),'Winner order reflects the winning seat');
}
simCheck(PokeSimulateGame(42,1)===$rows[0],'Same seed and starting order reproduce the same result');
$capped=PokeSimulateGame(42,1,1);
simCheck($capped['status']==='capped'&&$capped['winner']===0&&$capped['actions']===1,'Action cap is reported without inventing a winner');
$summary=PokeSimulationSummary(array_merge($rows,[$capped]));
simCheck($summary['games']===7&&$summary['completed']===6&&$summary['incomplete']===1,'Incomplete games are counted separately');
simCheck($summary['firstWins']+$summary['secondWins']===6&&$summary['firstWinRate']+$summary['secondWinRate']===1.0,'Win-rate denominator excludes incomplete games');
simCheck($summary['seat1FirstGames']===3&&$summary['seat1SecondGames']===3,'Seat 1 comparison is balanced across starting order');
simCheck(PokeSimulationSummary([])['averageTurns']===null,'Empty summaries do not divide by zero');
foreach([[0,1],[1,0],[2147483647,2]] as [$seed,$pairs]){
    try{PokeSimulatePairs($seed,$pairs);throw new RuntimeException('Accepted invalid parameters');}
    catch(InvalidArgumentException $e){++$checks;}
}
echo "PASS $checks simulation checks\n";
