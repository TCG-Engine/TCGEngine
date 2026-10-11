<?php
/** Public-board prize mapping for the Dhelmise policy. Unknown hands, future
 * draws, healing and attachment availability are deliberately not simulated.
 */
function PokeBotCanAttack(array $ctx,array $card): bool {
    if($ctx['view']['phase']!=='MAIN'||$ctx['view']['turnPlayer']!==$ctx['seat']
        ||$ctx['seat']===$ctx['view']['firstPlayer']&&$ctx['own']['turns']===1
        ||!empty($card['conditions']['Asleep'])||!empty($card['conditions']['Paralyzed']))return false;
    foreach(CardAttacks($card['id'])??[] as $attack)if(PokeHasAttackEnergy((object)['Energy'=>$card['energy']],$attack['cost']??[]))return true;
    return false;
}
function PokeBotVisibleThreat(array $ctx,array $target): float {
    // Tie breakers reward removing visible powered attackers and draw engines.
    $value=count($target['energy'])?30:0;
    if(in_array($target['id'],['sv05-129','sv09-121'],true))$value+=20;
    return $value;
}
function PokeBotPrizeTarget(array $ctx,array $attacker,array $target): array {
    // Boss makes the target Active: Bench-only protection no longer applies.
    $activeTarget=$target;$activeTarget['ref']='p'.(3-$ctx['seat']).'Active-0';
    $damage=PokeBotDamage(str_contains($target['ref'],'Bench')?PokeBotGustContext($ctx,$target):$ctx,$attacker,$activeTarget);
    $hp=max(0,$target['hp']-$target['damage']);
    $hits=$damage>0?(int)ceil($hp/$damage):PHP_INT_MAX;
    $prizes=min($ctx['own']['prizeCount'],PokePrizeValue($target['id']));
    $ko=$damage>0&&$hits<=1;
    $win=$ko&&$prizes>=$ctx['own']['prizeCount'];
    $healingRisk=$target['id']==='me02-084'&&!$ko;
    $threat=PokeBotVisibleThreat($ctx,$target);
    $value=$win?100000:($ko?10000+1000*$prizes+$threat:(!$healingRisk&&$hits<=2?100*$prizes/$hits+$threat:0));
    return compact('damage','hits','prizes','ko','win','value','threat','healingRisk');
}
function PokeBotBossTargetPlan(array $ctx,array $attacker,array $target,array $baseline): ?array {
    $plan=PokeBotPrizeTarget($ctx,$attacker,$target);
    // A two-hit estimate cannot guarantee another attack into this target:
    // the opponent can retreat, heal or KO our attacker. Preserve Boss.
    if(!$plan['ko'])return null;
    if($plan['value']<=$baseline['value'])return null;
    return $plan+['target'=>$target['ref']];
}
function PokeBotBossPlan(array $ctx): ?array {
    $attacker=$ctx['own']['Active'][0]??null;$current=$ctx['enemy']['Active'][0]??null;
    if(!$attacker||!$current||!PokeBotCanAttack($ctx,$attacker)||$ctx['own']['supporterUsed'])return null;
    // Matcha Spin hits the whole opposing field; changing the Active does not
    // improve the prize route in the normal case. Keep that Supporter for setup.
    if($attacker['id']==='me05-006')return null;
    $baseline=PokeBotPrizeTarget($ctx,$attacker,$current);
    if($baseline['win'])return null;
    $best=null;
    foreach($ctx['enemy']['Bench'] as $target){
        $plan=PokeBotBossTargetPlan($ctx,$attacker,$target,$baseline);
        if($plan&&(!$best||$plan['value']>$best['value']))$best=$plan;
    }
    return $best;
}
