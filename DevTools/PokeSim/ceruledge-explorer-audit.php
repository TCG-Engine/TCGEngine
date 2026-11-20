<?php
require __DIR__.'/ceruledge-deck-study.php';
$out=['policy'=>PokeSimulationPolicy(),'seedRange'=>[170001,170100],'games'=>[],'explorers'=>[],'supporterChoices'=>[]];
foreach(['brisbane-lopunny','dhelmise-v2'] as $opponent)foreach(range(170001,170100) as $seed)foreach([1,2] as $first){
    $turns=[];
    $g=cerStudyGame(PokeNamedDeck('ceruledge-ex'),$opponent,$seed,$first,function($view,$action)use(&$out,&$turns,$seed,$first,$opponent){
        if($view['viewer']!==2||$view['phase']!=='MAIN')return;
        $ctx=PokeCeruledgeContext($view);
        if(str_contains($view['decision']['prompt']??'',"Explorer's Guidance")){
            $kept=[];$discarded=[];
            foreach($view['decision']['choices'] as $c){if(in_array($c['value'],explode('&',$action['value']),true))$kept[]=$c['card'];else $discarded[]=$c['card'];}
            $out['explorers'][]=['opponent'=>$opponent,'seed'=>$seed,'firstPlayer'=>$first,'turn'=>$ctx['own']['turns'],'hand'=>$ctx['hand'],'field'=>array_column($ctx['field'],'id'),'deck'=>$ctx['own']['deckCount'],'fire'=>$ctx['fire'],'discardEnergy'=>$ctx['discardEnergy'],'kept'=>$kept,'discarded'=>$discarded];
        }
        if($action['type']==='trainer'&&CardTrainerType(PokeBotCard($ctx,$action['source'])['id'])==='Supporter'){
            $out['supporterChoices'][]=['turn'=>$ctx['own']['turns'],'played'=>PokeBotCard($ctx,$action['source'])['id'],'hand'=>$ctx['hand'],'deck'=>$ctx['own']['deckCount']];
        }
        if(in_array($action['type'],['attack','end'],true))$turns[$ctx['own']['turns']]=['deck'=>$ctx['own']['deckCount'],'lines'=>$ctx['lines'],'ex'=>PokeBotFieldCount($ctx,['sv08-036']),'handFire'=>$ctx['fire'],'discard'=>$ctx['discardEnergy']];
    });
    $out['games'][]=['opponent'=>$opponent,'seed'=>$seed,'firstPlayer'=>$first,'won'=>$g['won'],'complete'=>$g['complete'],'turns'=>$turns];
}
$path=$argv[1]??__DIR__.'/fixtures/ceruledge-explorer-audit-before.json';
file_put_contents($path,json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode(['games'=>count($out['games']),'wins'=>array_sum(array_column($out['games'],'won')),'explorerUses'=>count($out['explorers'])]).PHP_EOL;
