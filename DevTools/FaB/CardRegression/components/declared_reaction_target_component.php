<?php
// Actual extracted declaration/binding/revalidation/remap/tag functions. Zones,
// queue transport, Undo loading and final payment API are mocks, not native games.
require __DIR__.'/extract_component_functions.php';
$root=$argv[1]??dirname(__DIR__,4);$mutant=$argv[2]??'none';
if(!in_array($mutant,['none','ignore-bound-link','lose-snag-tag','wrong-shred-amount'],true))throw new RuntimeException('Unknown component mutant.');
$component_checks=0;$component_function_hashes=[];$profiles=[];$currentProfile='startup';
class ComponentObject {
    public $CardID='';public $UniqueID=0;public $Owner=1;public $Controller=1;public $Kind='';public $Role='';public $ChainLink=1;public $FromZone='Hand';public $Location='';public $FaceDown=0;public $TurnEffects=[];public $Counters=[];public $Params=[];public $removed=false;
    public function __construct(array $fields=[]){foreach($fields as$k=>$v)$this->$k=$v;}
    public function Removed(){return $this->removed;}
}
class DecisionQueueController {
    static function ExecutingSeat(){return $GLOBALS['executing'];}
    static function AddDecision($p,$type,$param,$block,$tip=''){$GLOBALS['queues'][$p][]=(object)['Type'=>$type,'Param'=>$param,'Block'=>$block,'Tooltip'=>$tip,'removed'=>false];}
    static function GetVariable($key){return $GLOBALS['variables'][$key]??null;}
}
function FaBGetState(){return $GLOBALS['state'];} function FaBSetState($s){$GLOBALS['state']=$s;}
function FaBLiveSeats(){return [1,2,3,4];} function FaBSeatOrder(){return [1,2,3,4];}
function GetDecisionQueue($p){return $GLOBALS['queues'][$p];}
function GetCombatChain($p){return $GLOBALS['chains'][$p];}
function GetVersions($p){return [(object)['Version'=>$GLOBALS['snapshot']]];}
function LoadVersion($p,$index){++$GLOBALS['rollbacks'];$GLOBALS['state']['pendingPayment']=null;$GLOBALS['source']->Location='Hand';}
function SetFlashMessage($message){$GLOBALS['flash']=$message;}
function FaBTryCompletePayment(){++$GLOBALS['payments'];$GLOBALS['state']['pendingPayment']=null;return true;}
function FaBFindUID($uid){
    if(intval($GLOBALS['source']->UniqueID)===$uid&&$GLOBALS['source']->Location==='Stack')return ['object'=>$GLOBALS['source'],'zone'=>'Stack','player'=>0,'mzID'=>'Stack-0'];
    foreach([1,2,3,4]as$p)foreach($GLOBALS['chains'][$p]as$i=>$o)if($o&&!$o->removed&&intval($o->UniqueID)===$uid)return ['object'=>$o,'zone'=>'CombatChain','player'=>$p,'mzID'=>'p'.$p.'CombatChain-'.$i];
    return null;
}
function FaBIdentityFromMZ($ref){
    if($ref==='Stack-0')return FaBFindUID(intval($GLOBALS['source']->UniqueID));
    if(preg_match('/^p([1-4])CombatChain-([0-9]+)$/D',$ref,$m)!==1)return null;
    $p=intval($m[1]);$i=intval($m[2]);$o=$GLOBALS['chains'][$p][$i]??null;
    return $o&&!$o->removed?['object'=>$o,'zone'=>'CombatChain','player'=>$p,'mzID'=>$ref]:null;
}
function CardPower($id){return match($id){'harmonized_kodachi'=>1,'wounding_blow_blue'=>2,'isolate_red'=>3,'wounding_blow_red'=>4,default=>0};}
function CardPitch($id){return str_ends_with($id,'_red')?1:(str_ends_with($id,'_yellow')?2:3);}
function FaBHasType($o,$type){
    $id=is_object($o)?$o->CardID:$o;
    $types=match($id){'nip_at_the_heels_blue'=>['Generic','Attack Reaction'],'shred_red','shred_yellow','shred_blue'=>['Assassin','Attack Reaction'],'harmonized_kodachi'=>['Ninja','Weapon','Dagger'],'isolate_red'=>['Assassin','Action','Attack'],default=>['Generic','Action','Attack']};
    return in_array($type,$types,true);
}
function EffectiveCardType($o){return ['Generic','Action'];}
function FaBMPGSuppressed($o){return false;} function FaBSUPSuppressed($o){return false;} function FaBMSTHidden($o){return false;}
function FaBWTRHeroActive($p){return true;} function FaBObjectCounters($o){return $o->Counters;}
function FaBHasKeyword($o,$keyword){return false;}
function FaBPENPowerTag($o,$tag){return $tag;} function FaBHVYPowerTag($o,$tag){return $tag;} function FaBOUTPowerTag($o,$tag){return $tag;}
function FaBELEKorshemActivity(){} function FaBMONArena($p,$id){return [];}
function FaBARCEffect($p,$key){return $key==='CRU_SNAG'&&$GLOBALS['snag']?1:0;}
function FaBARCCard($uid,$key,$default=0){return $default;}
function EvaluateAttackPowerModifier(...$args){return 0;} function FaBProfessorPower(...$args){return 0;}
function FaBDYNContract($id){return '';}

