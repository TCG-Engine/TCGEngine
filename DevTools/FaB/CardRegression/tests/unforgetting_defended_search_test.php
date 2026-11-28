<?php
declare(strict_types=1);
$repo=dirname(__DIR__,4);
$candidate=$argv[1]??($repo.'/FaBSim/Custom/SUPCards.php');
$game=$argv[2]??($repo.'/FaBSim/Custom/GameLogic.php');
function load_functions(string $file,array $names):void {
 $tokens=token_get_all(file_get_contents($file));$loaded=[];
 for($i=0;$i<count($tokens);++$i){if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;
  if(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||!in_array($tokens[$j][1],$names,true))continue;
  $name=$tokens[$j][1];$body='';$depth=0;$begun=false;
  for($k=$i;$k<count($tokens);++$k){$t=$tokens[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$begun=true;}elseif($t==='}')--$depth;if($begun&&$depth===0)break;}
  eval($body);$loaded[]=$name;$i=$k;
 }sort($loaded);sort($names);if($loaded!==$names)throw new RuntimeException('Function extraction mismatch');
}
load_functions($candidate,['FaBSUPUnforgettingCondition','FaBSUPUnforgettingStage','FaBSUPUnforgettingFinish']);
load_functions($game,['FaBOwnedEventMacro','FaBQueueOwnedEventMacro','FaBResolveOwnedEventMacro']);
load_functions($repo.'/FaBSim/Custom/CardChoices.php',['FaBChoiceRefs','FaBStageSearch']);
load_functions($repo.'/FaBSim/Custom/DTDCards.php',['FaBDTDGrant','FaBDTDPermission','FaBDTDActivatePermissions','FaBDTDEndActionPermissions']);
$checks=0;
function ck(bool $ok,string $why):void{global$checks;++$checks;if(!$ok)throw new RuntimeException($why);}
function FaBGetState():array{return $GLOBALS['s'];}function FaBSetState(array $s):void{$GLOBALS['s']=$s;}
function FaBLiveSeats():array{return [1,2];}function FaBSeatIsLive(int $p):bool{return in_array($p,[1,2],true);}
function FaBFindUID(int $uid):?array{return $GLOBALS['objects'][$uid]??null;}
function FaBIdentityFromMZ(string $ref):?array{foreach($GLOBALS['objects']as$f)if($f['mzID']===$ref)return$f;return null;}
function FaBObjectCounters(object $o):array{return$o->Counters??[];}
function FaBHasType(object $o,string $type):bool{return in_array($type,$o->types??[],true);}
function CardName(string $id):string{return str_starts_with($id,'mangle_')?'Mangle':$id;}
function FaBWTRBase(string $id):string{return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBZoneGet(string $zone,int $p):array{$out=[];foreach($GLOBALS['objects']as$uid=>$f)if($f['player']===$p&&$f['zone']===$zone)$out[$uid]=$f['object'];return$out;}
function FaBMoveUID(int $uid,string $zone,int $p,bool $events=true):?object{
 $f=FaBFindUID($uid);if(!$f)return null;$f['player']=$p;$f['zone']=$zone;$f['mzID']='p'.$p.$zone.'-'.$uid;$GLOBALS['objects'][$uid]=$f;
 if($zone==='Banish'&&!empty($GLOBALS['replaceFaceDown']))$f['object']->FaceDown=1;return$f['object'];
}
function FaBUPRBleak():bool{return false;}function FaBShuffleDeck(int $p):void{++$GLOBALS['shuffles'];}
function HasNoAbilities(object $o):bool{return!empty($o->noAbilities);}function FaBMPGSuppressed(object $o):bool{return false;}function FaBSUPSuppressed(object $o):bool{return false;}
function FaBIsCapturedBurnLayer(object $o):bool{return false;}
function FaBROSQueue(int $p,string $id,int $source,array $params):void{$GLOBALS['layers'][]=(object)['Kind'=>'ABILITY','CardID'=>$id,'UniqueID'=>20+count($GLOBALS['layers']),'SourceUniqueID'=>$source,'Controller'=>$p,'Params'=>$params];}
class DecisionQueueController {public static array $vars=[];public static function StoreVariable(string $k,$v):void{self::$vars[$k]=$v;}}
function GetTurnNumber():int{return$GLOBALS['turn'];}function GetTurnPlayer():int{return$GLOBALS['turnPlayer'];}function GetCurrentPhase():string{return$GLOBALS['phase'];}
function obj(int $uid,string $zone='Equipment',int $seat=1,string $id='courage_of_bladehold',int $wear=1):object{
 $o=(object)['UniqueID'=>$uid,'CardID'=>$id,'Controller'=>$seat,'Owner'=>$seat,'Counters'=>['DEFENSE'=>$wear],'types'=>['Equipment'],'FaceDown'=>0];
 $GLOBALS['objects'][$uid]=['player'=>$seat,'zone'=>$zone,'object'=>$o,'mzID'=>'p'.$seat.$zone.'-'.$uid];return$o;
}
function reset_case():void{$GLOBALS['objects']=[];$GLOBALS['s']=['window'=>'DEFEND_PRIORITY','attacker'=>1];$GLOBALS['turn']=2;$GLOBALS['turnPlayer']=1;$GLOBALS['phase']='MAIN';$GLOBALS['shuffles']=0;$GLOBALS['layers']=[];$GLOBALS['replaceFaceDown']=false;DecisionQueueController::$vars=[];}
reset_case();ck(!FaBSUPUnforgettingCondition(1),'empty false');
foreach(['Hero','Equipment','Weapons','Arena','CombatChain']as$zone){reset_case();$o=obj(8,$zone);$o->noAbilities=true;ck(FaBSUPUnforgettingCondition(1),'physical counter even with suppressed equipment in '.$zone);}
foreach([0,-1]as$wear){reset_case();$o=obj(8,'Equipment',1,'courage_of_bladehold',$wear);$o->Damage=1;$o->TurnEffects=['WTR_DEFENSE:-1'];ck(!FaBSUPUnforgettingCondition(1),'non-wear does not qualify '.$wear);}
foreach(['Hand','Graveyard','Banish']as$zone){reset_case();obj(8,$zone);ck(!FaBSUPUnforgettingCondition(1),'private/non-inplay excludes '.$zone);}
reset_case();$o=obj(8);$o->types=['Weapon'];ck(!FaBSUPUnforgettingCondition(1),'plain weapon excluded');$o->types=['Weapon','Equipment'];ck(FaBSUPUnforgettingCondition(1),'equipment weapon qualifies');$o->Controller=2;ck(!FaBSUPUnforgettingCondition(1)&&FaBSUPUnforgettingCondition(2),'actual controller not storage seat');
reset_case();$o=obj(4,'CombatChain',2,'unforgetting_unforgiving_red',0);$o->types=['Action'];$params=['supUnforgetAttacker'=>1];ck(FaBQueueOwnedEventMacro(2,$o,'Defended',$params)===1&&count($GLOBALS['layers'])===1,'false condition earns one layer');$layer=$GLOBALS['layers'][0];$o->noAbilities=true;unset($GLOBALS['objects'][4]);$GLOBALS['s']['attacker']=2;$GLOBALS['s']['resolvingReaction']=['resolutionClass'=>'ROS_EVENT','uid'=>$layer->UniqueID];
$GLOBALS['defendedAbilities']['unforgetting_unforgiving_red:0']=function($p){$GLOBALS['called']=[$p,DecisionQueueController::$vars['supUnforgetAttacker'],FaBSUPUnforgettingCondition(DecisionQueueController::$vars['supUnforgetAttacker'])];};
$equipment=obj(8);FaBResolveOwnedEventMacro(2,$layer);ck($GLOBALS['called']===[2,1,true],'captured attacker and current equipment after source departure');$equipment->Counters['DEFENSE']=0;FaBResolveOwnedEventMacro(2,$layer);ck($GLOBALS['called']===[2,1,false],'condition recomputed after removal');
reset_case();$o=obj(4,'CombatChain',2,'unforgetting_unforgiving_red',0);$o->noAbilities=true;ck(FaBQueueOwnedEventMacro(2,$o,'Defended',$params)===0&&!$GLOBALS['layers'],'pre-earning suppression blocks');
reset_case();$m=obj(6,'Deck',2,'mangle_red',0);obj(7,'Temp',2,'mangle_blue',0);obj(8,'Deck',2,'wounding_blow_blue',0);$refs=FaBSUPUnforgettingStage(2);ck(FaBFindUID(8)['zone']==='Deck','actual shared base filter preserves unrelated Deck card');ck($refs==='p2Temp-6','only original deck UID offered, unrelated temp excluded');FaBSUPUnforgettingFinish(2,$refs,[6]);ck(FaBFindUID(6)['zone']==='Banish'&&empty($m->FaceDown),'faceup original Mangle banished');ck(FaBFindUID(7)['zone']==='Temp'&&$GLOBALS['shuffles']===1,'unrelated temp retained and shuffle once');ck(($GLOBALS['s']['dtdPermissions'][6]??[])===['player'=>2,'created'=>2,'next'=>true,'activeTurn'=>0],'existing next-phase grant');ck(!FaBDTDPermission(2,$m),'no current turn permission');
$GLOBALS['turn']=3;$GLOBALS['turnPlayer']=2;$GLOBALS['s']['dtdStarting']=true;FaBDTDActivatePermissions(2);ck(!FaBDTDPermission(2,$m),'not during starting transition');$GLOBALS['s']['dtdStarting']=false;$GLOBALS['s']['window']='ACTION';ck(FaBDTDPermission(2,$m),'next own Action permission');$GLOBALS['s']['window']='END_PHASE';ck(!FaBDTDPermission(2,$m),'not End');$GLOBALS['s']['window']='ACTION';FaBDTDEndActionPermissions(2);ck(!FaBDTDPermission(2,$m),'expires at end own Action');
foreach(['-','p1Temp-7','p2Temp-7','p2Temp-6&p2Temp-7','stale']as$answer){reset_case();obj(6,'Deck',2,'mangle_red',0);obj(7,'Temp',2,'other',0);FaBSUPUnforgettingStage(2);FaBSUPUnforgettingFinish(2,$answer,[6]);ck(FaBFindUID(6)['zone']==='Deck'&&empty($GLOBALS['s']['dtdPermissions'])&&$GLOBALS['shuffles']===1,'invalid/zero selection cleans only staged cards '.$answer);}
reset_case();obj(6,'Deck',2,'mangle_red',0);FaBSUPUnforgettingStage(2);$GLOBALS['replaceFaceDown']=true;FaBSUPUnforgettingFinish(2,'p2Temp-6',[6]);ck(FaBFindUID(6)['object']->FaceDown===1&&empty($GLOBALS['s']['dtdPermissions']),'banish replacement not overridden or granted');
reset_case();FaBSUPUnforgettingFinish(2,'-',[]);ck($GLOBALS['shuffles']===1,'search failing to find still shuffles');
echo 'PASS '.$checks." actual-function component checks; isolated state, not native await/game coverage\n";
