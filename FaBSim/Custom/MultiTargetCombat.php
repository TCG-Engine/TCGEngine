<?php

function FaBIsDefendingHero(int $player,array $state): bool {
    foreach($state['attackTargets']??[$state['attackTarget']??[]] as $t)if(($t['type']??'')==='HERO'&&intval($t['player']??0)===$player&&FaBSeatIsLive($player))return true;
    return false;
}
function FaBNextDefendTarget(array $state): bool {
    $targets=$state['attackTargets']??[];
    for($i=intval($state['defendIndex']??0)+1;$i<count($targets);++$i){
        if(!FaBSeatIsLive(intval($targets[$i]['player'])))continue;
        $state['defendIndex']=$i;$state['defender']=intval($targets[$i]['player']);$state['attackTarget']=$targets[$i];
        $state['window']='DEFEND_DECLARE';$state['combatStep']='DEFEND';$state['defenseValue']=FaBDefenseValue($state);
        FaBSetState($state);SetPriorityPlayer($state['defender']);SetConsecutivePasses(0);return true;
    }
    return false;
}
function FaBRepairMultiTargetDefender(): void {
    $s=FaBGetState();if(count($s['attackTargets']??[])<2||FaBSeatIsLive(intval($s['defender'])))return;
    if($s['window']==='DEFEND_DECLARE'&&FaBNextDefendTarget($s))return;
    foreach($s['attackTargets'] as $t)if(FaBSeatIsLive(intval($t['player']))){
        $s['defender']=intval($t['player']);$s['attackTarget']=$t;
        if($s['window']==='DEFEND_DECLARE'){$s['window']='DEFEND_PRIORITY';SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);}
        FaBSetState($s);return;
    }
}
function FaBMultiTargetDamage(array $state): void {
    $attack=FaBFindUID(intval($state['attackUID']));$power=FaBAttackPower($state);$packets=[];
    // Calculate every packet before anyone loses life or is eliminated.
    foreach($state['attackTargets'] as $t){
        $p=intval($t['player']);if(!FaBSeatIsLive($p))continue;
        $defense=FaBDefenseValue($state,$p);$amount=max(0,$power-$defense);
        if($attack&&in_array('WTR_DOUBLE_DAMAGE',(array)$attack['object']->TurnEffects,true))$amount*=2;
        $packets[]=[$t,$defense,$amount];
    }
    $state['combatStep']='DAMAGE';$state['window']='DAMAGE';$state['attackPower']=$power;$state['targetDamage']=[];FaBSetState($state);
    $hits=[];$total=0;
    foreach($packets as [$t,$defense,$amount]){
        $p=intval($t['player']);$dealt=$attack?DoDamage(intval($state['attacker']),$attack['mzID'],$p,$amount,'PHYSICAL'):0;
        $s=FaBGetState();$s['targetDamage'][(string)$p]=['power'=>$power,'defense'=>$defense,'damage'=>$dealt];FaBSetState($s);
        if($dealt>0)$hits[]=[$t,$dealt];$total+=$dealt;
    }
    // Hit triggers are queued after the simultaneous damage event.
    foreach($hits as [$t,$dealt]){
        $s=FaBGetState();$s['defender']=intval($t['player']);$s['attackTarget']=$t;$s['attackHit']=true;FaBSetState($s);
        OnHit(intval($s['attacker']),$attack['mzID'],$dealt);
    }
    $s=FaBGetState();$s['attackHit']=$total>0;$s['damageDealt']=$total;
    if(!$total)$s['consecutiveHits']=0;
    $s['defender']=$state['defender'];$s['attackTarget']=$state['attackTarget'];FaBSetState($s);
    FaBRepairMultiTargetDefender();SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);FaBAutoPassShortcuts();
}
