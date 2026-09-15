<?php
// Dromai's pinned Ashwing list. Scores use only our hand and public game state;
// all actions and chooser candidates are still validated by the shared engine.
function FaBIsDromaiBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='dromai';}
function FaBDromaiReadyDragons(int $p): int {
    return count(array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Dragon']),fn($r)=>FaBCRUWeaponReady(FaBIdentityFromMZ($r)['object'])&&!FaBUPRFrozen(FaBIdentityFromMZ($r)['object'])));
}
function FaBDromaiKeepValue(object $o,int $p): float {
    $b=FaBWTRBase($o->CardID);
    if($b==='rake_the_embers')return 11;
    if(str_starts_with($b,'invoke_'))return 8;
    if(in_array($b,['billowing_mirage','sweeping_blow','skittering_sands'],true))return 7;
    if($b==='sink_below')return GetHealth($p)<10?8:4;
    if($b==='silken_form')return 9;
    if($b==='deep_blue')return 10;
    if(str_starts_with($b,'ironhide_'))return 2;
    $v=floatval(CardPower($o->CardID))-FaBCardCost($o,$p);
    if(FaBPrintedKeywordIsActive($o->CardID,'Go again'))$v+=2;
    if(in_array($b,['sigil_of_solace','oasis_respite'],true))$v=4;
    if($b==='healing_balm')$v=1;
    if(intval(CardPitch($o->CardID))===3)$v+=3;
    return $v;
}
function FaBDromaiPitchScore(int $p,object $o): float {
    $pitch=intval(CardPitch($o->CardID));$ash=count(FaBMONArena($p,'ash'));
    // One red pitch can be worth an extra Ashwing. Once stocked, blue pays for
    // expensive attacks without exhausting the rest of the hand.
    return 100+5*$pitch-FaBDromaiKeepValue($o,$p)+($pitch===1&&$ash<2?18:0);
}
function FaBDromaiPlayScore(int $p,object $o,string $zone): float {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();$mine=$p===intval(GetTurnPlayer());
    $incoming=FaBIsDefendingHero($p,$s)?max(0,FaBAttackPower($s)-FaBDefenseValue($s,$p)):0;
    if($b==='sink_below')return $incoming>0?35+min(4,$incoming): -100;
    if($b==='oasis_respite')return $incoming>0&&in_array(FaBFindUID(FaBUPRHeroUID($p))['mzID']??'',explode('&',FaBARCHeroTargets($p,false)),true)?32:-100;
    if($b==='sigil_of_solace')return $mine?(!FaBUPRRed($p)?44:18):($incoming>0?28:-100);
    if(!$mine)return -100;
    $ready=FaBDromaiReadyDragons($p);
    if($b==='rake_the_embers')return 45+min(3,count(FaBMONArena($p,'ash')));
    if($b==='skittering_sands'||str_starts_with($b,'invoke_'))return 40;
    if(FaBWTRIsAttackAction($o)){
        $go=FaBAttackHasGoAgain(array_replace($s,['attacker'=>$p]),$o);
        $v=($go?32:8)+FaBDromaiKeepValue($o,$p);
        if($go&&!FaBUPRRed($p)&&intval(CardPitch($o->CardID))===1)$v+=10;
        // Dragon activations score above finishers even when the hand has a
        // large printed attack. Never strand a ready swarm behind a finisher.
        if(!$go&&$ready>0&&GetActionPoints($p)<=1)$v=-20;
        if(in_array($b,['red_hot','lava_burst'],true)&&intval($s['chainLink'])>=3)$v+=5;
        return $v;
    }
    if($b==='healing_balm')return $ready>0&&GetActionPoints($p)<=1?-100:2;
    return -100;
}
function FaBDromaiAbilityScore(int $p,object $o): float {
    if($p!==intval(GetTurnPlayer()))return -100;
    $f=FaBFindUID(intval($o->UniqueID));$b=FaBWTRBase($o->CardID);
    if($f&&$f['zone']==='Arena'&&FaBHasType($o,'Dragon')){
        if(!FaBUPRRed($p)&&GetActionPoints($p)<=1){
            foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$h=FaBIdentityFromMZ($r)['object'];if(intval(CardPitch($h->CardID))===1&&CanPlayCard($p,$r)&&FaBDromaiPlayScore($p,$h,$z)>20)return -100;}
        }
        return (FaBUPRRed($p)?28:4)+floatval(CardPower($o->CardID));
    }
    if($b==='silken_form')return FaBUPRAsh($p)!==''&&FaBUPRRed($p)?36:-100;
    if($b==='deep_blue'&&FaBHandCount($p)>=2&&GetResources($p)<2&&FaBUPRAsh($p)!==''){
        foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z) as $r)if(FaBCardCost(FaBIdentityFromMZ($r)['object'],$p)>=2)return 50;
    }
    return -100;
}
function FaBDromaiBlockScore(int $p,object $o,string $zone): float {
    $s=FaBGetState();$remaining=max(0,FaBAttackPower($s)-FaBDefenseValue($s,$p));$def=FaBCurrentDefense($o,$p);$lethal=$remaining>=intval(GetHealth($p));
    if(str_starts_with($o->CardID,'ironhide_')&&FaBAvailablePitch($p)>0){
        // Reserve pitch for every Ironhide already declared this link.
        $owed=0;foreach(FaBChoiceRefs($p,'CombatChain') as $r){$c=FaBIdentityFromMZ($r)['object'];if(intval($c->ChainLink)===intval($s['chainLink'])&&str_starts_with($c->CardID,'ironhide_'))++$owed;}
        return $remaining>2*$owed&&FaBAvailablePitch($p)>$owed?12:-100;
    }
    if($zone==='Equipment'&&!$lethal)return -100;
    if($remaining<=0||$def<=0)return -100;
    return min($remaining,$def)*($lethal?12:3)-FaBDromaiKeepValue($o,$p)+(GetHealth($p)<9?5:0);
}
function FaBDromaiChoice(int $p,object $d): ?string {
    $tip=(string)$d->Tooltip;
    if($d->Type==='MZMODAL'){
        if($tip==='Pay_for_equipment_defense')return FaBAvailablePitch($p)>0?'1':'0';
        if($tip==='Gain_one_life')return '0';
    }
    if(!in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true))return null;
    $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));
    if(!$refs)return $d->Type==='MZMAYCHOOSE'?'PASS':null;
    if(str_contains(strtolower($tip),'pitch')){usort($refs,fn($a,$b)=>FaBDromaiPitchScore($p,FaBIdentityFromMZ($b)['object'])<=>FaBDromaiPitchScore($p,FaBIdentityFromMZ($a)['object']));return $refs[0];}
    if($tip==='Choose_hero'){foreach($refs as $r)if(FaBIdentityFromMZ($r)['player']===$p)return $r;return null;}
    if($tip==='Choose_damage_source'){
        $uid=intval(FaBGetState()['attackUID']);foreach($refs as $r)if(intval(FaBIdentityFromMZ($r)['object']->UniqueID)===$uid)return $r;
        foreach($refs as $r)if(FaBIdentityFromMZ($r)['player']!==$p)return $r;
    }
    if(in_array($tip,['Choose_ash','Transform_ash'],true)){
        foreach($refs as $r){$f=FaBIdentityFromMZ($r);if($f['player']===$p&&$f['object']->CardID==='ash')return $r;}return $d->Type==='MZMAYCHOOSE'?'PASS':null;
    }
    if(in_array($tip,['Choose_attack_target','Choose_damage_target'],true)){
        $score=function($r)use($p){$f=FaBIdentityFromMZ($r);if($f['player']===$p)return -1000;if($f['zone']==='Hero')return 100-intval(GetHealth($f['player']));return 10+floatval(CardPower($f['object']->CardID));};
        usort($refs,fn($a,$b)=>$score($b)<=>$score($a));return $refs[0];
    }
    // Bottoming, discarding and optional hand exchange keep the swarm engines.
    if(in_array($tip,['Bottom_card_as_cost','Put_a_card_on_the_bottom_of_your_deck'],true)){
        usort($refs,fn($a,$b)=>FaBDromaiKeepValue(FaBIdentityFromMZ($a)['object'],$p)<=>FaBDromaiKeepValue(FaBIdentityFromMZ($b)['object'],$p));
        return $d->Type==='MZMAYCHOOSE'&&FaBDromaiKeepValue(FaBIdentityFromMZ($refs[0])['object'],$p)>=7?'PASS':$refs[0];
    }
    return null;
}
