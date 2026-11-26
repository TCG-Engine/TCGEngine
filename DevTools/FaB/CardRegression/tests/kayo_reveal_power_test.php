<?php
/** Actual reveal/current-power functions with isolated card/state I/O. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$mon=$argv[2]??($repo.'/FaBSim/Custom/MONCards.php');
function KRLoad(string $file,array $names):void{
 $ts=token_get_all(file_get_contents($file));$loaded=[];
 for($i=0;$i<count($ts);++$i){if(!is_array($ts[$i])||$ts[$i][0]!==T_FUNCTION)continue;$j=$i+1;while(is_array($ts[$j])&&$ts[$j][0]===T_WHITESPACE)++$j;if(!is_array($ts[$j])||$ts[$j][0]!==T_STRING||!in_array($ts[$j][1],$names,true))continue;$name=$ts[$j][1];$body='';$depth=0;$begun=false;for($k=$i;$k<count($ts);++$k){$t=$ts[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$begun=true;}elseif($t==='}')--$depth;if($begun&&$depth===0)break;}eval($body);$loaded[]=$name;$i=$k;}
 sort($loaded);sort($names);if($loaded!==$names)throw new RuntimeException('Missing actual functions');
}
KRLoad($mon,['FaBMONRevealPower','FaBMONBasePower']);KRLoad($repo.'/FaBSim/Custom/HVYCards.php',['FaBHVYOwnedPower','FaBHVYPowerOutsideChain']);
$checks=0;function KC(bool $v,string $m):void{++$GLOBALS['checks'];if(!$v)throw new RuntimeException($m);}
function FaBIdentityFromMZ(string $r):?array{return$GLOBALS['refs'][$r]??null;}
function FaBFindUID(int $u):?array{foreach($GLOBALS['refs']as$f)if(intval($f['object']->UniqueID)===$u)return$f;return null;}
function FaBWTRIsAttackAction(object $o):bool{return$o->CardID!=='energy_potion_blue';}
function CardPower(string $id){return match($id){'raging_onslaught_blue'=>5,'raging_onslaught_yellow'=>6,'mutated_mass_blue'=>'*',default=>0};}
function FaBMONMass(int $p):int{return 8;}function FaBMONCount(int $p,string $k):int{return 0;}
function HasNoAbilities(object $o):bool{return false;}function FaBIARHasBlasmophet(int $p):bool{return false;}
function FaBSUPBase(int $p,object $o,int $n):int{return$n;}function FaBPENBase(int $p,object $o,int $n):int{return$n;}
function FaBHVYHero(int $p,string $id):bool{return($GLOBALS['heroes'][$p]??'')===$id;}
function GetHero(int $p):array{return[(object)['CardID'=>$GLOBALS['heroes'][$p]??'dorinthea']];}
function FaBPENPowerGain(object $o,int $n):int{return$n;}function FaBMPGOutsidePower(int $p,object $o):int{return intval($GLOBALS['extra']??0);}
function KSetup():void{$GLOBALS['heroes']=[1=>'kayo',2=>'dorinthea'];$GLOBALS['extra']=0;$GLOBALS['refs']=[];foreach(['raging_onslaught_blue','raging_onslaught_yellow','energy_potion_blue']as$i=>$id)$GLOBALS['refs']['p1Hand-'.$i]=['player'=>1,'zone'=>'Hand','object'=>(object)['CardID'=>$id,'Owner'=>1,'Controller'=>1,'UniqueID'=>$i+1]];}
KSetup();KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===13,'Current owned Hand6+7 meets thirteen');
$GLOBALS['heroes'][1]='dorinthea';KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===11,'Same printed5+6 without Kayo still below thirteen');
KSetup();KC(FaBMONRevealPower('p1Hand-1')===7,'Single modified7 is insufficient');KC(FaBMONRevealPower('p1Hand-0&p1Hand-2')===6,'Non-attack is not counted');KC(FaBMONRevealPower('missing')===0,'Missing reference never contributes');
foreach($GLOBALS['refs']as&$f)$f['object']->Owner=2;unset($f);KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===11,'Ownership, not Hand controller, grants Kayo power');
KSetup();foreach($GLOBALS['refs']as&$f)$f['zone']='CombatChain';unset($f);KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===11,'Kayo never buffs combat-chain cards');
KSetup();$GLOBALS['refs']['p1Hand-1']['object']->CardID='mutated_mass_blue';KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===15,'Variable current base power and Kayo compose');
KSetup();$GLOBALS['refs']['p1Hand-1']['object']->CardID='diabolic_offering_blue';KC(FaBMONRevealPower('p1Hand-1')===1,'Conditional base, rather than printed fallback, participates');
KSetup();$GLOBALS['extra']=2;KC(FaBMONRevealPower('p1Hand-0&p1Hand-1')===17,'Existing outside-power evaluator remains shared');
echo 'PASS '.$checks." actual-function checks; isolated card/state I/O, native tests separate\n";
