<?php
/** Actual scoped event producer/payload/dispatcher; card and zone I/O isolated. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$candidate=$argv[2]??$repo;
function FLLoad(string $file,array $names):void{
 $ts=token_get_all(file_get_contents($file));$loaded=[];
 for($i=0;$i<count($ts);++$i){if(!is_array($ts[$i])||$ts[$i][0]!==T_FUNCTION)continue;$j=$i+1;while(is_array($ts[$j])&&$ts[$j][0]===T_WHITESPACE)++$j;if(!is_array($ts[$j])||$ts[$j][0]!==T_STRING||!in_array($ts[$j][1],$names,true))continue;$name=$ts[$j][1];$body='';$depth=0;$begun=false;for($k=$i;$k<count($ts);++$k){$t=$ts[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$begun=true;}elseif($t==='}')--$depth;if($begun&&$depth===0)break;}eval($body);$loaded[]=$name;$i=$k;}
 sort($loaded);sort($names);if($loaded!==$names)throw new RuntimeException('Missing actual functions');
}
FLLoad($candidate.'/FaBSim/Custom/CRUCards.php',['FaBCRUQueueFloodAttack','FaBCRUResolveFloodAttack','FaBCRUSelfTag']);
FLLoad($candidate.'/FaBSim/Custom/GameLogic.php',['FaBOwnedEventMacro','FaBQueueOwnedEventMacro','FaBResolveOwnedEventMacro']);
FLLoad($repo.'/FaBSim/Custom/WTRCards.php',['FaBWTRTag']);FLLoad($repo.'/FaBSim/Custom/CardChoices.php',['FaBPreviousAttackBase']);
$checks=0;function FC(bool $v,string $m):void{++$GLOBALS['checks'];if(!$v)throw new RuntimeException($m);}
function FaBGetState():array{return$GLOBALS['worldState'];}function FaBSetState(array $s):void{$GLOBALS['worldState']=$s;}
function GetTurnNumber():int{return 2;}function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2],true);}
function FaBWTRBase(string $id):string{return preg_replace('/_(red|yellow|blue)$/','',$id);}
function CardName(string $id):string{return match($id){'flood_of_force_yellow'=>'Flood of Force','rushing_river_red'=>'Rushing River',default=>'Wounding Blow'};}
function HasNoAbilities(object $o):bool{return!empty($o->blank);}function FaBMPGSuppressed(object $o):bool{return!empty($o->mpg);}function FaBSUPSuppressed(object $o):bool{return!empty($o->sup);}
function FaBIsCapturedBurnLayer(object $o):bool{return false;}
class DecisionQueueController{public static array $vars=[];public static function StoreVariable(string $k,$v):void{self::$vars[$k]=$v;}}
function FaBROSQueue(int $p,string $id,int $source,array $params):void{$o=(object)['UniqueID'=>++$GLOBALS['next'],'CardID'=>$id,'Controller'=>$p,'Kind'=>'ABILITY','SourceUniqueID'=>$source,'Params'=>$params+['rosTrigger'=>true]];$GLOBALS['layers'][]=$o;}
function FaBFindUID(int $u):?array{foreach($GLOBALS['objects']as$f)if(intval($f['object']->UniqueID)===$u&&empty($f['object']->removed))return$f;return null;}
function FaBIdentityFromMZ(string $r):?array{foreach($GLOBALS['objects']as$f)if($f['mzID']===$r&&empty($f['object']->removed))return$f;return null;}
function FaBChoiceRefs(int $p,string $zone):array{return array_values(array_map(fn($f)=>$f['mzID'],array_filter($GLOBALS['objects'],fn($f)=>$f['player']===$p&&$f['zone']===$zone&&empty($f['object']->removed))));}
function FaBHasKeyword(object $o,string $k):bool{return$k==='Combo'&&!empty($o->combo);}
function FaBRevealChoices(int $p,string $r):void{$GLOBALS['reveals'][]=['p'=>$p,'ref'=>$r,'effectController'=>$GLOBALS['fabEffectController']];}
function DoDrawCard():void{throw new RuntimeException('Flood must put, not draw');}
function FaBMoveUID(int $u,string $z,int $p):?object{$GLOBALS['puts'][]=[$u,$z,$p];if(!$GLOBALS['moveWorks'])return null;foreach($GLOBALS['objects']as&$f)if(intval($f['object']->UniqueID)===$u){$f['zone']=$z;return$f['object'];}return null;}
function FaBPENPowerTag(object $o,string $tag):string{return$tag;}function FaBHVYPowerTag(object $o,string $tag):string{return$tag;}function FaBOUTPowerTag(object $o,string $tag):string{return$tag;}function FaBELEKorshemActivity():void{}
function FS(bool $prior=false,bool $combo=true,bool $deck=true):object{
 $GLOBALS['worldState']=['combatOpen'=>true,'attackUID'=>5,'attacker'=>1,'chainLink'=>2,'previousAttackCardID'=>$prior?'rushing_river_red':'','outPreviousNames'=>[1=>$prior?['Rushing River']:[]]];$GLOBALS['next']=20;$GLOBALS['layers']=[];$GLOBALS['reveals']=[];$GLOBALS['puts']=[];$GLOBALS['moveWorks']=true;$GLOBALS['fabEffectController']=3;DecisionQueueController::$vars=[];
 $source=(object)['UniqueID'=>5,'CardID'=>'flood_of_force_yellow','Controller'=>1,'Owner'=>1,'Role'=>'ATTACK','ChainLink'=>2,'TurnEffects'=>[]];$GLOBALS['objects']=[['player'=>1,'zone'=>'CombatChain','object'=>$source,'mzID'=>'p1CombatChain-0']];if($deck)$GLOBALS['objects'][]=['player'=>1,'zone'=>'Deck','object'=>(object)['UniqueID'=>6,'CardID'=>$combo?'herons_flight_red':'wounding_blow_red','combo'=>$combo],'mzID'=>'p1Deck-0'];return$source;
}
function FQ(object $s):object{FC(FaBCRUQueueFloodAttack(1,$s,['mzID'=>'p1CombatChain-0','attacker'=>1,'defender'=>2])===1,'normal attack earns one layer');return$GLOBALS['layers'][0];}
function FR(object $a):void{$GLOBALS['worldState']['resolvingReaction']=['resolutionClass'=>'ROS_EVENT','uid'=>$a->UniqueID];FaBResolveOwnedEventMacro(1,$a);}
$attackDeclaredAbilities=['flood_of_force_yellow:0'=>function(){throw new RuntimeException('Legacy inline body must not execute');}];
$s=FS();$a=FQ($s);FC(!$GLOBALS['reveals']&&!$GLOBALS['puts']&&!$s->TurnEffects,'nothing happens before owned response layer');FR($a);FC(!$GLOBALS['reveals']&&!$GLOBALS['puts']&&!$s->TurnEffects,'false predecessor produces no payload');FC(FaBPreviousAttackBase()==='flood_of_force','default early/bot latest lookup remains unchanged');
$s=FS(true);$a=FQ($s);$GLOBALS['worldState']['outPreviousNames'][1]=[];FR($a);FC(count($GLOBALS['reveals'])===1&&$GLOBALS['puts']===[[6,'Hand',1]],'captured true predecessor reveals current Combo top and puts it into Hand');FC($s->TurnEffects===['WTR_POWER:3','CRU_SUPPRESS_POWER:3','GO_AGAIN'],'own power has Snag provenance and GA');FC($GLOBALS['reveals'][0]['effectController']===1&&$GLOBALS['fabEffectController']===3,'actual dispatcher binds and restores captured effect controller');FR($a);FC(count($GLOBALS['reveals'])===1&&count($GLOBALS['puts'])===1,'same layer resolves payload once');
$s=FS(true,false);FR(FQ($s));FC(count($GLOBALS['reveals'])===1&&!$GLOBALS['puts']&&!$s->TurnEffects,'non-Combo is revealed without put or bonus');
$s=FS(true,true,false);FR(FQ($s));FC(!$GLOBALS['reveals']&&!$GLOBALS['puts']&&!$s->TurnEffects,'empty Deck produces no effects');
$s=FS(true);$a=FQ($s);$s->removed=true;FR($a);FC(count($GLOBALS['puts'])===1&&!$s->TurnEffects,'departed source does not cancel independent put or fatal');
$s=FS(true);$a=FQ($s);$s->ChainLink=3;FR($a);FC(count($GLOBALS['puts'])===1&&!$s->TurnEffects,'later sameUID attack not modified');
$s=FS(true);$a=FQ($s);$GLOBALS['moveWorks']=false;FR($a);FC(count($GLOBALS['puts'])===1&&count($s->TurnEffects)===3,'failed put does not cancel independent later bonus');
$s=FS(true);$s->blank=true;FC(FaBCRUQueueFloodAttack(1,$s,[])===0&&!$GLOBALS['layers'],'suppression before earning prevents layer');
$s=FS(true);$a=FQ($s);$s->blank=true;FR($a);FC(count($GLOBALS['puts'])===1&&count($s->TurnEffects)===3,'suppression after earning does not erase effect');
echo 'PASS '.$checks." actual-function checks with isolated card/zone I/O; native witnesses separate\n";
