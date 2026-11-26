<?php
/** Adventure Lantern variant of the Fighting policy. Own/public observation only. */
function PokeCeruledgeIsLightningEnergy(string $id): bool {
    return CardType($id)==='Energy' && CardEnergyType($id)==='Normal' && in_array('Lightning',PokeEnergyTypes($id),true);
}
function PokeCeruledgeLanternContext(array $view): array {
    $ctx=PokeCeruledgeFightingContext($view);
    $ctx['lightning']=count(array_filter($ctx['own']['Hand'],fn($c)=>PokeCeruledgeIsLightningEnergy($c['id'])));
    $known=$ctx['lightning']+count(array_filter($ctx['own']['Discard'],fn($c)=>PokeCeruledgeIsLightningEnergy($c['id'])));
    foreach($ctx['field'] as $card)foreach($card['energy'] as $id)if(PokeCeruledgeIsLightningEnergy($id))++$known;
    // This profile's registered list has four Lightning Energy. Visible cards
    // bound what may remain; this does not inspect deck order or Prize identities.
    $ctx['unseenLightning']=max(0,min($ctx['own']['deckCount'],4-$known));
    return $ctx;
}
function PokeCeruledgeLanternTrainerScore(array $ctx): float {
    if($ctx['own']['deckCount']<=2 || $ctx['unseenFire']+$ctx['unseenLightning']===0)return -20;
    if($ctx['fire']===0 && PokeCeruledgeFireReserve($ctx)>0 && $ctx['unseenFire']>0)return 440;
    $canDiscard=(!$ctx['own']['supporterUsed'] && isset($ctx['hand']['sv06-145']))
        || (isset($ctx['hand']['me01-131']) && count($ctx['own']['Hand'])>=3);
    if($ctx['discardEnergy']<15 && $canDiscard)return 360;
    // Thin the deck and bank fuel before Lillie or the next draw, but preserve
    // the late-game draw buffer once additional damage fuel is unnecessary.
    return $ctx['discardEnergy']<15 && $ctx['own']['deckCount']>6 ? 260 : -20;
}
function PokeCeruledgeLanternDiscardScore(array $ctx,string $id): float {
    if(PokeCeruledgeIsLightningEnergy($id))return 200;
    if($id==='me06-090')return ($ctx['hand'][$id]??0)>1 ? 70 : 40-PokeCeruledgeLanternTrainerScore($ctx)/2;
    return PokeCeruledgeFightingOpeningDiscardScore($ctx,$id);
}
function PokeCeruledgeLanternSearchScore(array $ctx,string $id): float {
    // Finish an available draw-engine pair before fetching discard fuel. A
    // Solrock start may have no Charcadet line yet, unlike the older policy.
    if($id==='sv08-036' && $ctx['own']['turns']>1 && !isset($ctx['hand'][$id])
        && PokeBotFieldCount($ctx,['me02-019'])>0 && PokeBotFieldCount($ctx,['sv08-036'])===0)return 650;
    if($id==='me01-074' && $ctx['missingLuna'] && !$ctx['missingSol'])return 600;
    if($id==='me01-075' && $ctx['missingSol'] && !$ctx['missingLuna'])return 600;
    // Complete an existing attacker before searching for a second Basic.
    // The legal-action gate still owns evolution timing after the search.
    if($id==='sv08-036' && $ctx['own']['turns']>1 && !isset($ctx['hand'][$id]) && PokeBotFieldCount($ctx,['me02-019'])>0)return 500;
    if(PokeCeruledgeIsFightingEnergy($id) && $ctx['discardEnergy']<15 && !$ctx['own']['supporterUsed'] && isset($ctx['hand']['sv06-145']))return 420;
    if(PokeCeruledgeIsLightningEnergy($id))return $ctx['discardEnergy']<15?160:-20;
    if($id==='me06-090')return isset($ctx['hand'][$id])?-20:PokeCeruledgeLanternTrainerScore($ctx);
    return $ctx['own']['turns']===1 && !($ctx['own']['lunarCycleUsed']??false)
        ? PokeCeruledgeFightingOpeningSearchScore($ctx,$id) : PokeCeruledgeFightingSearchScore($ctx,$id);
}
function PokeCeruledgeLanternSafeCost(array $ctx,string $source): bool {
    $remaining=$ctx['own']['Hand'];
    foreach($remaining as $i=>$card)if($card['ref']===$source){unset($remaining[$i]);--$ctx['hand'][$card['id']];break;}
    for($i=0;$i<2;++$i){
        usort($remaining,fn($a,$b)=>PokeCeruledgeLanternDiscardScore($ctx,$b['id'])<=>PokeCeruledgeLanternDiscardScore($ctx,$a['id']));
        $card=array_shift($remaining);if(!$card || PokeCeruledgeLanternDiscardScore($ctx,$card['id'])<=0)return false;
        --$ctx['hand'][$card['id']];
        if($card['id']==='mee-002')--$ctx['fire'];
        if(PokeCeruledgeIsFightingEnergy($card['id']))--$ctx['fighting'];
        if(PokeCeruledgeIsLightningEnergy($card['id']))--$ctx['lightning'];
    }
    return true;
}
function PokeCeruledgeLanternActionScore(array $ctx,array $action): float {
    $base=PokeCeruledgeFightingActionScore($ctx,$action);
    $id=isset($action['source'])?(PokeBotCard($ctx,$action['source'])['id']??''):'';
    if($action['type']==='evolve' && $id==='sv08-036' && PokeBotFieldCount($ctx,['sv08-036'])===0)return 550;
    $second=$ctx['seat']!==$ctx['view']['firstPlayer'];
    // Ceruledge cannot evolve on our first turn. Start the one-Energy attacker
    // when going second instead of choosing Charcadet's zero-damage setup attack.
    if($action['type']==='setup-active' && $second && $id==='me01-075')return 600;
    $active=$ctx['own']['Active'][0]??null;
    // One off-type attachment cannot pay Charcadet/Ceruledge's two-Energy
    // retreat. Do not spend the turn's attachment before draws can find Fire
    // and turn that same Pokémon into an attacking Ceruledge instead.
    if($action['type']==='attach' && $active && $action['target']===$active['ref']
        && in_array($active['id'],['me02-019','sv08-036'],true)
        && !in_array('Fire',PokeEnergyTypes($id),true)
        && !PokeHasAttackEnergy((object)['Energy'=>$active['energy']],['Fire'])
        && count($active['energy'])+1<(int)CardRetreat($active['id']))return -20;
    if($ctx['own']['turns']===1 && $second && ($active['id']??'')==='me01-075'
        && PokeBotFieldCount($ctx,['me01-074'])>0 && !PokeCeruledgeCanPay($ctx,$active,0)
        && $action['type']==='attach' && $action['target']===$active['ref'] && PokeCeruledgeIsFightingEnergy($id))return 600;
    if($action['type']==='trainer') {
        if($id==='me01-131' && !PokeCeruledgeLanternSafeCost($ctx,$action['source']))return -20;
        // Tutor the first attacker before placing the manual attachment on a
        // backup. Evolution may reveal that the attachment must pay retreat.
        if($id==='me01-131' && $base>=0 && $ctx['own']['turns']>1 && !isset($ctx['hand']['sv08-036'])
            && PokeBotFieldCount($ctx,['me02-019'])>0 && PokeBotFieldCount($ctx,['sv08-036'])===0)return max(480,$base);
        if($id==='me06-090')return PokeCeruledgeLanternTrainerScore($ctx);
        // The inherited score can be negative specifically because it does not
        // count Lightning. Evaluate the real discard-and-draw opportunity here.
        if($id==='sv06-145' && $ctx['own']['deckCount']>=7 && $ctx['fire']+$ctx['fighting']+$ctx['lightning']>=2)
            return max($base,180+min(60,10*($ctx['fire']+$ctx['fighting']+$ctx['lightning'])));
        if($id==='me01-116' && $ctx['own']['deckCount']>=8 && $ctx['unseenFighting']>0
            && !$ctx['own']['supporterUsed'] && isset($ctx['hand']['sv06-145']) && $ctx['discardEnergy']<15)return max(370,$base);
    }
    return $base;
}
function PokeCeruledgeLanternDecision(array $ctx,array $decision): string {
    $prompt=$decision['prompt'];
    if(str_contains($prompt,'Adventure Lantern'))return $ctx['own']['deckCount']>2?($decision['choices'][0]['value']??'-'):'-';
    $discard=str_contains($prompt,'discard');
    $search=str_contains($prompt,'Gather Strength') || str_contains($prompt,'search') || str_contains($prompt,'Search') || str_contains($prompt,'Fighting Gong');
    if(str_contains($prompt,'Brilliant Blender')) {
        $choices=array_values(array_filter($decision['choices'],fn($c)=>CardType($c['card'])==='Energy'));
        $fire=count(array_filter($choices,fn($c)=>in_array('Fire',PokeEnergyTypes($c['card']),true)));
        $fighting=count(array_filter($choices,fn($c)=>PokeCeruledgeIsFightingEnergy($c['card'])));
        $rank=fn($c)=>PokeCeruledgeIsLightningEnergy($c['card'])?3:(PokeCeruledgeIsFightingEnergy($c['card'])?2:1);
        usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));$selected=[];
        foreach($choices as $choice) {
            if(count($selected)>=$decision['max'])break;
            if(in_array('Fire',PokeEnergyTypes($choice['card']),true)) {
                if($ctx['fire']===0 && PokeCeruledgeFireReserve($ctx)>0 && $fire<=1)continue;
                --$fire;
            }
            if(PokeCeruledgeIsFightingEnergy($choice['card'])) {
                if($ctx['fighting']===0 && PokeBotFieldCount($ctx,['me01-074','me01-075'])>0 && $fighting<=1)continue;
                --$fighting;
            }
            $selected[]=$choice['value'];
        }
        return implode('&',$selected)?:'-';
    }
    if(($discard || $search) && in_array($decision['type'],['MZCHOOSE','MZMAYCHOOSE','MZMULTICHOOSE'],true)) {
        $choices=$decision['choices'];$selected=[];$max=$decision['type']==='MZMULTICHOOSE'?$decision['max']:1;
        while($choices && count($selected)<$max) {
            $rank=fn($c)=>$discard?PokeCeruledgeLanternDiscardScore($ctx,$c['card']):PokeCeruledgeLanternSearchScore($ctx,$c['card']);
            usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));$choice=array_shift($choices);
            if(count($selected)>=$decision['min'] && $rank($choice)<0)break;
            $selected[]=$choice['value'];$id=$choice['card'];$delta=$discard?-1:1;
            $ctx['hand'][$id]=($ctx['hand'][$id]??0)+$delta;
            if($id==='mee-002')$ctx['fire']+=$delta;
            if(PokeCeruledgeIsFightingEnergy($id))$ctx['fighting']+=$delta;
            if(PokeCeruledgeIsLightningEnergy($id))$ctx['lightning']+=$delta;
        }
        return implode('&',$selected)?:'-';
    }
    return PokeCeruledgeFightingDecision($ctx,$decision);
}
function PokeCeruledgeLanternChoose(array $view): ?array {
    $ctx=PokeCeruledgeLanternContext($view);
    if($view['decision'])return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeCeruledgeLanternDecision($ctx,$view['decision'])];
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeCeruledgeLanternActionScore($ctx,$b)<=>PokeCeruledgeLanternActionScore($ctx,$a));
    return $actions[0]??null;
}
