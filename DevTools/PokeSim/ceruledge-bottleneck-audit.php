<?php
/** Observe preparation ending, without changing decisions or hidden information. */
require_once __DIR__.'/ceruledge-deck-study.php';
function cerBottleneckSnapshot(array $view,array $action): array {
    $ctx=PokeCeruledgeContext($view);$own=$ctx['own'];$attached=0;$poweredEx=0;$ex=0;$char=0;$freshChar=0;
    foreach($ctx['field'] as $card){
        $attached+=count($card['energy']);
        if($card['id']==='sv08-036'){++$ex;if(PokeCeruledgeCanPay($ctx,$card,0))++$poweredEx;}
        if($card['id']==='me02-019'){++$char;if(GetZoneObject($card['ref'])->EnteredTurn>=GetTurnNumber())++$freshChar;}
    }
    $legalEx=(bool)array_filter($view['actions'],fn($a)=>$a['type']==='attack'&&(PokeBotCard($ctx,$a['source'])['id']??'')==='sv08-036');
    $supporter=null;foreach($view['log'] as $event)if(($event['event']??'')==='play-trainer'&&($event['player']??0)===2&&$event['turn']===$view['turn']&&CardTrainerType($event['card'])==='Supporter')$supporter=$event['card'];
    $outlets=[];foreach($view['actions'] as $a){
        $id=isset($a['source'])?(PokeBotCard($ctx,$a['source'])['id']??null):null;
        if($a['type']==='trainer'&&in_array($id,['sv06-145','sv08.5-107','me01-131','sv08-164'],true)||$a['type']==='ability'&&$id==='me04-080')$outlets[]=$id;
    }
    $active=$own['Active'][0]??null;
    $winning=$action['type']==='attack'&&$active&&PokeCeruledgeAttackValue($ctx,$active,null,true,$action['index'])>=100000;
    $discard=$ctx['discardEnergy'];$fire=$ctx['fire'];$exposed=$discard+$fire+$attached;
    $prismUsed=PokeVar('prismTower:2',-1)===GetTurnNumber();
    return ['turn'=>$own['turns'],'action'=>$action['type'],'discard'=>$discard,'handFire'=>$fire,'attached'=>$attached,'exposed'=>$exposed,
        'ex'=>$ex,'poweredEx'=>$poweredEx,'char'=>$char,'freshChar'=>$freshChar,'handEx'=>$ctx['hand']['sv08-036']??0,'legalExAttack'=>$legalEx,
        'supporter'=>$supporter,'supporterUsed'=>$own['supporterUsed'],'prismUsed'=>$prismUsed,'legalOutlets'=>$outlets,'winningAttack'=>$winning,
        'ready330'=>$discard>=15&&$legalEx,
        'blockers'=>[
            'shortDiscard'=>$discard<15,
            'insufficientExposedMass'=>$discard<15&&$exposed<16,
            'enoughHandFuel'=>$discard<15&&$discard+$fire>=15,
            'handBacklog3Plus'=>$discard<15&&$fire>=3,
            'noHandFire'=>$discard<15&&$fire===0,
            'noEx'=>$ex===0,
            'noEvolutionInHand'=>$ex===0&&$char>0&&($ctx['hand']['sv08-036']??0)===0,
            'evolutionTiming'=>$ex===0&&$char>0&&$char===$freshChar&&($ctx['hand']['sv08-036']??0)>0,
            'exButNoPoweredEx'=>$ex>0&&$poweredEx===0,
            'poweredExNotAttacking'=>$poweredEx>0&&!$legalEx,
            'blockedCarmine'=>$discard<15&&$fire>=1&&$own['supporterUsed']&&($ctx['hand']['sv06-145']??0)>0,
            'blockedExplorer'=>$discard<15&&$own['supporterUsed']&&($ctx['hand']['sv08.5-107']??0)>0,
            'prismUsedWithFuel'=>$discard<15&&$fire>=1&&$prismUsed,
            'handFuelNoLegalOutlet'=>$discard<15&&$fire>=1&&!$outlets,
        ]];
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__)return;
$opts=getopt('', ['pairs:','seed:','out:']);$pairs=(int)($opts['pairs']??50);$seed=(int)($opts['seed']??110001);
if($pairs<1||$pairs>1000||$seed<1)throw new InvalidArgumentException('Invalid audit range');
$out=['policy'=>PokeSimulationPolicy(),'seeds'=>[$seed,$seed+$pairs-1],'pairs'=>$pairs,
    'notes'=>['Snapshots are immediately before the bot attacks or ends its own turn, after its preparation.','Blocker percentages overlap and condition on reaching that turn.','Energy mass counts discard plus hand Fire plus attached Energy. 16 is an optimistic lower bound for 15 discarded plus one attached Fire.','Diagnostics do not change decisions. No cards are moved or revealed by the audit.'], 'variants'=>[]];
