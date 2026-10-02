<?php
/** Standalone bulk runner: php DevTools/PokeSim/simulate.php --games=1000 --seed=1 --output=results.csv */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
try {
    $options=getopt('',['games:','seed:','output:','deck1:','deck2:']);
    $games=filter_var($options['games']??200,FILTER_VALIDATE_INT);
    $seed=filter_var($options['seed']??1,FILTER_VALIDATE_INT);
    if($games===false||$games<2||$games%2!==0||$seed===false||$seed<1||$seed>2147483647-intdiv($games,2)+1)
        throw new InvalidArgumentException('games must be a positive even number; seed range must fit 32 bits');
    $policy=PokeSimulationPolicy();$rows=PokeSimulatePairs($seed,intdiv($games,2),1500,$options['deck1']??'sinistcha',$options['deck2']??'sinistcha');
    if(isset($options['output'])){
        $file=fopen($options['output'],'x');
        if(!$file)throw new RuntimeException('Cannot create output file (existing files are not overwritten)');
        fputcsv($file,['seed','deck1','deck2','firstPlayer','winner','winnerOrder','turns','actions','status','reason','damageTurns','policy']);
        foreach($rows as $row){$row['damageTurns']=json_encode($row['damageTurns'],JSON_THROW_ON_ERROR);fputcsv($file,array_merge(array_values($row),[$policy]));}
        fclose($file);
    }
    echo json_encode(['policy'=>$policy,'startSeed'=>$seed,'summary'=>PokeSimulationSummary($rows)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
    exit(count(array_filter($rows,fn($row)=>$row['status']!=='complete'))?2:0);
} catch(Throwable $error){fwrite(STDERR,$error->getMessage()."\n");exit(1);}
