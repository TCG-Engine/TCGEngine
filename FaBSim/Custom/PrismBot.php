<?php
function FaBIsPrismBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='prism';}
function FaBPrismKeepValue(object $o,int $p): float {
    $b=FaBWTRBase($o->CardID);
    if($b==='the_librarian')return count(FaBChoiceRefs($p,'Deck'))>12?12:0;
    $v=floatval(CardPower($o->CardID))-FaBCardCost($o,$p);
    if(str_starts_with($b,'herald_')||$b==='wartune_herald')$v+=3;
    if($b==='herald_of_protection')$v+=2;
    if($b==='ode_to_wrath')$v=8;
    if($b==='merciful_retribution')$v=5;
    if(intval(CardPitch($o->CardID))===3)$v+=1;
    return $v;
}
function FaBPrismFollowup(int $p,int $exclude=0,int $reserve=0): bool {
    foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z,['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];
        if(intval($o->UniqueID)!==$exclude&&FaBAvailablePitch($p,$z==='Hand'?intval($o->UniqueID):0)>=$reserve+FaBCardCost($o,$p))return true;
    }return false;
}
function FaBPrismMentorReady(int $p): bool {
    foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'the_librarian']) as $r){$o=FaBIdentityFromMZ($r)['object'];
        if(intval($o->FaceDown??1)===0&&!HasNoAbilities($o)&&intval(FaBObjectCounters($o)['LIBRARIAN_TURN']??-1)!==intval(GetTurnNumber()))return true;
    }return false;
}
function FaBPrismPlayScore(int $p,object $o,string $zone): float {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$mine=$p===intval(GetTurnPlayer());
    if(FaBWTRIsAttackAction($o))return 8+FaBPrismKeepValue($o,$p)+(FaBAttackHasGoAgain(array_replace($s,['attacker'=>$p]),$o)?6:0);
    if(in_array($b,['seek_enlightenment','phantasmify'],true))return $mine&&FaBPrismFollowup($p,intval($o->UniqueID),FaBCardCost($o,$p))?30:-100;
    if($b==='prismatic_shield')return FaBPrismMentorReady($p)?35:((FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>FaBDefenseValue($s,$p))||($mine&&!FaBPrismFollowup($p,intval($o->UniqueID)))?12:-100);
    if(in_array($b,['ode_to_wrath','merciful_retribution'],true))return $mine?($b==='ode_to_wrath'?26:18):-100;
    return -100;
}
function FaBPrismAbilityScore(int $p,object $o): float {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$mine=$p===intval(GetTurnPlayer());
    if($b==='prism')return FaBPrismMentorReady($p)?38:((FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>FaBDefenseValue($s,$p))||($mine&&!FaBPrismFollowup($p)&&(!FaBChoiceRefs($p,'Arena')||intval(GetResources($p))>=2))?10:-100);
    if(!$mine)return -100;
    $f=FaBFindUID(intval($o->UniqueID));
    if($f&&$f['zone']==='Arena')return FaBPrismFollowup($p,0,3)?27:6;
    if($b==='heartened_cross_strap')return FaBPrismFollowup($p)?40:-100;
    if($b==='dream_weavers')return FaBPrismFollowup($p)?39:-100;
    if($b==='halo_of_illumination')return FaBHandCount($p)>1&&!FaBPrismFollowup($p)?15:-100;
    return -100;
}
function FaBPrismChoice(int $p,object $d): ?string {
    if($d->Type==='MZMODAL'&&$d->Tooltip==='Reveal_your_mentor')return '1';
    if(in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true)){
        $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));
        if($refs&&in_array(FaBIdentityFromMZ($refs[0])['zone'],['Temp','Graveyard'],true)){
            usort($refs,fn($a,$b)=>FaBPrismKeepValue(FaBIdentityFromMZ($b)['object'],$p)<=>FaBPrismKeepValue(FaBIdentityFromMZ($a)['object'],$p));return $refs[0];
        }
    }return null;
}
