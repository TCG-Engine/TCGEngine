<?php
/** Actual save mapper with isolated state/eligibility I/O. No game or fixture writes. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$file=$argv[2]??$repo.'/FaBSim/Custom/WTRCards.php';
$tokens=token_get_all(file_get_contents($file));$loaded=[];
for($i=0;$i<count($tokens);++$i){if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;$j=$i+1;while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;if(!is_array($tokens[$j])||!in_array($tokens[$j][1],['FaBPreparedHandParamForSave','FaBPreparedHandChoicesForSave'],true))continue;$name=$tokens[$j][1];$body='';$depth=0;$start=false;for($k=$i;$k<count($tokens);++$k){$t=$tokens[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$start=true;}elseif($t==='}')--$depth;if($start&&$depth===0)break;}eval($body);$loaded[]=$name;$i=$k;}
if(count($loaded)!==2)throw new RuntimeException('Missing actual mapper functions');
class PObj {public $removed=false;function __construct(array $props){foreach($props as$k=>$v)$this->$k=$v;}function Removed(){return $this->removed;}}
class DecisionQueueController {static function GetAwaitFrame($key){return $GLOBALS['frame'];}}
function GetHand($p){return $GLOBALS['hand'];}function FaBSeatIsLive($p){return $p===1;}function FaBGetState(){return $GLOBALS['state'];}function FaBFindUID($uid){return $uid===5?$GLOBALS['source']:null;}
function FaBMONAffordableHand(...$a){return implode('&',$GLOBALS['eligible']);}function FaBChoiceRefs(...$a){return $GLOBALS['eligible'];}function FaBELESelect(...$a){return implode('&',$GLOBALS['eligible']);}
function PS($id,$kind,$type,$label,$pc,$local,$refs){
 $GLOBALS['hand']=[new PObj(['UniqueID'=>5,'removed'=>true]),new PObj(['UniqueID'=>6]),new PObj(['UniqueID'=>7])];
 $GLOBALS['eligible']=explode('&',$refs);$GLOBALS['state']=['pendingPayment'=>['player'=>1,'uid'=>5,'fromZone'=>'Hand','kind'=>$kind]];
 $GLOBALS['source']=['zone'=>'Stack','mzID'=>'Stack-0','object'=>new PObj(['CardID'=>$id,'UniqueID'=>5,'Kind'=>$kind,'SourceZone'=>'Hand','SourceUniqueID'=>5,'Owner'=>1,'Controller'=>1])];
 $GLOBALS['frame']=['locals'=>['uid'=>5,'mzID'=>'Stack-0',$local=>$refs,'opaque'=>'p1Hand-1 NOT_A_REF']];
 return [new PObj(['Type'=>$type,'Tooltip'=>$label,'Block'=>1,'Param'=>$refs]),new PObj(['Type'=>'CUSTOM','Block'=>1,'Param'=>$id.':0:PrepareCard-await|'.$id.':0:PrepareCard#1|'.$pc.'|chosen'])];
}
$checks=0;function PC($ok,$why){++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($why);}
$profiles=[['light_the_way_red','ATTACK','MZMAYCHOOSE','Charge_your_soul','4','targets','p1Hand-1&p1Hand-2'],['spirit_of_war_red','ATTACK','MZMAYCHOOSE','Charge_your_soul','7','refs','p1Hand-1&p1Hand-2'],['submerge_red','ATTACK','MZCHOOSE','Put_card_fifth_from_top','1','refs','p1Hand-1&p1Hand-2'],['emerging_avalanche_red','ACTION','MZMAYCHOOSE','Reveal_Ice_to_fuse','1','targets','p1Hand-1'],['force_of_nature_blue','ACTION','MZMAYCHOOSE','Reveal_Earth_to_fuse','1','targets','p1Hand-1']];
foreach($profiles as$p){$z=PS(...$p);$before=serialize([$z,$GLOBALS['hand'],$GLOBALS['frame'],$GLOBALS['state']]);$r=FaBPreparedHandChoicesForSave($z,1);PC($r[0]->Param===str_replace(['Hand-1','Hand-2'],['Hand-0','Hand-1'],$p[6]),'Correct projected coordinates: '.$p[0]);PC(serialize([$z,$GLOBALS['hand'],$GLOBALS['frame'],$GLOBALS['state']])===$before&&$r[1]===$z[1],'Only a decision clone changes, never runtime state or opaque frame: '.$p[0]);}
foreach(['uid','pc','zone','owner','controller','local','seat','mixed','duplicate']as$bad){$z=PS(...$profiles[2]);if($bad==='uid')$GLOBALS['frame']['locals']['uid']=99;if($bad==='pc')$z[1]->Param=str_replace('|1|chosen','|2|chosen',$z[1]->Param);if($bad==='zone')$GLOBALS['source']['object']->SourceZone='Arsenal';if($bad==='owner')$GLOBALS['source']['object']->Owner=2;if($bad==='controller')$GLOBALS['source']['object']->Controller=2;if($bad==='local')$GLOBALS['frame']['locals']['refs']='other';if($bad==='mixed'){$z[0]->Param='p1Hand-1&p2Hand-2';$GLOBALS['frame']['locals']['refs']=$z[0]->Param;}if($bad==='duplicate'){$z[0]->Param='p1Hand-1&p1Hand-1';$GLOBALS['frame']['locals']['refs']=$z[0]->Param;}PC(FaBPreparedHandChoicesForSave($z,$bad==='seat'?2:1)===$z,'Unknown/ambiguous context untouched: '.$bad);}
PS(...$profiles[2]);PC(FaBPreparedHandParamForSave(1,'p1Hand-0&p1Hand-1',['p1Hand-1'])==='p1Hand-0','Removed source cannot alias a successor');PC(FaBPreparedHandParamForSave(1,'p1Hand-0',[])==='p1Hand-999999','All removed candidates stay invalid');PC(FaBPreparedHandParamForSave(1,'p1Hand-2',['p1Hand-1'])===null,'Never add newly ineligible candidates');
echo 'PASS '.$checks.' actual save-mapper checks with isolated state/eligibility I/O'.PHP_EOL;
