<?php
require __DIR__.'/ceruledge-deck-study.php';
$rows=[];$games=0;
foreach(['brisbane-lopunny','dhelmise-v2'] as $opponent)foreach(range(160001,160050) as $seed)foreach([1,2] as $first){
    cerStudyGame(cerStudyDeck(cerSearchVariants()['boss-to-poffin'],'search'),$opponent,$seed,$first,function($view,$action)use(&$rows,$seed,$first,$opponent){
        if($view['viewer']!==2||!str_contains($view['decision']['prompt']??'','Buddy-Buddy Poffin'))return;
        $ctx=PokeCeruledgeContext($view);$selected=[];
        foreach($view['decision']['choices'] as $c)if(in_array($c['value'],explode('&',$action['value']),true))$selected[]=$c['card'];
        $rows[]=['seed'=>$seed,'first'=>$first,'opponent'=>$opponent,'turn'=>$ctx['own']['turns'],'lines'=>$ctx['lines'],
            'fieldRotom'=>PokeBotFieldCount($ctx,['sv07-118']),'handRotom'=>$ctx['hand']['sv07-118']??0,
            'availableChar'=>count(array_filter($view['decision']['choices'],fn($c)=>$c['card']==='me02-019')),'selected'=>$selected];
    });++$games;
}
$summary=['games'=>$games,'uses'=>count($rows),'zero'=>0,'one'=>0,'two'=>0,'char'=>0,'rotom'=>0,'rotomWithRotomAlreadyHeldOrInPlay'=>0,'thirdCharLine'=>0,'rotomInsteadOfAvailableSecondChar'=>0];
foreach($rows as $r){++$summary[['zero','one','two'][count($r['selected'])]];$chars=count(array_filter($r['selected'],fn($id)=>$id==='me02-019'));$rotom=count($r['selected'])-$chars;
    $summary['char']+=$chars;$summary['rotom']+=$rotom;
    if($rotom&&($r['fieldRotom']||$r['handRotom']))++$summary['rotomWithRotomAlreadyHeldOrInPlay'];
    if($r['lines']+$chars>2)++$summary['thirdCharLine'];
    if($rotom&&$r['availableChar']>$chars)++$summary['rotomInsteadOfAvailableSecondChar'];
}
file_put_contents(__DIR__.'/fixtures/ceruledge-poffin-audit.json',json_encode(['policy'=>PokeSimulationPolicy(),'seedRange'=>[160001,160050],'summary'=>$summary,'searches'=>$rows],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode($summary,JSON_PRETTY_PRINT).PHP_EOL;
