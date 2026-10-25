<?php
declare(strict_types=1);
// Reproducible public-observation combat smoke test; no forced wins or hidden choices.
require_once __DIR__.'/World.php';
$results=[];
foreach([514,741] as $seed){
    $j=ADNewJourney();$j['flags']=['invited'=>true,'canalRestored'=>true,'gardenWitness'=>true];$rng=$seed+17;$bouts=[];
    foreach(['iddin','imdugud','huwawa','bull-of-heaven'] as $enemy){
        if($enemy==='bull-of-heaven')$j['flags']['witnessed']=true;
        $e=ADEntities()[$enemy];foreach([[0,1],[1,0],[0,-1],[-1,0]] as [$dx,$dy])if(ADWalkable($e['x']+$dx,$e['y']+$dy)){$j['position']=['x'=>$e['x']+$dx,'y'=>$e['y']+$dy];break;}
        ADStartFight($j,$enemy,'Red',$seed+count($bouts));$actions=0;
        while($j['phase']==='fight'&&$actions++<350){$move=DFGBotChoose(DFGObserve($j['battle'],0),0,$rng);$j=ADApply($j,['type'=>'battle','move'=>$move]);}
        if($j['phase']!=='result')throw new LogicException('Bout did not finish within the action limit.');
        $bouts[]=['enemy'=>$enemy,'winner'=>$j['battle']['winner'],'rounds'=>$j['battle']['round'],'actions'=>$actions,'life'=>$j['battle']['players'][0]['life']];
        if($j['phase']!=='result'||!$j['lastResult']['won'])break;
        $j=ADApply($j,['type'=>'continue']);
        // A player may freely equip newly earned moves and gear between bouts.
        $deck=[];foreach(DFG_COLORS as $color){$pool=array_values(array_filter($j['collection'],fn($id)=>DFGCatalog()[$id]['color']===$color));usort($pool,fn($a,$b)=>(DFGCatalog()[$b]['attack']+DFGCatalog()[$b]['block'])<=>(DFGCatalog()[$a]['attack']+DFGCatalog()[$a]['block']));$deck=array_merge($deck,array_slice($pool,0,6));}
        $j=ADApply($j,['type'=>'deck','cards'=>$deck]);foreach($j['inventory'] as $id)$j=ADApply($j,['type'=>'equip','slot'=>ADItems()[$id]['slot'],'item'=>$id]);
    }
    $results[]=['seed'=>$seed,'regionComplete'=>!empty($j['flags']['regionComplete']),'bouts'=>$bouts];
}
echo json_encode($results,JSON_PRETTY_PRINT)."\n";
// A legal loss or draw is valid; the smoke test must exercise every matchup.
if(!array_filter($results,fn($r)=>count($r['bouts'])===4))exit(1);
