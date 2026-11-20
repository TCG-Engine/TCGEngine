<?php
/** Observe the first half of the search confirmation seeds; no policy changes. */
require __DIR__.'/ceruledge-deck-study.php';
$out=['seedRange'=>[160001,160050],'policy'=>PokeSimulationPolicy(),'note'=>'Turn snapshots condition on reaching the turn; sample overlaps search confirmation. Search uses span the whole game.','variants'=>[]];
foreach(['baseline','boss-to-pad'] as $variant){
    $games=[];$pad=[];$recoveries=[];
    foreach(['brisbane-lopunny','dhelmise-v2'] as $opponent)foreach(range(160001,160050) as $seed)foreach([1,2] as $first){
        $snapshots=[];$pending=null;
        $g=cerStudyGame(cerStudyDeck(cerSearchVariants()[$variant],'search'),$opponent,$seed,$first,function($view,$action)use(&$snapshots,&$pending,&$pad,&$recoveries,$seed,$first,$opponent){
            if($view['viewer']!==2||$view['phase']!=='MAIN')return;
            $ctx=PokeCeruledgeContext($view);$own=$ctx['own'];
            if($action['type']==='trainer'){
                $id=PokeBotCard($ctx,$action['source'])['id'];
                $pending=in_array($id,['me03-081','me02.5-196'],true)?$id:null;
                if($id==='me03-081')$pad[]=['seed'=>$seed,'opponent'=>$opponent,'first'=>$first,'turn'=>$own['turns'],'lines'=>$ctx['lines'],'ex'=>PokeBotFieldCount($ctx,['sv08-036']),'discardChar'=>count(array_filter($own['Discard'],fn($c)=>$c['id']==='me02-019'))];
            }
            if($action['type']==='decision'&&$pending){
                foreach($view['decision']['choices'] as $choice)if($choice['value']===($action['value']??'')){
                    if($pending==='me03-081')$pad[array_key_last($pad)]['chosen']=$choice['card'];
                    else $recoveries[]=['turn'=>$own['turns'],'card'=>$choice['card'],'lines'=>$ctx['lines']];
                }
                $pending=null;
            }
            if(in_array($action['type'],['attack','end'],true)){
                $benchLines=count(array_filter($own['Bench'],fn($c)=>in_array($c['id'],['me02-019','sv08-036'],true)));
                $snapshots[$own['turns']]=['lines'=>$ctx['lines'],'ex'=>PokeBotFieldCount($ctx,['sv08-036']),'benchedEvolutionLine'=>$benchLines>0,'noEx'=>PokeBotFieldCount($ctx,['sv08-036'])===0];
            }
        });
        $games[]=['won'=>$g['won'],'snapshots'=>$snapshots];
    }
    $summary=['games'=>count($games),'wins'=>array_sum(array_column($games,'won')),'padUses'=>count($pad),'padFirstLine'=>count(array_filter($pad,fn($r)=>$r['lines']===0)),
        'padBackup'=>count(array_filter($pad,fn($r)=>$r['lines']>0)),'padTargets'=>array_count_values(array_column($pad,'chosen')),'stretcherTargets'=>array_count_values(array_column($recoveries,'card')),'turns'=>[]];
    foreach(range(2,5) as $turn){
        $rows=[];foreach($games as $g)if(isset($g['snapshots'][$turn]))$rows[]=$g['snapshots'][$turn];
        $summary['turns'][$turn]=['reached'=>count($rows),'benchedEvolutionLine'=>array_sum(array_column($rows,'benchedEvolutionLine')),'noEx'=>array_sum(array_column($rows,'noEx'))];
    }
    $out['variants'][$variant]=['summary'=>$summary,'pad'=>$pad,'stretcherRecoveries'=>$recoveries];
    echo json_encode(['variant'=>$variant]+$summary).PHP_EOL;
}
file_put_contents(__DIR__.'/fixtures/ceruledge-backup-audit.json',json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
