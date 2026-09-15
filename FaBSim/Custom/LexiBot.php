<?php
// Shiver Lexi: only inspect our hand and public zones; legality stays in the engine.
function FaBIsLexiBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='lexi';}
function FaBLexiKeepValue(object $o,int $p): float {
    $b=FaBWTRBase($o->CardID);$v=floatval(CardPower($o->CardID))-FaBCardCost($o,$p);
    if(FaBHasType($o,'Arrow'))$v+=3;
    if(in_array($b,['boltn_shot','dazzling_crescendo','entwine_lightning'],true))$v+=2;
    if(in_array($b,['electrify','weave_lightning','take_aim','over_flex'],true))$v=4;
    if(FaBELEElement($o,'Lightning|Ice'))$v+=1;
    if(intval(CardPitch($o->CardID))===3)$v+=2;
    return $v;
}
function FaBLexiFollowup(int $p,int $exclude=0,int $reserve=0): bool {
    foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z,['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];
        if(intval($o->UniqueID)===$exclude)continue;
        if($z==='Hand'&&FaBHasType($o,'Arrow')&&!FaBELEArsenalSpace($p))continue;
        if(FaBAvailablePitch($p,$z==='Hand'?intval($o->UniqueID):0)>=$reserve+FaBCardCost($o,$p)+($z==='Hand'&&FaBHasType($o,'Arrow')?1:0))return true;
    }return false;
}
function FaBLexiArsenalScore(object $o,int $p): float {
    return FaBLexiKeepValue($o,$p)+10+(FaBHasType($o,'Lightning')?5:0)+(FaBWTRBase($o->CardID)==='electrify'?4:0);
}
function FaBLexiPlayScore(int $p,object $o,string $zone): float {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$mine=$p===intval(GetTurnPlayer());
    if($b==='lightning_press')return intval($s['attacker'])===$p&&FaBAttackPower($s)<=FaBDefenseValue($s,intval($s['defender']))+3?18:-100;
    if($b==='pitfall_trap')return FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>FaBDefenseValue($s,$p)?20:-100;
    if(!$mine)return -100;
    if(FaBWTRIsAttackAction($o))return 10+FaBLexiKeepValue($o,$p)+(FaBPrintedKeywordIsActive($o->CardID,'Go again')?5:0)+($zone==='Arsenal'?3:0);
    if(in_array($b,['electrify','weave_lightning','take_aim','over_flex','chill_to_the_bone'],true))return FaBLexiFollowup($p,intval($o->UniqueID),FaBCardCost($o,$p))?30:-100;
    if($b==='amulet_of_lightning')return FaBLexiFollowup($p,intval($o->UniqueID))||FaBHandCount($p)===1?22:-100;
    if($b==='winters_bite')return 8;
    return -100;
}
function FaBLexiAbilityScore(int $p,object $o): float {
    if($p!==intval(GetTurnPlayer()))return -100;
    $b=FaBWTRBase($o->CardID);
    if($b==='lexi')foreach(FaBChoiceRefs($p,'Arsenal') as $r){$a=FaBIdentityFromMZ($r)['object'];if(intval($a->FaceDown??1)&&FaBELEElement($a,'Lightning|Ice'))return 50;}
    if($b==='shiver'&&FaBELEArsenalSpace($p))foreach(FaBChoiceRefs($p,'Hand') as $r){$a=FaBIdentityFromMZ($r)['object'];if(FaBHasType($a,'Arrow')&&FaBAvailablePitch($p,intval($a->UniqueID))>=1+FaBCardCost($a,$p))return 25;}
    if($o->CardID==='deep_blue')return intval(GetResources($p))===0&&FaBHandCount($p)>=2&&FaBELESelect($p,'Hand','','AA')!==''&&FaBLexiFollowup($p)?40:-100;
    if($b==='honing_hood'&&FaBChoiceRefs($p,'Arsenal')&&FaBELESelect($p,'Hand','','Arrow')!==''){
        foreach(FaBChoiceRefs($p,'Arsenal') as $r)if(CanPlayCard($p,$r))return -100;
        return 12;
    }
    if($b==='amulet_of_lightning'){$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));return $a&&intval($s['attacker'])===$p&&!FaBAttackHasGoAgain($s,$a['object'])&&FaBLexiFollowup($p)?35:-100;}
    return -100;
}
function FaBLexiChoice(int $p,object $d): ?string {
    if($d->Type==='MZMODAL'){
        if($d->Tooltip==='Choose_arrow_bonus')return '0'; // Power also turns on Bolt'n Shot's go again.
        if($d->Tooltip==='Mark_of_Lightning')return '1';
    }
    if(!in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true))return null;
    $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));
    if(in_array($d->Tooltip,['Empower_attack','Grant_go_again'],true)){
        $refs=array_values(array_filter($refs,fn($r)=>FaBIdentityFromMZ($r)['player']===$p));
        return $refs[0]??($d->Type==='MZMAYCHOOSE'?'PASS':null);
    }
    if(in_array($d->Tooltip,['Load_arrow','Reload','Reload_a_card_face_down','Put_card_in_arsenal'],true)&&$refs){
        $score=function($r)use($p){$o=FaBIdentityFromMZ($r)['object'];return FaBLexiKeepValue($o,$p)+(FaBHasType($o,'Arrow')?20:0)-(FaBAvailablePitch($p,intval($o->UniqueID))<FaBCardCost($o,$p)?30:0);};
        usort($refs,fn($a,$b)=>$score($b)<=>$score($a));return $refs[0];
    }
    return null;
}
