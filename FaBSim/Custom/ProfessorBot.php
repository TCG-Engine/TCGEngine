<?php

function FaBIsProfessorBot(int $player): bool {return (FaBGetState()['botProfiles'][(string)$player]??'')==='professor';}
function FaBProfessorKeepValue(object $o,int $player): float {
    $base=FaBWTRBase($o->CardID);$cost=FaBCardCost($o,$player);
    if(FaBHasType($o,'Evo'))return FaBEvoBase($player,$o)!==null?11:0;
    if(FaBHasType($o,'Block'))return floatval(CardDefense($o->CardID))+1;
    $v=floatval(CardPower($o->CardID))+FaBProfessorPower($player,$o)-$cost;
    if(FaBHasKeyword($o,'Boost'))$v+=2;
    if($base==='apocalypse_automaton')$v=FaBEvoCount($player)>0?6+2*min(FaBEvoCount($player),count(FaBOpponents($player))):0;
    if(intval(CardPitch($o->CardID))===3)$v+=2;
    return $v;
}
function FaBProfessorPlayScore(int $player,object $o,string $zone): float {
    $s=FaBGetState();$cost=FaBCardCost($o,$player);$hand=FaBHandCount($player);
    if(FaBHasType($o,'Evo')){
        if(FaBEvoBase($player,$o)===null)return -100;
        $weight=['evo_energy_matrix_blue'=>5,'evo_rapid_fire_blue'=>4,'evo_scatter_shot_blue'=>3,'evo_tekloscope_blue'=>1];
        return 28+($weight[$o->CardID]??0)-$cost+($zone==='Banish'?3:0);
    }
    $v=FaBProfessorKeepValue($o,$player)+3;
    if(FaBHasKeyword($o,'Boost')&&$hand>=2)$v+=8;
    if($o->CardID==='apocalypse_automaton_red')$v+=4*min(FaBEvoCount($player),count(FaBOpponents($player)));
    // A final attack should spend the hand efficiently; don't pitch away the attack chain.
    if(!FaBHasKeyword($o,'Boost')&&$hand>2)$v-=3;
    return $v;
}
function FaBProfessorBoostChoice(int $player): string {
    // No deck-order inspection. Only known hand, resources, weapon and public deck count.
    if(count(FaBChoiceRefs($player,'Deck'))<=1)return '1';
    $s=FaBGetState();$pending=$s['pendingPayment']??[];
    $budget=max(0,FaBAvailablePitch($player)-intval($pending['cost']??0));
    foreach(FaBChoiceRefs($player,'Hand') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if(!FaBHasType($o,'Action'))continue;
        if(FaBHasType($o,'Evo')&&FaBEvoBase($player,$o)===null)continue;
        if($o->CardID==='apocalypse_automaton_red'&&FaBEvoCount($player)<1)continue;
        // The proposed follow-up card itself cannot also be pitched to fund it.
        if(FaBCardCost($o,$player)<=max(0,$budget-intval(CardPitch($o->CardID))))return '0';
    }
    foreach(GetWeapons($player) as $o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='teklo_blaster'&&intval($o->Status)===2&&FaBTekloBlasterCost($player)<=$budget)return '0';
    return '1';
}
