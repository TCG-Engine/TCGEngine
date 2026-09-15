<?php
function FaBIsBoltynBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='boltyn';}
function FaBBoltynKeepValue(object $o,int $p): float {
    $b=FaBWTRBase($o->CardID);$v=floatval(CardPower($o->CardID))-FaBCardCost($o,$p);
    if(in_array($b,['beaming_bravado','bolt_of_courage','engulfing_light','light_the_way','take_flight'],true))$v+=3;
    if($b==='duty_bound_blitz')$v+=FaBMONCount($p,'YELLOW_SOUL')?5:0;
    if($b==='v_of_the_vanguard')$v+=4;
    if($b==='valiant_thrust')$v+=FaBMONCount($p,'CHARGED')?4:0;
    if($b==='roaring_beam')$v=FaBMONSoul($p)===''?7:3;
    if($b==='courageous_steelhand')$v=FaBMONCount($p,'CHARGED')?5:1;
    if($b==='edict_of_steel')$v=5;
    if($b==='banneret_of_salvation')$v=2;
    return $v;
}
function FaBBoltynFollowups(int $p,int $exclude=0): int {
    $n=0;
    foreach(['Hand','Arsenal'] as $zone)foreach(FaBChoiceRefs($p,$zone,['attackAction'=>true]) as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(intval($o->UniqueID)===$exclude)continue;
        if(FaBWTRBase($o->CardID)==='duty_bound_blitz'&&!FaBMONCount($p,'YELLOW_SOUL'))continue;
        if(FaBCardCost($o,$p)<=FaBAvailablePitch($p,$zone==='Hand'?intval($o->UniqueID):0))++$n;
    }
    foreach(FaBChoiceRefs($p,'Weapons') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(FaBCRUWeaponReady($o)&&FaBMONCount($p,'CHARGED'))++$n;}
    return $n;
}
function FaBBoltynPlayScore(int $p,object $o,string $zone): float {
    $s=FaBGetState();$b=FaBWTRBase($o->CardID);$charged=FaBMONCount($p,'CHARGED')>0;$hand=FaBHandCount($p);
    $follow=FaBBoltynFollowups($p,intval($o->UniqueID));
    if($b==='courageous_steelhand'){
        if(!$charged)return -100;
        $a=FaBFindUID(intval($s['attackUID']));$remaining=FaBAttackPower($s)-FaBDefenseValue($s,intval($s['defender']));
        return $remaining<=0||($a&&$follow&&!FaBAttackHasGoAgain($s,$a['object']))?28:-5;
    }
    if($b==='roaring_beam')return !$charged&&FaBMONSoul($p)===''?32:($follow?9:-10);
    if($b==='edict_of_steel')return $hand>=3||($charged&&FaBMONSoul($p)!=='')?27:3;
    if($b==='toe_the_line')return $p!==intval($s['attacker'])&&FaBAttackPower($s)>FaBDefenseValue($s,$p)?25:-100;
    if($b==='glisten')return $charged&&$p===intval(GetTurnPlayer())&&$follow?16:-100;
    if($b==='springboard_somersault')return FaBAttackPower($s)>FaBDefenseValue($s,$p)?20:-100;
    if(!FaBWTRIsAttackAction($o))return -20;
    if($b==='v_of_the_vanguard')return $hand>=3?38:2;
    $v=FaBBoltynKeepValue($o,$p)+5;
    if(!$charged&&in_array($b,['beaming_bravado','bolt_of_courage','engulfing_light','light_the_way','take_flight'],true)&&($zone==='Arsenal'?$hand>0:$hand>1))$v+=18;
    if($charged&&$b==='duty_bound_blitz')$v+=15;
    if($charged&&$b==='take_flight')$v+=12;
    if($charged&&$b==='valiant_thrust')$v+=7;
    if($b==='snatch'&&$follow===0)$v+=5;
    return $v;
}
function FaBBoltynAbilityScore(int $p,object $o): float {
    $s=FaBGetState();$mine=$p===intval(GetTurnPlayer());
    if(str_starts_with($o->CardID,'boltyn')||$o->CardID==='ser_boltyn_breaker_of_dawn'){
        foreach(GetStack() as $layer)if(empty($layer->removed)&&$layer->CardID===$o->CardID&&intval($layer->Controller)===$p)return -100;
        $a=FaBFindUID(intval($s['attackUID']));
        return $a&&!FaBAttackHasGoAgain($s,$a['object'])&&FaBBoltynFollowups($p)>0?45:-100;
    }
    if($o->CardID==='raydn_duskbane')return FaBMONCount($p,'CHARGED')?25:0;
    if($o->CardID==='flat_trackers')return $mine&&$s['window']==='ACTION'?22:-100;
    if($o->CardID==='garland_of_spring'){
        foreach(FaBChoiceRefs($p,'Hand') as $ref)if(FaBCardCost(FaBIdentityFromMZ($ref)['object'],$p)>intval(GetResources($p)))return $mine?40:-100;
        return -100;
    }
    if($o->CardID==='radiant_touch')return !$mine&&FaBAttackPower($s)-FaBDefenseValue($s,$p)>=2?25:-100;
    return -100;
}
function FaBBoltynChoice(int $p,object $d): ?string {
    if(!in_array($d->Tooltip,['Charge_your_soul','Charge_any_number'],true))return null;
    $multi=$d->Type==='MZMULTICHOOSE';$parts=$multi?explode('|',$d->Param,3):[];
    $refs=array_values(array_filter(explode('&',$multi?($parts[2]??''):$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));
    if(!$refs)return $multi?'':'PASS';
    if(!$multi&&$d->Type==='MZMAYCHOOSE'&&FaBMONCount($p,'CHARGED')&&FaBMONCount($p,'YELLOW_SOUL')&&count(FaBChoiceRefs($p,'Soul'))>=2)return 'PASS';
    usort($refs,function($a,$b)use($p){
        $score=function($r)use($p){$o=FaBIdentityFromMZ($r)['object'];return ($o->CardID==='banneret_of_salvation_yellow'?15:0)+(intval(CardPitch($o->CardID))===2?8:0)+(FaBHasType($o,'Light')?2:0)-FaBBoltynKeepValue($o,$p);};
        return $score($b)<=>$score($a);
    });
    if(!$multi)return $refs[0];
    $selected=[];$maximum=min(intval($parts[1]),max(1,FaBHandCount($p)-1),2);
    foreach($refs as $ref){
        if(count($selected)>=$maximum)break;
        $try=implode('&',array_merge($selected,[$ref]));
        if(GameValidateDecisionAnswer($p,$try))$selected[]=$ref;
    }
    return implode('&',$selected);
}
