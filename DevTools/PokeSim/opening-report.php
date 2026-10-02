<?php
/** Compare opening attack rates without running whole matches. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
$options=getopt('',['games:','seed:','trace:','opponent:']);
$games=(int)($options['games']??100);$start=(int)($options['seed']??1);
$opponent=$options['opponent']??'brisbane-lopunny';
if($games<1||$games>10000||$start<1||$start>2147483647-$games+1)throw new InvalidArgumentException('Use 1–10000 games and a positive 32-bit seed range');
$counts=['games'=>0,'dhelmise'=>0,'poweredDhelmise'=>0,'poltchageist'=>0,'other'=>0,'noAttack'=>0];
for($seed=$start;$seed<$start+$games;$seed++){
    PokeCreateGame(PokeNamedDeck('sinistcha'),PokeNamedDeck($opponent),$seed,2);
    $attack=null;$seen=false;$trace=[];
    for($i=0;$i<400&&!GetWinner();$i++){
        if($seen&&GetTurnPlayer()!==1)break;
        $seat=PokePendingPlayer();$view=PokeObservation($seat);$action=PokeBotChoose($view);
        if(!$action)throw new RuntimeException('No opening action');
        if($seat===1){
            if($view['phase']==='MAIN')$seen=true;
            $source=isset($action['source'])?GetZoneObject($action['source'])->CardID:null;
            $target=isset($action['target'])?GetZoneObject($action['target'])->CardID:null;
            $choices=[];if($action['type']==='decision')foreach($view['decision']['choices']??[] as $c)if(in_array($c['value'],explode('&',$action['value']),true))$choices[]=$c['label'];
            $trace[]=['phase'=>$view['phase'],'type'=>$action['type'],'source'=>$source,'target'=>$target,'choices'=>$choices];
            if($action['type']==='attack')$attack=['id'=>$source,'hide'=>PokeCountHideSneak(1)];
        }
        PokeApplyAction($action);
    }
    ++$counts['games'];
    if(!$attack)++$counts['noAttack'];
    elseif($attack['id']==='me05-039'){++$counts['dhelmise'];if($attack['hide']>=4)++$counts['poweredDhelmise'];}
    elseif($attack['id']==='me05-005')++$counts['poltchageist'];else ++$counts['other'];
    if(isset($options['trace'])&&$seed===(int)$options['trace'])echo json_encode(['seed'=>$seed,'trace'=>$trace,'attack'=>$attack],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
}
echo json_encode($counts,JSON_PRETTY_PRINT)."\n";