foreach(['baseline','fire-to-petrel'] as $variant){
    $deck=cerStudyDeck(cerRefineVariants()[$variant],'refine');$games=[];
    foreach(['brisbane-lopunny','dhelmise-v2'] as $opponent)foreach(range($seed,$seed+$pairs-1) as $s)foreach([1,2] as $first){
        $snapshots=[];$preparation=[];
        $result=cerStudyGame($deck,$opponent,$s,$first,function($view,$action)use(&$snapshots,&$preparation){
            if($view['viewer']!==2||$view['phase']!=='MAIN'||$view['turnPlayer']!==2||$view['players'][2]['turns']>5)return;
            $turn=$view['players'][2]['turns'];$ctx=PokeCeruledgeContext($view);
            $preparation[$turn][]= ['action'=>$action,'card'=>isset($action['source'])?(PokeBotCard($ctx,$action['source'])['id']??null):null,'discard'=>$ctx['discardEnergy'],'handFire'=>$ctx['fire'],'hand'=>$ctx['hand'],'prompt'=>$view['decision']['prompt']??null];
            if(in_array($action['type'],['attack','end'],true))$snapshots[$turn]=cerBottleneckSnapshot($view,$action);
        });
        $games[]=['seed'=>$s,'firstPlayer'=>$first,'opponent'=>$opponent,'complete'=>$result['complete'],'won'=>$result['won'],'snapshots'=>$snapshots,'preparation'=>$preparation];
    }
    $summary=['games'=>count($games),'completed'=>count(array_filter($games,fn($g)=>$g['complete'])),'turns'=>[]];
    foreach(range(2,5) as $turn){
        $rows=[];foreach($games as $g)if(isset($g['snapshots'][$turn]))$rows[]=$g['snapshots'][$turn];$n=count($rows);if(!$n)continue;
        $data=['reached'=>$n,'ready330'=>count(array_filter($rows,fn($r)=>$r['ready330'])),'winningBelow330'=>count(array_filter($rows,fn($r)=>$r['winningAttack']&&!$r['ready330'])),
            'avgDiscard'=>round(array_sum(array_column($rows,'discard'))/$n,2),'avgHandFire'=>round(array_sum(array_column($rows,'handFire'))/$n,2),'avgAttached'=>round(array_sum(array_column($rows,'attached'))/$n,2),'avgExposed'=>round(array_sum(array_column($rows,'exposed'))/$n,2),'blockers'=>[],'supporters'=>[]];
        foreach($rows as $row){foreach($row['blockers'] as $key=>$flag)$data['blockers'][$key]=($data['blockers'][$key]??0)+(int)$flag;$name=$row['supporter']??'none';$data['supporters'][$name]=($data['supporters'][$name]??0)+1;}
        $summary['turns'][$turn]=$data;
    }
    $out['variants'][$variant]=['summary'=>$summary,'games'=>$games];
    if(isset($opts['out']))file_put_contents($opts['out'],json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
    echo json_encode(['variant'=>$variant]+$summary,JSON_PRETTY_PRINT)."\n";
}