function ResetReactionComponent(string $cardID='nip_at_the_heels_blue',bool $tombstone=false):void{
    $GLOBALS['source']=new ComponentObject(['CardID'=>$cardID,'UniqueID'=>6,'Kind'=>'ATTACK_REACTION','Location'=>'Stack']);
    $GLOBALS['chains']=[1=>[],2=>[],3=>[],4=>[]];
    if($cardID==='nip_at_the_heels_blue'){
        $target=new ComponentObject(['CardID'=>'harmonized_kodachi','UniqueID'=>7,'Role'=>'ATTACK','Counters'=>['WEAPON_UID'=>5]]);
        if($tombstone)$GLOBALS['chains'][1][]=new ComponentObject(['CardID'=>'wounding_blow_red','UniqueID'=>88,'Role'=>'ATTACK','removed'=>true]);
        $GLOBALS['chains'][1][]=$target;$attackUID=7;
    }else{
        $GLOBALS['chains'][1][]=new ComponentObject(['CardID'=>'isolate_red','UniqueID'=>5,'Role'=>'ATTACK']);
        if($tombstone)$GLOBALS['chains'][4][]=new ComponentObject(['CardID'=>'wounding_blow_red','UniqueID'=>88,'Role'=>'DEFENSE','Owner'=>4,'Controller'=>4,'removed'=>true]);
        $target=new ComponentObject(['CardID'=>'wounding_blow_red','UniqueID'=>7,'Role'=>'DEFENSE','Owner'=>4,'Controller'=>4]);
        $GLOBALS['chains'][4][]=$target;$attackUID=5;
    }
    $GLOBALS['target']=$target;
    $GLOBALS['queues']=[1=>[],2=>[],3=>[],4=>[]];$GLOBALS['variables']=['mzID'=>'Stack-0'];
    $GLOBALS['executing']=1;$GLOBALS['snapshot']='component-preannouncement-snapshot';$GLOBALS['rollbacks']=0;$GLOBALS['payments']=0;$GLOBALS['flash']='';$GLOBALS['snag']=false;
    $GLOBALS['state']=['pendingPayment'=>['player'=>1,'uid'=>6,'cost'=>0,'kind'=>'ATTACK_REACTION'],'attacker'=>1,'defender'=>4,'attackUID'=>$attackUID,'chainLink'=>1,'window'=>'PITCH','previousAttackCardID'=>''];
}
function PrepareAndBindReaction():void{
    FaBPrepareDeclaredReaction(1,$GLOBALS['source']);
    ComponentCheck(count($GLOBALS['queues'][1])===2,'one chooser and one short continuation');
    ComponentCheck($GLOBALS['payments']===0,'no cost completion before target selection');
    ComponentCheck($GLOBALS['queues'][1][0]->Type==='MZCHOOSE','actual manual target choice');
    $answer=$GLOBALS['queues'][1][0]->Param;
    FaBContinueDeclaredReaction(1,['6'],$answer);
    ComponentCheck($GLOBALS['payments']===1,'normal payment completion exactly once after binding');
    ComponentCheck($GLOBALS['source']->Params===['declaredReactionTarget'=>['uid'=>7,'chainLink'=>1]],'bound stable UID and chain link, temporary declaration data removed');
    ComponentCheck($GLOBALS['state']['pendingPayment']===null,'no pending cost after accepted target');
}
function HasTag($o,$tag){return in_array($tag,$o->TurnEffects,true);}

