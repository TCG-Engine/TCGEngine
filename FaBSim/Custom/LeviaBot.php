<?php
function FaBIsLeviaBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='levia';}
function FaBLeviaGraveCost(string $id): int {
    return match(FaBWTRBase($id)){'boneyard_marauder','convulsions_from_the_bellows_of_hell','dread_screamer','endless_maw','hungering_slaughterbeast','unworldly_bellow','writhing_beast_hulk'=>3,'soul_harvest'=>6,default=>0};
}
function FaBLeviaSixChance(int $p,int $draws=3): float {
    $refs=FaBChoiceRefs($p,'Graveyard');$count=count($refs);$six=0;
    foreach($refs as $r)if(FaBMONBasePower($p,FaBIdentityFromMZ($r)['object'])>=6)++$six;
    if($count<$draws||!$six)return 0;
    $miss=1.0;for($i=0;$i<$draws;++$i)$miss*=max(0,$count-$six-$i)/($count-$i);
    return 1-$miss;
}
function FaBLeviaKeepValue(object $o,int $p): float {
    $b=FaBWTRBase($o->CardID);$v=FaBMONBasePower($p,$o)-FaBCardCost($o,$p);
    if($b==='lady_barthimont')return count(FaBChoiceRefs($p,'Deck'))>12?10:0;
    if($b==='blood_tribute')return -1;
    if($b==='dread_screamer')$v+=3*FaBLeviaSixChance($p);
    if($b==='endless_maw')$v+=3*FaBLeviaSixChance($p);
    if($b==='graveling_growl')$v+=FaBMONCount($p,'BANISHED_SIX')?2:-4;
    if($b==='soul_harvest')$v=count(FaBChoiceRefs($p,'Graveyard'))>=6?6:0;
    if(in_array($b,['unworldly_bellow','convulsions_from_the_bellows_of_hell'],true))$v=1;
    if(intval(CardPitch($o->CardID))===3)$v+=1;
    return $v;
}
function FaBLeviaFollowups(int $p,int $exclude=0,int $resourceReserve=0,int $graveReserve=0): int {
    $count=0;$grave=count(FaBChoiceRefs($p,'Graveyard'))-$graveReserve;
    foreach(['Hand','Arsenal'] as $zone)foreach(FaBChoiceRefs($p,$zone,['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];
        if(intval($o->UniqueID)===$exclude||FaBLeviaGraveCost($o->CardID)>$grave)continue;
        if(FaBWTRBase($o->CardID)==='graveling_growl'&&!FaBMONCount($p,'BANISHED_SIX'))continue;
        if(FaBAvailablePitch($p,$zone==='Hand'?intval($o->UniqueID):0)>=$resourceReserve+FaBCardCost($o,$p))++$count;
    }return $count;
}
function FaBLeviaPlayScore(int $p,object $o,string $zone): float {
    $b=FaBWTRBase($o->CardID);$debt=FaBMONBloodDebt($p);$safe=FaBMONCount($p,'BANISHED_SIX')>0;$chance=FaBLeviaSixChance($p);$s=FaBGetState();
    if($b==='blood_tribute')return $p===intval(GetTurnPlayer())&&!$safe&&$debt>0&&count(FaBChoiceRefs($p,'Deck'))>0&&FaBLeviaFollowups($p,intval($o->UniqueID))===0?25:-100;
    if(in_array($b,['unworldly_bellow','convulsions_from_the_bellows_of_hell'],true)){
        $follow=FaBLeviaFollowups($p,intval($o->UniqueID),FaBCardCost($o,$p),3);
        return $follow?26+(!$safe&&$debt?10*$chance:0):(!$safe&&$debt>=intval(GetHealth($p))?20:-100);
    }
    if(!FaBWTRIsAttackAction($o))return -100;
    $v=FaBLeviaKeepValue($o,$p)+4;
    if(FaBLeviaGraveCost($o->CardID)&&!$safe)$v+=$chance*($debt?18:3);
    if($b==='dread_screamer'&&FaBLeviaFollowups($p,intval($o->UniqueID),FaBCardCost($o,$p),3)>0)$v+=12*$chance;
    if($b==='endless_maw')$v+=3*$chance;
    if($b==='writhing_beast_hulk')$v+=2*$chance;
    if($b==='graveling_growl')$v+=8;
    if($b==='deadwood_rumbler')$v+=count(FaBChoiceRefs($p,'Graveyard'))<3?5:0;
    if($b==='soul_harvest')$v+=min(5,count(FaBChoiceRefs(intval($s['defender']?:FaBOpponents($p)[0]),'Soul')));
    return $v;
}
function FaBLeviaAbilityScore(int $p,object $o): float {
    $s=FaBGetState();$mine=$p===intval(GetTurnPlayer());$b=FaBWTRBase($o->CardID);
    if($b==='rally_the_rearguard')return FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>FaBDefenseValue($s,$p)&&FaBHandCount($p)>0?22:-100;
    if(!$mine)return -100;
    if($b==='goliath_gauntlet')foreach(['Hand','Arsenal'] as $zone)foreach(FaBChoiceRefs($p,$zone,['attackAction'=>true,'minCost'=>2]) as $r){$f=FaBIdentityFromMZ($r);if(FaBLeviaGraveCost($f['object']->CardID)<=count(FaBChoiceRefs($p,'Graveyard'))&&FaBAvailablePitch($p,$zone==='Hand'?intval($f['object']->UniqueID):0)>=FaBCardCost($f['object'],$p))return 40;}
    if($b==='ravenous_meataxe')return count(FaBChoiceRefs($p,'Deck'))?2:0;
    if($b==='ebon_fold'&&!FaBMONCount($p,'BANISHED_SIX')&&FaBMONBloodDebt($p)>0){
        foreach(explode('&',FaBMONAffordableHand($p)) as $r){$f=FaBIdentityFromMZ($r);if($f&&FaBMONBasePower($p,$f['object'])>=6&&FaBAvailablePitch($p)-FaBMONPitchValue($p,$f['object']->CardID)>=1)return 36;}
    }
    return -100;
}
function FaBLeviaChoice(int $p,object $d): ?string {
    if($d->Type==='MZREARRANGE'&&str_starts_with($d->Param,'Top=')&&str_contains($d->Param,';Bottom=')){
        // Only inspect cards the opt decision actually revealed, never deck order.
        $top=[];$bottom=[];
        foreach(explode(';',$d->Param) as $pile)foreach(explode(',',explode('=',$pile,2)[1]??'') as $id){
            if($id==='')continue;
            if(intval(CardPower($id))>=6)$top[]=$id;else $bottom[]=$id;
        }
        return 'Top='.implode(',',$top).';Bottom='.implode(',',$bottom);
    }
    if($d->Type==='MZMODAL'&&$d->Tooltip==='Reveal_your_mentor')return '1';
    if($d->Type==='MZMODAL'&&$d->Tooltip==='Hooves_of_the_Shadowbeast'){
        $s=FaBGetState();$pending=$s['pendingPayment']??[];$top=FaBStackTop();
        $current=$top?$top->CardID:'';
        if(FaBWTRBase($current)==='dread_screamer'||($top&&FaBPrintedKeywordIsActive($current,'Go again')))return '0';
        return $p===intval(GetTurnPlayer())&&FaBLeviaFollowups($p,0,intval($pending['cost']??0))>0?'1':'0';
    }
    if($d->Type==='MZMULTICHOOSE'&&$d->Tooltip==='Banish_to_pay'){
        $parts=explode('|',$d->Param,3);$refs=explode('&',$parts[2]??'');
        usort($refs,function($a,$b)use($p){$score=function($r)use($p){$o=FaBIdentityFromMZ($r)['object'];return intval(FaBHasKeyword($o,'Blood Debt'))+(!FaBMONCount($p,'BANISHED_SIX')&&FaBMONBasePower($p,$o)>=6?2:0);};return $score($b)<=>$score($a);});
        return implode('&',array_slice($refs,0,intval($parts[0])));
    }
    if(in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true)&&in_array($d->Tooltip,['Pay_additional_cost','Pay_optional_cost','Banish_from_a_graveyard'],true)){
        $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));if(!$refs)return null;
        usort($refs,function($a,$b)use($p){$score=function($r)use($p){$f=FaBIdentityFromMZ($r);$o=$f['object'];return ($f['player']===$p&&!FaBMONCount($p,'BANISHED_SIX')&&FaBMONBasePower($p,$o)>=6?25:0)-FaBLeviaKeepValue($o,$p)+(FaBHasType($o,'Shadow')?2:0);};return $score($b)<=>$score($a);});return $refs[0];
    }
    return null;
}
