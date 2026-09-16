<?php

// Equipped cards may be defending on the chain; cards defended from hand are not equipped.
function FaBProfessorEquipped(int $player): array {
    $cards=[];
    foreach(['Equipment','CombatChain'] as $zone)foreach(FaBZoneGet($zone,$player) as $o){
        if(!is_object($o)||!empty($o->removed))continue;
        if($zone==='CombatChain'&&($o->FromZone??'')!=='Equipment')continue;
        $cards[]=$o;
    }
    return $cards;
}
function FaBProfessorActive(int $player): bool {
    foreach(GetHero($player) as $o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='professor_teklovossen'&&!HasNoAbilities($o))return true;
    return false;
}
function FaBEvoCount(int $player): int {return (FaBMONHero($player,'teklovossen_the_mechropotent')?4:0)+count(array_filter(FaBProfessorEquipped($player),fn($o)=>FaBHasType($o,'Evo')));}
function FaBEvoActive(int $player,string $id): bool {
    foreach(FaBProfessorEquipped($player) as $o)if($o->CardID===$id&&!HasNoAbilities($o))return true;
    return false;
}
function FaBEvoBase(int $player,object $evo): ?object {
    foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBHasType($evo,$slot)){
        foreach(FaBProfessorEquipped($player) as $o)if(FaBHasType($o,'Base')&&FaBHasType($o,$slot))return $o;
    }
    return null;
}
function FaBEvoEquip(int $player,string $mzID): void {
    FaBEVOTransform($player,$mzID);
}
function FaBProfessorCost(int $player,object $o): int {
    if(FaBHasType($o,'Evo')&&FaBProfessorActive($player))return -count(FaBOpponents($player));
    if(FaBWTRBase($o->CardID)==='liquid_cooled_mayhem')return -FaBEvoCount($player);
    return 0;
}
function FaBProfessorPower(int $player,object $o): int {
    if(FaBWTRBase($o->CardID)==='mechanical_strength')return in_array($o->Role??'', ['DEFENSE','DEFENSE_REACTION'],true)?0:FaBEvoCount($player);
    return $o->CardID==='teklo_blaster'&&FaBEvoActive($player,'evo_scatter_shot_blue')?count(FaBOpponents($player)):0;
}
function FaBTekloBlasterCost(int $player): int {return max(0,3-(FaBEvoActive($player,'evo_energy_matrix_blue')?count(FaBOpponents($player)):0));}
function FaBFirewall(int $player): void {
    $refs=FaBChoiceRefs($player,'Deck');if(!$refs)return;
    $o=FaBIdentityFromMZ($refs[0])['object'];FaBRevealChoices($player,$refs[0]);
    if(!FaBHasType($o,'Evo'))FaBARCToDeck($player,intval($o->UniqueID),false);
}
function FaBProfessorAnyHero(int $player,string $id): bool {
    return $id==='apocalypse_automaton_red'||($id==='teklo_blaster'&&FaBEvoActive($player,'evo_tekloscope_blue'));
}
function FaBProfessorAttackTargets(int $player,int $sourceUID): array {
    $f=FaBFindUID($sourceUID);
    if($f===null||!FaBProfessorAnyHero($player,$f['object']->CardID))return FaBLegalAttackTargets($player);
    $out=[];foreach(FaBOpponents($player) as $seat)foreach(FaBChoiceRefs($seat,'Hero') as $ref){
        $target=FaBAttackTargetDescriptor(FaBIdentityFromMZ($ref));$target['anyHero']=true;$out[]=$target;
    }
    return $out;
}
function FaBAutomatonTargets(int $player): string {
    $refs=[];foreach(FaBOpponents($player) as $seat)$refs=array_merge($refs,FaBChoiceRefs($seat,'Hero'));return implode('&',$refs);
}
function FaBAutomatonPrepare(int $player,int $uid,string $choices): void {
    $f=FaBFindUID($uid);if($f===null||$f['zone']!=='Stack')return;
    $targets=[];$allowed=explode('&',FaBAutomatonTargets($player));
    foreach(array_unique(explode('&',$choices)) as $ref)if(in_array($ref,$allowed,true)){
        $target=FaBAttackTargetDescriptor(FaBIdentityFromMZ($ref));$target['anyHero']=true;$targets[]=$target;
    }
    $targets=array_slice($targets,0,FaBEvoCount($player));
    // The order of selection does not change clockwise declaration order.
    $order=FaBSeatOrder();$pivot=array_search($player,$order,true);$order=array_merge(array_slice($order,$pivot+1),array_slice($order,0,$pivot));
    usort($targets,fn($a,$b)=>array_search($a['player'],$order,true)<=>array_search($b['player'],$order,true));
    $f['object']->Params['attackTargets']=$targets;$f['object']->Params['attackTarget']=$targets[0]??null;
    FaBFinishPreparedCard($uid);
}