try{
    $choices=$root.'/FaBSim/Custom/CardChoices.php';$cru=$root.'/FaBSim/Custom/CRUCards.php';$wtr=$root.'/FaBSim/Custom/WTRCards.php';
    foreach(['FaBUsesDeclaredReactionTarget','FaBDeclaredReactionTargetRefs','FaBDeclaredReactionSource','FaBReverseDeclaredReaction','FaBPrepareDeclaredReaction','FaBContinueDeclaredReaction','FaBDeclaredReactionChoicesForSave','FaBFinishPreparedCard','FaBTagUID','FaBDefendingChoices']as$name)ComponentLoadFunction($choices,$name);
    $replacements=[];
    if($mutant==='ignore-bound-link')$replacements["        || intval(\$target['object']->ChainLink ?? -1) !== intval(\$bound['chainLink'] ?? -2)\n"]='';
    if($mutant==='wrong-shred-amount')$replacements['5 - intval(CardPitch($source->CardID))']='4 - intval(CardPitch($source->CardID))';
    ComponentLoadFunction($choices,'FaBResolveDeclaredReaction',$replacements);
    foreach(['FaBWTRTag','FaBWTRTagValue','FaBWTRBase','FaBWTRIsAttackAction']as$name)ComponentLoadFunction($wtr,$name);
    ComponentLoadFunction($root.'/FaBSim/Custom/GameLogic.php','HasNoAbilities');
    ComponentLoadFunction($cru,'FaBCRUSelfTag',$mutant==='lose-snag-tag'?["    if(str_starts_with(\$tag,'WTR_POWER:')&&intval(substr(\$tag,10))>0)FaBWTRTag(\$o,'CRU_SUPPRESS_POWER:'.intval(substr(\$tag,10)));"=>'']:[]);
    foreach(['FaBCRUCount','FaBCRUOwnPower','FaBCRUPower']as$name)ComponentLoadFunction($cru,$name);
    foreach(['FaBDYNMatch','FaBDYNAttacks','FaBDYNDefenders']as$name)ComponentLoadFunction($root.'/FaBSim/Custom/DYNCards.php',$name);
    foreach(['FaBHNTMatch','FaBHNTAffectsAttack']as$name)ComponentLoadFunction($root.'/FaBSim/Custom/HNTCards.php',$name);
    foreach(['nip_at_the_heels_blue','shred_red','shred_yellow','shred_blue']as$card){
        $currentProfile=['card'=>$card,'case'=>'accept-and-apply'];ResetReactionComponent($card);PrepareAndBindReaction();
        FaBResolveDeclaredReaction(1,$GLOBALS['source']);
        $tag=$card==='nip_at_the_heels_blue'?'WTR_POWER:1':'DYN_DEFENSE:-'.(5-CardPitch($card));
        ComponentCheck(HasTag($GLOBALS['target'],$tag),'correct amount on original selected physical target');$profiles[]=$currentProfile;
    }
    foreach(['nip_at_the_heels_blue','shred_red']as$card){
        foreach(['missing','changed-link','ineligible','suppressed-source']as$case){
            $currentProfile=['card'=>$card,'case'=>$case];ResetReactionComponent($card);PrepareAndBindReaction();
            if($case==='missing')$GLOBALS['target']->removed=true;
            if($case==='changed-link'){$GLOBALS['target']->ChainLink=2;$GLOBALS['state']['chainLink']=2;}
            if($case==='ineligible'){if($card==='nip_at_the_heels_blue')$GLOBALS['target']->CardID='wounding_blow_red';else $GLOBALS['target']->Role='ATTACK';}
            if($case==='suppressed-source')$GLOBALS['source']->TurnEffects=['NO_ABILITIES'];
            $other=new ComponentObject(['CardID'=>$card==='nip_at_the_heels_blue'?'harmonized_kodachi':'wounding_blow_red','UniqueID'=>9,'Role'=>$card==='nip_at_the_heels_blue'?'ATTACK':'DEFENSE','Owner'=>$card==='nip_at_the_heels_blue'?1:4,'Controller'=>$card==='nip_at_the_heels_blue'?1:4]);
            $GLOBALS['chains'][$card==='nip_at_the_heels_blue'?1:4][]=$other;
            FaBResolveDeclaredReaction(1,$GLOBALS['source']);
            ComponentCheck($GLOBALS['target']->TurnEffects===[]&&$other->TurnEffects===[],'invalid original target never applies or retargets');$profiles[]=$currentProfile;
        }
        foreach(['PASS','stale-index','wrong-link','wrong-source','extra-parts','wrong-executor','wrong-snapshot']as$case){
            $currentProfile=['card'=>$card,'case'=>'declaration-'.$case];ResetReactionComponent($card);FaBPrepareDeclaredReaction(1,$GLOBALS['source']);
            $answer=$GLOBALS['queues'][1][0]->Param;$parts=['6'];
            if($case==='PASS'||$case==='wrong-snapshot')$answer='PASS';
            if($case==='stale-index')$GLOBALS['target']->UniqueID=9;
            if($case==='wrong-link')$GLOBALS['target']->ChainLink=2;
            if($case==='wrong-source')$parts=['999'];
            if($case==='extra-parts')$parts=['6','extra'];
            if($case==='wrong-executor')$GLOBALS['executing']=2;
            if($case==='wrong-snapshot')$GLOBALS['snapshot']='overwritten-snapshot';
            FaBContinueDeclaredReaction(1,$parts,$answer);
            ComponentCheck($GLOBALS['payments']===0,'invalid declaration never completes payment');
            ComponentCheck(!isset($GLOBALS['source']->Params['declaredReactionTarget']),'invalid declaration never binds a target');
            $expectedRollback=in_array($case,['PASS','stale-index','wrong-link'],true)?1:0;
            ComponentCheck($GLOBALS['rollbacks']===$expectedRollback,'rollback only exact source/continuation/matching Undo hash');$profiles[]=$currentProfile;
        }
        $currentProfile=['card'=>$card,'case'=>'save-remap-and-reload'];ResetReactionComponent($card,true);FaBPrepareDeclaredReaction(1,$GLOBALS['source']);
        $original=$GLOBALS['queues'][1];$projected=FaBDeclaredReactionChoicesForSave($original,1);
        $seat=$card==='nip_at_the_heels_blue'?1:4;
        ComponentCheck($original[0]->Param==='p'.$seat.'CombatChain-1','original raw coordinates retained');
        ComponentCheck($projected[0]->Param==='p'.$seat.'CombatChain-0','writer projects same target through removed-slot compaction');
        $GLOBALS['chains'][$seat]=array_values(array_filter($GLOBALS['chains'][$seat],fn($o)=>!$o->Removed()));$GLOBALS['queues'][1]=$projected;
        FaBContinueDeclaredReaction(1,['6'],$projected[0]->Param);
        ComponentCheck($GLOBALS['payments']===1&&$GLOBALS['source']->Params['declaredReactionTarget']===['uid'=>7,'chainLink'=>1],'reloaded compact coordinates bind original UID/link');$profiles[]=$currentProfile;
    }
    foreach([false,true]as$attackAction){
        $currentProfile=['card'=>'nip_at_the_heels_blue','case'=>'Snag-tag-pipeline','attack_action'=>$attackAction];ResetReactionComponent();
        if($attackAction){$GLOBALS['target']->CardID='wounding_blow_blue';$GLOBALS['target']->Counters=[];}
        PrepareAndBindReaction();FaBResolveDeclaredReaction(1,$GLOBALS['source']);
        ComponentCheck(HasTag($GLOBALS['target'],'WTR_POWER:1')&&HasTag($GLOBALS['target'],'CRU_SUPPRESS_POWER:1'),'actual reaction tagging preserves native Snag marker');
        $base=CardPower($GLOBALS['target']->CardID);$power=$base+FaBWTRTagValue($GLOBALS['target'],'WTR_POWER')+FaBCRUPower(1,$GLOBALS['target']);
        ComponentCheck($power===$base+1,'normal positive amount');$GLOBALS['snag']=true;
        $power=$base+FaBWTRTagValue($GLOBALS['target'],'WTR_POWER')+FaBCRUPower(1,$GLOBALS['target']);
        ComponentCheck($power===($attackAction?$base:$base+1),'Snag suppresses new AA power but not weapon proxy');$profiles[]=$currentProfile;
    }
    $status='PASS';$error=null;
}catch(Throwable$e){$status='FAIL';$error=$e->getMessage();}
echo json_encode(['status'=>$status,'mutant'=>$mutant,'checks'=>$component_checks,'profiles'=>$profiles,'current_profile'=>$currentProfile,'error'=>$error,'function_hashes'=>$component_function_hashes,'scope'=>'Actual extracted declaration/continuation/target legality/revalidation/writer projection and native CRU tag-power functions. External queue transport, zone storage, Undo restore and payment completion are mocked. No supported user action, fixture, serialization implementation, game integration or full target-universe claim.'],JSON_PRETTY_PRINT).PHP_EOL;
exit($status==='PASS'?0:1);
