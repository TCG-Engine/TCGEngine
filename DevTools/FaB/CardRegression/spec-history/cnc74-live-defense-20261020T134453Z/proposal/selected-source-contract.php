<?php
// Selected native branch + aggregator + damage-prefix; no engine includes or files.
$n=0;
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
function check($v,$m){++$GLOBALS['n'];if(!$v)throw new RuntimeException($m);}
function FaBMoveStackUID($uid,$zone,$p){$obj=clone $GLOBALS['stack'];$GLOBALS['stack']->removed=true;$obj->removed=false;$GLOBALS['chain'][$p][]=$obj;return $obj;}
function FaBCurrentAttackHasKeyword($s,$k){return false;}
function FaBHandDefendingCount($s,$p){return 0;}
function FaBSetState($s){$GLOBALS['state']=$s;}
function FaBWTRApplyNextDefense($p,$o){++$GLOBALS['apply'];}
function FaBResolveRules($p,$src,$o){++$GLOBALS['rules'];}
function OnDefended($p,$ref,$same){$GLOBALS['defended']=[$p,$ref,$same];}
function SetPriorityPlayer($p){$GLOBALS['priority']=$p;}
function SetConsecutivePasses($p){$GLOBALS['passes']=$p;}
function FaBFindUID($uid){foreach($GLOBALS['chain']as$p=>$cards)foreach($cards as$i=>$o)if($o->UniqueID===$uid)return ['mzID'=>'p'.$p.'CombatChain-'.$i,'object'=>$o];return null;}
function FaBSeatOrder(){return [1,2];}
function GetCombatChain($p){return $GLOBALS['chain'][$p]??[];}
function FaBObjectCounters($o){return [];}
function FaBCurrentDefense($o,$p){return $GLOBALS['printed'][$o->CardID];}
function FaBAttackPower($s){return 4;}
function FaBEVRCount($p,$k){return 0;}
function FaBEVRClear($p,$k){}

function FaBDefenseValue(array $state, ?int $defender=null): int {
    $defender ??= intval($state['defender']);
    $total = 0;
    foreach (FaBSeatOrder() as $seat) foreach (GetCombatChain($seat) as $obj) {
        if (!is_object($obj) || !empty($obj->removed) || intval($obj->ChainLink ?? 0) !== intval($state['chainLink'])) continue;
        if (!in_array((string)($obj->Role ?? ''), ['DEFENSE', 'DEFENSE_REACTION'], true)) continue;
        if($seat===$defender||intval(FaBObjectCounters($obj)['DEFENDING_HERO']??0)===$defender)$total += FaBCurrentDefense($obj, $defender);
    }
    return $total;
}
function selectedReaction($controller,$obj,$state,$uid){$kind="DEFENSE_REACTION";
        $owner = $controller;
        if($kind==='DEFENSE_REACTION'&&$obj->SourceZone==='Hand'&&FaBCurrentAttackHasKeyword($state,'Dominate')&&FaBHandDefendingCount($state,$controller)>=1){
            FaBMoveStackUID($uid,'Graveyard',$owner);SetPriorityPlayer(intval($state['attacker']));SetConsecutivePasses(0);return true;
        }
        $chain = FaBMoveStackUID($uid, 'CombatChain', $owner);
        if($kind==='DEFENSE_REACTION'&&$obj->SourceZone==='Hand'){$state['handBlockUIDs'][]=$uid;FaBSetState($state);}
        if ($chain !== null) { $chain->Role = $kind; $chain->ChainLink = intval($state['chainLink']); $chain->FromZone=(string)$obj->SourceZone; if($kind==='DEFENSE_REACTION')FaBWTRApplyNextDefense($controller,$chain); }
        FaBResolveRules($controller, $obj, $chain);
        if ($kind === 'DEFENSE_REACTION' && $chain !== null) OnDefended($controller, FaBFindUID($uid)['mzID'], $controller);
        SetPriorityPlayer(intval($state['attacker'])); SetConsecutivePasses(0);

}
function selectedDamagePrefix($state){
    $power = FaBAttackPower($state); $defense = FaBDefenseValue($state);
    $amount = FaBEVRCount(intval($state['attacker']),'SHATTER_REPLACE')?0:max(0, $power - $defense);FaBEVRClear(intval($state['attacker']),'SHATTER_REPLACE'); $attack = FaBFindUID(intval($state['attackUID']));
    $state['combatStep'] = 'DAMAGE'; $state['window'] = 'DAMAGE';
    $state['attackPower'] = $power; $state['defenseValue'] = $defense; $state['damageDealt'] = $amount;
    FaBSetState($state);
return $state;
}

$GLOBALS['printed']=['dodge_blue'=>2,'wounding_blow_blue'=>3];
foreach(['Arsenal','Hand']as$from){
 $GLOBALS['state']=['attacker'=>1,'defender'=>2,'chainLink'=>2,'attackUID'=>5,'handBlockUIDs'=>[],'defenseValue'=>0,'window'=>'REACTION','combatStep'=>'REACTION'];
 $GLOBALS['chain']=[1=>[],2=>[(object)['CardID'=>'wounding_blow_blue','UniqueID'=>7,'ChainLink'=>1,'Role'=>'DEFENSE','removed'=>false],(object)['CardID'=>'wounding_blow_blue','UniqueID'=>8,'ChainLink'=>1,'Role'=>'DEFENSE','removed'=>false]]];
 $GLOBALS['stack']=(object)['CardID'=>'dodge_blue','UniqueID'=>6,'Owner'=>2,'Controller'=>2,'SourceZone'=>$from,'removed'=>false];$GLOBALS['rules']=0;$GLOBALS['apply']=0;
 selectedReaction(2,$GLOBALS['stack'],$GLOBALS['state'],6);
 $d=$GLOBALS['chain'][2][2];
 foreach(['CardID'=>'dodge_blue','UniqueID'=>6,'Owner'=>2,'Controller'=>2,'Role'=>'DEFENSE_REACTION','FromZone'=>$from,'ChainLink'=>2]as$k=>$v)check($d->$k===$v,'Native moved physical property '.$k);
 check($GLOBALS['state']['defenseValue']===0,'Cached defense not refreshed by selected reaction branch');
 check(FaBDefenseValue($GLOBALS['state'])===2,'Live defense2 excludes previous-link six defense');
 check($GLOBALS['apply']===1&&$GLOBALS['rules']===1,'Canonical apply/rules callbacks retained');
 check($GLOBALS['defended']===[2,'p2CombatChain-2',2],'Canonical OnDefended physical reference');
 check($GLOBALS['priority']===1&&$GLOBALS['passes']===0,'Normal attacker priority and pass reset');
 check($GLOBALS['state']['handBlockUIDs']===($from==='Hand'?[6]:[]),'Actual hand-only accounting');
 $s=selectedDamagePrefix($GLOBALS['state']);
 foreach(['combatStep'=>'DAMAGE','window'=>'DAMAGE','attackPower'=>4,'defenseValue'=>2,'damageDealt'=>2]as$k=>$v)check($s[$k]===$v,'Real damage prefix numerical property '.$k);
}
echo json_encode(['status'=>'PASS','checks'=>$GLOBALS['n'],'scope'=>'Selected exact native DEFENSE_REACTION body, live defense aggregator and initial damage arithmetic/cache prefix. Stub movement/globals/printed-base lookup; no full engine action, replay, modifier coverage or native writes.'])."\n";
