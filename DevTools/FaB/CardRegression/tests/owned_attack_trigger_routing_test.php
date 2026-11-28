<?php
// Isolated routing-contract checks, not native game or whole-card coverage.
declare(strict_types=1);
$path=$argv[1]??dirname(__DIR__,4).'/FaBSim/Custom/GameLogic.php';
$source=file_get_contents($path);$checks=0;$queued=[];
function FaBIsCapturedBurnLayer(object $o):bool{return false;}
function HasNoAbilities(object $o):bool{return !empty($o->blank);}
function FaBMPGSuppressed(object $o):bool{return !empty($o->mpgBlank);}
function FaBSUPSuppressed(object $o):bool{return !empty($o->supBlank);}
function FaBROSQueue(int $p,string $id,int $uid,array $params):void{$GLOBALS['queued'][]=compact('p','id','uid','params');}
foreach(['FaBOwnedEventMacro','FaBQueueOwnedEventMacro']as$name){
 if(!preg_match('/^function '.preg_quote($name,'/').'\([^\n]*\).*?^\}/ms',$source,$match))throw new RuntimeException('Missing routing function '.$name);
 eval($match[0]);
}
function check(bool $ok,string $label):void{++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($label);}
foreach(['deathly_duet_red','deathly_duet_yellow','deathly_duet_blue','head_leads_the_tail_red']as$id){
 $o=(object)['CardID'=>$id,'UniqueID'=>17];$before=count($queued);
 check(FaBQueueOwnedEventMacro(3,$o,'AttackDeclared',['mzID'=>'p3CombatChain-0','attacker'=>3,'defender'=>4])===1,$id.' earns queue');
 check(count($queued)===$before+1,$id.' exactly one layer per delivery');
 $row=end($queued);check($row['p']===3&&$row['id']===$id&&$row['uid']===17&&$row['params']['eventMacro']==='AttackDeclared',$id.' captured controller/source/macro');
 $before=count($queued);check(FaBQueueOwnedEventMacro(3,$o,'ResolveCard',[])===0&&count($queued)===$before,$id.' wrong macro cannot route');
}
foreach(['blank','mpgBlank','supBlank']as$flag){$o=(object)['CardID'=>'deathly_duet_red','UniqueID'=>17,$flag=>true];$before=count($queued);check(FaBQueueOwnedEventMacro(1,$o,'AttackDeclared',[])===0&&count($queued)===$before,'Suppression checked when earned '.$flag);}
$invalid=(object)['Kind'=>'ABILITY','CardID'=>'deathly_duet_red','SourceUniqueID'=>0,'Params'=>['rosTrigger'=>true,'rosEvent'=>'ownedCardMacro','eventMacro'=>'AttackDeclared','eventParams'=>[]]];
check(FaBOwnedEventMacro($invalid)==='','Invalid source UID rejected');$invalid->SourceUniqueID=17;$invalid->Kind='CARD';check(FaBOwnedEventMacro($invalid)==='','Physical card not a synthetic event');
$o=(object)['CardID'=>'wounding_blow_red','UniqueID'=>17];$before=count($queued);check(FaBQueueOwnedEventMacro(1,$o,'AttackDeclared',[])===0&&count($queued)===$before,'Unlisted attack unchanged');
$o=(object)['CardID'=>'drowning_dire_red','UniqueID'=>17];check(FaBQueueOwnedEventMacro(1,$o,'Hit',[])===1,'Existing owned Hit binding preserved');
echo "PASS {$checks} isolated owned attack routing checks\n";
