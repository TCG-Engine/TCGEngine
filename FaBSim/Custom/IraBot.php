<?php
function FaBIsIraBot(int $player): bool {return (FaBGetState()['botProfiles'][(string)$player]??'')==='ira';}
function FaBIraKeepValue(object $o,int $player): float {
    $base=FaBWTRBase($o->CardID);
    if($base==='tiger_eye_reflex')return 2;
    if($base==='blessing_of_qi')return 2;
    if($base==='predatory_streak')return 3;
    $value=floatval(CardPower($o->CardID))-FaBCardCost($o,$player);
    if(FaBPrintedKeywordIsActive($o->CardID,'Go again'))$value+=3;
    if(in_array($base,['pouncing_qi','qi_unleashed'],true))$value+=2;
    if($base==='growl')$value+=2;
    return $value;
}
function FaBIraTigerOptions(int $player): array {
    $out=[];
    foreach(['Hand','Arsenal','Banish'] as $zone)foreach(FaBChoiceRefs($player,$zone,['base'=>'crouching_tiger']) as $ref){
        $o=FaBIdentityFromMZ($ref)['object'];
        if($zone!=='Banish'||intval($o->PlayableFromBanish??0)>0)$out[]=$ref;
    }
    return $out;
}
function FaBIraComboInHand(int $player): bool {
    foreach(['Hand','Arsenal'] as $zone)foreach(FaBChoiceRefs($player,$zone) as $ref){
        $o=FaBIdentityFromMZ($ref)['object'];
        if(in_array(FaBWTRBase($o->CardID),['pouncing_qi','qi_unleashed','mauling_qi'],true)&&FaBCardCost($o,$player)<=max(0,FaBAvailablePitch($player)-($zone==='Hand'?intval(CardPitch($o->CardID)):0)))return true;
    }
    return false;
}
function FaBIraPlayScore(int $player,object $o,string $zone): float {
    $s=FaBGetState();$base=FaBWTRBase($o->CardID);$tigers=FaBIraTigerOptions($player);
    $combo=FaBPreviousAttackBase()==='crouching_tiger';$hand=FaBHandCount($player);
    if($base==='predatory_streak'){
        // A non-attack closes the chain: generate before attacking, not between combo links.
        if($s['combatOpen']&&($combo||count($tigers)>0))return -10;
        return $tigers?3:22;
    }
    if($base==='blessing_of_qi')return $hand<=2?4:0;
    if($base==='crouching_tiger'){
        $buff=FaBARCEffect($player,'IRA_NEXT_TIGER')+FaBARCEffect($player,'IRA_CHAIN_TIGER');
        if($combo&&FaBIraComboInHand($player))return 6;
        return 12+($buff?14:0)+(FaBCRUCount($player,'ATTACKS')===1?8:0)+(FaBIraComboInHand($player)?5:0);
    }
    if($base==='pouncing_qi')return $combo?35:1;
    if($base==='growl')return 20+($tigers?2:0)+intval(CardPower($o->CardID));
    if(in_array($base,['qi_unleashed','mauling_qi'],true))return $combo?23+($base==='mauling_qi'?count(FaBOpponents($player)):0):($tigers?-5:3);
    $value=FaBIraKeepValue($o,$player)+3;
    if(FaBAttackHasGoAgain(array_replace($s,['attacker'=>$player]),$o))$value+=10;
    else{
        if($hand>2||$tigers)$value-=5;
        if($base==='flying_kick'&&intval($s['chainLink'])>=2)$value+=2;
        if($base==='salt_the_wound')$value+=intval($s['chainHits']??0);
    }
    if($base==='flex_claws')$value+=2;
    return $value;
}
function FaBIraAbilityScore(int $player,object $o,float $fallback): float {
    $s=FaBGetState();
    if($player!==intval(GetTurnPlayer())||$s['window']!=='ACTION')return -100;
    $tigers=FaBIraTigerOptions($player);
    return match($o->CardID){
        'mask_of_three_tails'=>40,
        'blood_scent'=>intval(GetResources($player))<1&&(FaBHandCount($player)>0||FaBChoiceRefs($player,'Weapons'))?38:-100,
        'tearing_shuko'=>$tigers?38:-100,
        'pouncing_paws'=>!$tigers&&intval(GetActionPoints($player))>0&&(FaBIraComboInHand($player)||FaBARCEffect($player,'IRA_CHAIN_TIGER')>0||FaBCRUCount($player,'ATTACKS')===1)?30:-100,
        'edge_of_autumn'=>FaBPreviousAttackBase()==='crouching_tiger'&&FaBIraComboInHand($player)?0:($tigers||FaBHandCount($player)>1?10:2),
        default=>$fallback,
    };
}
