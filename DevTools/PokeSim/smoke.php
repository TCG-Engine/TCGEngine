<?php
/** Small deterministic exerciser, not a deck-strength evaluator. */
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
set_error_handler(function($severity,$message,$file,$line){if(error_reporting() & $severity)throw new ErrorException($message,0,$severity,$file,$line);});
function smokeDecision(int $seat,array $d): string {
    if($d['type']==='NUMBERCHOOSE')return (string)$d['max'];
    $choices=$d['choices'];
    $discard=str_contains($d['prompt'],'discard');
    usort($choices,function($a,$b)use($discard){
        $score=function($c)use($discard){
            $id=$c['card']??'';
            if($discard)return in_array($id,['me05-005','me05-006','me05-034'],true)?0:1;
            return array_search($id,['mee-005','me05-039','me05-005','me05-006','me02.5-192','me02.5-207','sv08-164'],true) ?: 10;
        };return $score($a)<=>$score($b);
    });
    if($d['type']==='MZMULTICHOOSE')return implode('&',array_column(array_slice($choices,0,$d['max']),'value'))?:'-';
    return $choices[0]['value']??'-';
}
function smokeAction(int $seat): array {
    $actions=PokeLegalActions($seat);
    if(!$actions)throw new RuntimeException('No legal action or decision');
    $ranks=['setup-active'=>0,'bench'=>1,'ready'=>2,'evolve'=>3,'trainer'=>4,'attach'=>5,'attack'=>6,'end'=>8,'retreat'=>9];
    usort($actions,function($a,$b)use($ranks){
        $rank=function($a)use($ranks){$rank=$ranks[$a['type']]??10;
            if($a['type']==='setup-active'&&GetZoneObject($a['source'])->CardID==='me05-039')$rank-=1;
            if($a['type']==='attach') { if(!str_contains($a['target'],'Active'))$rank+=2;if(GetZoneObject($a['source'])->CardID==='mee-005')$rank-=.1; }
            return $rank;
        };return $rank($a)<=>$rank($b);
    });return $actions[0];
}
$deck=PokeParseDeckText(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt'));
foreach([1,42,99] as $seed){
    PokeCreateGame($deck,$deck,$seed,1);
    for($steps=0;$steps<1500&&!GetWinner();++$steps){
        PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
        $seat=PokePendingPlayer();$d=PokeDecisionOptions($seat);
        $action=$d?['type'=>'decision','player'=>$seat,'value'=>smokeDecision($seat,$d)]:smokeAction($seat);
        PokeApplyAction($action);
    }
    if(!GetWinner())throw new RuntimeException("Seed $seed did not finish");
    echo "Seed $seed: player ".GetWinner()." won after ".GetTurnNumber()." turns and $steps actions\n";
}
