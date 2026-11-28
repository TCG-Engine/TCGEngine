<?php
$repo=dirname(__DIR__,4);
require $repo.'/Core/RegressionTestFramework.php';
require __DIR__.'/../final_snapshot_compatibility.php';
$slugs=['card-count-your-blessings-blue-conditional-heal-own-hand-floating-exact','card-aether-arc-blue-four-live-sparse-real-damage-to-all-three-opponents-and-three-owned-ponders'];
$proposal=['policies'=>[]];$checks=0;
foreach($slugs as $slug){$meta=json_decode(file_get_contents($repo.'/Tests/Integration/FaBSim/'.$slug.'/meta.json'),true,512,JSON_THROW_ON_ERROR);$proposal['policies'][]=['slug'=>$slug,'policy'=>$meta['reviewedFinalCompatibility']];}
function expectResult($wanted,$raw,$actual,$policy,$label){[$ok,$why]=FaBRegressionReviewedFinalCompatibility($raw,$actual,$policy);if($ok!==$wanted)throw new RuntimeException($label.': '.$why);++$GLOBALS['checks'];}
function parts($text){$lines=explode("\n",RegressionNormalizeNewlines($text));if(end($lines)==='')array_pop($lines);RegressionConsumeGamestateLayout(RegressionGamestateSchemaLayout('FaBSim'),$lines,$blocks,4);return [$lines,$blocks];}
function editState($text,$edit){[$lines,$blocks]=parts($text);foreach($blocks as $b)if($b['name']==='GameState'){$s=json_decode($lines[$b['start']]);$edit($s);$lines[$b['start']]=json_encode($s,JSON_PRESERVE_ZERO_FRACTION);return implode("\n",$lines)."\n";}throw new RuntimeException('No GameState');}
function editContext($text,$edit){[$lines,$blocks]=parts($text);foreach($blocks as $b)if($b['name']==='DecisionQueueVariables'){$s=json_decode($lines[$b['start']]);$edit($s);$lines[$b['start']]=json_encode($s,JSON_PRESERVE_ZERO_FRACTION);return implode("\n",$lines)."\n";}throw new RuntimeException('No context');}
function editBlock($text,$name,$active=false){[$lines,$blocks]=parts($text);foreach($blocks as $b)if($b['name']===$name){if($active){$lines[$b['start']]='1';array_splice($lines,$b['start']+1,0,['{}']);}else $lines[$b['start']]=(string)(intval($lines[$b['start']])+1);return implode("\n",$lines)."\n";}throw new RuntimeException('No block '.$name);}
foreach($proposal['policies']as$r){
 $slug=$r['slug'];$raw=file_get_contents($repo.'/Tests/Integration/FaBSim/'.$slug.'/expected_final_gamestate.txt');// Synthetic comparison-only permutation; native outcomes are verified by
 // the normal saved regression cases, not fabricated by this component.
 $actual=editState($raw,function($s){$ordered=[];$counts=[];foreach($s->turnEffects->{'1'}as$r){if(in_array($r->type??null,['EVR_GRAVEYARD','SEA_BLUE_GRAVE','ROS_LIFE'],true))$counts[]=$r;else$ordered[]=$r;}$s->turnEffects->{'1'}=array_merge($ordered,array_reverse($counts));});
 $actual=editContext($actual,function($s){$s->mzID='Stack-0';});$p=$r['policy'];
 expectResult(true,$raw,$actual,$p,$slug.' reviewed complete pair');
 expectResult(false,$raw,$actual,[],'No opt-in');$bad=$p;$bad['expected_raw_sha256']=str_repeat('0',64);expectResult(false,$raw,$actual,$bad,'Stale hash');
 expectResult(false,$raw,editBlock($actual,'Health'),$p,'Health corruption');
 expectResult(false,$raw,editBlock($actual,'UniqueIDCounter'),$p,'UID corruption');
 expectResult(false,$raw,editBlock($actual,'Stack',true),$p,'Active layer');
 expectResult(false,$raw,editBlock($actual,'DecisionQueue',true),$p,'Active decision');
 expectResult(false,$raw,editContext($actual,function($s){$s->awaitFrame=['card'=>123];}),$p,'Await frame');
 expectResult(false,$raw,editContext($actual,function($s){$s->cardID='wrong_card';}),$p,'Wrong context card');
 expectResult(false,$raw,editContext($actual,function($s){$s->mzID='p2Hand-0';}),$p,'Unreviewed source ref');
 expectResult(false,$raw,editState($actual,function($s){$s->pendingPayment=(object)['cost'=>1];}),$p,'Pending payment');
 expectResult(false,$raw,editState($actual,function($s){$s->resolvingReaction=(object)['uid'=>3];}),$p,'Pending resolution');
 expectResult(false,$raw,editState($actual,function($s){$s->window='DAMAGE';}),$p,'Nonquiet window');
 expectResult(false,$raw,editState($actual,function($s){foreach($s->turnEffects->{'1'}as$r)if($r->type==='EVR_GRAVEYARD'){++$r->amount;break;}}),$p,'Counter amount');
 expectResult(false,$raw,editState($actual,function($s){foreach($s->turnEffects->{'1'}as$r)if($r->type==='EVR_GRAVEYARD'){$r->uses=2;break;}}),$p,'Counter uses');
 expectResult(false,$raw,editState($actual,function($s){foreach($s->turnEffects->{'1'}as$r)if($r->type==='EVR_GRAVEYARD'){$r->target=9;break;}}),$p,'Counter extra target');
 expectResult(false,$raw,editState($actual,function($s){foreach($s->turnEffects->{'1'}as$i=>$r)if($r->type==='EVR_GRAVEYARD'){array_splice($s->turnEffects->{'1'},$i,1);break;}}),$p,'Missing counter');
 expectResult(false,$raw,editState($actual,function($s){foreach($s->turnEffects->{'1'}as$r)if($r->type==='EVR_GRAVEYARD'){$s->turnEffects->{'1'}[]=clone$r;break;}}),$p,'Duplicate counter');
 expectResult(false,$raw,editState($actual,function($s){$e=&$s->turnEffects->{'1'};[$e[0],$e[1]]=[$e[1],$e[0]];}),$p,'Ordered real effects');
 expectResult(false,$raw,editState($actual,function($s){$s->turnEffects->{'1'}[0]->amount=(float)$s->turnEffects->{'1'}[0]->amount;}),$p,'Nonaccounting numeric type');
 expectResult(false,$raw,$actual."0\n",$p,'Extra schema line');
}
echo json_encode(['status'=>'PASS','checks'=>$checks,'native_games'=>0,'scope'=>'Synthetic full-shape accounting/context contract and fail-closed corruption controls; native cases are tested separately through the runner.'])."\n";
