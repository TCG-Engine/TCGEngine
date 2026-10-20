<?php
/** Pure extracted-function contracts; no engine includes, games, or filesystem mutations. */
declare(strict_types=1);
function ExtractFunction(string $path,string $name):string {
 $tokens=token_get_all(file_get_contents($path));
 for($i=0;$i<count($tokens);++$i){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;
  if(!is_array($tokens[$j])||$tokens[$j][1]!==$name)continue;
  $text='';$depth=0;$opened=false;
  for(;$i<count($tokens);++$i){
   $t=$tokens[$i];$text.=is_array($t)?$t[1]:$t;
   if($t==='{'||(is_array($t)&&in_array($t[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES],true))){++$depth;$opened=true;}
   elseif($t==='}'&&--$depth===0&&$opened)return $text;
  }
 }
 throw new RuntimeException('Function not found '.$name);
}
$repo=__DIR__;while(!is_file($repo.'/Core/RegressionTestFramework.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
$core=$repo.'/Core/RegressionTestFramework.php';
foreach(['RegressionStateFieldInput','RegressionJsonValuesEqual','RegressionEvaluateStateField','RegressionBuildAssertionFromInput','RegressionEvaluateAssertion']as$name)eval(ExtractFunction($core,$name));
$checks=0;
function Expect(bool $condition,string $label):void {global$checks;if(!$condition)throw new RuntimeException($label);++$checks;}
function StateAssertion(array $path,string $json='null',string $comparison='equals'):array {
 $a=['type'=>'game_state_field','viewerPlayerID'=>1,'rootName'=>'FaBSim','path'=>$path,'comparison'=>$comparison];
 if($comparison==='equals')$a['valueJson']=$json;return$a;
}
function Check(array $a,bool $want,string $label):void {[$ok,$message]=RegressionEvaluateAssertion($a);Expect($ok===$want,$label.': '.$message);}
Check(StateAssertion(['pendingPayment']),false,'Missing hook cannot pass expected null');
Check(StateAssertion(['resolvingReaction'],'null','missing_or_null'),false,'Missing hook cannot pass missing-or-null');
eval('function GameRegressionState(string $rootName): ?string {$GLOBALS["hook_calls"]++;if($rootName!=="FaBSim")return null;if(!empty($GLOBALS["throw_hook"]))throw new RuntimeException("stub error");return $GLOBALS["snapshot_json"];}');
$GLOBALS['hook_calls']=0;$GLOBALS['snapshot_json']='{"window":"ACTION","pendingPayment":null,"arcaneDealt":{"1":{"2":4},"2":{"1":0}},"false":false,"zero":0,"float":1.0,"list":[],"object":{},"ordered":[1,2],"nested":{"a":1,"b":{"c":true}}}';
$snapshotBefore=$GLOBALS['snapshot_json'];
Check(StateAssertion(['pendingPayment']),true,'Present null equals null');
Check(StateAssertion(['absent']),false,'Missing does not equal null');
Check(StateAssertion(['absent'],'null','missing_or_null'),true,'Explicit absent policy');
Check(StateAssertion(['pendingPayment'],'null','missing_or_null'),true,'Explicit null policy');
foreach(['false','zero','list','object','window']as$key)Check(StateAssertion([$key],'null','missing_or_null'),false,'Non-null '.$key.' cannot pass cleared marker');
foreach([['false','false'],['zero','0'],['float','1.0'],['list','[]'],['object','{}'],['window','"ACTION"']]as[$key,$json])Check(StateAssertion([$key],$json),true,'Exact typed '.$key);
foreach([['false','0'],['zero','"0"'],['float','1'],['list','{}'],['object','[]'],['window','"PRIORITY"']]as[$key,$json])Check(StateAssertion([$key],$json),false,'Wrong typed/value '.$key);
Check(StateAssertion(['arcaneDealt','1','2'],'4'),true,'Original controller-to-target attribution');
Check(StateAssertion(['arcaneDealt',1,2],'4'),true,'Numeric object keys');
Check(StateAssertion(['arcaneDealt','2','1'],'4'),false,'Defender misattribution fails');
Check(StateAssertion(['ordered',0],'1'),true,'Literal list index');
Check(StateAssertion(['ordered','0'],'1'),false,'List requires integer index');
Check(StateAssertion(['ordered',2],'null'),false,'Missing array index');
Check(StateAssertion(['ordered',0,'invalid'],'null'),false,'Cannot descend through scalar');
Check(StateAssertion(['ordered'],'[2,1]'),false,'List order matters');
Check(StateAssertion(['nested'],'{"b":{"c":true},"a":1}'),true,'Object order immaterial');
Check(StateAssertion(['nested'],'{"a":1}'),false,'Object additional member matters');
Check(StateAssertion(['nested'],'{"a":1,"b":{"c":1}}'),false,'Nested boolean type matters');
$unknown=StateAssertion(['pendingPayment']);$unknown['rootName']='UnknownRoot';Check($unknown,false,'Unknown root cannot pass null');
$unknown['comparison']='missing_or_null';unset($unknown['valueJson']);Check($unknown,false,'Unknown root cannot pass missing policy');
foreach(['null','[]','false','"x"','{broken']as$json){$GLOBALS['snapshot_json']=$json;Check(StateAssertion(['pendingPayment']),false,'Invalid/nonobject snapshot '.$json);}
$GLOBALS['snapshot_json']=null;Check(StateAssertion(['pendingPayment']),false,'Unsupported snapshot null');
$GLOBALS['throw_hook']=true;Check(StateAssertion(['pendingPayment']),false,'Hook exception fails');$GLOBALS['throw_hook']=false;$GLOBALS['snapshot_json']=$snapshotBefore;
$invalid=[];
foreach(['rootName','path','valueJson']as$key){$a=StateAssertion(['window'],'"ACTION"');unset($a[$key]);$invalid[]=$a;}
foreach([[],[''],[-1],[false],[1.5],str_repeat('x',2),array_fill(0,33,'x'),['a'=>1]]as$path){$a=StateAssertion(['window']);$a['path']=$path;$invalid[]=$a;}
foreach([null,[],1,false,'{bad','undefined','NaN']as$value){$a=StateAssertion(['window']);$a['valueJson']=$value;$invalid[]=$a;}
foreach(['',null,[],1,'FaBSim/../SWUSim']as$root){$a=StateAssertion(['window']);$a['rootName']=$root;$invalid[]=$a;}
foreach(['',null,'missing',1]as$comparison){$a=StateAssertion(['window']);$a['comparison']=$comparison;$invalid[]=$a;}
$invalid[]=StateAssertion(['pendingPayment'],'null','missing_or_null')+['valueJson'=>'null'];
foreach($invalid as$i=>$a){$before=$GLOBALS['hook_calls'];[$normalized,$error]=RegressionBuildAssertionFromInput(1,$a);Expect($normalized===null&&is_string($error),'Normalizer rejects malformed '.$i);Check($a,false,'Evaluator rejects malformed '.$i);Expect($GLOBALS['hook_calls']===$before,'Malformed rejected before hook '.$i);}
$a=StateAssertion(['nested'],'{"b":{"c":true},"a":1}');[$normalized,$error]=RegressionBuildAssertionFromInput(2,$a+['step'=>7]);
Expect($error===null&&$normalized['viewerPlayerID']===2&&$normalized['step']===7&&$normalized['valueJson']===$a['valueJson'],'Normalization preserves payload/types');
$saved=json_decode(json_encode($normalized,JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);Check($saved,true,'Existing associative saved assertion roundtrip');
$old=['type'=>'scalar_zone_equals','zone'=>'p1Resources','value'=>'3'];[$legacy,$error]=RegressionBuildAssertionFromInput(1,$old);Expect($error===null&&$legacy['value']===3&&$legacy['zone']==='p1Resources','Legacy normalizer unchanged');
Expect($GLOBALS['snapshot_json']===$snapshotBefore,'No state mutation');


// The prior suite used a deliberately adversarial hook. In this separate process,
// verify the exact FaB hook body under a distinct function name to avoid redeclaration.
$hook=ExtractFunction($repo.'/FaBSim/Custom/GameLogic.php','GameRegressionState');
eval(str_replace('function GameRegressionState(', 'function ActualFaBGameRegressionState(', $hook));
function FaBGetState():array{return$GLOBALS['stub_state'];}
$GLOBALS['stub_state']=['window'=>'PRIORITY','pendingPayment'=>null,'arcaneDealt'=>[1=>[2=>4],2=>[1=>0]],'list'=>[],'float'=>1.0];
$before=serialize($GLOBALS['stub_state']);
Expect(ActualFaBGameRegressionState('SWUSim')===null,'Actual hook rejects another root');
$GLOBALS['snapshot_json']=ActualFaBGameRegressionState('FaBSim');
Check(StateAssertion(['arcaneDealt','1','2'],'4'),true,'Actual hook preserves controller-target map');
Check(StateAssertion(['arcaneDealt','2','1'],'4'),false,'Actual hook wrong actor map rejected');
Check(StateAssertion(['float'],'1.0'),true,'Actual hook preserves float');
Expect(serialize($GLOBALS['stub_state'])===$before,'Actual hook read-only');
eval(ExtractFunction($repo.'/DevTools/FaB/CardRegression/record_card_regressions.php','FCRCheck'));
$GLOBALS['recorded']=[];
function FCRAction(int $seat,int $mode,string $card='',string $text=''):void {
 Expect($mode===11002&&$seat===1&&$card==='','Recorder emits assertion only');
 $payload=json_decode($text,true,512,JSON_THROW_ON_ERROR);[$normalized,$error]=RegressionBuildAssertionFromInput($seat,$payload);
 Expect($error===null,'Recorder payload normalizes');$GLOBALS['recorded'][]=$normalized;
}
foreach([['key'=>'window','value'=>'PRIORITY'],['key'=>'pendingPayment','value'=>null],['key'=>'resolvingReaction','value'=>null],['key'=>'arcaneDealt','value'=>[1=>[2=>4],2=>[1=>0]]]]as$check)FCRCheck(['kind'=>'state']+$check);
Expect(count($GLOBALS['recorded'])===4,'Every state diagnostic recorded');
foreach($GLOBALS['recorded']as$a){Expect($a['type']==='game_state_field'&&$a['rootName']==='FaBSim','Native type/root persisted');Check(json_decode(json_encode($a),true),true,'Recorder native roundtrip');}
Expect($GLOBALS['recorded'][0]['valueJson']==='"PRIORITY"','String JSON survives');
Expect($GLOBALS['recorded'][1]['comparison']==='missing_or_null'&&!array_key_exists('valueJson',$GLOBALS['recorded'][1]),'Legacy null policy explicit');
FCRCheck(['kind'=>'state','key'=>'pendingPayment','value'=>null,'comparison'=>'equals']);
Expect(end($GLOBALS['recorded'])['comparison']==='equals'&&end($GLOBALS['recorded'])['valueJson']==='null','Strict explicit null supported');
$caught=false;try{FCRCheck(['kind'=>'state','key'=>'resolvingReaction','value'=>null,'comparison'=>'equals']);}catch(RuntimeException$error){$caught=true;}
Expect($caught,'Strict null rejects absent marker before recording');
$bad=StateAssertion(['arcaneDealt','1','2'],'0');Check($bad,false,'Attribution negative control fails semantic assertion');
Expect(serialize($GLOBALS['stub_state'])===$before,'Recorder checks do not mutate stub state');
echo "Pure recorder/hook contracts total passed: {$checks}.\n";
