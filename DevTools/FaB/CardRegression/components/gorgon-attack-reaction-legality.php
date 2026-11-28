<?php
// Bounded extracted availability predicate only; native game qualification is separate.
declare(strict_types=1);
$sourcePath=$argv[1]??'';
$expectedGorgon=($argv[2]??'')==='allowed';
$raw=file_get_contents($sourcePath);
if($raw===false)throw new RuntimeException('Source unavailable');
$start=strpos($raw,'function FaBWTRCanPlay(');
$end=strpos($raw,'function FaBWTRCardDiscarded(',$start);
if($start===false||$end===false)throw new RuntimeException('Function boundary missing');
eval(substr($raw,$start,$end-$start));
function FaBWTRBase(string $id):string{return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBHandCount(int $p):int{return 3;}
function FaBWTRNeedsDiscard(string $id):bool{return false;}
function FaBHasType($o,string $type):bool{
 $id=is_object($o)?$o->CardID:$o;
 if($type==='Attack Reaction')return in_array($id,['gorgons_gaze_yellow','out_for_blood_red'],true);
 return is_object($o)&&in_array($type,$o->Types??[],true);
}
function FaBReactionPriorityForState(array $s):bool{return $s['window']==='REACTION';}
function FaBFindUID(int $uid):?array{return $GLOBALS['attack']===null?null:['object'=>$GLOBALS['attack']];}
function FaBWTRIsWeapon(object $o):bool{return in_array('Weapon',$o->Types,true);}
function FaBUsesDeclaredReactionTarget(string $id):bool{return false;} // Neither selected face declares a target choice in this implementation.
$aa=(object)['CardID'=>'wounding_blow_red','UniqueID'=>6,'Types'=>['Generic','Action','Attack']];
$weapon=(object)['CardID'=>'hatchet_of_body','UniqueID'=>6,'Types'=>['Warrior','Weapon','Axe']];
$cases=[
 ['Gorgon permits generic AA without Chi','gorgons_gaze_yellow',$aa,1,$expectedGorgon],
 ['Gorgon retains weapon permission','gorgons_gaze_yellow',$weapon,1,true],
 ['Gorgon cannot react as nonattacker','gorgons_gaze_yellow',$aa,4,false],
 ['Gorgon requires an actual attack','gorgons_gaze_yellow',null,1,false],
 ['Out for Blood retains printed weapon restriction','out_for_blood_red',$aa,1,false],
 ['Out for Blood retains weapon permission','out_for_blood_red',$weapon,1,true],
];
$results=[];
foreach($cases as[$label,$id,$attack,$attacker,$expected]){
 $GLOBALS['attack']=$attack;
 $found=['zone'=>'Hand','object'=>(object)['CardID'=>$id,'UniqueID'=>5]];
 $actual=FaBWTRCanPlay(1,$found,['window'=>'REACTION','attackUID'=>6,'attacker'=>$attacker]);
 $results[]=['label'=>$label,'expected'=>$expected,'actual'=>$actual];
 if($actual!==$expected)throw new RuntimeException($label.' expected '.json_encode($expected).' got '.json_encode($actual));
}
echo json_encode(['status'=>'PASS','scope'=>'Extracted actual FaBWTRCanPlay; dependencies isolated, not full engine/native proof','source_sha256'=>hash('sha256',$raw),'checks'=>count($results),'results'=>$results],JSON_PRETTY_PRINT).PHP_EOL;
