<?php
/** Actual shared attack modifier consumer; state/type I/O isolated. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$candidate=$argv[2]??$repo;
function HLoad(string $file,array $names):void{
 $ts=token_get_all(file_get_contents($file));$loaded=[];
 for($i=0;$i<count($ts);++$i){if(!is_array($ts[$i])||$ts[$i][0]!==T_FUNCTION)continue;$j=$i+1;while(is_array($ts[$j])&&$ts[$j][0]===T_WHITESPACE)++$j;if(!is_array($ts[$j])||$ts[$j][0]!==T_STRING||!in_array($ts[$j][1],$names,true))continue;$name=$ts[$j][1];$body='';$depth=0;$begun=false;for($k=$i;$k<count($ts);++$k){$t=$ts[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$begun=true;}elseif($t==='}')--$depth;if($begun&&$depth===0)break;}eval($body);$loaded[]=$name;$i=$k;}
 sort($loaded);sort($names);if($loaded!==$names)throw new RuntimeException('Missing actual functions');
}
HLoad($candidate.'/FaBSim/Custom/HNTRuntime.php',['FaBHNTApplyNextAttack','FaBHNTPlayed']);HLoad($repo.'/FaBSim/Custom/HNTCards.php',['FaBHNTMatch']);HLoad($repo.'/FaBSim/Custom/WTRCards.php',['FaBWTREffects','FaBWTRSetEffects','FaBWTRTag']);
$checks=0;function HC(bool $v,string $m):void{++$GLOBALS['checks'];if(!$v)throw new RuntimeException($m);}
function FaBGetState():array{return$GLOBALS['world'];}function FaBSetState(array $s):void{$GLOBALS['world']=$s;}
function GetTurnPlayer():int{return 1;}function FaBWTRBase(string $id):string{return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBWTRIsAttackAction(object $o):bool{return in_array('Action',$o->types,true)&&in_array('Attack',$o->types,true);}
function FaBHasType(object $o,string $t):bool{return in_array($t,$o->types,true);}function FaBHasKeyword(object $o,string $k):bool{return false;}
function FaBPENPowerTag(object $o,string $tag):string{return$tag;}function FaBHVYPowerTag(object $o,string $tag):string{return$tag;}function FaBOUTPowerTag(object $o,string $tag):string{return$tag;}function FaBELEKorshemActivity():void{}
function FaBHNTCount(int $p,string $k):int{return 0;}function FaBHNTVengeanceTarget(int $p,$target):bool{return false;}
function FaBHNTAdd(...$args):void{$GLOBALS['side'][]=['add',$args];}function FaBHNTClear(...$args):void{$GLOBALS['side'][]=['clear',$args];}
function HE(string $kind,int $n,array $tags=[]):array{return['type'=>'HNT_NEXT','amount'=>$n,'kind'=>$kind,'tags'=>$tags,'uses'=>1,'chain'=>false];}
function HO(string $id,array $types):object{return(object)['CardID'=>$id,'UniqueID'=>5,'Kind'=>'ATTACK','TurnEffects'=>[],'types'=>$types,'Params'=>[]];}
function HS(array $effects):void{$GLOBALS['world']=['turnEffects'=>['1'=>$effects,'2'=>[HE('ANGEL',7)]]];$GLOBALS['side']=[];}
$angel=HE('ANGEL',4);$aa=HE('AA',5);$other=['type'=>'OTHER','amount'=>9,'uses'=>1];HS([$angel,$aa,$other]);$o=HO('suraya_archangel_of_erudition',['Angel','Ally','Light']);FaBHNTApplyNextAttack(1,$o);
HC($o->TurnEffects===['WTR_POWER:4'],'Arena Ally proxy gets matching Angel bonus');HC(FaBWTREffects(1)===[$aa,$other],'consume matching modifier, preserve nonmatching and unrelated order');HC(FaBWTREffects(2)===[HE('ANGEL',7)],'foreign controller modifier unaffected');HC(!$GLOBALS['side'],'Arena consumer has no played-card/Fealty/Draconic side effects');FaBHNTApplyNextAttack(1,$o);HC($o->TurnEffects===['WTR_POWER:4'],'matching next modifier applies only once');
HS([$angel]);$o=HO('aether_ashwing',['Ally','Dragon']);FaBHNTApplyNextAttack(1,$o);HC(!$o->TurnEffects&&FaBWTREffects(1)===[$angel],'non-Angel Ally preserves future Angel modifier');
HS([$angel,$aa]);$o=HO('wounding_blow_red',['Action','Attack']);FaBHNTPlayed(1,$o,'Hand');HC($o->TurnEffects===['WTR_POWER:5']&&FaBWTREffects(1)===[$angel],'ordinary Hand AA still uses same consumer and matching gate');
HS([HE('ANY',2,['GO_AGAIN']),$angel]);$o=HO('centauri_saber',['Weapon','Sword']);FaBHNTPlayed(1,$o,'Weapons');HC($o->TurnEffects===['WTR_POWER:2','GO_AGAIN']&&FaBWTREffects(1)===[$angel],'ordinary Weapon still consumes existing matching tags');
HS([HE('ANY',2)]);$o=HO('war_cry_of_themis_yellow',['Action']);FaBHNTPlayed(1,$o,'Hand');HC(!$o->TurnEffects&&count(FaBWTREffects(1))===1,'nonattack card play never consumes attack modifier');
HS([$angel,HE('ANGEL',2,['GO_AGAIN']),$other]);$o=HO('suraya_archangel_of_erudition',['Angel','Ally']);FaBHNTApplyNextAttack(1,$o);HC($o->TurnEffects===['WTR_POWER:4','WTR_POWER:2','GO_AGAIN']&&FaBWTREffects(1)===[$other],'multiple existing matching modifiers preserve tag order and each consume once');
echo 'PASS '.$checks." actual-function checks with isolated state/type I/O; native verification separate\n";
