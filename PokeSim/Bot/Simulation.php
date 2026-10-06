<?php
/** Both seats use their own observations and the same authoritative action gate
 *  as local play. This runner never imports or saves a user's session game.
 */
function PokeSimulationPolicy(): string {
    $colressFiles=[__DIR__.'/RelicanthColressBot.php',__DIR__.'/../Decks/relicanth-v4-colress.txt',__DIR__.'/../Decks/relicanth-v5-bastiodon.txt',__DIR__.'/../Decks/relicanth-v6-explorers-guidance.txt',__DIR__.'/../Decks/relicanth-v7-lanas-aid.txt'];
    $fossilFiles=[__DIR__.'/RelicanthBot.php',__DIR__.'/RelicanthDrawBot.php',__DIR__.'/RelicanthMetaTuneBot.php',__DIR__.'/../Custom/FossilLogic.php',__DIR__.'/../Decks/relicanth-fossils.txt',__DIR__.'/../Decks/relicanth-v2-draw.txt',__DIR__.'/../Decks/relicanth-v3-meta-tune.txt'];
    $files=[__DIR__.'/HeuristicBot.php',__DIR__.'/PrizeLogic.php',__DIR__.'/LopunnyBot.php',__DIR__.'/../Decks/Registry.php',__DIR__.'/../Decks/brisbane-lopunny.txt',__DIR__.'/../Custom/AttachmentLogic.php',__DIR__.'/../Decks/sinistcha.txt',__DIR__.'/../Decks/dhelmise-v2.txt',__DIR__.'/../Custom/GameLogic.php',
        __DIR__.'/../Custom/CombatLogic.php',__DIR__.'/../Custom/DamageStats.php',__DIR__.'/../Custom/OpeningStats.php',__DIR__.'/../Custom/OpeningProfiles.php',__FILE__,__DIR__.'/../Runtime.php',__DIR__.'/../GeneratedCode/GeneratedCardDictionaries.php',__DIR__.'/../Custom/CardLogic.php',__DIR__.'/../GeneratedCode/GeneratedMacroCode.php'];
    return substr(hash('sha256',implode('',array_map('file_get_contents',array_merge($files,$fossilFiles,$colressFiles)))),0,16);
}
function PokeSimulateGame(int $seed,int $firstPlayer,int $maxActions=1500,string $deckKey1='sinistcha',string $deckKey2='sinistcha',?array $deckOverride1=null,?array $deckOverride2=null,?callable $observer=null): array {
    if($seed<1||$seed>2147483647||!in_array($firstPlayer,[1,2],true)||$maxActions<1)
        throw new InvalidArgumentException('Invalid simulation parameters');
    $deck1=$deckOverride1 ?? PokeNamedDeck($deckKey1);$deck2=$deckOverride2 ?? PokeNamedDeck($deckKey2);
    $steps=0;$result=['seed'=>$seed,'deck1'=>$deckKey1,'deck2'=>$deckKey2,'firstPlayer'=>$firstPlayer,'winner'=>0,'winnerOrder'=>null,
        'turns'=>0,'actions'=>0,'status'=>'capped','reason'=>'Action limit reached'];
    try {
        PokeCreateGame($deck1,$deck2,$seed,$firstPlayer);
        while(!GetWinner()&&$steps<$maxActions){
            $seat=PokePendingPlayer();
            $action=PokeBotChoose(PokeObservation($seat));
            if($action===null)throw new RuntimeException('No bot action for pending player '.$seat);
            PokeApplyAction($action);++$steps;
            // Diagnostics observe accepted actions; they never choose or alter a move.
            if($observer!==null)$observer($action);
        }
        $result['winner']=GetWinner();$result['turns']=GetTurnNumber();
        if($result['winner']){
            $result['status']='complete';
            // Starting order refers to the original match, including matches
            // that later require the engine's sudden-death tiebreaker.
            $result['winnerOrder']=$result['winner']===$firstPlayer?'first':'second';
            foreach(array_reverse(PokeVar('log',[])) as $event)if($event['event']==='game-over'){
                $result['reason']=$event['reason'];break;
            }
        }
    } catch(Throwable $error){
        $result['status']='error';$result['reason']=$error->getMessage();
    }
    $result['actions']=$steps;
    $result['damageTurns']=PokeVar('damageTurns', []);
    $result['openingStats']=PokeOpeningResults();
    return $result;
}
function PokeSimulatePairs(int $startSeed,int $pairs,int $maxActions=1500,string $deckKey1='sinistcha',string $deckKey2='sinistcha'): array {
    if($pairs<1||$startSeed<1||$startSeed>2147483647-$pairs+1)throw new InvalidArgumentException('Invalid seed range');
    $rows=[];
    for($i=0;$i<$pairs;++$i)foreach([1,2] as $first)$rows[]=PokeSimulateGame($startSeed+$i,$first,$maxActions,$deckKey1,$deckKey2);
    return $rows;
}
function PokeSimulationSummary(array $rows): array {
    $out=['games'=>count($rows),'completed'=>0,'firstWins'=>0,'secondWins'=>0,'incomplete'=>0,
        'seat1FirstGames'=>0,'seat1FirstWins'=>0,'seat1SecondGames'=>0,'seat1SecondWins'=>0,
        'averageTurns'=>null,'firstWinRate'=>null,'secondWinRate'=>null];
    $turns=0;
    foreach($rows as $row){
        if($row['status']!=='complete'){++$out['incomplete'];continue;}
        ++$out['completed'];++$out[$row['winnerOrder']==='first'?'firstWins':'secondWins'];$turns+=$row['turns'];
        $key=$row['firstPlayer']===1?'seat1First':'seat1Second';++$out[$key.'Games'];
        if($row['winner']===1)++$out[$key.'Wins'];
    }
    if($out['completed']){
        $out['averageTurns']=$turns/$out['completed'];
        $out['firstWinRate']=$out['firstWins']/$out['completed'];
        $out['secondWinRate']=$out['secondWins']/$out['completed'];
    }
    $out['openings']=PokeOpeningSummary($rows);
    return $out;
}
